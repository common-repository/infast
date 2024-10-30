<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://intia.fr
 * @since      1.0.0
 *
 * @package    Infast_Woocommerce
 * @subpackage Infast_Woocommerce/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Infast_Woocommerce
 * @subpackage Infast_Woocommerce/admin
 * @author     INTIA <dev@intia.fr>
 */
class Infast_Woocommerce_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		// We don't use any CSS in admin yet
		//wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/infast-woocommerce-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/infast-woocommerce-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Call our specific INFast API class to run the process of creating a new invoice
	 *
	 * @since    1.0.0
	 */
	public function create_invoice( $order_id ) {

		$infast_document_api = Infast_Woocommerce_Document_Api::getInstance();
		$infast_document_api->create_invoice( $order_id );

	}

	/**
	 * This function test authentication
	 *
	 * @since    1.0.3
	 */
	public function test_authentication() {

		$infast_auth_api = Infast_Woocommerce_Auth_Api::getInstance();
		return $infast_auth_api->test_authentication();
		
	}

	/**
	 * This function synchronise all WooCommerce products to INFast
	 *
	 * @since    1.0.0
	 */
	public function synchronise_all() {

		$infast_item_api = Infast_Woocommerce_Item_Api::getInstance();

		$args = array(
		  'numberposts' => -1,
		  'post_type'   => 'product'
		);
		 
		$posts = get_posts( $args );
		foreach ( $posts as $post ) {
			$product = wc_get_product( $post->ID ); 
			$infast_item_api->create_product_or_variations( $product );
			
			// add a sleep to don't be blocked by the rate limiter
			usleep(1/10.0);
		}

	}


	/**
	 * This function remove all infast id from metadata
	 *
	 * @since    1.0.16
	 */
	public function unlink_items() {

		$args = array(
		  'numberposts' => -1,
		  'post_type'   => 'product'
		);
		 
		$posts = get_posts( $args );
		foreach ( $posts as $post ) {
			delete_post_meta( $post->ID, '_infast_product_id');
		}

		$args = array(
			'numberposts' => -1,
			'post_type'   => 'product_variation'
		  );
		   
		  $posts = get_posts( $args );
		  foreach ( $posts as $post ) {
			  delete_post_meta( $post->ID, '_infast_product_id');
		}
	}

	
	/**
	 * Update the product on INFast
	 *
	 * @since    1.0.0
	 * @param      integer    $product_id       WooCommerce Product ID updated
	 */
	public function update_product( $product_id ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		    return;

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
			return;

		$product = wc_get_product( $product_id );

		$infast_item_api = Infast_Woocommerce_Item_Api::getInstance();
		$infast_item_api->create_product_or_variations( $product );

	}

	
	/**
	 * Hook during duplcate a product
	 * We need remove infast _id
	 *
	 * @since    1.0.15
	 */
	public function duplicate_product( $duplicate, $product ) {
		$product_id = $duplicate->get_id();
		$infast_product_id = get_post_meta( $product_id, '_infast_product_id', true );
		if ( ! $infast_product_id ) {
			return;
		}
		delete_post_meta( $product_id, '_infast_product_id');
	}

	/**
	 * Add a link to view the invoice in the order view
	 *
	 * @since    1.0.8
	 */
	public function add_link_to_customer_order_page( $order ) {
		$order_id = $order->get_id();
		$inbox_url = get_post_meta( $order_id, '_infast_inbox_url' );

		if( !$inbox_url ) {
			return;
		}

		$link = '<p>';
		$link .= '<a href="'. $inbox_url[0] . '"  target="_blank">';
		$link .= __( 'Click here to see the invoice.', 'infast' );
		$link .= '</a>';
		$link .= '</p>';
		echo $link;
	}

	public function add_link_to_admin_order_page( $order) {
		$order_id = $order->get_id();
		$infast_document_id = get_post_meta( $order_id, '_infast_document_id' );

		if( !$infast_document_id ) {
			return;
		}

		$link = '<p>';
		$link = '<strong>Facture :</strong><br>';
		$link .= '<a href="https://app.intia.fr/portal.html#/invoice/'. $infast_document_id[0] . '"  target="_blank">';
		$link .= __( 'Click here to see the invoice.', 'infast' );
		$link .= '</a>';
		$link .= '</p>';
		echo $link;
	}

	/**
	 * Add documentId in data during display the shop_order page
	 *
	 * @since    1.0.8
	 */
	public function prepare_link_to_admin_order_page_preview( $data, $order ) {
		$order_id = $order->get_id();
		$document_id = get_post_meta( $order_id, '_infast_document_id' );

		$link = '';
		if( $document_id ) {
			$link = '<p class="wc-backbone-modal-header">';
			$link .= '<a href="https://app.intia.fr/portal.html#/invoice/'. $document_id[0] . '" target="_blank">';
			$link .= __( 'Click here to view the invoice in INFast.', 'infast' );
			$link .= '</a>';
			$link .= '</p>';			
		}

		$data['infast_invoice_link'] = $link;
		return $data;
	}	

	/**
	 * Add a link to view the invoice in INFast in the shop_order view
	 *
	 * @since    1.0.8
	 */
	public function add_link_to_admin_order_page_preview( ) {
		?> {{{data.infast_invoice_link}}} <?php
	}	

	public function http_headers_useragent( $user_agent, $url) {
		return $user_agent . '; infast-plugin-version/1.0.30';
	}	

	public function add_settings_link( array $links ) {
		$url = get_admin_url() . "admin.php?page=infast-page";
		$settings_link = '<a href="' . $url . '">' . __( 'Settings', 'infast' ). '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
	
}
