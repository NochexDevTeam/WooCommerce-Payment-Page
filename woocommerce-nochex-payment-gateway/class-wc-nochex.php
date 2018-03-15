<?php
/*
Plugin Name: Nochex Payment Gateway for Woocommerce
Description: Accept Nochex Payments, orders are updated using APC.
Version: 2.2
Author: Nochex Ltd
License: GPL2
*/

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

add_action('plugins_loaded', 'woocommerce_nochex_init', 0);
function woocommerce_nochex_init() {

class wc_nochex extends WC_Payment_Gateway {

    public function __construct() { 
	global $woocommerce;
	
		$this->id				= 'nochex';
		$this->icon 			=  WP_PLUGIN_URL . "/" . plugin_basename( dirname(__FILE__)) . '/images/clear-amex-mp.png';
		$this->has_fields 		= false;
		$this->method_title     = __( 'Nochex', 'woocommerce' );
		// Load the form fields.
		$this->init_form_fields();		
		// Load the settings.
		$this->init_settings();		
		
		if($this->settings['hide_billing_details'] == "Yes" || $this->settings['hide_billing_details'] == "yes"){		
		$billingNote = "<p style=\"font-weight:bold;margin-bottom:10px!important\">".$this->settings['description']."</p><p style=\"font-weight:bold;color:red;\">Please check your billing address details match the details on your card that you are going to use.</p>"; 
		}else{
		$billingNote = $this->settings['description'];      
		}
		
		// Define user set variables
		$this->title 			         = $this->settings['title'];
		$this->description               = $billingNote;
		$this->merchant_id               = $this->settings['merchant_id'];
		$this->hide_billing_details      = $this->settings['hide_billing_details'];
		$this->xmlitemcollection 	     = $this->settings['xmlitemcollection'];
		$this->showPostage		 	     = $this->settings['showPostage'];
		$this->test_mode                 = $this->settings['test_mode'];
		$this->debug			         = $this->settings['debug'];
		$this->callbackNew			     = $this->settings['callbackNew'];
		
		$this->callback_url = add_query_arg( 'wc-api', 'wc_nochex', home_url( '/' ) );
		
		// Actions
		// Update admin options
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    	
		// APC Handler
		add_action( 'woocommerce_api_wc_nochex', array( $this, 'apc' ) );

		// Success Page
		add_action('woocommerce_thankyou_nochex', array( $this, 'receipt_page'));

    } 


	public static function log( $debugMsg ) {
		if ( self::$log_enabled ) {
			if ( empty( self::$log ) ) {
				self::$log = new WC_Logger();
			}
			self::$log->add( 'Nochex', $debugMsg );
		}
	}

	/**
	* Debug Function
	* - Record sections of the Nochex module to check everything is working correctly.
	*/
		public function writeDebug($DebugData){
	// Calls the configuration information about a control in the module config. 
	//$nochex_debug = Configuration::get('NOCHEX_APC_DEBUG');
	// If the control nochex_debug has been checked in the module config, then it will use data sent and received in this function which will write to the nochex_debug file
		if ($this->debug == 'yes'){
		// Receives and stores the Date and Time
		$debug_TimeDate = date("m/d/Y h:i:s a", time());
		// Puts together, Date and Time, as well as information in regards to information that has been received.
		$stringData = "\n Time and Date: " . $debug_TimeDate . "... " . $DebugData ."... ";
		 // Try - Catch in case any errors occur when writing to nochex_debug file.
			try
			{
			// Variable with the name of the debug file.
				$debugging = "wp-content/plugins/woocommerce-nochex-payment-gateway/nochex_debug.txt";
			// variable which will open the nochex_debug file, or if it cannot open then an error message will be made.
				$f = fopen($debugging, 'a') or die("File can't open");
			// Open and write data to the nochex_debug file.
			$ret = fwrite($f, $stringData);
			// Incase there is no data being shown or written then an error will be produced.
			if ($ret === false)
			die("Fwrite failed");
			
				// Closes the open file.
				fclose($f)or die("File not close");
			} 
			//If a problem or something doesn't work, then the catch will produce an email which will send an error message.
			catch(Exception $e)
			{
			mail($this->email, "Debug Check Error Message", $e->getMessage());
			}
		}
	}
	
	/**
     * Initialise Gateway Settings Form Fields
     **/
    function init_form_fields() {
    
	$this->form_fields = array(
			'enabled' => array(
							'title' => __( 'Enable/Disable', 'woocommerce' ), 
							'type' => 'checkbox', 
							'label' => __( 'Enable Nochex', 'woocommerce' ), 
							'default' => 'yes'
						), 
			'title' => array(
							'title' => __( 'Title', 'woocommerce' ), 
							'type' => 'text', 
							'desc_tip' => __( 'Title of the Nochex payment option, visible by customers at the checkout.', 'woocommerce' ), 
							'default' => __( 'Nochex', 'woocommerce' )
						),
			'description' => array(
							'title' => __( 'Checkout Message', 'woocommerce' ), 
							'type' => 'textarea', 
							'desc_tip' => __( 'Message the customer will see after selecting Nochex as their payment option.', 'woocommerce' ), 
							'default' => __('Pay securely using Nochex. You can pay using your credit or debit card if you do not have a Nochex account.', 'woocommerce')
						),
			'merchant_id' => array(
							'title' => __( 'Nochex Merchant ID/Email', 'woocommerce' ), 
							'type' => 'text', 
							'desc_tip' => 'Your Nochex Merchant ID / Email address for example: test123@test.com', 
							'default' => ''
						),
			'hide_billing_details' => array(
							'title' => __( 'Hide Billing Details', 'woocommerce' ), 
							'type' => 'checkbox', 
							'label' => __( 'Hide Customer Billing Details', 'woocommerce' ), 
							'desc_tip' => __( 'Hide the customer\'s billing details so they cannot be changed when the customer is sent to Nochex.', 'woocommerce' ),
							'default' => 'no'
						), 
			'test_mode' => array(
							'title' => __( 'Nochex Test Mode', 'woocommerce' ), 
							'type' => 'checkbox', 
							'label' => __( 'Enable Nochex Test Mode', 'woocommerce' ), 
							'desc_tip' => __( 'If test mode is selected test transaction can be made.', 'woocommerce' ),
							'description' => __( 'Enable this feature to enable test transactions, <br/><font style="font-weight:bold; color:red;">Note: To accept live transactions disable this option.</font>', 'woocommerce' ),
							'default' => 'no'
						), 
			'xmlitemcollection' => array(
							'title' => __( 'Detailed Product Information', 'woocommerce' ), 
							'type' => 'checkbox', 
							'label' => __( 'Enable Detailed Product Information', 'woocommerce' ), 
							'desc_tip' => __( 'If Detailed Product Information is selected, a detailed product and structured list will display on your Nochex Payment Page.', 'woocommerce' ),
							'default' => 'no'
						), 
			'showPostage' => array(
							'title' => __( 'Nochex Show Postage', 'woocommerce' ), 
							'type' => 'checkbox', 
							'label' => __( 'Enable Show Postage', 'woocommerce' ), 
							'desc_tip' => __( 'If ShowPosting is selected, postage will be displayed on your Nochex payment page.', 'woocommerce' ),
							'default' => 'no'
						), 
			'debug' => array(
							'title' => __( 'Debug Log', 'woocommerce' ),
							'type' => 'checkbox',
							'label' => __( 'Enable logging', 'woocommerce' ),
							'default' => 'no',
							'desc_tip' => sprintf( __( 'Log Nochex actions, such as APC requests, can be found inside: <code>woocommerce/logs/nochex.txt</code>', 'woocommerce' ), sanitize_file_name( wp_hash( 'nochex' ) ) ),
						),
			'callbackNew' => array(
							'title' => __( 'Callback', 'woocommerce' ),
							'type' => 'checkbox',
							'label' => __( 'Enable Callback', 'woocommerce' ),
							'default' => 'no',
							'desc_tip' => sprintf( __( 'To use the callback functionality, please contact Nochex Support to enable this functionality on your merchant account otherwise this function wont work.', 'woocommerce' ), sanitize_file_name( wp_hash( 'nochex' ) ) ),
						)
			);
			
    
    }     	
	
	/**
	 * Admin Panel Options 
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 */
	public function admin_options() {
    	?>
    	<h3><?php _e('Nochex', 'woocommerce'); ?></h3>
    	<p><?php _e('After selecting Nochex customers will be sent to Nochex to enter their payment information.', 'woocommerce'); ?></p>
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
				
		if ($_REQUEST["finished"] == "1") {
		echo '<h4 style="color:darkgreen;">'.__('Your order has been paid!', 'woocommerce').'</h4>';
		}else{
			echo "<style> .payment_method_nochex img{max-height: 80px;height: 80px;margin-left: 50px!important;} </style>";
			echo '<p>'.__('If you are not transferred to Nochex click the button below.', 'woocommerce').'</p>';
			echo $this->generate_nochex_form($order);
		}
		
	}
	
	/**
	* Generated Nochex Payment Form 
	*/
	private function generate_nochex_form( $order_id ) {	
	
	global $woocommerce;		
	$orders = new WC_Order( $order_id );	
	/*$order_id = $order->id;*/
	
	/* Nochex Features - check to see if they are present, and updates the value on the payment form*/
	if ($this->callbackNew == 'yes'){			
		$optional_2 = "Enabled";
	}else{
		$optional_2 = "Disabled";
	}
		
	if ($this->hide_billing_details == 'yes'){
		$hide_billing_details = 'true';			
	}else{
		$hide_billing_details = 'false';	
	}
	
	/* Test Mode */	
	if ($this->test_mode == 'yes'){
		$testTransaction = '100';		
	}else{
		$testTransaction = '0';
	}
	
	/* Show Postage */		
	if ($this->showPostage == 'yes'){					
		$amountPostageTotal = number_format( $orders->get_total_shipping() + $orders->get_shipping_tax(), 2, '.', '' );				
		$amountTotal = number_format( $orders->get_total() - $amountPostageTotal, 2, '.', '' );				
	}else{				
		$amountTotal = number_format( $orders->get_total(), 2, '.', '' );
		$amountPostageTotal= number_format( 0, 2, '.', '' );		
	}


	// Debug - Features
	$featItems = 'Order Details: - Hide Billing Details Feature: ' . $this->hide_billing_details . '. \n Test Mode Feature: ' . $this->test_mode. '.\n Show Postage Feature - ' . $this->showPostage . ", XML Collection Feature: " . $this->xmlitemcollection;
	$this->writeDebug($featItems); 
	
	$item_loop = 0;		
	$description = '';		
	
	$item_collect = '<items>';		
	if ( sizeof( $orders->get_items() ) > 0 ) {			
	foreach ( $orders->get_items() as $item ) {				
	
	if ( $item['qty'] ) {						
	
	$item_loop++;						
	$product = $orders->get_product_from_item( $item );						
	$item_name 	= $item['name'];						
	$item_meta = new WC_Order_Item_Product( $item['item_meta'] );						
		
	$filterName = filter_var($item['name'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW);
	$filterName = str_replace('|', ',', $filterName);	
	$taxing = $orders->get_line_tax( $item, false) + $orders->get_item_total( $item, false );
	
	/* Description */
	$product = $orders->get_product_from_item( $item );			
	$description .= $filterName .", qty ordered " . $item['qty'] . " x " . number_format($taxing, 2, '.', '' )  . ", ";
 
	/* XML Collection */
	$item_collect.= "<item><id></id><name>". $filterName . "</name><description>". $filterName . "</description><quantity>" . $item['qty'] . "</quantity><price>" . number_format($taxing, 2, '.', '' ) . "</price></item>";				
	}			
	}		
	}		
	
	$item_collect .= '</items>';
	
	if ($this->xmlitemcollection == 'yes'){
		$description = "Order created: " . $order_id;
	}else{
		$item_collect = "";
	}	
	
// Debug - Features
$descriptionItems = 'Order Details: - Description: ' . $description . '. \n XML Item Collection: ' . $item_collect;
$this->writeDebug($descriptionItems);	
	
/*Nochex Payment Form - Fields & Values*/
	$displayForm = '<style>#loader{display:none!important}</style><div style="background:white;position:fixed;width:100%;height:100%;z-index:1;top:0px;left:0px;" id="ncxBackgroundForm"></div>
	<div id="ncxForm" style="z-index: 10;position: fixed;top: 300px;text-align:center;">
	<i style="font-size: 60px;margin: 25px;" class="fa fa-spinner fa-spin"></i>
	<form action="https://secure.nochex.com/default.aspx" method="post" id="nochex_payment_form">				
	<input type="hidden" name="merchant_id" value="'.$this->merchant_id.'" />				
	<input type="hidden" name="amount" value="'.$amountTotal.'" />				
	<input type="hidden" name="Postage" value="'.$amountPostageTotal.'" />				
	<input type="hidden" name="xml_item_collection" value="'.$item_collect .'" />				
	<input type="hidden" name="description" value="'.$description .'" />				
	<input type="hidden" name="order_id" value="'.$order_id.'" />							
	<input type="hidden" name="optional_1" value="'.serialize( array( $order_id, $orders->get_order_key() ) ).'" />							
	<input type="hidden" name="optional_2" value="'.$optional_2.'" />							
	<input type="hidden" name="billing_fullname" value="'.$orders->get_billing_first_name().' '.$orders->get_billing_last_name().'" />				
	<input type="hidden" name="billing_address" value="'.$orders->get_billing_address_1().' '.$orders->get_billing_address_2().'" />				
	<input type="hidden" name="billing_city" value="'.$orders->get_billing_city().'" />
	<input type="hidden" name="billing_country" value="'.$orders->get_billing_country().'" />					
	<input type="hidden" name="billing_postcode" value="'.$orders->get_billing_postcode().'" />				
	<input type="hidden" name="delivery_fullname" value="'.$orders->get_shipping_first_name().' '.$orders->get_shipping_last_name().'" />				
	<input type="hidden" name="delivery_address" value="'.$orders->get_shipping_address_1().' '.$orders->get_shipping_address_2().'" />				
	<input type="hidden" name="delivery_city" value="'.$orders->get_shipping_city().'" />			
	<input type="hidden" name="delivery_country" value="'.$orders->get_shipping_country().'" />			
	<input type="hidden" name="delivery_postcode" value="'.$orders->get_shipping_postcode().'" />				
	<input type="hidden" name="email_address" value="'.$orders->get_billing_email().'" />				
	<input type="hidden" name="customer_phone_number" value="'.$orders->get_billing_phone().'" />				
	<input type="hidden" name="success_url" value="'.$this->get_return_url( $orders ).'&finished=1" />				
	<input type="hidden" name="hide_billing_details" value="'.$hide_billing_details.'" />				
	<input type="hidden" name="callback_url" value="'.$this->callback_url.'" />				
	<input type="hidden" name="cancel_url" value="'.$orders->get_cancel_order_url().'" />				
	<input type="hidden" name="test_success_url" value="'.$this->get_return_url( $orders ).'&finished=1" />				
	<input type="hidden" name="test_transaction" value="'.$testTransaction.'" />
	<p>If you are not transferred to Nochex shortly,<br/>Press the button below;</p>				
	<input type="submit" style="background-color:#08c;color:#fff;" class="button-alt" id="submit_nochex_payment_form" value="'.__('Pay via Nochex', 'woocommerce').'" /> 				
	</form> 			
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
	<script type="text/javascript">			
	$(document).ready(function(){
     $("#nochex_payment_form").submit();
	});			
	</script></div>';		
		
	// Debug - Features
	$FormFields = 'Order Details: - Display Form: ' . print_r($displayForm, true);
	$this->writeDebug($FormFields);	

	return $displayForm;	
	}

    /**
    * Process the payment and return the result
    **/
    function process_payment( $order_id ) {
    	global $woocommerce;
    	
		$order = new WC_Order( $order_id );
		
		return array(					
		'result' 	=> 'success',					
		'redirect'	=> $this->get_return_url( $order ) . "&finished=0"			
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

		if(isset($_POST['order_id'])){
		
		if(isset($_POST['optional_2']) == "Enabled"){
			
				$order = new WC_Order ( $_POST['order_id'] );
			
			if ( $order->get_total() != $_POST['amount'] ) {
	
				// Put this order on-hold for manual checking
				$order->update_status( 'on-hold', sprintf( __( 'Validation error: Nochex amounts do not match (total %s).', 'woocommerce' ), $_POST['amount'] ) );

				return;
			}
			
			$postvars = http_build_query($_POST);
			
			$nochex_apc_url = "https://secure.nochex.com/callback/callback.aspx";
			
			$params = array(
        	'body' 			=> $postvars,
        	'sslverify' 	=> true,
			'Content-Type'	=> 'application/x-www-form-urlencoded',
			'Content-Length'	=> strlen($postvars),
			'Host'	=> 'www.nochex.com',
        	'user-agent'	=> 'WooCommerce/' . $woocommerce->version
			);
			
			// Post back to get a response
			$output = wp_remote_retrieve_body(wp_remote_post($nochex_apc_url, $params));
			
				// Debug - Features
				$FormFields = 'Order Details: - APC Output: ' . $output;
				$this->writeDebug($FormFields);	
				
				$apcFieldsReturn = 'APC Fields: to_email: ' . $_POST['merchant_id'] . ', from_email: ' .$_POST['email_address'] .', transaction_id: ' . $_POST['transaction_id'] .', transaction_date: '.$_POST['transaction_date'] . ', order_id: ' .$_POST['order_id'] . ', amount: ' .$_POST['amount'] . ', status: ' . $_POST['transaction_status'];
			//Output Actions
			
			if ($_POST['transaction_status'] == "100"){
			$status = " TEST";
			}else{
			$status = " LIVE";
			}
			
			if( $output == 'AUTHORISED' ) {
			
				// Notes for an Order - Output status (AUTHORISED / DECLINED), and Transaction Status (Test / Live)
			
			$callbackNotes = "<ul style=\"list-style:none;\"><li>Callback: " . $output . "</li>";			
			$callbackNotes .= "<li>Transaction Status: " . $status . "</li>";			
			$callbackNotes .= "<li>Transaction ID: ".$_POST["transaction_id"] . "</li>";
			$callbackNotes .= "<li>Payment Received From: ".$_POST["email_address"] . "</li>";			
			$callbackNotes .= "<li>Total Paid: ".$_POST["gross_amount"] . "</li></ul>";	
	
				$order->add_order_note( $callbackNotes, $status);
				
				// APC Debug, Output and fields
				$apcRequestPass =  'Callback Passed, Response: ' . $output . ', ' . $apcFieldsReturn;
				$FormFields = 'Order Details: - CALLBACK AUTHORISED: ' . $apcRequestPass . ", Order Note 1: Nochex CALLBACK Passed, Response: " . $output . ", Order Note 2: Nochex Payment Status:" . $status;
				$this->writeDebug($FormFields);	
				
				$order->payment_complete();
				$woocommerce->cart->empty_cart();
				
			} else {
				
				//Output Action - Declined
				$apcRequestFail =  'Callback Failed, Response: ' . $output . ', ' . $apcFieldsReturn;
			
				// Notes for an Order - Output status (AUTHORISED / DECLINED), and Transaction Status (Test / Live)
					
			$callbackNotes = "<ul style=\"list-style:none;\"><li>Callback: " . $output . "</li>";			
			$callbackNotes .= "<li>Transaction Status: " . $status . "</li>";			
			$callbackNotes .= "<li>Transaction ID: ".$_POST["transaction_id"] . "</li>";
			$callbackNotes .= "<li>Payment Received From: ".$_POST["email_address"] . "</li>";			
			$callbackNotes .= "<li>Total Paid: ".$_POST["gross_amount"] . "</li></ul>";	
				
				//$callbackNotes = "Callback:".$output.",<br/> Transaction Status:".$status.", Transaction ID:".$_POST['transaction_id'].", Payment Received from:".$_POST['email_address'].", Total Amount Paid:".$_POST['gross_amount'].".";
				
				$order->add_order_note( $callbackNotes, $status );
				
				
				// APC Debug, Output and fields
				$FormFields = 'Order Details: - CALLBACK AUTHORISED: ' . $apcRequestFail . ", Order Note 1: Nochex CALLBACK Passed, Response: " . $output . ", Order Note 2: Nochex Payment Status:" . $status;
				$this->writeDebug($FormFields);	
				
			}
			
		}else{
		
		$order = new WC_Order ( $_POST['order_id'] );
			
			if ( $order->get_total() != $_POST['amount'] ) {
	
				// Put this order on-hold for manual checking
				$order->update_status( 'on-hold', sprintf( __( 'Validation error: Nochex amounts do not match (total %s).', 'woocommerce' ), $_POST['amount'] ) );

				return;
			}
			
			$postvars = http_build_query($_POST);

			$nochex_apc_url = "https://www.nochex.com/apcnet/apc.aspx";
			
			$params = array(
        	'body' 			=> $postvars,
        	'sslverify' 	=> true,
			'Content-Type'	=> 'application/x-www-form-urlencoded',
			'Content-Length'	=> strlen($postvars),
			'Host'	=> 'www.nochex.com',
        	'user-agent'	=> 'WooCommerce/' . $woocommerce->version
			);
			
			// Post back to get a response
			$output = wp_remote_retrieve_body(wp_remote_post($nochex_apc_url, $params));
			
				// Debug - Features
				$FormFields = 'Order Details: - APC Output: ' . $output;
				$this->writeDebug($FormFields);	
				
				$apcFieldsReturn = 'APC Fields: to_email: ' . $_POST['to_email'] . ', from_email: ' .$_POST['from_email'] .', transaction_id: ' . $_POST['transaction_id'] .', transaction_date: '.$_POST['transaction_date'] . ', order_id: ' .$_POST['order_id'] . ', amount: ' .$_POST['amount'] . ', status: ' . $_POST['status'];
			//Output Actions
			if( $output == 'AUTHORISED' ) {
			
				//Output Action - AUTHORISED 
				
				// Notes for an Order - Output status (AUTHORISED / DECLINED), and Transaction Status (Test / Live)
				$order->add_order_note( sprintf( __('Nochex APC Passed, Response: %s', 'wc_nochex' ), $output ) );
				$order->add_order_note( sprintf( __('Nochex Payment Status: %s', 'wc_nochex' ), $_POST['status'] ) );
				
				// APC Debug, Output and fields
				$apcRequestPass =  'APC Passed, Response: ' . $output . ', ' . $apcFieldsReturn;
				$FormFields = 'Order Details: - APC AUTHORISED: ' . $apcRequestPass . ", Order Note 1: Nochex APC Passed, Response: " . $output . ", Order Note 2: Nochex Payment Status:" . $_POST['status'];
				$this->writeDebug($FormFields);	
				
				$order->payment_complete();
				$woocommerce->cart->empty_cart();
			} else {
				//Output Action - Declined
				$apcRequestFail =  'APC Failed, Response: ' . $output . ', ' . $apcFieldsReturn;
			
				// Notes for an Order - Output status (AUTHORISED / DECLINED), and Transaction Status (Test / Live)
				$order->add_order_note( sprintf( __('Nochex APC Failed, Response: %s', 'wc_nochex' ), $output ) );
				$order->add_order_note( sprintf( __('Nochex Payment Status: %s', 'wc_nochex' ), $_POST['status'] ) );
				
				// APC Debug, Output and fields
				$FormFields = 'Order Details: - APC AUTHORISED: ' . $apcRequestFail . ", Order Note 1: Nochex APC Passed, Response: " . $output . ", Order Note 2: Nochex Payment Status:" . $_POST['status'];
				$this->writeDebug($FormFields);	
				
			}
		
		}
		}else wp_die( "Nochex APC Request Failure" );

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
