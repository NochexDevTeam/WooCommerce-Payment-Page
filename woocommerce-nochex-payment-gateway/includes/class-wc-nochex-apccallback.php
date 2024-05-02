<?php
/**
 * Nochex Gateway - APC and Callback Script 
 * Update orders and send email confirmations.
*/

defined( 'ABSPATH' ) || exit;

if ($_POST['order_id']) {
	$order_id = sanitize_text_field($_POST['order_id']);
	$order_id = esc_html($order_id);
	$transaction_id = sanitize_text_field($_POST['transaction_id']);
	$transaction_id = esc_html($transaction_id);
	$transaction_date = sanitize_text_field($_POST['transaction_date']);
	$transaction_date = esc_html($transaction_date);
	if ( !empty($_POST['optional_2']) and $_POST['optional_2'] == "Enabled") {
	
	$this->debug_log("Callback ----------"); 
	
		$transaction_amount = sanitize_text_field($_POST['amount']);
		$transaction_amount = esc_html($transaction_amount);
		$callback_transaction_status = sanitize_text_field($_POST['transaction_status']);
		$callback_transaction_status = esc_html($callback_transaction_status);
		$callback_transaction_to = sanitize_text_field($_POST['merchant_id']);
		$callback_transaction_to = esc_html($callback_transaction_to);
		$callback_transaction_from = sanitize_text_field($_POST['email_address']);
		$callback_transaction_from = esc_html($callback_transaction_from);
		$order = new WC_Order($order_id);
		
		if ($order->get_status() != $this->order_complete_status){
		
		if ( $order->get_total() != $transaction_amount ) {
			// Put this order on-hold for manual checking
			$order->update_status( $this->settings['order_onhold_status'], sprintf( __( 'Validation error: Nochex amounts do not match (total %s).', 'woocommerce' ), $transaction_amount ) );
			return;
		}
		$postvars = http_build_query($_POST);
		$nochex_apc_url = "https://secure.nochex.com/callback/callback.aspx";
		$params = array(
			'body' => $postvars,
			'sslverify' => true,
			'Content-Type'=> 'application/x-www-form-urlencoded',
			'Content-Length'=> strlen($postvars),
			'Host'=> 'www.nochex.com',
			'user-agent'=> 'WooCommerce/' . $woocommerce->version
		);
		// Post back to get a response
		$output = wp_remote_retrieve_body(wp_remote_post($nochex_apc_url, $params));
		// Debug - Features
		$FormFields = 'Order Details: - APC Output: ' . $output;
		$this->debug_log($FormFields);
		$apcFieldsReturn = 'APC Fields: to_email: ' . $callback_transaction_to . ', from_email: ' .$callback_transaction_from.', transaction_id: ' . $transaction_id .', transaction_date: '.$transaction_date . ', order_id: ' .$order_id . ', amount: ' .$transaction_amount . ', status: ' . $callback_transaction_status;
		//Output Actions
		if ($callback_transaction_status == "100") {
			$status = " TEST";
		} else {
			$status = " LIVE";
		}
		if( $output == 'AUTHORISED' ) {
			// Notes for an Order - Output status (AUTHORISED / DECLINED), and Transaction Status (Test / Live)
			$callbackNotes = "<ul style=\"list-style:none;\">";
			$callbackNotes .= "<li>Transaction Status: " . $status . "</li>";
			$callbackNotes .= "<li>Transaction ID: ".$transaction_id . "</li>";
			$callbackNotes .= "<li>Total Paid: ".$transaction_amount. "</li></ul>";
			$order->add_order_note( $callbackNotes);
			// APC Debug, Output and fields
			$apcRequestPass =  'Callback Passed, Response: ' . $output . ', ' . $apcFieldsReturn;
			$FormFields = 'Order Details: - CALLBACK AUTHORISED: ' . $apcRequestPass . ", Order Note 1: Nochex CALLBACK Passed, Response: " . $output . ", Order Note 2: Nochex Payment Status:" . $status;
			$this->debug_log($FormFields);
			$order->payment_complete();
			$woocommerce->cart->empty_cart();
		} else {
			//Output Action - Declined
			$apcRequestFail =  'Callback Failed, Response: ' . $output . ', ' . $apcFieldsReturn;
			// Notes for an Order - Output status (AUTHORISED / DECLINED), and Transaction Status (Test / Live)
			$callbackNotes = "<ul style=\"list-style:none;\">";
			$callbackNotes .= "<li>Transaction Status: " . $status . "</li>";
			$callbackNotes .= "<li>Transaction ID: ". $transaction_id . "</li>";
			$callbackNotes .= "<li>Total Paid: ". $transaction_amount . "</li></ul>";
			$order->add_order_note( $callbackNotes);
			// APC Debug, Output and fields
			$FormFields = 'Order Details: - CALLBACK AUTHORISED: ' . $apcRequestFail . ", Order Note 1: Nochex CALLBACK Passed, Response: " . $output . ", Order Note 2: Nochex Payment Status:" . $status;
			$this->debug_log($FormFields);
		}
		exit;
		
		}
		} else {
	
	$this->debug_log("APC ----------"); 
			$transaction_amount = sanitize_text_field($_POST['amount']);
			$transaction_amount = esc_html($transaction_amount);
			$apc_transaction_status = sanitize_text_field($_POST['status']);
			$apc_transaction_status = esc_html($apc_transaction_status);
			$apc_transaction_to = sanitize_text_field($_POST['to_email']);
			$apc_transaction_to = esc_html($apc_transaction_to);
			$apc_transaction_from = sanitize_text_field($_POST['from_email']);
			$apc_transaction_from = esc_html($apc_transaction_from);
			$order = new WC_Order ( $order_id );
			
			if ($order->get_status() != $this->settings['order_complete_status']){
			
			if ( $order->get_total() != $transaction_amount ) {
				// Put this order on-hold for manual checking
				$order->update_status( $this->settings['order_onhold_status'], sprintf( __( 'Validation error: Nochex amounts do not match (total %s).', 'woocommerce' ), $transaction_amount ) );
				return;
			}
			$postvars = http_build_query($_POST);
			$nochex_apc_url = "https://secure.nochex.com/apc/apc.aspx";
			
			$params = array(
				'body' => $postvars,
				'sslverify' => true,
				'Content-Type'=> 'application/x-www-form-urlencoded',
				'Content-Length'=> strlen($postvars),
				'Host'=> 'secure.nochex.com',
				'user-agent'=> 'WooCommerce/' . $woocommerce->version
			);
			// Post back to get a response
			$output = wp_remote_retrieve_body(wp_remote_post($nochex_apc_url, $params));
			// Debug - Features
			$FormFields = 'Order Details: - APC Output: ' . $output;
			$this->debug_log($FormFields);
			$apcFieldsReturn = 'APC Fields: to_email: ' . $apc_transaction_to . ', from_email: ' .$apc_transaction_from .', transaction_id: ' . $transaction_id .', transaction_date: '.$transaction_date. ', order_id: ' .$order_id . ', amount: ' .$transaction_amount. ', status: ' . $apc_transaction_status;
			
			//Output Actions
			if( strstr($output, 'AUTHORISED') !== false ) {
				//Output Action - AUTHORISED 
				// Notes for an Order - Output status (AUTHORISED / DECLINED), and Transaction Status (Test / Live)
				$order->add_order_note( sprintf( __('Nochex APC Passed, Response: %s', 'wc_nochex' ), $output ) );
				$order->add_order_note( sprintf( __('Nochex Payment Status: %s', 'wc_nochex' ), $apc_transaction_status ) );
				// APC Debug, Output and fields
				$apcRequestPass =  'APC Passed, Response: ' . $output . ', ' . $apcFieldsReturn;
				$FormFields = 'Order Details: - APC AUTHORISED: ' . $apcRequestPass . ", Order Note 1: Nochex APC Passed, Response: " . $output . ", Order Note 2: Nochex Payment Status:" . $apc_transaction_status;
				$this->debug_log($FormFields);
				$order->payment_complete();
				$woocommerce->cart->empty_cart();
			} else {
				//Output Action - Declined
				$apcRequestFail =  'APC Failed, Response: ' . $output . ', ' . $apcFieldsReturn;
				// Notes for an Order - Output status (AUTHORISED / DECLINED), and Transaction Status (Test / Live)
				$order->add_order_note( sprintf( __('Nochex APC Failed, Response: %s', 'wc_nochex' ), $output ) );
				$order->add_order_note( sprintf( __('Nochex Payment Status: %s', 'wc_nochex' ), $apc_transaction_status ) );
				// APC Debug, Output and fields
				$FormFields = 'Order Details: - APC AUTHORISED: ' . $apcRequestFail . ", Order Note 1: Nochex APC Passed, Response: " . $output . ", Order Note 2: Nochex Payment Status:" . $apc_transaction_status;
				$this->debug_log($FormFields);
			}
		exit;
		
		}
	}
	
} else wp_die( "Nochex APC Page - Request Failed" );
