<?php

/**
 * The file that defines the class interacting with INFast API
 *
 * @link       https://intia.fr
 * @since      1.0.0
 * @package    Infast_Woocommerce
 * @subpackage Infast_Woocommerce/includes/infast-api
 * @author     INTIA <dev@intia.fr>
 */
class Infast_Woocommerce_Customer_Api {

    /**
     * This class in a Singleton, so you can access it from anywhere else with Infast_Woocommerce_Customer_Api::getInstance();
     *
     * @since    1.0.0
     */
    private static $instance = null;

    private function __construct() {
    }

    public static function getInstance()
    {
        if ( self::$instance == null ) {
            self::$instance = new Infast_Woocommerce_Customer_Api();
        }  
        return self::$instance;
    }

    /**
     * Create a customer on INFast
     *
     * @since    1.0.0
     * @param      int    $user_id              User ID used to create the customer on INFast
     * @param      int    $order                WooCommerce order 
     * @param      int    $infast_customer_id   INFast customer ID used to update INFast customer
     * @param      bool   $force                Force OAuth2 token regeneration
     */
    public function create_customer( $order, $force = false ) {
        $infast_auth_api = Infast_Woocommerce_Auth_Api::getInstance();
        $access_token = $infast_auth_api->get_oauth2_token( $force );
        if ( $access_token == false ) {
            if ( $order )
                $order->add_order_note( __('INFast API - Failed to create/update client, check if « Client ID » and « Client Secret » are valid in the INFast WooCommerce plugin configuration.', 'infast' ));
            error_log( 'INFast API - Failed to create/update customer. Invalid Client ID and/or Client Secret' );
            return false;
        }

        $user_id = $order->get_user_id();
        $infast_customer_id = get_user_meta( $user_id, '_infast_customer_id', true );        
    
        $data = $this->create_customer_prepare_data_from_order( $order );

        if ( $infast_customer_id == NULL ) {
            $request_url = INFAST_API_URL . 'api/v1/customers';
            $request_type = 'POST';
        } else {
            $request_url = INFAST_API_URL . 'api/v1/customers/' . $infast_customer_id;
            $request_type = 'PATCH';
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

        $resp = wp_remote_post( $request_url, $args );
        $http_code = wp_remote_retrieve_response_code( $resp );

        if ( $http_code == 401 ) { // access token is expired
            if(!$force) {
                return $this->create_customer( $order, true );
            }

            if ( $order ) {
                $order->add_order_note( __('INFast API - Client creation/update failed. Authentication failed.', 'infast' ));
            }
            error_log( 'INFast API - Failed to create/update customer. Authentication failed' );
            return false;
        }

        if ( $http_code == 404 ) { // customer not found on INFast
            if(!$force) {
                delete_user_meta( $user_id, '_infast_customer_id' );
                return $this->create_customer( $order, true );
            }

            error_log( 'INFast API - Failed to create/update customer. Customer not found on INFast' );
            return false;
        }

        if ( is_wp_error( $resp ) ) {
            $error_message = $resp->get_error_message();
            if ( $order )
                $order->add_order_note( __('INFast API - Failed to create/update client. ', 'infast' ) . $error_message);

            error_log( 'INFast API - Failed to create/update customer. ' . $error_message );
            return false;
        } else {
            $response = json_decode( $resp['body'], true );
            $customer_id = $response['_id'];
            if ( $order ) {
                $order->add_order_note( __('INFast API - Client creation/update: ', 'infast' ) . $customer_id . '<br><a href="https://app.intia.fr/portal.html#/customer/' . $customer_id . '" target="_blank">' . __('INFast Link', 'infast' ) . '</a>');
            }
            
            add_user_meta( $user_id, '_infast_customer_id', $customer_id, true );
            
            return $customer_id;     
        }

    }

    private function get_vat_number($order) {
        $vat = '';
        if ( ! $vat ) {
            $vat = $order->get_meta('_vat_number');
        }

        if ( ! $vat ) {
            $vat = $order->get_meta('vat_number');
        }

        if ( ! $vat ) {
            $vat = $order->get_meta('_billing_vat_number');
        }

        if ( ! $vat ) {
            $vat = $order->get_meta('billing_vat_number');
        }
    

        return $vat;
    }

    private function create_customer_prepare_data_from_order( $order ) {

        $endl = chr(13) . chr(10);
        $data = array();

        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $company_name = $order->get_billing_company();
        if($company_name) {
            $data['name'] = $company_name;
        } else {
            $data['name'] = $customer_name;
        }
        $data['address'] = array();
        $data['address']['street'] = $order->get_billing_address_1();
        $address2 = $order->get_billing_address_2();
        if($address2) 
            $data['address']['street'] = $data['address']['street'] . $endl . $address2;
        
        $data['address']['postalCode'] = $order->get_billing_postcode();
        $data['address']['city'] = $order->get_billing_city();
        $billing_country = WC()->countries->countries[ $order->get_billing_country() ];
        if($billing_country)
            $data['address']['country'] = $billing_country;
        $data['email'] = $order->get_billing_email();
        $data['phone'] = $order->get_billing_phone();

        // shipping data
        $delivery_name = $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name();
        $delivery_company = $order->get_shipping_company();
        $shipping_street = $order->get_shipping_address_1();
        $shipping_address2 = $order->get_shipping_address_2();
        if($shipping_address2) {
            $shipping_street = $shipping_street . $endl . $shipping_address2;
        }
        $shipping_postalcode = $order->get_shipping_postcode();
        $shipping_city = $order->get_shipping_city();
        $shipping_country = WC()->countries->countries[ $order->get_shipping_country() ];

        $has_shipping_address = 
            ($shipping_street != $data['address']['street']) ||
            ($shipping_postalcode != $data['address']['postalCode']) ||
            ($shipping_city != $data['address']['city']) ||
            ($shipping_country != $data['address']['country']);

        $has_shipping_address = true; // force to display same address to ensure to display the company name and the customer name will we have no contact management in the INFast API
        if( $has_shipping_address) {
            $data['delivery'] = array();
            if($delivery_company) {
                $data['delivery']['name'] = $delivery_company;
                $data['delivery']['toAttention'] = $delivery_name;
            } else {
                $data['delivery']['name'] = $delivery_name;
            }
            
            $data['delivery']['address'] = array();
            $data['delivery']['address']['street'] = $shipping_street;
            $data['delivery']['address']['postalCode'] = $shipping_postalcode;
            $data['delivery']['address']['city'] = $shipping_city;
            if($shipping_country)
                $data['delivery']['address']['country'] = $shipping_country;
        }

        $data['useDelivery'] = $has_shipping_address;
        $data['sendToDelivery'] = false;

        $data['outsideEU'] = $this->is_outside_EU( $order->get_billing_country() );

        $vat = '';
        if(function_exists('wc_eu_vat_get_vat_from_order')) {
            $vat = wc_eu_vat_get_vat_from_order($order);
        }

        if ( !$vat ) {   
            $vat = $this->get_vat_number($order);
        }
        
        
        if ( $vat ) {
            $data['vat'] = $vat;
        }

        return $data;
    }

    /**
     * Check if a country is outside European Union. Can be used for VAT purpose.
     *
     * @since    1.0.0
     * @param      string    $country_code       Country code to check (same format that the one used by WooCommerce)
     */
    private function is_outside_EU( $country_code ) {

        $eu_country_codes = array(
            'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'EL',
            'ES', 'FI', 'FR', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV',
            'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK'
        );

        return ( ! in_array( $country_code , $eu_country_codes ) );

    }

}