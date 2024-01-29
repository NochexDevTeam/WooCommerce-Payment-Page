<?php

/**
 * Nochex Payment Form - All the relevant data
*/

$orders = new WC_Order( $order );
$order_id = $order;
/* Nochex Features - check to see if they are present, and updates the value on the payment form */
$optional_2 = "Enabled";
if ($this->settings['hide_billing_details'] == 'yes') {
	$hide_billing_details = 'true';
} else {
	$hide_billing_details = 'false';
}
/* Test Mode */
if ($this->settings['test_mode'] == 'yes') {
	$testTransaction = '100';
} else {
	$testTransaction = '0';
}
/* Show Postage */
if ($this->settings['showPostage'] == 'yes') {
	$amountPostageTotal = number_format( $orders->get_total_shipping() + $orders->get_shipping_tax(), 2, '.', '' );
	$amountTotal = number_format( $orders->get_total() - $amountPostageTotal, 2, '.', '' );
	
	if ($amountTotal == 0){	
	    $amountTotal = $amountPostageTotal;
	    $amountPostageTotal= number_format( 0, 2, '.', '' );	
	}
} else {
	$amountTotal = number_format( $orders->get_total(), 2, '.', '' );
	$amountPostageTotal= number_format( 0, 2, '.', '' );
}
// Debug - Features
$featItems = 'Order Details: - Hide Billing Details Feature: ' . $this->settings['hide_billing_details'] . '. Test Mode Feature: ' . $this->settings['test_mode']. '. Show Postage Feature - ' . $this->settings['showPostage'] . ", XML Collection Feature: " . $this->settings['xmlitemcollection'];
$this->debug_log($featItems); 
$item_loop = 0;
$description = '';
$item_collect = '<items>';
if ( sizeof( $orders->get_items() ) > 0 ) {
	foreach ( $orders->get_items() as $item ) {
		if ( $item['qty'] ) {
		$item_loop++;
		$item_name = $item['name'];
		$item_meta = new WC_Order_Item_Product( $item['item_meta'] );
		$filterName = filter_var($item['name'], FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW);
		$filterName = str_replace('|', ',', $filterName);
		if ($orders->get_prices_include_tax() == 1) {
			$taxing = $orders->get_item_total( $item, true );
		} else {
			$taxing = $orders->get_line_tax( $item, false) + $orders->get_item_total( $item, false );
		}
		/* Description */
		$description .= $filterName .", qty ordered " . $item['qty'] . " x " . number_format($taxing, 2, '.', '' )  . ", ";
		/* XML Collection */
		$item_collect.= "<item><id></id><name>". $filterName . "</name><description>". $filterName . "</description><quantity>" . $item['qty'] . "</quantity><price>" . number_format($taxing, 2, '.', '' ) . "</price></item>";
		}
	}
}
$item_collect .= '</items>';
if ($this->settings['xmlitemcollection'] == 'yes') {
	$description = "Order for #" . $order_id;
} else {
	$item_collect = "";
}
// Debug - Features
$descriptionItems = 'Order Details: - Description: ' . $description . '. \n XML Item Collection: ' . $item_collect;
$this->debug_log($descriptionItems);

$orderInfo = 'Order ID: ' . $order_id . ', Order Total: ' . $amountTotal . ' (Amount:'.number_format( $orders->get_total() - ($orders->get_total_shipping() + $orders->get_shipping_tax()), 2, '.', '' ).') + (Postage:'.number_format( $orders->get_total_shipping() + $orders->get_shipping_tax(), 2, '.', '' ).'), Your Saved Merchant ID / Email Address:' .$this->settings['merchant_id'];
$this->debug_log($orderInfo);
/* Sanitize Input */
$billing_first_name = sanitize_text_field($orders->get_billing_first_name());
$billing_last_name = sanitize_text_field($orders->get_billing_last_name());
$billing_address_line_1 = sanitize_text_field($orders->get_billing_address_1());
$billing_address_line_2 = sanitize_text_field($orders->get_billing_address_2());
$billing_city = sanitize_text_field($orders->get_billing_city());
$billing_country = sanitize_text_field($orders->get_billing_country());
$billing_postcode = sanitize_text_field($orders->get_billing_postcode());
$shipping_first_name = sanitize_text_field($orders->get_shipping_first_name());
$shipping_last_name = sanitize_text_field($orders->get_shipping_last_name());
$shipping_address_line_1 = sanitize_text_field($orders->get_shipping_address_1());
$shipping_address_line_2 = sanitize_text_field($orders->get_shipping_address_2());
$shipping_city = sanitize_text_field($orders->get_shipping_city());
$shipping_country = sanitize_text_field($orders->get_shipping_country());
$shipping_postcode = sanitize_text_field($orders->get_shipping_postcode());
$contact_number = sanitize_text_field($orders->get_billing_phone());
$email_address = sanitize_email($orders->get_billing_email());
/* clean urls */
$cancel_url = $orders->get_cancel_order_url();
$success_url = $this->get_return_url( $orders );
$callback_url = add_query_arg( 'wc-api', 'wc_nochex', home_url( '/' ) );
$cancel_url_clean = esc_url( $cancel_url );
$success_url_clean = esc_url( $success_url );
$callback_url_clean = esc_url( $callback_url );
/* Nochex Payment Form - Fields & Values */
$nochexParams = array('Nochex_Settings' => Array(
			'Merchant_id' => esc_html($this->settings['merchant_id']),
			'test_transaction' => $testTransaction,
			'hide_billing_details' => $hide_billing_details,
			'xml_item_collection' => $item_collect,
			'postage' => $amountPostageTotal,
		),
		'Nochex_Urls' => Array(
			'test_success_url' => $success_url_clean,
			'success_url' => $success_url_clean,
			'cancel_url' => $cancel_url_clean,
			'callback_url' => $callback_url_clean,
		),
		'order_info' => Array(
			'order_id' => esc_html($order_id),
			'optional_1' => serialize( array( $order_id, $orders->get_order_key() ) ),
			'optional_2' => esc_html($optional_2),
			'amount' => $amountTotal,
			'description' => esc_html($description),
		),
		'customer_info' => Array(
			'customer_phone_number' => esc_html($contact_number),
			'email_address' => esc_html($email_address),
			'billing_fullname' => esc_html($billing_first_name).' '.esc_html($billing_last_name),
			'billing_address' => esc_html($billing_address_line_1).' '.esc_html($billing_address_line_2),
			'billing_city' => esc_html($billing_city),
			'billing_country' => esc_html($billing_country),
			'billing_postcode' => esc_html($billing_postcode),
			'delivery_fullname' => esc_html($shipping_first_name).' '.esc_html($shipping_last_name),
			'delivery_address' => esc_html($shipping_address_line_1).' '.esc_html($shipping_address_line_2),
			'delivery_city' => esc_html($shipping_city),
			'delivery_country' => esc_html($shipping_country),
			'delivery_postcode' => esc_html($shipping_postcode),
		),
		);