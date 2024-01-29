<?php
/**
 * Settings for Nochex Gateway.
*/

defined( 'ABSPATH' ) || exit;

//$this->init_settings();

if ( defined( 'WC_LOG_DIR' ) ) {
if ( !empty($this->settings['debug']) == "Yes" ) {
	$log_url = add_query_arg( 'tab', 'logs', add_query_arg( 'page', 'wc-status', admin_url( 'admin.php' ) ) );
	$log_key = 'class-wc-nochex-here-' . sanitize_file_name( wp_hash( 'class-wc-nochex' ) ) . '-log';
	$log_url = add_query_arg( 'log_file', $log_key, $log_url );

	$label = '' . sprintf( __( '%1$sView Your Nochex Logs%2$s', 'your-textdomain-here' ), '<a href="' . esc_url( $log_url ) . '">', '</a>' );
}
}

return array(
'enabled' => array(
'title' => __( 'Enable/Disable', 'woocommerce' ),
'type' => 'checkbox',
'label' => __( 'Enable Nochex', 'woocommerce' ),
'default' => 'yes'
), 
'title' => array(
'title' => __( 'Title', 'woocommerce' ),
'type' => 'text',
'desc_tip' => __( 'Title of the Nochex payment option, visible to customers at the checkout.', 'woocommerce' ),
'default' => __( 'Nochex', 'woocommerce' ),
'placeholder' => 'example: Pay by Credit/Debit Card.',
),
'description' => array(
'title' => __( 'Checkout Message', 'woocommerce' ),
'type' => 'textarea',
'desc_tip' => __( 'Message visible to customers after selecting Nochex as their payment option.', 'woocommerce' ),
'default' => __('Pay securely using Nochex. You can pay using your credit or debit card.', 'woocommerce'),
'placeholder' => 'example: Pay securely using Nochex. You can pay using your credit or debit card.',
),
'merchant_id' => array(
'title' => __( 'Nochex Merchant ID /<br/> Email Address', 'woocommerce' ),
'type' => 'text',
'desc_tip' => 'Your Nochex Merchant ID / Email address for example: test123@test.com',
'default' => '',
'placeholder' => 'example: test123@test.com or test123',
),
'order_complete_status' => array(
'title' => __( 'Payment complete status' ),
'type' => 'select',
'desc_tip' => 'Select the order status you wish to be assigned after a sucessful transaction',
'default' => 'processing',
'options' => array(
'processing' => 'Processing',
'on-hold' => 'On hold',
'pending' => 'Pending payment',
'completed' => 'Completed',
'cancelled' => 'Cancelled',
'failed' => 'Failed',
),
),
'order_onhold_status' => array(
'title' => __( 'Payment and order mismatch status' ),
'type' => 'select',
'desc_tip' => 'Select the order status you wish to be assigned for a transaction and order mismatch',
'default' => 'on-hold',
'options' => array(
'on-hold' => 'On hold',
'processing' => 'Processing',
'pending' => 'Pending payment',
'completed' => 'Completed',
'cancelled' => 'Cancelled',
'failed' => 'Failed',
),
),
'order_failed_status' => array(
'title' => __( 'Payment declined status' ),
'type' => 'select',
'desc_tip' => 'Select the order status you wish to be assigned after a payment declines',
'default' => 'pending',
'options' => array(
'pending' => 'Pending payment',
'processing' => 'Processing',
'on-hold' => 'On hold',
'completed' => 'Completed',
'cancelled' => 'Cancelled',
'failed' => 'Failed',
),
),
'hide_billing_details' => array(
'title' => __( 'Hide Billing Details', 'woocommerce' ),
'type' => 'checkbox',
'label' => __( 'Hide Customer Billing Details', 'woocommerce' ),
'desc_tip' => __( 'Hide the customer\'s billing details so they cannot be changed when the customer is sent to Nochex.', 'woocommerce' ),
'default' => 'no',
), 
'test_mode' => array(
'title' => __( 'Nochex Test Mode', 'woocommerce' ),
'type' => 'checkbox',
'label' => __( 'Enable Nochex Test Mode', 'woocommerce' ),
'desc_tip' => __( 'Enable this feature to allow test transactions. Note: Ensure this option is disabled to accept live payments', 'woocommerce' ),
'description' => __( '<font style="font-weight:bold; color:red;">Note: To accept live transactions disable this option.</font>', 'woocommerce' ),
'default' => 'no',
), 
'xmlitemcollection' => array(
'title' => __( 'Detailed Product Information', 'woocommerce' ),
'type' => 'checkbox',
'label' => __( 'Enable Detailed Product Information', 'woocommerce' ),
'desc_tip' => __( 'If Detailed Product Information is selected, a detailed product and structured list will display on your Nochex Payment Page.', 'woocommerce' ),
'default' => 'no',
), 
'showPostage' => array(
'title' => __( 'Nochex Show Postage', 'woocommerce' ),
'type' => 'checkbox',
'label' => __( 'Enable Show Postage', 'woocommerce' ),
'desc_tip' => __( 'If ShowPosting is selected, postage will be displayed on your Nochex payment page.', 'woocommerce' ),
'default' => 'no',
), 
'debug' => array(
'title' => __( 'Debug Log', 'woocommerce' ),
'type' => 'checkbox',
'label' => __( 'Enable logging', 'woocommerce' ),
'default' => 'no',
'desc_tip' => sprintf( __( 'Log Nochex actions, such as APC requests, can be found inside: <code>woocommerce/logs/nochex.txt</code>', 'woocommerce' ), sanitize_file_name( wp_hash( 'nochex' ) ) ),
)
);
