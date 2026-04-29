<?php

use Automattic\WooCommerce\Utilities\NumberUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates requests to send to Nochex.
 */
 
class Nochex_Payment_Gateway_For_Woocommerce_Request {

	protected $gateway;		
		
	public function __construct( $gateway ) {
		$this->gateway    = $gateway; 
	}
	
	public function get_ncxurl( $order, $sandbox, $settings, $successURL ) {
	
	$secure_url    = 'https://secure.nochex.com?';
		
	if ($settings['showPostage'] == 'yes') {
		
		if (!empty($order->get_total_shipping())){
		 
		$amountPostage = $order->get_total_shipping() + $order->get_shipping_tax();
		
		}else {
		
		$amountPostage = 0;
		
		}
		
		$amountPostageTotal = number_format( $amountPostage, 2, '.', '' );			
		
		$amountTotal = number_format( $order->get_total() - $amountPostageTotal, 2, '.', '' );
		
		if ($amountTotal == 0){	
			$amountTotal = $amountPostageTotal;
			$amountPostageTotal= number_format( 0, 2, '.', '' );	
		}
	} else {
		$amountTotal = number_format( $order->get_total(), 2, '.', '' );
		$amountPostageTotal= number_format( 0, 2, '.', '' );
	}


	$billing_first_name = sanitize_text_field($order->get_billing_first_name());
	$billing_last_name = sanitize_text_field($order->get_billing_last_name());
	$billing_address_line_1 = sanitize_text_field($order->get_billing_address_1());
	$billing_address_line_2 = sanitize_text_field($order->get_billing_address_2());
	$billing_city = sanitize_text_field($order->get_billing_city());
	$billing_country = sanitize_text_field($order->get_billing_country());
	$billing_postcode = sanitize_text_field($order->get_billing_postcode());
	$shipping_first_name = sanitize_text_field($order->get_shipping_first_name());
	$shipping_last_name = sanitize_text_field($order->get_shipping_last_name());
	$shipping_address_line_1 = sanitize_text_field($order->get_shipping_address_1());
	$shipping_address_line_2 = sanitize_text_field($order->get_shipping_address_2());
	$shipping_city = sanitize_text_field($order->get_shipping_city());
	$shipping_country = sanitize_text_field($order->get_shipping_country());
	$shipping_postcode = sanitize_text_field($order->get_shipping_postcode());
	$contact_number = sanitize_text_field($order->get_billing_phone());
	$email_address = sanitize_email($order->get_billing_email());

		$item_loop = 0;
		$description = '';

		if ( sizeof( $order->get_items() ) > 0 ) {
			foreach ( $order->get_items() as $item ) {
				if ( $item['qty'] ) {
				$item_loop++;
				$item_name = $item['name'];
				$filterName = filter_var($item['name'], FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW);
				$filterName = str_replace('|', ',', $filterName);
				if ($order->get_prices_include_tax() == 1) {
					$taxing = $order->get_item_total( $item, true );
				} else {
					$taxing = $order->get_line_tax( $item, false) + $order->get_item_total( $item, false );
				}
				/* Description */
				$description .= $filterName .", qty ordered " . $item['qty'] . " x " . number_format($taxing, 2, '.', '' )  . ", ";
				}
			}
		}

		$ncx_args['merchant_id'] = esc_html($settings['merchant_id']);
		
		$ncx_args['amount'] = $amountTotal;
		$ncx_args['postage'] = $amountPostageTotal;
		
		$ncx_args['test_transaction'] = $sandbox;
		
		$ncx_args['order_id'] = $order->get_order_number();

		$ncx_args['billing_fullname'] = $billing_first_name . " " . $billing_last_name;
		$ncx_args['billing_address'] = $billing_address_line_1 . " " . $billing_address_line_2;
		$ncx_args['billing_city'] = $billing_city;
		$ncx_args['billing_country'] = $billing_country;
		$ncx_args['billing_postcode'] = $billing_postcode;

		$ncx_args['delivery_fullname'] = $shipping_first_name . " ". $shipping_last_name;
		$ncx_args['delivery_address'] = $shipping_address_line_1 . " " .$shipping_address_line_2;
		$ncx_args['delivery_city'] = $shipping_city;
		$ncx_args['delivery_country'] = $shipping_country;
		$ncx_args['delivery_postcode'] = $shipping_postcode;

		$ncx_args['customer_phone_number'] = $contact_number;
		$ncx_args['email_address'] = $email_address;
		
		$ncx_args['optional_2'] = "Enabled";
		
		$ncx_args['cancel_url'] = esc_url($order->get_cancel_order_url());
		$ncx_args['callback_url'] = esc_url(add_query_arg( 'wc-api', 'nochex_payment_gateway_for_woocommerce', home_url( '/' ) ));
		$ncx_args['success_url'] = $successURL;
		$ncx_args['test_success_url'] = $successURL;	
	
		$ncx_args['description'] = $description;
		
		return $secure_url . http_build_query( $ncx_args, '', '&' );
	}



}