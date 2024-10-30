<?php

/**
 * The file that defines the class interacting with INFast API document
 *
 * @link       https://intia.fr
 * @since      1.0.0
 * @package    Infast_Woocommerce
 * @subpackage Infast_Woocommerce/includes/infast-api
 * @author     INTIA <dev@intia.fr>
 */
class Infast_Woocommerce_Document_Api {

    /**
     * This class in a Singleton, so you can access it from anywhere else with Infast_Woocommerce_Document_Api::getInstance();
     *
     * @since    1.0.0
     */
    private static $instance = null;

    private function __construct() {
    }

    public static function getInstance()
    {
        if ( self::$instance == null ) {
            self::$instance = new Infast_Woocommerce_Document_Api();
        }  
        return self::$instance;
    }

    private function check_wip($order_id, $wip_random_string, $next_step) {
        $infast_document_wip = get_post_meta( $order_id, '_infast_document_wip' );
        $infast_document_wip_date = get_post_meta( $order_id, '_infast_document_wip_date' );
        $infast_document_wip_random_string = get_post_meta( $order_id, '_infast_document_wip_random_string' );        

        $now = new DateTime();

        // check when we set wip _infast_document_wip
        // if more than 2 minutes we consider wip has not been deleted during last run then we return true
        if ($infast_document_wip_date && count($infast_document_wip_date) > 0) {
            $wip_date = new DateTime();
            $wip_date->setTimestamp($infast_document_wip_date[0]);
            
            $wip_date->add(new DateInterval('PT2M'));
            if ($wip_date < $now) {
                update_post_meta( $order_id, '_infast_document_wip_random_string', $wip_random_string );
                update_post_meta( $order_id, '_infast_document_wip_date', $now->getTimestamp() );
                update_post_meta( $order_id, '_infast_document_wip', $next_step );
                return true;
            }          
        } 
            
        // check random string
        $existing_random_string = '';
        if ($infast_document_wip_random_string && count($infast_document_wip_random_string) > 0) {
            $existing_random_string = $infast_document_wip_random_string[0];
        }

        if($existing_random_string && $existing_random_string !== $wip_random_string) {
            return false;
        }

        // check step
        if ($infast_document_wip && count($infast_document_wip) > 0) {
            $step = floatval($infast_document_wip[0]);
            if($step >= $next_step) {
                return false;
            }            
        }

        // update meta
        update_post_meta( $order_id, '_infast_document_wip_random_string', $wip_random_string );
        update_post_meta( $order_id, '_infast_document_wip_date', $now->getTimestamp() );
        update_post_meta( $order_id, '_infast_document_wip', $next_step );
        return true;
    }

    private function delete_wip($order_id) {
        delete_post_meta( $order_id, '_infast_document_wip'); 
        delete_post_meta( $order_id, '_infast_document_wip_date');  
        delete_post_meta( $order_id, '_infast_document_wip_random_string');  
    }

    private function generateRandomString($length = 10) {
        return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
    }

    /**
     * Manage to process to create a new invoice on INFast from a WooCommerce order ID, including creating a new customer on INFast first
     *
     * @since    1.0.0
     * @param      int    $order_id       WooCommerce order ID used to create the invoice on INFast
     */
    public function create_invoice( $order_id ) {

        $order = wc_get_order( $order_id );

        // get WooCommerce status to convert into INFast invoice
        // if no option set, we set completed as default statuts
        $options = get_option( 'infast_woocommerce' );
        if(!isset( $options['status_to_convert'] ) || count($options['status_to_convert']) == 0) {
			$options['status_to_convert']['wc-completed'] = 1;
		}

        $status_to_convert = $options['status_to_convert'];        
        $status = 'wc-' . $order->get_status();
        if(!in_array($status, array_keys($status_to_convert))) {
            return;
        }
        
        // if order is not paid, don't create it
        $isPaid = $order->is_paid();
        if (!$isPaid) {
            return;
        }


        // if order has already an INFast document id, don't create it
        $infast_document_id = get_post_meta( $order_id, '_infast_document_id', true );
        if ($infast_document_id) {
            
            // if document already validated don't allow to regenerate the order
            $infast_document_generated = get_post_meta( $order_id, '_infast_document_validated', true );
            if($infast_document_generated) {
                return;
            }
            
            // we allow to generate a new invoice from same order if first invoice was generated since more 2 minutes and has not _infast_document_validated metadata
            $objectid8 = substr($infast_document_id, 0, 8);
            $objectid8dec = hexdec($objectid8);
            $objectid_date = new DateTime();
            $objectid_date->setTimestamp($objectid8dec);
            $now = new DateTime();
            // add 2 minutes
            $objectid_date->add(new DateInterval('PT2M'));
            if ($now < $objectid_date) {
                return;
            }
        }

        $check_wip_step = 10;
        $check_wip_random = $this->generateRandomString();
        // test the order is not currently in process
        if(!$this->check_wip($order_id, $check_wip_random, $check_wip_step)) {
            return;
        }
        $check_wip_step += 10;

        // wait 2s to ensure there is no other request in same time for same order
        sleep(2);

        $infast_customer_api = Infast_Woocommerce_Customer_Api::getInstance();
        $infast_customer_id = $infast_customer_api->create_customer( $order );

        if ( $infast_customer_id ) {
            $max_try = 5.0; // float to make float division instead integer division
            $nb_try = 0;
            $document_validated = false;
            $rounded_offset = 0;
            $have_refund = false;
            $draft_document_id = '';
            $abs_rounded_offset = 0;

            do{
                $nb_try++;

                if($draft_document_id) {
                    // delete draft if $exists
                    if(!$this->check_wip($order_id, $check_wip_random, $check_wip_step)) {
                        return;
                    }
                    $check_wip_step += 10;

                    $this->delete_draft_document( $order_id, $draft_document_id );
                }

                // generate draft
                if(!$this->check_wip($order_id, $check_wip_random, $check_wip_step)) {
                    return;
                }
                $check_wip_step += 10;

                $response = $this->create_draft_document( $order_id, $infast_customer_id, $rounded_offset );
                if(!$response) {
                    continue;
                }

                $draft_document_id = $response['document_id'];
                $document_amount_vat = floatval($response['document_amount_vat']);

                if ( $draft_document_id ) {
                    // check amounts
                    // if amounts are different, add a error log and stop process
                    $order_total = floatval($order->get_total());
                    $refund_total = floatval($order->get_total_refunded());
                    $order_total = $order_total - $refund_total;

                    $rounded_offset = ($order_total - $document_amount_vat) / ($max_try / $nb_try);
                    $rounded_offset = round($rounded_offset, 4, PHP_ROUND_HALF_DOWN);
                    $abs_rounded_offset = abs($rounded_offset);
    
                    if( $abs_rounded_offset > 0.01 ) {                            
                        $order = wc_get_order( $order_id );     
                        $order->add_order_note( 'INFast API - La facture créée dans INFast n\'a pas le même montant (' . $document_amount_vat . ') que la commande WooCommerce (' . $order_total . '). La facture est restée à l\'état "brouillon" dans INFast' );
                        error_log( 'INFast API - INFast document has not same amount that the WooCommerce order.');    
                    } else {
                        $this->validate_invoice( $order_id, $draft_document_id, $check_wip_random, $check_wip_step );
                        $document_validated = true;
                    }                
                }
            } while ($nb_try < $max_try && !$document_validated && $abs_rounded_offset < 0.01 && !$have_refund && $draft_document_id);            

            $this->delete_wip($order_id);                   
        }
    }
    
    private function validate_invoice( $order_id, $document_id, $check_wip_random, $check_wip_step ) {
        if(!$this->check_wip($order_id, $check_wip_random, $check_wip_step)) {
            return;
        }
        $check_wip_step += 10;

        if($this->validate_draft_document($order_id, $document_id)) {
            if(!$this->check_wip($order_id, $check_wip_random, $check_wip_step)) {
                return;
            }
            $check_wip_step += 10;

            $this->add_document_payment( $order_id, $document_id );

            $options = get_option( 'infast_woocommerce' );
            if ( $options['enable_email'] ) {
                if(!$this->check_wip($order_id, $check_wip_random, $check_wip_step)) {
                    return;
                }
                $check_wip_step += 10;

                $this->send_document_email( $order_id, $document_id );
            }    
        }
    }

    /**
     * Create a document on INFast
     *
     * @since    1.0.0
     * @param      int    $order_id       WooCommerce order ID used to create the invoice on INFast
     * @param      string    $customer_id       INFast customer ID previously created
     * @param      bool    $force       Force OAuth2 token regeneration
     */
    private function create_draft_document( $order_id, $customer_id, $rounded_offset = 0, $force = false ) {

        $order = wc_get_order( $order_id );            

        $infast_auth_api = Infast_Woocommerce_Auth_Api::getInstance();
        $access_token = $infast_auth_api->get_oauth2_token( $force );
        if ( $access_token == false ) {
            $order->add_order_note( __('INFast API - Failed to create the draft invoice, check if « Client ID » and « Client Secret » are valid in the INFast WooCommerce plugin configuration.', 'infast' ));
            error_log( 'INFast API - Document not created. Invalid Client ID and/or Client Secret' );
            return false;
        }

        $data = $this->create_document_prepare_data( $order_id, $customer_id, $rounded_offset );
        if(count($data['lines']) == 0) {
            $order->add_order_note( __('INFast API - Failed to create draft invoice. No item or shipping costs on invoice.', 'infast' ));
            error_log( 'INFast API - Failed to create document. No item or shipping costs on invoice');
            return false;
        }

        $headers = array(
            'authorization' =>  'Bearer ' . $access_token,
            'content-type'  =>  'application/json',
        );

        $args = array(
            'body'        => json_encode( $data ),
            'headers'     => $headers,
            'timeout'     => '30',
            'redirection' => '10',
            'httpversion' => '1.1',
        );

        $resp = wp_remote_post( INFAST_API_URL . 'api/v1/documents', $args );
        $http_code = wp_remote_retrieve_response_code( $resp );

        if ( $http_code == 401) { // access token is expired
            if(!$force) {
                return $this->create_draft_document( $order_id, $customer_id, $rounded_offset, true );
            }

            $order->add_order_note( __('INFast API - Failed to create draft invoice. Authentication failed.', 'infast' ));
            error_log( 'INFast API - Failed to create document. Authentication failed');
            return false;
        }

        if ( is_wp_error( $resp ) ) {
            $error_message = $resp->get_error_message();
            $order->add_order_note( __('INFast API - Failed to create draft invoice. ', 'infast' ) . $error_message );
            error_log( 'INFast API - Failed to create document. ' . $error_message );
            return false;
        } else {
            $response = json_decode( $resp['body'], true );
            $document_id = $response['_id'];
            $document_amount_vat = $response['amountVatNet'];
            $document_reference = $response['referenceTemporary'];
            $order->add_order_note( __('INFast API - Creating a draft invoice: ', 'infast' ) . '<br>' . $document_reference . '<br>(' . $document_id . ')<br><a href="https://app.intia.fr/portal.html#/invoice/'. $document_id . '" target="_blank">' . __('INFast Link', 'infast' ) . '</a>');

            update_post_meta( $order_id, '_infast_document_id', $document_id );

            return array(
                'document_id'        => $document_id,
                'document_amount_vat'     => $document_amount_vat,
            );
        }

    }

    /**
     * Fill an array with all data needed to call the API to create a document
     * Full parameters list: https://infast.docs.stoplight.io/api-reference/documents/createdocument
     *
     * @since    1.0.0
     * @param      int    $order_id       WooCommerce order ID used to create the invoice on INFast
     * @param      string    $customer_id       INFast customer ID previously created
     */
    private function create_document_prepare_data( $order_id, $customer_id, $rounded_offset = 0 ) {

        $order = wc_get_order( $order_id );

        $data = array();
        $data['type'] = 'INVOICE';
        $data['status'] = 'DRAFT';
        $data['customerId'] = $customer_id;
        $data['refInt'] = strval( $order_id );
        $data['emitDate'] = date( DATE_ISO8601, strtotime('today'));
        $data['dueDate'] = date( DATE_ISO8601, strtotime('today'));
        $data['paymentMethod'] = 'OTHER'; // We use 'OTHER' because a lot of different gateway ID are existing. Gateway ID is specified in line below
        $data['paymentMethodInfo'] = $order->get_payment_method();

        $tax = new WC_Tax();
        $data['lines'] = array();
        
        $infast_item_api = Infast_Woocommerce_Item_Api::getInstance();

        foreach ( $order->get_items() as $item_id => $item ) {
            $item_tax_rate = '';
            $item_description = '';

            $product = wc_get_product( $item->get_product_id() );
            if ( $product->is_type( 'variable' ) ) {
                $variation_id = $item->get_variation_id();
                $infast_product_id = get_post_meta( $variation_id, '_infast_product_id', true );                    
                if ( ! $infast_product_id ) {
                    $infast_item_api->create_product_or_variations( $product );
                    $infast_product_id = get_post_meta( $variation_id, '_infast_product_id', true );
                }

                $variation    = new WC_Product_Variation( $variation_id );

                $item_tax_rate = $infast_item_api->get_product_tax_rate($variation); 
                $item_description = $infast_item_api->get_variation_description($product, $variation);                                    
            }
            else {
                $infast_product_id = get_post_meta( $product->get_id(), '_infast_product_id', true );
                if ( ! $infast_product_id ) {
                    $infast_item_api->create_product_or_variations( $product );
                    $infast_product_id = get_post_meta( $product->get_id(), '_infast_product_id', true );
                }

                $item_tax_rate = $infast_item_api->get_product_tax_rate($product);                 
                $item_description = $infast_item_api->get_product_description($product);                
            }        

            $price_with_discount = floatval( $item->get_total() );
            $price_without_discount = floatval( $item->get_subtotal() );
            if ( $price_with_discount == $price_without_discount )
                $discount_percent = 0;
            else
                $discount_percent = 100 - ( ( 100 * $price_with_discount ) / $price_without_discount );

            $quantity = $item->get_quantity();
            $price = $item->get_subtotal() / $item->get_quantity();

            $item_qty_refunded = $order->get_qty_refunded_for_item( $item_id ); // Get the refunded amount for a line item.

            $quantity += $item_qty_refunded;
            if($quantity <= 0) {
                continue;
            }
                
            // lastFieldType = 0
            $data['lines'][] = array(
                'lineType' => 'product',
                'productId' => $infast_product_id,
                'quantity' => $quantity,
                'vat' => $item_tax_rate, // VAT rate (ex : 20 for 20% VAT rate)
                'description' => $item_description,
                'price' => $price, // Unit price excluding taxes
                'discount'  => $discount_percent,
            );


            // lastFieldType = 2
            // $data['lines'][] = array(
            //     'lineType' => 'product',
            //     'productId' => $infast_product_id,
            //     'quantity' => $item->get_quantity(),
            //     'vat' => $item_tax_rate, // VAT rate (ex : 20 for 20% VAT rate)
            //     'description' => $item_description,
            //     'amountVat' => $price_with_discount + floatval( $item->get_total_tax() ),
            //     'discount'  => $discount_percent,
            // );            
        }

        foreach( $order->get_items( 'fee' ) as $item_id => $item ) {

            $tax_rate = 0;
            if ( wc_tax_enabled() ) {
                $taxes = $tax->get_rates( $item->get_tax_class() );
                $rates = array_shift( $taxes );
                if ( $rates != NULL  && $item->get_total_tax() != "0" )
                    $tax_rate = array_shift( $rates );
            } 
            if(!$tax_rate)
                $tax_rate = 0;

            $data['lines'][] = array(
                'lineType' => 'product',
                'name' => $item->get_name(),
                'price' => floatval( $item->get_total() ),
                'vat' => floatval( $tax_rate ),
                'quantity' => 1,
                'isService' => false,
            );
        }

    
        $shipping_methods = $order->get_shipping_methods();
        foreach( $shipping_methods as $shipping_method ) {

            $infast_shipping_api = Infast_Woocommerce_Shipping_Api::getInstance();
            $infast_shipping_id = $infast_shipping_api->create_product_shipping( $shipping_method );            

            if($infast_shipping_id) {
                $tax_rate = $infast_shipping_api->get_shipping_tax_rate($shipping_method);                

                $price = floatval( $shipping_method->get_total() );

                $refunds = $order->get_refunds();
                $price_to_refund = 0;
                foreach( $refunds as $refund ) {
                    $price_to_refund += floatval( $refund->get_total_shipping() );
                }

                if($price + $price_to_refund <= 0) {
                    continue;
                }

                $data['lines'][] = array(
                    'lineType' => 'product',
                    'productId' => $infast_shipping_id,
                    'price' => $price + $price_to_refund, // force use WP value, shipping method is not updated on INFast side
                    'vat' => floatval( $tax_rate ),  // force use WP value, shipping method is not updated on INFast side
                    'quantity' => 1,
                    'isService' => true,
                );
            }
        }    

        if( abs($rounded_offset) > 0 && abs($rounded_offset) <= 0.01) {
            $data['lines'][] = array(
                'lineType' => 'product',
                'quantity' => 1,
                'vat' => 0,
                'name' => 'Correction arrondi',
                // 'description' => $item_description,
                'price' => $rounded_offset,
                'isService' => false,
                'phantom' => true,
            );    
        }
        
        return $data;
    }

    private function validate_draft_document($order_id, $document_id, $force = false) {
        $order = wc_get_order( $order_id );

        $infast_auth_api = Infast_Woocommerce_Auth_Api::getInstance();
        $access_token = $infast_auth_api->get_oauth2_token( $force );
        if ( $access_token == false ) {
            $order->add_order_note( __('INFast API - Failed to validate draft invoice, check if « Client ID » and « Client Secret » are valid in the INFast WooCommerce plugin configuration.', 'infast' ));
            error_log( 'INFast API - Document not validated. Invalid Client ID and/or Client Secret' );
            return false;
        }

        $headers = array(
            'authorization' =>  'Bearer ' . $access_token,
            'content-type'  =>  'application/json',
        );

        $args = array(
            'headers'     => $headers,
            'timeout'     => '30',
            'redirection' => '10',
            'httpversion' => '1.1',
        );

        $resp = wp_remote_post( INFAST_API_URL . 'api/v1/documents/' . $document_id . '/validate', $args );
        $http_code = wp_remote_retrieve_response_code( $resp );

        if ( $http_code == 401) { // access token is expired
            if(!$force) {
                return $this->validate_draft_document( $order_id, $document_id, true );
            }

            $order->add_order_note( __('INFast API - Failed to validate invoice. Authentication failed.', 'infast' ));
            error_log( 'INFast API - Failed to validate document. Authentication failed');
            return false;
        }

        if ( is_wp_error( $resp ) ) {
            $error_message = $resp->get_error_message();
            $order->add_order_note( __('INFast API - Failed to validate invoice. ', 'infast' ) . $error_message );
            error_log( 'INFast API - Failed to validate document. ' . $error_message );
            return false;
        } else {
            $response = json_decode( $resp['body'], true );
            $document_id = $response['_id'];
            $document_reference = $response['reference'];
            $order->add_order_note( __('INFast API - Invoice validated: ', 'infast' ) . '<br>' . $document_reference . '<br>(' . $document_id . ')<br><a href="https://app.intia.fr/portal.html#/invoice/'. $document_id . '" target="_blank">' . __('INFast Link', 'infast' ) . '</a>');

            update_post_meta( $order_id, '_infast_document_validated', $document_id );
            return $document_id;
        }
    }

    /**
     * Add a payment on INFast
     *
     * @since    1.0.0
     * @param      int    $order_id       WooCommerce order ID used to get payment infos
     * @param      int    $document_id       INFast document ID used to add a payment on INFast
     * @param      bool    $force       Force OAuth2 token regeneration
     */
    private function add_document_payment( $order_id, $document_id, $force = false ) {

        $order = wc_get_order( $order_id );

        $infast_auth_api = Infast_Woocommerce_Auth_Api::getInstance();
        $access_token = $infast_auth_api->get_oauth2_token( $force );
        if ( $access_token == false ) {
            $order->add_order_note( __('Failed to add payment on invoice. Check if « Client ID » and « Client Secret » are valid in the INFast WooCommerce plugin configuration.', 'infast' ));
            error_log( 'INFast API - Failed to add payment on invoice. Invalid Client ID and/or Client Secret' );
            return false;
        }

        $data = $this->add_document_payment_prepare_data( $order );

        $headers = array(
            'authorization' =>  'Bearer ' . $access_token,
            'content-type'  =>  'application/json',
        );

        $args = array(
            'body'        => json_encode( $data ),
            'headers'     => $headers,
            'timeout'     => '30',
            'redirection' => '10',
            'httpversion' => '1.1',
        );

        $resp = wp_remote_post( INFAST_API_URL . 'api/v1/documents/' . $document_id . '/payment', $args );
        $http_code = wp_remote_retrieve_response_code( $resp );

        if ( $http_code == 401 ) { // access token is expired
            if(!$force) {
                return $this->add_document_payment( $order_id, $document_id, true );
            }

            $order->add_order_note( __('INFast API - Failed to add payment on invoice. Authentication failed.', 'infast' ));
            error_log( 'INFast API - Failed to add payment on invoice. Authentication failed');
            return false;
        }

        if ( is_wp_error( $resp ) ) {
            $error_message = $resp->get_error_message();
            $order->add_order_note( __('INFast API - Failed to add payment on invoice. ', 'infast' ) . $error_message);
            error_log( 'INFast API - Failed to add payment on invoice. ' . $error_message );
            return false;
        } else {
            $response = json_decode( $resp['body'], true );
            $order->add_order_note( __('INFast API - Invoice paid', 'infast'));
            return true;          
        }

    }

    /**
     * Fill an array with all data needed to call the API to add a payment
     * Full parameters list: https://infast.docs.stoplight.io/api-reference/documents/addpaymentoninvoice
     *
     * @since    1.0.0
     * @param      int    $order       WooCommerce order ID used to create the invoice on INFast
     */
    private function add_document_payment_prepare_data( $order ) {

        $payment_method = $order->get_payment_method();
        $data = array();
        $data['method'] = 'OTHER';
        $data['info'] = $payment_method;  

        return $data;
    }

    /**
     * Send INFast document by email
     *
     * @since    1.0.0
     * @param      int    $order_id       WooCommerce order ID
     * @param      int    $document_id       INFast document ID
     * @param      bool    $force       Force OAuth2 token regeneration
     */
    private function send_document_email( $order_id, $document_id, $force = false ) {

        $order = wc_get_order( $order_id );

        $infast_auth_api = Infast_Woocommerce_Auth_Api::getInstance();
        $access_token = $infast_auth_api->get_oauth2_token( $force );
        if ( $access_token == false ) {
            $order->add_order_note( __('INFast API - Failed to send invoice by email, check that « Client ID » and « Client Secret » are valid in the INFast WooCommerce plugin configuration.', 'infast' ));
            error_log( 'INFast API - Failed to send invoice by email. Invalid Client ID and/or Client Secret' );
            return false;
        }

        $data = $this->send_document_email_prepare_data( $order_id );
        if ( count ( $data ) == 0 )
            $data = new stdClass();
        
        $headers = array(
            'authorization' =>  'Bearer ' . $access_token,
            'content-type'  =>  'application/json',
        );

        $args = array(
            'body'        => json_encode( $data ),
            'headers'     => $headers,
            'timeout'     => '30',
            'redirection' => '10',
            'httpversion' => '1.1',
        );

        $resp = wp_remote_post( INFAST_API_URL . 'api/v1/documents/' . $document_id . '/email', $args );
        $http_code = wp_remote_retrieve_response_code( $resp );

        if ( $http_code == 401 ) { // access token is expired
            if(!$force) {
                return $this->send_document_email( $order_id, $document_id, true );
            }

            $order->add_order_note( __('INFast API - Failed to send invoice by email. Authentication failed.', 'infast' ));
            error_log( 'INFast API - Failed to send document by email. Authentication failed' );
            return false;
        }

        if ( is_wp_error( $resp ) ) {
            $error_message = $resp->get_error_message();
            $order->add_order_note( __('INFast API - Failed to send invoice by email. ', 'infast' ) . $error_message);
            error_log( 'INFast API - Failed to send invoice by email. ' . $error_message );
            return false;
        } else {
            $response = json_decode( $resp['body'], true );
            $inbox_url = $response['inboxUrl'];
            $order->add_order_note( __('INFast API - Document sent by email', 'infast') . '<br><a href="' . $inbox_url . '" target="_blank">' . __('INFast-INBox Link', 'infast') . '</a>' );
            
            update_post_meta( $order_id, '_infast_inbox_url', $inbox_url );
            return $inbox_url;           
        }

    }

    /**
     * Fill an array with all data needed to call the API to send document by email
     * Full parameters list: https://infast.docs.stoplight.io/api-reference/documents/sendemail
     *
     * @since    1.0.0
     */
    private function send_document_email_prepare_data() {

        $cc_setting = get_option( 'infast_woocommerce' )['cc_email'];

        $data = array();
        if ( ! empty( $cc_setting ) && $cc_setting != NULL )
            $data['cc'] = $cc_setting;

        return $data;

    }

    /**
     * Delete a draft document by its _id
     *
     * @since    1.0.16
     */
    private function delete_draft_document($order_id, $document_id, $force = false) {
        $order = wc_get_order( $order_id );

        $infast_auth_api = Infast_Woocommerce_Auth_Api::getInstance();
        $access_token = $infast_auth_api->get_oauth2_token( $force );
        if ( $access_token == false ) {
            $order->add_order_note( __('INFast API - Failed to delete draft invoice, check if « Client ID » and « Client Secret » are valid in the INFast WooCommerce plugin configuration.', 'infast' ));
            error_log( 'INFast API - Failed to delete draft document. Invalid Client ID and/or Client Secret' );
            return false;
        }

        $headers = array(
            'authorization' =>  'Bearer ' . $access_token,
            'content-type'  =>  'application/json',
        );

        $args = array(
            'headers'     => $headers,
            'timeout'     => '30',
            'redirection' => '10',
            'httpversion' => '1.1',
        );

        // wordpress has no wp_remote_delete function
        $defaults = array('method' => 'DELETE');
        $r = wp_parse_args( $args, $defaults );

        $resp = wp_remote_request( INFAST_API_URL . 'api/v1/documents/' . $document_id, $r );
        $http_code = wp_remote_retrieve_response_code( $resp );

        if ( $http_code == 401 ) { // access token is expired
            if(!$force) {
                return $this->delete_draft_document( $order_id, $document_id, true );
            }

            $order->add_order_note( __('INFast API - Failed to delete draft invoice. Authentication failed.', 'infast' ));
            error_log( 'INFast API - Failed to delete draft document. Authentication failed' );
            return false;
        }

        if ( is_wp_error( $resp ) ) {
            $error_message = $resp->get_error_message();
            $order->add_order_note( __('INFast API - Failed to delete draft invoice. ', 'infast' ) . $error_message);
            error_log( 'INFast API - Failed to delete draft document. ' . $error_message );
            return false;
        } else {
            $order->add_order_note( __('INFast API - Draft invoice deleted.' , 'infast'));           
        }        
    }
}