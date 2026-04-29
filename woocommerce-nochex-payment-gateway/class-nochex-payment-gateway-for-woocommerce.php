<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
/*
Plugin Name: Nochex Payment Gateway for Woocommerce
Plugin URI: https://github.com/NochexDevTeam/WooCommerce
Description: Accept Nochex Payments in Woocommerce.
Version: 3.0.2
Author: Nochex Ltd
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
 
if ( is_plugin_active('nochexapi/nochexapi.php') ) {		 
	add_action( 'admin_notices', 'nochex_install_ncx_notice' );	
	add_action( 'admin_init', 'deactivate_plugin_now' );
} else {
	add_action('plugins_loaded', 'woocommerce_nochex_init', 0);
}

function deactivate_plugin_now() {
    if ( is_plugin_active('nochex-payment-gateway-for-woocommerce/class-nochex-payment-gateway-for-woocommerce.php') ) {
        deactivate_plugins('nochex-payment-gateway-for-woocommerce/class-nochex-payment-gateway-for-woocommerce.php');
    }
}

/* Admin Notices */
function nochex_install_wc_notice(){
	?>
	<div class="error">
		<p><?php esc_html_e( 'WooCommerce is Required.', 'nochex-payment-gateway-for-woocommerce' ); ?></p>
	</div>
	<?php
}
function nochex_install_ncx_notice(){
	?>
	<div class="error">
		<p><?php esc_html_e( 'You can only have 1 Nochex integration on your website, please deactivate all other Nochex plugins first before enabling. If you are having integration issues we encourage you to contact us at support.nochex.com', 'nochex-payment-gateway-for-woocommerce' ); ?></p>
	</div>
	<?php
}

function woocommerce_nochex_init() {

class nochex_payment_gateway_for_woocommerce extends WC_Payment_Gateway {

function __construct() { 

$this->id = 'nochex_payment_gateway_for_woocommerce';
$this->icon = plugin_dir_url( __FILE__ ) . 'images/nochex-logo.png';
$this->has_fields = false;
$this->method_title     = esc_attr__( 'Nochex Payment Page.', 'nochex-payment-gateway-for-woocommerce' );
$this->method_description= esc_attr__( 'Accept payments by Credit / Debit Card (Nochex), customers will be redirected to your payment page', 'nochex-payment-gateway-for-woocommerce' );
// Load the form fields.
$this->init_form_fields();
// Load the settings.
$this->init_settings();

if( ! empty( $this->settings['hide_billing_details'] ) ){
if ( 'Yes' === $this->settings['hide_billing_details'] || 'yes' === $this->settings['hide_billing_details'] ) {
$billingNote = "<p style=\"font-weight:bold;margin-bottom:10px!important;\">" . ( $this->settings['description'] ?? '' ) . "</p><p style=\"font-weight:bold;color:red;\">Please check your billing address details match the details on your card that you are going to use.</p>";
} else {
$billingNote = $this->settings['description'] ?? '';
}
} else {
	$billingNote = $this->settings['description'] ?? '';
}
 
// Define user set variables
$this->title                  = $this->settings['title'] ?? __( 'Nochex', 'nochex-payment-gateway-for-woocommerce' );
$this->description            = $billingNote;

// Actions
// Update admin options
add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
// APC Handler
add_action( 'woocommerce_api_nochex_payment_gateway_for_woocommerce', array( $this, 'apc' ) );
// Success Page
add_action('woocommerce_receipt_nochex', array( $this, 'receipt_page'));
// Update and check amounts
add_filter( 'woocommerce_available_payment_gateways', array( $this, 'disable_payment_gateway_below_minimum') );
			
}
   function disable_payment_gateway_below_minimum( $available_gateways ) {
        $total = ( WC()->cart && method_exists( WC()->cart, 'get_total' ) ) ? (float) WC()->cart->get_total( 'edit' ) : 0;
        if ( $total > 0 && $total < 0.50 ) {
            unset( $available_gateways['nochex_payment_gateway_for_woocommerce'] ); // Replace 'your_payment_gateway_id' with the ID of the payment method
        }
        return $available_gateways;
   }

/*** Debug Function* Record sections of the Nochex module to check everything is working correctly.*/
function debug_log( $debugMsg ) {
$log = new WC_Logger();
$log->add( 'Nochex', $debugMsg );
}

/**
* Initialise Gateway Settings Form Fields
**/
function init_form_fields() {
$this->form_fields = include 'includes/settings-nochex.php';
}

/**
* Processes and saves options.
* If there is an error thrown, will continue to save and validate fields, but will leave the erroring field out.
*
* @return bool was anything saved?
*/
public function process_admin_options() {	
	$saved = parent::process_admin_options();
	return $saved;		
}

/**
 * Admin Panel Options 
 * Options for bits like 'title' and availability on a country-by-country basis
 *
 * @since 1.0.0
 */
function admin_options() {
?>
<h3><?php esc_html_e( 'Pay by Credit / Debit Card (Nochex Payment Page)', 'nochex-payment-gateway-for-woocommerce' ); ?></h3>
<p><?php esc_html_e('Once Nochex has been setup and active. Customers will be redirected to pay by Nochex after pressing place order on your checkout page.', 'nochex-payment-gateway-for-woocommerce'); ?></p>

<?php
// Nochex Validation - Check module enabled and if merchant field is blank / empty
$posted_enabled = isset( $_POST['woocommerce_nochex_enabled'] ) ? sanitize_text_field( wp_unslash( $_POST['woocommerce_nochex_enabled'] ) ) : '';
$posted_merchant_id = isset( $_POST['woocommerce_nochex_merchant_id'] ) ? sanitize_text_field( wp_unslash( $_POST['woocommerce_nochex_merchant_id'] ) ) : '';
if ( '1' === $posted_enabled ) {
	$this->debug_log("Nochex - Settings Save - If Nochex is enabled, begin checking required field ** Nochex Merchant ID / Email Address");	
	if ( '' === $posted_merchant_id ) {
	$this->debug_log("Nochex - Settings - Empty - Show Error message");	
	$this->debug_log("Reload Nochex Settings for the merchant");	
	?>
	<style>
	#woocommerce_nochex_merchant_id{
		border:1px solid red;
	}
	#message{
		display:none;
	}
	</style>	
	<div class="inline error">
	<p>
		<strong>Nochex Merchant ID / Email Issue</strong>: <?php esc_html_e( 'There appears to be an issue with your Nochex Merchant ID, please check.', 'nochex-payment-gateway-for-woocommerce' ); ?>
	</p>
	</div>

<?php
	}else{
	
	$this->debug_log("Nochex - Settings - Success");	
	$this->debug_log("Reload Nochex Settings for the merchant");	
	
?>
<style>
#woocommerce_nochex_merchant_id{
	border:1px solid inherit;
}
#message{
	display:block;
}
</style>

<?php

}
}

?>
 
<table class="form-table">
<?php
// Generate the HTML For the settings form.
$this->generate_settings_html();
?>
</table><!--/.form-table-->
<?php
}

public function process_payment( $order_id ) {

		include_once dirname( __FILE__ ) . '/includes/class-nochex-payment-gateway-for-woocommerce-request.php';

		$order          = wc_get_order( $order_id );
		if ( ! $order ) {
			wc_add_notice( __( 'Unable to load the order for payment.', 'nochex-payment-gateway-for-woocommerce' ), 'error' );
			return array( 'result' => 'failure' );
		}
		$nochex_request = new Nochex_Payment_Gateway_For_Woocommerce_Request( $this );
		
		if ( isset( $this->settings['test_mode'] ) && 'yes' === $this->settings['test_mode'] ) {
			$testTransaction = '100';
		} else {
			$testTransaction = '0';
		}
		
		return array(
			'result'   => 'success',
			'redirect' => $nochex_request->get_ncxurl( $order, $testTransaction, $this->settings, $this->get_return_url( $order )),
		);
	}
	
/**
 * Perform Automatic Payment Confirmation (APC)
 *
 * @access public
 * @return void
 */
function apc() {
global $woocommerce;

$this->debug_log("APC - APC / Callback script to update orders - Begin");	
if ( ! empty( $_POST ) ) {
$this->apc = include 'includes/class-nochex-payment-gateway-for-woocommerce-apccallback.php';
}
}

}

/** 
 * Add setting link to plugins page
**/
function nochex_settings_link( $links ) {
	// Build and escape the URL.
	$url = esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=nochex_payment_gateway_for_woocommerce' ) );
	// Create the link.
	$settings_link = "<a href='$url'>" . esc_attr__( 'Settings.', 'nochex-payment-gateway-for-woocommerce' ) . '</a>';
	// Adds the link to the end of the array.
	array_unshift(
		$links,
		$settings_link
	);
	return $links;
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'nochex_settings_link');


/**
 * Add the Gateway to WooCommerce
**/
function woocommerce_add_nochex_gateway($gateways) {
	$gateways[] = 'nochex_payment_gateway_for_woocommerce';
	return $gateways;
}
add_filter('woocommerce_payment_gateways', 'woocommerce_add_nochex_gateway' );


/**
 * Custom function to declare compatibility with cart_checkout_blocks feature 
*/
function declare_ncx_cart_checkout_blocks_compatibility() {
    // Check if the required class exists
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        // Declare compatibility for 'cart_checkout_blocks'
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}
// Hook the custom function to the 'before_woocommerce_init' action
add_action('before_woocommerce_init', 'declare_ncx_cart_checkout_blocks_compatibility');

// Hook the custom function to the 'woocommerce_blocks_loaded' action
add_action( 'woocommerce_blocks_loaded', 'register_order_approval_payment_method_type_ncx' );

/**
 * Custom function to register a payment method type

 */
function register_order_approval_payment_method_type_ncx() {
    // Check if the required class exists
    if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        return;
    }

    // Include the custom Blocks Checkout class
    require_once plugin_dir_path(__FILE__) . '/includes/class-block.php';

    // Hook the registration function to the 'woocommerce_blocks_payment_method_type_registration' action
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
            // Register an instance of My_Custom_Gateway_Blocks
            $payment_method_registry->register( new Nochex_Payment_Gateway_For_Woocommerce_Blocks );
        }
    );
}

}
}
