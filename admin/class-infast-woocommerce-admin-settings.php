<?php

/**
 * Admin class used to build our plugin settings page
 *
 * @link       https://intia.fr
 * @since      1.0.0
 *
 * @package    Infast_Woocommerce
 * @subpackage Infast_Woocommerce/admin
 */

/**
 * Admin class used to build our plugin settings page
 *
 * @package    Infast_Woocommerce
 * @subpackage Infast_Woocommerce/admin
 * @author     INTIA <dev@intia.fr>
 */
class Infast_Woocommerce_Admin_Settings {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $admin ) {

		$this->admin = $admin;

	}

	public function is_woo_commerce_activated() {
		if ( ! function_exists( 'is_woocommerce_activated' ) ) {			
			if ( class_exists( 'woocommerce' ) ) {
				return true;
			}
			
			return false;
		}
	}

	/**
	 * Register our settings page
	 *
	 * @since    1.0.0
	 */
	public function register_admin_page() {

	    add_submenu_page(
	    	'woocommerce',
	    	__( 'INFast Parameters', 'infast' ),
	    	'INFast',
	    	'manage_options',
	    	'infast-page',
	    	array( $this, 'infast_page' )
	    );

	}

	/**
	 * Adding our section with multiple fields to our settings page
	 *
	 * @since    1.0.0
	 */
	public function register_sections() {
		if(!$this->is_woo_commerce_activated()) {
			return;
		}

	    register_setting(
	    	'infast-woocommerce',
	    	'infast_woocommerce',
	    	array( 'sanitize_callback' => array( $this, 'infast_sanitize_inputs' ) ) );

	    add_settings_section(
	        'infast_woocommerce_section',
	        __( 'INFast Parameters', 'infast' ),
	        array( $this, 'infast_woocommerce_section_callback' ),
	        'infast-woocommerce'
	    );

	    add_settings_field(
	        'infast_woocommerce_client_id',
	        __( 'Client ID', 'infast' ),
	        array( $this, 'infast_woocommerce_client_id_render' ),
	        'infast-woocommerce',
	        'infast_woocommerce_section'
	    );

	    add_settings_field(
	        'infast_woocommerce_client_secret',
	        __( 'Client Secret', 'infast' ),
	        array( $this, 'infast_woocommerce_client_secret_render' ),
	        'infast-woocommerce',
	        'infast_woocommerce_section'
	    );

	    add_settings_field(
	        'infast_woocommerce_enable_email',
	        __( 'Send invoices automatically by email?', 'infast' ),
	        array( $this, 'infast_woocommerce_enable_email_render' ),
	        'infast-woocommerce',
	        'infast_woocommerce_section'
	    );

	    add_settings_field(
	        'infast_woocommerce_cc_email',
	        __( 'Send a copy of the emails to this address', 'infast' ),
	        array( $this, 'infast_woocommerce_cc_email_render' ),
	        'infast-woocommerce',
	        'infast_woocommerce_section'
	    );

	    add_settings_field(
	        'infast_woocommerce_omit_item_description',
	        __( 'Import WooCommerce items without description into INFast', 'infast' ),
	        array( $this, 'infast_woocommerce_omit_item_description_render' ),
	        'infast-woocommerce',
	        'infast_woocommerce_section'
	    );

	    add_settings_field(
	        'infast_woocommerce_order_status_to_create_invoice',
	        __( 'Select the order status that triggers conversion to an INFast invoice', 'infast' ),
	        array( $this, 'infast_woocommerce_order_status_to_create_invoice_render' ),
	        'infast-woocommerce',
	        'infast_woocommerce_section'
	    );		
	}

	/**
	 * HTML render of Client ID field
	 *
	 * @since    1.0.0
	 */
	public function infast_woocommerce_client_id_render() {

	    $options = get_option( 'infast_woocommerce' );
		$client_id = '';
		if( is_array($options) && array_key_exists('client_id', $options)) {
			$client_id = $options['client_id'];
		}

	    ?>
	    <input type='text' name='infast_woocommerce[client_id]' id="infast-client-id" value='<?php  echo esc_attr( $client_id ); ?>'>
	    <?php

	}

	/**
	 * HTML render of Client secret field
	 *
	 * @since    1.0.0
	 */
	public function infast_woocommerce_client_secret_render() {

	    $options = get_option( 'infast_woocommerce' );
		$client_secret = '';
	    if ( is_array($options) && array_key_exists( 'client_secret', $options ) ) {
		    $client_secret = $options['client_secret'];
		    if ( ! empty( $client_secret ) )
		    	$client_secret = '*******************************';
		}
	    ?>
	    <input type='text' name='infast_woocommerce[client_secret]' id="infast-client-secret" value='<?php echo esc_attr( $client_secret ); ?>'>
	    <?php
	}

	/**
	 * HTML render of Enable email field
	 *
	 * @since    1.0.0
	 */
	public function infast_woocommerce_enable_email_render() {

	    $options = get_option( 'infast_woocommerce' );
		$enable_email = false;
		if( is_array($options) && array_key_exists('enable_email', $options)) {
			$enable_email = $options['enable_email'];
		}
	    ?>
	    <input type="checkbox" name="infast_woocommerce[enable_email]" value="1" <?php if ( isset ( $enable_email ) ) checked( $enable_email, 1 ); ?>
		<?php

	}

	/**
	 * HTML render of CC email field
	 *
	 * @since    1.0.0
	 */
	public function infast_woocommerce_cc_email_render() {

	    $options = get_option( 'infast_woocommerce' );
		$cc_email = false;
		if( is_array($options) && array_key_exists('cc_email', $options)) {
			$cc_email = $options['cc_email'];
		}
	    ?>
	    <input type="email" name="infast_woocommerce[cc_email]" value="<?php if ( isset ( $cc_email ) ) echo esc_attr( $cc_email ); ?>" />
		<?php

	}

	/**
	 * HTML render of checkbox to add item description in INFast items
	 *
	 * @since    1.0.8
	 */
	public function infast_woocommerce_omit_item_description_render() {

	    $options = get_option( 'infast_woocommerce' );
		$omit_item_description = false;
		if( is_array($options) && array_key_exists('omit_item_description', $options)) {
			$omit_item_description = $options['omit_item_description'];
		}
	    ?>
		<input type="checkbox" name="infast_woocommerce[omit_item_description]" value="1" <?php if ( isset( $omit_item_description ) ) checked( $omit_item_description, 1 ); ?> />
		<?php

	}
	
	/**
	 * HTML render of checkbox to add item description in INFast items
	 *
	 * @since    1.0.25
	 */
	public function infast_woocommerce_order_status_to_create_invoice_render() {

	    $options = get_option( 'infast_woocommerce' );

		// if no status selected, select completed by default
		if(!isset( $options['status_to_convert'] ) || count($options['status_to_convert']) == 0) {
			$options['status_to_convert']['wc-completed'] = 1;
		}

		$order_statuses = wc_get_order_statuses();

		foreach($order_statuses as $status => $label) {	
			?>
				<input type="checkbox" name="infast_woocommerce[status_to_convert][<?php echo esc_attr($status) ?>]" value="1" <?php if ( isset( $options['status_to_convert'][$status] ) ) checked( $options['status_to_convert'][$status], 1 ); ?> >
					<?php echo esc_attr($label) ?>
				</input>
				</br>
			<?php		
		} 
	}

	/**
	 * HTML render of our section
	 *
	 * @since    1.0.0
	 */
	public function infast_woocommerce_section_callback() {
	    ?>
		<a href="https://apps.infast.fr/app/en/settings/api/keys" target="_blank"><?php _e( 'Find your API keys in INFast', 'infast' ); ?></a>
		<?php
	}

	/**
	 * Sanitize the user inputs
	 *
	 * @since    1.0.0
	 */
	public function infast_sanitize_inputs( $input ) {
		$output = array();
		foreach( $input as $idx => $value ) {
			if( isset( $input[$idx] ) ) {
		    	if ( $idx == 'client_secret' ) {
					$infast_auth_api = Infast_Woocommerce_Auth_Api::getInstance();

					$oldClientSecret = get_option( 'infast_woocommerce' )['client_secret'];
					$oldClientSecretDecrypted = $infast_auth_api->decrypt_key( $oldClientSecret );

					// BUG ???
					// I don't understand why we pass twice in infast_sanitize_inputs
					// We pass a first time, and we encrypt the clientSecret
					// During the second pass $oldClientSecret is empty but $input[$idx] as the value of the encrypted clientSecret of the fist pass.
					// => We encrypt the clientSecret again

					/* Added by Damien on 27/12/2021 :
					This bug is known since several years: https://core.trac.wordpress.org/ticket/21989
					To work around this, we add a '~' at the start of the encrypted key
					'~' is not a possible character of encrypted key
					So if we find it at the start of the key, it means the key has already been encrypted, we can keep it like this
					In consequence, the decrypting function is now removing the first '~' when used
					*/
		    		if ( strpos( $input[$idx], '*' ) !== false || $input[$idx] == $oldClientSecretDecrypted ) {
			    		$output[$idx] = $oldClientSecret;
			    	} else if ( $input[$idx][0] == '~' ) {
			    		$output[$idx] = $input[$idx];
			    	} else if ( ! empty( $value ) ) {
			    		$output[$idx] = $infast_auth_api->encrypt_key( $value );
			    	}
		    	} else {
					if(is_array($value)) {
						foreach( $value as $idx2 => $value2 ) {
							$output[$idx][$idx2] = strip_tags( stripslashes( $input[ $idx ][ $idx2 ] ) );
						}
					} else {
						$output[$idx] = strip_tags( stripslashes( $input[ $idx ] ) );
					}
		        	
		    	}
		    }   
		}

		return $output;

	}	

	/**
	 * HTML render of our settings page
	 *
	 * @since    1.0.0
	 */
	public function infast_page() {

		settings_errors();
	    ?>
		<h2><?php _e( 'INFast WooCommerce', 'infast' ); ?></h2>
		<img src="https://assets.intia.fr/app/themes/intia/assets/images/dashboard.png?v=2" width="300">
		<p>
			<?php _e( 'INFast is a Web application that lets you easily create quotes and invoices.', 'infast' ); ?><br>
			<?php _e( 'For more information: ', 'infast' ); ?> <a href="https://intia.fr/?utm_source=plugin_woocommerce" target="_blank"><?php _e( 'Click here.', 'infast' ); ?></a><br>
			<br>
			<?php _e( 'With INFast WooCommerce, your customers and items are automatically synchronized in INFast.', 'infast' ); ?><br>
			<?php _e( 'When an order is validated and paid, an invoice is created in INFast.', 'infast' ); ?><br>
			<br>
			<?php _e( 'To use this plugin, you need an INFast account.', 'infast' ); ?><br>
			<?php _e( 'If you don’t have an INFast account, you can create one by', 'infast' ) . ' '; ?><a href="https://apps.infast.fr/account/fr/signup?utm_source=plugin_woocommerce" target="_blank"><?php _e( 'clicking here.', 'infast' ); ?></a><br>
			<br>
			<?php _e( 'Would you like to discuss your project? ', 'infast' ); ?>
			<a href="https://calendly.com/intia-devis-factures/renseignement-plugin-woocomerce?utm_source=plugin_woocommerce" target="_blank"><?php _e( 'Book an appointment', 'infast' ); ?></a><br>
			<br>
		</p>

		<?php
			if(!$this->is_woo_commerce_activated()) {
				?>
				<h1 style="color:red">
					<?php _e( 'WooCommerce is not activated. Please activate WooCommerce to use this plugin.', 'infast' ); ?>
				</h1>
				<?php
				return;
			}
		?>

	    <form action='options.php' method='post'>

	        <?php
	        settings_fields( 'infast-woocommerce' );
	        do_settings_sections( 'infast-woocommerce' );
	        submit_button();
	        ?>

	    </form>

		<button type="button" class="button button-primary infast-test-btn"><?php _e( 'Test the connection', 'infast' ); ?></button>
		</br>
		</br>
		
	    <h2><?php _e( 'Synchronize products', 'infast' ); ?></h2>
	    <button type="button" class="button button-primary infast-syncall-btn"><?php _e( 'Start synchronization', 'infast' ); ?></button>

		</br>
		</br>
	    <h2><?php _e( 'Unlink WooCommerce products from INFast items', 'infast' ); ?></h2>
	    <button type="button" class="button button-primary infast-unlink-items-btn"><?php _e( 'Unlink', 'infast' ); ?></button>

	    <?php
	}

	/**
	 * Generate a new OAuth2 token when client ID and/or secret has been updated
	 *
	 * @since    1.0.0
	 */
	public function infast_option_updated( $option, $old_value, $value ) {

		if ( $option == 'infast_woocommerce' ) {
			$old_client_id = '';
			$old_client_secret = '';
			$new_client_id = '';
			$new_client_secret = '';
			if( is_array($value) && array_key_exists('client_id', $value)) {
				$new_client_id = $value['client_id'];
			}
			if( is_array($value) && array_key_exists('client_secret', $value)) {
				$new_client_secret = $value['client_secret'];
			}
			if( is_array($old_value) && array_key_exists('client_id', $old_value)) {
				$old_client_id = $old_value['client_id'];
			}
			if( is_array($old_value) && array_key_exists('client_secret', $old_value)) {
				$old_client_secret = $old_value['client_secret'];
			}

			if ( ( ! empty( $new_client_id ) && ! empty( $new_client_secret ) ) &&
				 ( $new_client_id != $old_client_id ||
				 $new_client_secret != $old_client_secret ) ) {

				$infast_auth_api = Infast_Woocommerce_Auth_Api::getInstance();
				$access_token = $infast_auth_api->get_oauth2_token( true );

				if ( $access_token == false ) {
					add_settings_error( 'infast-woocommerce', 'OAuth2 Error', 'Votre client ID et/ou client secret n\'a pas pu etre vérifié' );
				}

			}
		}

	}

	/**
	 * Add custom field to shipping methods to store INFast ID
	 *
	 * @since    1.0.0
	 */
	public function infast_shipping_add_infast_id_field( $settings ) {

	    $settings['infast_shipping_id'] = array(
	        'title'       => esc_html__( 'INFast ID', 'flightbox' ),
	        'type'        => 'hidden',
	        'description' => '',
	    );

	    return $settings;
	}

	public function infast_shipping_add_infast_id_field_filter() {
		if(!$this->is_woo_commerce_activated()) {
			return;
		}

	    $shipping_methods = WC()->shipping->get_shipping_methods();
	    foreach ( $shipping_methods as $shipping_method ) {
	        add_filter( 'woocommerce_shipping_instance_form_fields_' . $shipping_method->id, array( $this, 'infast_shipping_add_infast_id_field' ) );
	    }
	}

	public function infast_synchronise_all() {
		$this->admin->synchronise_all();
		wp_send_json_success();
	}

	public function infast_unlink_items() {
		$this->admin->unlink_items();
		wp_send_json_success();
	}

	

	public function infast_test_authentication() {
		$name = $this->admin->test_authentication();
		if($name) {
			wp_send_json_success($name);
		} else {
			wp_send_json_error();
		}		
	}
	
}
