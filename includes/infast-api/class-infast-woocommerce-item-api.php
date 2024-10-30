<?php

/**
 * The file that defines the class interacting with INFast API item
 *
 * @link       https://intia.fr
 * @since      1.0.0
 * @package    Infast_Woocommerce
 * @subpackage Infast_Woocommerce/includes/infast-api
 * @author     INTIA <dev@intia.fr>
 */
class Infast_Woocommerce_Item_Api {

    /**
     * This class in a Singleton, so you can access it from anywhere else with Infast_Woocommerce_Item_Api::getInstance();
     *
     * @since    1.0.0
     */
    private static $instance = null;

    private function __construct() {
    }

    public static function getInstance()
    {
        if ( self::$instance == null ) {
            self::$instance = new Infast_Woocommerce_Item_Api();
        }  
        return self::$instance;
    }


    /**
     * Create/Update a product or its variations on INFast
     *
     * @since    1.0.0
     * @param      int    $product       WooCommerce product
     * @param      bool    $force       Force OAuth2 token regeneration
     */
    public function create_product_or_variations( $product ) {

        if ( $product->is_type( 'variable' ) ) {
            $variations = $product->get_available_variations('object');
            foreach ( $variations as $variation ) {
                $this->create_product($variation, true);
                
                // add a sleep to don't be blocked by the rate limiter
                usleep(1/10.0);
            }
        } else {
            $this->create_product($product);
        }
    }

    public function get_product_description( $product ) {
        $description = '';

        $omit_item_description = false;
        $options = get_option( 'infast_woocommerce' );
        if( is_array($options) && array_key_exists('omit_item_description', $options)) {
            $omit_item_description = $options['omit_item_description'];
        }

        if ( !$omit_item_description ) {
            // $description = 
            //     $product->get_description() 
            //         ? $product->get_description() 
            //         : $product->get_short_description();        
            $description = $product->get_short_description();

            $description = wp_strip_all_tags( $description );            
        }
        return $description;
    }

    public function get_variation_description( $product, $variation ) {
        $description = '';
        $options = get_option( 'infast_woocommerce' );
        if ( !$options['omit_item_description'] ) {
            // product description AND variation desctiption
            // $description = $this->get_product_description($product) . '<br>' . $variation->get_description();        

            // variation description
            // $description = $variation->get_description();  

            // variation attributes
            $attributes = $variation->get_attributes();
            foreach ($attributes as $attribute_index => $attribute_value) {
                $description .= $attribute_index . ' : ' . $attribute_value . chr(13) . chr(10);
            }


            $description = wp_strip_all_tags( $description );               
        }

        $description = wp_strip_all_tags( $description );

        return $description;
    } 

    public function get_product_tax_rate($product) {
        $tax_rate = 0;
        if ( wc_tax_enabled() ) {
            $tax = new WC_Tax();
            $tax_class = $product->get_tax_class() ;
            $taxes = $tax->get_rates( $tax_class );
            $rates = array_shift( $taxes );
            if ( $rates != NULL)
                $tax_rate = array_shift( $rates );
        }
        if(!$tax_rate)
            $tax_rate = 0;

        return $tax_rate;
    }

    /**
     * Create/Update a product or its variations on INFast
     *
     * @since    1.0.0
     * @param      int    $product       WooCommerce product
     * @param      bool    $force       Force OAuth2 token regeneration
     */
    private function create_product( $product, $is_variation = false, $force = false ) {

        $product_id = $product->get_id();
		$infast_product_id = get_post_meta( $product_id, '_infast_product_id', true );
		if ( ! $infast_product_id )
			$infast_product_id = NULL;


        if ( $infast_product_id == NULL ) {
            $request_url = INFAST_API_URL . 'api/v1/products';
            $request_type = 'POST';
        } else {
            $request_url = INFAST_API_URL . 'api/v1/products/' . $infast_product_id;
            $request_type = 'PATCH';
        }

        if ( $is_variation )
            $data = $this->create_variation_prepare_data( $product );
        else
            $data = $this->create_product_prepare_data( $product );


        $infast_auth_api = Infast_Woocommerce_Auth_Api::getInstance();
        $access_token = $infast_auth_api->get_oauth2_token( $force );
        if ( $access_token == false ) {
            error_log( 'INFast API - Failed to create/update product. Invalid Client ID and/or Client Secret' );
            return false;
        }

        $headers = array(
            'authorization' =>  'Bearer ' . $access_token,
            'content-type'  =>  'application/json',
        );

        $args = array(
            'body'        => json_encode( $data ),
            'method'      => $request_type,
            'headers'     => $headers,
            'timeout'     => '30',
            'redirection' => '10',
            'httpversion' => '1.1',
        );

        $resp = wp_remote_request( $request_url, $args );
        $http_code = wp_remote_retrieve_response_code( $resp );

        if ( $http_code == 401 ) { // access token is expired
            if(!$force) {
                return $this->create_product( $product, $is_variation, true );
            }

            error_log( 'INFast API - Failed to create/update item. Authentication failed' );
            return false;
        }

        if ( $http_code == 404 ) { // product not found (deleted on INFast side)
            if(!$force) {
                delete_post_meta( $product_id, '_infast_product_id');
                return $this->create_product( $product, $is_variation, true );
            }

            error_log( 'INFast API - Failed to create/update item. Item not found on INFast' );
            return false;
        }

        if ( is_wp_error( $resp ) ) {
            $error_message = $resp->get_error_message();
            error_log( 'INFast API - Failed to create/update item. ' . $error_message );
            return false;
        } else {
            $response = json_decode( $resp['body'], true );
            $infast_product_id = $response['_id'];
            update_post_meta( $product_id, '_infast_product_id', $infast_product_id );
            return $infast_product_id;        
        }

    }
    
    /**
     * Fill an array with all data needed to call the API to create/update a product
     * Full parameters list: https://infast.docs.stoplight.io/api-reference/products/createproduct
     *
     * @since    1.0.0
     * @param      int    $product       WooCommerce product 
     */
    private function create_product_prepare_data( $product ) {

        $data = array();

        $reference = $product->get_sku() ? $product->get_sku() : strval( $product->get_id() );
        $reference = substr($reference, 0, 24);

        $data['name'] = $product->get_name();
        $data['price'] = floatval( wc_get_price_excluding_tax( $product ) );
        $data['vat'] = $this->get_product_tax_rate($product);
        $data['reference'] = $reference;
        $data['description'] = $this->get_product_description($product);
        $data['isService'] = false;

        return $data;
    }

    /**
     * Fill an array with all data needed to call the API to create/update a product
     * Full parameters list: https://infast.docs.stoplight.io/api-reference/products/createproduct
     *
     * @since    1.0.0
     * @param      int    $product       WooCommerce product ID
     */
    private function create_variation_prepare_data( $variation ) {               

        $parent_id = $variation->get_parent_id();
        $product = wc_get_product( $parent_id ); 

        $reference = $variation->get_sku() ? $variation->get_sku() : strval( $variation->get_id() );
        $reference = substr($reference, 0, 24);

        $data = array();
        $data['name'] = $variation->get_name();
        $data['price'] = floatval( wc_get_price_excluding_tax($variation) );
        $data['vat'] = $this->get_product_tax_rate($variation);
        $data['reference'] = $reference;
        $data['description'] = $this->get_variation_description($product, $variation);
        $data['isService'] = false;                    

        return $data;
    }

   
}