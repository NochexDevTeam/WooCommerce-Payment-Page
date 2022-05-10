<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
/*
Plugin Name: Nochex Payment Gateway for Woocommerce
Plugin URI: https://github.com/NochexDevTeam/WooCommerce
Description: Accept Nochex Payments in Woocommerce.
Version: 2.7.5.1
Author: Nochex Ltd
*/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
add_action('plugins_loaded', 'woocommerce_nochex_init', 0);
function woocommerce_nochex_init() {
class wc_nochex extends WC_Payment_Gateway {
function __construct() { 
global $woocommerce;
$this->id = 'nochex';
$this->icon = WP_PLUGIN_URL . "/" . plugin_basename( dirname(__FILE__)) . '/images/nochex-logo.png';
$this->has_fields = false;
$this->method_title     = __( 'Nochex');
$this->method_description= __('Accept payments by Credit / Debit Card (Nochex), customers will be redirected to your payment page');
// Load the form fields.
$this->init_form_fields();
// Load the settings.
$this->init_settings();

if( !empty($this->settings['hide_billing_details']) ){
if ($this->settings['hide_billing_details'] == "Yes" || $this->settings['hide_billing_details'] == "yes") {
$billingNote = "<p style=\"font-weight:bold;margin-bottom:10px!important;\">".$this->settings['description']."</p><p style=\"font-weight:bold;color:red;\">Please check your billing address details match the details on your card that you are going to use.</p>";
} else {
$billingNote = $this->settings['description'];
}

// Define user set variables
$this->title                  = $this->settings['title'];
$this->description            = $billingNote;
$this->merchant_id            = $this->settings['merchant_id'];
$this->hide_billing_details   = $this->settings['hide_billing_details'];
$this->xmlitemcollection      = $this->settings['xmlitemcollection'];
$this->showPostage            = $this->settings['showPostage'];
$this->test_mode              = $this->settings['test_mode'];
$this->debug                  = $this->settings['debug'];
$this->order_complete_status  = $this->settings['order_complete_status'];
$this->order_onhold_status    = $this->settings['order_onhold_status'];
$this->order_failed_status    = $this->settings['order_failed_status'];
}

$this->callback_url = add_query_arg( 'wc-api', 'wc_nochex', home_url( '/' ) );

// Actions
// Update admin options
add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
// APC Handler
add_action( 'woocommerce_api_wc_nochex', array( $this, 'apc' ) );
// Success Page
add_action('woocommerce_receipt_nochex', array( $this, 'receipt_page'));
// Update and check amounts
add_action('woocommerce_order_button_text', array($this, 'updatePay'));
}

public function updatePay( $order_button_text ) {

global $post, $woocommerce;

$gtAmount = WC()->session->get('cart_totals');

if( $gtAmount["total"] > 0 && $gtAmount["total"] < 0.99) {
?>
	<style id="NCXlowAmt">
		.payment_method_nochex {
			display:none;
		}
	</style>
<?php
} else {
?>
	<script id="removeScr">
		var myEle = document.getElementById("NCXlowAmt");
		if(myEle){
			myEle.remove();
		}
		document.getElementById("removeScr").remove();		
	</script>
<?php
}

return $order_button_text;

}

/*** Debug Function* Record sections of the Nochex module to check everything is working correctly.*/
function debug_log( $debugMsg ) {
$log = new WC_Logger();
$log->add( 'Nochex', $debugMsg );
}

public function needs_setup() {		
return ! is_email( $this->merchant_id );
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
<h3><?php _e('Pay by Credit / Debit Card (Nochex)', 'woocommerce'); ?></h3>
<p><?php _e('Once Nochex has been setup and active. Customers will be able to pay by Nochex.', 'woocommerce'); ?></p>
<blockquote>> Note: Customers will be redirected when they press Place Order</blockquote>

<?php
// Nochex Validation - Check module enabled and if merchant field is blank / empty
if ( !empty($_REQUEST["woocommerce_nochex_enabled"]) == 1) {
	$this->debug_log("Nochex - Settings Save - If Nochex is enabled, begin checking required field ** Nochex Merchant ID / Email Address");	
	if ($_REQUEST["woocommerce_nochex_merchant_id"] == "") {
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
		<strong>Nochex Merchant ID / Email Issue</strong>: <?php esc_html_e( 'There appears to be an issue with your Nochex Merchant ID, please check.', 'woocommerce' ); ?>
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
// End admin_options()
/**
 * receipt_page
**/
function receipt_page( $order ) {
global $woocommerce;

$this->debug_log("Generate Nochex Form - Get all of the order data and information saved by the merchant");
$this->generate_nochex_form = include 'includes/class-wc-nochex-formBuilding.php';

$this->debug_log("Generate Nochex Form - Populate the payment form.");
include( plugin_dir_path( __FILE__ ) . '/templates/checkout/class-wc-nochex-form.php' );
	
}

/**
* Process the payment and return the result
**/
function process_payment( $order_id ) {
global $woocommerce;
$order = new WC_Order( $order_id );
if( $order->get_total() >= 0.99) {
return array(
	'result' => 'success',
	'redirect'=> $order->get_checkout_payment_url(true)
);
}
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
if($_POST){
$this->apc = include 'includes/class-wc-nochex-apccallback.php';
}
}

}

/**
 * Add the Gateway to WooCommerce
**/
function woocommerce_add_nochex_gateway($methods) {
	$methods[] = 'wc_nochex';
	return $methods;
}
add_filter('woocommerce_payment_gateways', 'woocommerce_add_nochex_gateway' );
}
} 
