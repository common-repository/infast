<?php

/**
 * The file that defines the class interacting with INFast API item for WooCommerce shipping methods
 *
 * @link       https://intia.fr
 * @since      1.0.0
 * @package    Infast_Woocommerce
 * @subpackage Infast_Woocommerce/includes
 * @author     INTIA <dev@intia.fr>
 */
class Infast_Woocommerce_Shipping_Api {

    /**
     * This class in a Singleton, so you can access it from anywhere else with Infast_Woocommerce_Shipping_Api::getInstance();
     *
     * @since    1.0.0
     */
    private static $instance = null;

    private function __construct() {
    }

    public static function getInstance()
    {
        if ( self::$instance == null ) {
            self::$instance = new Infast_Woocommerce_Shipping_Api();
        }  
        return self::$instance;
    }

    /**
     * Create/Update a product (shipping) on INFast
     *
     * @since      1.0.0
     * @param      WC_Order_Item_Shipping    $shipping_method       Order shipping_method used to compute VAT
     * @param      bool    $force       Force OAuth2 token regeneration
     */
    public function create_product_shipping( $shipping_method, $force = false ) {

        $infast_auth_api = Infast_Woocommerce_Auth_Api::getInstance();
        $access_token = $infast_auth_api->get_oauth2_token( $force );
        if ( $access_token == false ) {
            error_log( 'INFast API - Failed to create/update shipping item. Invalid Client ID and/or Client Secret' );
            return false;
        }

        $method_id = $shipping_method['method_id'];
        $shipping_id = $shipping_method['instance_id'];
        $data_shipping = get_option( 'woocommerce_' . $method_id . '_' . $shipping_id . '_settings' );
        $infast_shipping_id = $data_shipping['infast_shipping_id'];
        if ( $infast_shipping_id  == NULL ) {
            $request_url = INFAST_API_URL . 'api/v1/products';
            $request_type = 'POST';
        } else {
            $request_url = INFAST_API_URL . 'api/v1/products/' . $infast_shipping_id ;
            $request_type = 'PATCH';
        }

        $data = $this->create_product_shipping_prepare_data( $shipping_method );
        if(!$data || count($data) == 0) {
            return false;
        }

        $headers = array(
            'authorization' =>  'Bearer ' . $access_token,
            'content-type'  =>  'application/json',
        );

        $args = array(
            'body'        => json_encode( $data ),
            'headers'     => $headers,
            'method'      => $request_type,
            'timeout'     => '30',
            'redirection' => '10',
            'httpversion' => '1.1',
        );

        $resp = wp_remote_request( $request_url, $args );
        $http_code = wp_remote_retrieve_response_code( $resp );

        if ( $http_code == 401 ) { // access token is expired
            if(!$force) {
                return $this->create_product_shipping( $shipping_method, true );
            }
            
            error_log( 'INFast API - Failed to create/update shipping item. Authentication failed' );
            return false;
        }

        if ( $http_code == 404 ) { // product not found on INFast
            if(!$force) {
                $data_shipping = get_option( 'woocommerce_' . $method_id . '_' . $shipping_id . '_settings' );
                $data_shipping['infast_shipping_id'] = '';
                update_option( 'woocommerce_' . $method_id . '_' . $shipping_id . '_settings', $data_shipping );

                return $this->create_product_shipping( $shipping_method, true );
            }
            
            error_log( 'INFast API - Failed to create/update shipping item. Item not found' );
            return false;
        }

        if ( is_wp_error( $resp ) ) {
            $error_message = $resp->get_error_message();
            error_log( 'INFast API - Failed to create/update shipping item. ' . $error_message );
            return false;
        } else {
            $response = json_decode( $resp['body'], true );
            $infast_shipping_id = $response['_id'];

            $data_shipping = get_option( 'woocommerce_' . $method_id . '_' . $shipping_id . '_settings' );
            $data_shipping['infast_shipping_id'] = $infast_shipping_id;
            update_option( 'woocommerce_' . $method_id . '_' . $shipping_id . '_settings', $data_shipping );
            return $infast_shipping_id;         
        }

    }

    public function get_shipping_tax_rate($shipping_method) {
        $tax_rate = 0;

        if ( wc_tax_enabled() ) {
            $tax = new WC_Tax();
            $tax_class = $shipping_method->get_tax_class();
            if($tax_class == 'inherit') {
                $tax_class = '';
            }
            $taxes = $tax->get_rates( $tax_class );
            $rates = array_shift( $taxes );
            $total_tax = $shipping_method->get_total_tax();
            if ( $rates != NULL && $total_tax != "0" )
                $tax_rate = array_shift( $rates );
        }
        if(!$tax_rate)
            $tax_rate = 0;

        return $tax_rate;
    }

    /**
     * Fill an array with all data needed to call the API to create/update a product (shipping)
     * Full parameters list: https://infast.docs.stoplight.io/api-reference/products/createproduct
     *
     * @since    1.0.0
     * @param      WC_Order_Item_Shipping    $shipping_method       WooCommerce product ID
     */
    private function create_product_shipping_prepare_data( $shipping_method ) {

        $data = array();

        $tax_rate = $this->get_shipping_tax_rate($shipping_method);

        $data['name'] = "Livraison - " . $shipping_method->get_method_title();
        $data['price'] = floatval( $shipping_method->get_total() );
        $data['vat'] = $tax_rate;
        $data['isService'] = true;
        return $data;

    }

}