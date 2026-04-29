<?php
/**
 * Nochex Payment Gateway for Woocommerce - APC and Callback Script.
 * Update orders and send email confirmations.
 */

defined( 'ABSPATH' ) || exit;

$order_id_raw = isset( $_POST['order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) : '';
if ( '' === $order_id_raw ) {
	wp_die( 'Nochex APC Page - Request Failed' );
}

$order_id = absint( $order_id_raw );
$order    = wc_get_order( $order_id );
if ( ! $order ) {
	wp_die( 'Nochex APC Page - Invalid order' );
}

$order_complete_status = ! empty( $this->settings['order_complete_status'] ) ? $this->settings['order_complete_status'] : 'processing';
$order_onhold_status   = ! empty( $this->settings['order_onhold_status'] ) ? $this->settings['order_onhold_status'] : 'on-hold';

$transaction_id   = isset( $_POST['transaction_id'] ) ? sanitize_text_field( wp_unslash( $_POST['transaction_id'] ) ) : '';
$transaction_date = isset( $_POST['transaction_date'] ) ? sanitize_text_field( wp_unslash( $_POST['transaction_date'] ) ) : '';
$is_callback      = isset( $_POST['optional_2'] ) && 'Enabled' === sanitize_text_field( wp_unslash( $_POST['optional_2'] ) );

if ( $order->get_status() === $order_complete_status ) {
	exit;
}

if ( $is_callback ) {
	$this->debug_log( 'Callback ----------' );

	$transaction_amount          = isset( $_POST['amount'] ) ? sanitize_text_field( wp_unslash( $_POST['amount'] ) ) : '';
	$callback_transaction_status = isset( $_POST['transaction_status'] ) ? sanitize_text_field( wp_unslash( $_POST['transaction_status'] ) ) : '';
	$callback_transaction_to     = isset( $_POST['merchant_id'] ) ? sanitize_text_field( wp_unslash( $_POST['merchant_id'] ) ) : '';
	$callback_transaction_from   = isset( $_POST['email_address'] ) ? sanitize_text_field( wp_unslash( $_POST['email_address'] ) ) : '';

	if ( (float) $order->get_total() !== (float) $transaction_amount ) {
		/* translators: %s: callback amount */
		$order->update_status( $order_onhold_status, sprintf( __( 'Validation error: Nochex amounts do not match (total %s)', 'nochex-payment-gateway-for-woocommerce' ), $transaction_amount ) );
		exit;
	}

	$response = wp_remote_post(
		'https://secure.nochex.com/callback/callback.aspx',
		array(
			'body'      => wp_unslash( $_POST ),
			'sslverify' => true,
			'timeout'   => 30,
		)
	);
	$output = is_wp_error( $response ) ? '' : (string) wp_remote_retrieve_body( $response );

	$apc_fields = 'APC Fields: to_email: ' . $callback_transaction_to . ', from_email: ' . $callback_transaction_from . ', transaction_id: ' . $transaction_id . ', transaction_date: ' . $transaction_date . ', order_id: ' . $order_id . ', amount: ' . $transaction_amount . ', status: ' . $callback_transaction_status;
	$this->debug_log( 'Order Details: - APC Output: ' . $output );

	$status_label = ( '100' === $callback_transaction_status ) ? 'TEST' : 'LIVE';
	$callback_note = '<ul style="list-style:none;"><li>Transaction Status: ' . esc_html( $status_label ) . '</li><li>Transaction ID: ' . esc_html( $transaction_id ) . '</li><li>Total Paid: ' . esc_html( $transaction_amount ) . '</li></ul>';
	$order->add_order_note( $callback_note );

	if ( 'AUTHORISED' === trim( $output ) ) {
		$this->debug_log( 'Order Details: - CALLBACK AUTHORISED: Callback Passed, Response: ' . $output . ', ' . $apc_fields );
		$order->payment_complete();
		if ( function_exists( 'WC' ) && WC()->cart ) {
			WC()->cart->empty_cart();
		}
		exit;
	}

	$this->debug_log( 'Order Details: - CALLBACK DECLINED: Callback Failed, Response: ' . $output . ', ' . $apc_fields );
	exit;
}

$this->debug_log( 'APC ----------' );
$transaction_amount    = isset( $_POST['amount'] ) ? sanitize_text_field( wp_unslash( $_POST['amount'] ) ) : '';
$apc_status            = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
$apc_to                = isset( $_POST['to_email'] ) ? sanitize_text_field( wp_unslash( $_POST['to_email'] ) ) : '';
$apc_from              = isset( $_POST['from_email'] ) ? sanitize_text_field( wp_unslash( $_POST['from_email'] ) ) : '';

if ( (float) $order->get_total() !== (float) $transaction_amount ) {
	/* translators: %s: APC amount */
	$order->update_status( $order_onhold_status, sprintf( __( 'Validation error: Nochex amounts do not match (total %s)', 'nochex-payment-gateway-for-woocommerce' ), $transaction_amount ) );
	exit;
}

$response = wp_remote_post(
	'https://secure.nochex.com/apc/apc.aspx',
	array(
		'body'      => wp_unslash( $_POST ),
		'sslverify' => true,
		'timeout'   => 30,
	)
);
$output = is_wp_error( $response ) ? '' : (string) wp_remote_retrieve_body( $response );

$apc_fields = 'APC Fields: to_email: ' . $apc_to . ', from_email: ' . $apc_from . ', transaction_id: ' . $transaction_id . ', transaction_date: ' . $transaction_date . ', order_id: ' . $order_id . ', amount: ' . $transaction_amount . ', status: ' . $apc_status;
$this->debug_log( 'Order Details: - APC Output: ' . $output );

if ( false !== strpos( (string) $output, 'AUTHORISED' ) ) {
	/* translators: %s: APC response */
	$order->add_order_note( sprintf( __( 'Nochex APC Passed, Response: %s', 'nochex-payment-gateway-for-woocommerce' ), $output ) );
	/* translators: %s: APC status */
	$order->add_order_note( sprintf( __( 'Nochex Payment Status: %s', 'nochex-payment-gateway-for-woocommerce' ), $apc_status ) );
	$this->debug_log( 'Order Details: - APC AUTHORISED: APC Passed, Response: ' . $output . ', ' . $apc_fields );
	$order->payment_complete();
	if ( function_exists( 'WC' ) && WC()->cart ) {
		WC()->cart->empty_cart();
	}
	exit;
}

/* translators: %s: APC response */
$order->add_order_note( sprintf( __( 'Nochex APC Failed, Response: %s', 'nochex-payment-gateway-for-woocommerce' ), $output ) );
/* translators: %s: APC status */
$order->add_order_note( sprintf( __( 'Nochex Payment Status: %s', 'nochex-payment-gateway-for-woocommerce' ), $apc_status ) );
$this->debug_log( 'Order Details: - APC DECLINED: APC Failed, Response: ' . $output . ', ' . $apc_fields );
exit;
