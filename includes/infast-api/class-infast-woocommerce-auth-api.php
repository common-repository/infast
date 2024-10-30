<?php

/**
 * The file that defines the class interacting with INFast API authentication
 *
 * @link       https://intia.fr
 * @since      1.0.0
 * @package    Infast_Woocommerce
 * @subpackage Infast_Woocommerce/includes
 * @author     INTIA <dev@intia.fr>
 */
class Infast_Woocommerce_Auth_Api {

    /**
     * This class in a Singleton, so you can access it from anywhere else with Infast_Woocommerce_Auth_Api::getInstance();
     *
     * @since    1.0.0
     */
    private static $instance = null;



    private function __construct() {
    }

    public static function getInstance()
    {
        if ( self::$instance == null ) {
            self::$instance = new Infast_Woocommerce_Auth_Api();
        }  
        return self::$instance;
    }

    /**
	 * Encrypt a key
	 *
	 * @since    1.0.0
	 * @param    string     $string    key to encrypt
	 */
	public function encrypt_key( $string )
	{
		$salt1 = get_option( 'infast_saltkey_1' );
        $salt2 = get_option( 'infast_saltkey_2' );

	    $encrypt_method = 'AES-256-CBC';
	    $key = hash( 'sha256', $salt1 );
	    $iv = substr( hash( 'sha256', $salt2 ), 0, 16 ); // sha256 is hash_hmac_algo
	    $output = openssl_encrypt( $string, $encrypt_method, $key, 0, $iv );
	    $output = base64_encode( $output );

	    return '~' . $output;
	}

    /**
     * Decrypt key
     *
     * @since    1.0.0
     * @param      string    $string       The key
     */
    public function decrypt_key( $string ) {

        $string = ltrim( $string, '~' );
        $salt1 = get_option( 'infast_saltkey_1' );
        $salt2 = get_option( 'infast_saltkey_2' );

        $encrypt_method = 'AES-256-CBC';
        $key = hash( 'sha256', $salt1 );
        $iv = substr( hash( 'sha256',  $salt2 ), 0, 16 ); // sha256 is hash_hmac_algo
        $output = openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );

        return $output;
    }

    /**
     * Get the OAuth2 token used to authenticate INFast API calls. Generate it if needed.
     *
     * @since    1.0.0
     * @param      bool    $override       Force Oauth2 token regeneration
     */
    public function get_oauth2_token( $override = false ) {

        if ( $override == false ) {
            $access_token = get_option( 'infast_access_token' );
            if ( $access_token != false && ! empty( $access_token ) )
                return $access_token;
        }

        $url = INFAST_API_URL . 'oauth2/token';

        $client_id = '';
        $client_secret = '';

        $options = get_option( 'infast_woocommerce' );
        if( is_array($options) && array_key_exists('client_id', $options)) {
            $client_id = $options['client_id'];
        }
        if( is_array($options) && array_key_exists('client_secret', $options)) {
			$client_secret = $options['client_secret'];
		}

        $client_secret = $this->decrypt_key( $client_secret );
        $body = array(
            'client_id'     => $client_id ,
            'client_secret' => $client_secret,
            'grant_type'    => 'client_credentials',
            'scope'         => 'write',
        );

        $headers = array(
            'Content-Type'  =>  'application/x-www-form-urlencoded',
        );

        $args = array(
            'body'        => $body,
            'headers'     => $headers,
        );

        $resp = wp_remote_post( $url, $args );
        $http_code = wp_remote_retrieve_response_code( $resp );

        if ( is_wp_error( $resp ) ) {

            $error_message = $resp->get_error_message();
            error_log( 'INFast WooCommerce - Get OAuth2 access token: ' . $error_message );
            update_option( 'infast_access_token', false );
            return false;

        } else if ( $http_code == 200) {

            $resp_body = json_decode( $resp['body'], true );
            if ( is_array( $resp_body ) && array_key_exists( 'access_token', $resp_body ) ){
                $access_token = $resp_body['access_token'];
                update_option( 'infast_access_token', $access_token );
                return $access_token;                
            } else {
                error_log( 'INFast WooCommerce - Get OAuth2 access token: ' . print_r( $resp['body'] ) );
                update_option( 'infast_access_token', false );
                return false;
            }

        } else {
            error_log( 'INFast WooCommerce - Get OAuth2 access token: ' . print_r( $resp['body'] ) );
            update_option( 'infast_access_token', false );
            return false;  
        }

        
    }

    public function test_authentication( $force = false ) {
        $access_token = $this->get_oauth2_token( $force );
        if ( $access_token == false ) {
            error_log( 'INFast API - Failed to test authentication' );
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

        $resp = wp_remote_get( INFAST_API_URL . 'api/v1/me', $args );
        $http_code = wp_remote_retrieve_response_code( $resp );

        if ( $http_code == 401 ) { // access token is expired
            if(!$force) {
                return $this->test_authentication( true );
            }

            error_log( 'INFast API - Failed to test authentication' );
            return false;
        }

        if ( is_wp_error( $resp ) ) {
            $error_message = $resp->get_error_message();
            error_log( 'INFast API - Failed to test authentication' );
            return false;
        } else {
            $response = json_decode( $resp['body'], true );
            $me = $response['name'];
            return $me;            
        }
    }

}