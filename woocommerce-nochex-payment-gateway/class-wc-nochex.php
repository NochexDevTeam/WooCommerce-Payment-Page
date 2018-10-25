<?php
/*
Plugin Name: Nochex Payment Gateway for Woocommerce
Plugin URI: https://github.com/NochexDevTeam/WooCommerce
Description: Accept Nochex Payments in Woocommerce.
Version: 2.3
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
		$this->icon 			=  plugins_url('images/clear-amex-mp.png', __FILE__ );
		$this->has_fields 		= false;
		$this->method_title     = __( 'Nochex', 'woocommerce' );	    
	        $this->method_description	= __( 'Nochex', 'Accept payments by Nochex');
	    
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
	// If the control nochex_debug has been checked in the module config, then it will use data sent and received in this function which will write to the nochex_debug file
		if ($this->debug == 'yes'){
		// Receives and stores the Date and Time
		$debug_TimeDate = date("m/d/Y h:i:s a", time());
		// Puts together, Date and Time, as well as information in regards to information that has been received.
		$stringData = "\n Time and Date: " . $debug_TimeDate . "... " . $DebugData ."... ";
		 // Try - Catch in case any errors occur when writing to nochex_debug file.
		try{
				// Variable with the name of the debug file.
				$debugging = plugin_dir_path( __FILE__ ) . "nochex_debug.txt"; 
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
				error_log($e);
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
		
		$checkSuccess = esc_html($_REQUEST["finished"]);		
		$successVal = sanitize_text_field($_REQUEST["finished"]);
		
		if ($successVal == "1") {
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
		$description = "Order for #" . $order_id;
	}else{
		$item_collect = "";
	}	
	
	// Debug - Features
	$descriptionItems = 'Order Details: - Description: ' . $description . '. \n XML Item Collection: ' . $item_collect;
	$this->writeDebug($descriptionItems);	
			
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
	$success_url = $this->get_return_url( $orders ) . "&finished=1";
	$callback_url = $this->callback_url;

	$cancel_url_clean = esc_url( $cancel_url );
	$success_url_clean = esc_url( $success_url );
	$callback_url_clean = esc_url( $callback_url );
			
	/* Nochex Payment Form - Fields & Values */
	$displayForm = '<style>#loader{display:none!important}</style>
	<div style="background:white;position:fixed;width:100%;height:100%;z-index:1000000;top:0px;left:0px;" id="ncxBackgroundForm"></div>
	<div id="ncxForm" style="z-index: 1000000;position: fixed;top: 300px;text-align:center;">
	<i style="font-size: 60px;margin: 25px;" class="fa fa-spinner fa-spin"></i>
	<form action="https://secure.nochex.com/default.aspx" method="post" id="nochex_payment_form">				
	<input type="hidden" name="merchant_id" value="'.esc_html($this->merchant_id).'" />				
	<input type="hidden" name="amount" value="'.$amountTotal.'" />				
	<input type="hidden" name="Postage" value="'.$amountPostageTotal.'" />				
	<input type="hidden" name="xml_item_collection" value="'.$item_collect .'" />				
	<input type="hidden" name="description" value="'.esc_html($description).'" />				
	<input type="hidden" name="order_id" value="'.esc_html($order_id).'" />							
	<input type="hidden" name="optional_1" value="'.serialize( array( $order_id, $orders->get_order_key() ) ).'" />							
	<input type="hidden" name="optional_2" value="'.$optional_2.'" />							
	<input type="hidden" name="billing_fullname" value="'.esc_html($billing_first_name).' '.esc_html($billing_last_name).'" />				
	<input type="hidden" name="billing_address" value="'.esc_html($billing_address_line_1).' '.esc_html($billing_address_line_2).'" />				
	<input type="hidden" name="billing_city" value="'.esc_html($billing_city).'" />
	<input type="hidden" name="billing_country" value="'.esc_html($billing_country).'" />					
	<input type="hidden" name="billing_postcode" value="'.esc_html($billing_postcode).'" />				
	<input type="hidden" name="delivery_fullname" value="'.esc_html($shipping_first_name).' '.esc_html($shipping_last_name).'" />				
	<input type="hidden" name="delivery_address" value="'.esc_html($shipping_address_line_1).' '.esc_html($shipping_address_line_2).'" />				
	<input type="hidden" name="delivery_city" value="'.esc_html($shipping_city).'" />			
	<input type="hidden" name="delivery_country" value="'.esc_html($shipping_country).'" />			
	<input type="hidden" name="delivery_postcode" value="'.esc_html($shipping_postcode).'" />				
	<input type="hidden" name="email_address" value="'.esc_html($email_address).'" />				
	<input type="hidden" name="customer_phone_number" value="'.esc_html($contact_number).'" />				
	<input type="hidden" name="success_url" value="'.$success_url_clean.'" />				
	<input type="hidden" name="hide_billing_details" value="'.$hide_billing_details.'" />				
	<input type="hidden" name="callback_url" value="'.$callback_url_clean.'" />				
	<input type="hidden" name="cancel_url" value="'.$cancel_url_clean.'" />				
	<input type="hidden" name="test_success_url" value="'.$success_url_clean.'" />				
	<input type="hidden" name="test_transaction" value="'.$testTransaction.'" />
	<p>If you are not transferred to Nochex shortly,<br/>Press the button below;</p>				
	<input type="submit" style="background-color:#08c;color:#fff;" class="button-alt" id="submit_nochex_payment_form" value="'.__('Pay via Nochex', 'woocommerce').'" /> 				
	</form> 			
	<script type="text/javascript">
		window.onload = function(){
				document.getElementById("nochex_payment_form").submit();
		}
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
		

		if (!empty(esc_html($_POST['order_id']))) {
		
		$order_id = sanitize_text_field($_POST['order_id']);
		$order_id = esc_html($order_id);
		$transaction_amount = sanitize_text_field($_POST['amount']);		
		$transaction_amount = esc_html($transaction_amount);		
		
		$transaction_id = sanitize_text_field($_POST['transaction_id']);
		$transaction_id = esc_html($transaction_id);
		$transaction_date = sanitize_text_field($_POST['transaction_date']);
		$transaction_date = esc_html($transaction_date);
		
		if(!empty($_POST['optional_2'])){
			$callback_enabled = sanitize_text_field($_POST['optional_2']);	
			$callback_enabled = esc_html($callback_enabled);	
		}else{
			$callback_enabled = "Disabled";
		} 
				
		if($callback_enabled == "Enabled"){
		
			$callback_transaction_status = sanitize_text_field($_POST['transaction_status']);
			$callback_transaction_status = esc_html($callback_transaction_status);
			$callback_transaction_to = sanitize_text_field($_POST['merchant_id']);
			$callback_transaction_to = esc_html($callback_transaction_to);
			$callback_transaction_from = sanitize_text_field($_POST['email_address']);
			$callback_transaction_from = esc_html($callback_transaction_from);
			
			$order = new WC_Order ($order_id);
			
			if ( $order->get_total() != $transaction_amount ) {
				// Put this order on-hold for manual checking
				$order->update_status( 'on-hold', sprintf( __( 'Validation error: Nochex amounts do not match (total %s).', 'woocommerce' ), $transaction_amount ) );
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
				
				$apcFieldsReturn = 'APC Fields: to_email: ' . $callback_transaction_to . ', from_email: ' .$callback_transaction_from.', transaction_id: ' . $transaction_id .', transaction_date: '.$transaction_date . ', 	order_id: ' .$order_id . ', amount: ' .$transaction_amount . ', status: ' . $callback_transaction_status;
			//Output Actions
			
			if ($callback_transaction_status == "100"){
				$status = " TEST";
			}else{
				$status = " LIVE";
			}
			
			if( $output == 'AUTHORISED' ) {
			
				// Notes for an Order - Output status (AUTHORISED / DECLINED), and Transaction Status (Test / Live)			
				$callbackNotes = "<ul style=\"list-style:none;\">";			
				$callbackNotes .= "<li>Transaction Status: " . $status . "</li>";			
				$callbackNotes .= "<li>Transaction ID: ".$transaction_id . "</li>";		
				$callbackNotes .= "<li>Total Paid: ".$transaction_amount. "</li></ul>";	
	
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
						
				$callbackNotes = "<ul style=\"list-style:none;\">";			
				$callbackNotes .= "<li>Transaction Status: " . $status . "</li>";			
				$callbackNotes .= "<li>Transaction ID: ". $transaction_id . "</li>";		
				$callbackNotes .= "<li>Total Paid: ". $transaction_amount . "</li></ul>";	
				
				$order->add_order_note( $callbackNotes, $status );
				
				// APC Debug, Output and fields
				$FormFields = 'Order Details: - CALLBACK AUTHORISED: ' . $apcRequestFail . ", Order Note 1: Nochex CALLBACK Passed, Response: " . $output . ", Order Note 2: Nochex Payment Status:" . $status;
				$this->writeDebug($FormFields);	
				
			}
			
		}else{
		
			$apc_transaction_status = sanitize_text_field($_POST['status']);
			$apc_transaction_status = esc_html($apc_transaction_status);
			$apc_transaction_to = sanitize_text_field($_POST['to_email']);
			$apc_transaction_to = esc_html($apc_transaction_to);
			$apc_transaction_from = sanitize_text_field($_POST['from_email']);
			$apc_transaction_from = esc_html($apc_transaction_from);
		
			$order = new WC_Order ( $order_id );
			
			if ( $order->get_total() != $transaction_amount ) {
	
				// Put this order on-hold for manual checking
				$order->update_status( 'on-hold', sprintf( __( 'Validation error: Nochex amounts do not match (total %s).', 'woocommerce' ), $transaction_amount ) );

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
				
				$apcFieldsReturn = 'APC Fields: to_email: ' . $apc_transaction_to . ', from_email: ' .$apc_transaction_from .', transaction_id: ' . $transaction_id .', transaction_date: '.$transaction_date. ', order_id: ' .$order_id . ', amount: ' .$transaction_amount. ', status: ' . $apc_transaction_status;
			//Output Actions
			if( $output == 'AUTHORISED' ) {
			
				//Output Action - AUTHORISED 
				
				// Notes for an Order - Output status (AUTHORISED / DECLINED), and Transaction Status (Test / Live)
				$order->add_order_note( sprintf( __('Nochex APC Passed, Response: %s', 'wc_nochex' ), $output ) );
				$order->add_order_note( sprintf( __('Nochex Payment Status: %s', 'wc_nochex' ), $apc_transaction_status ) );
				
				// APC Debug, Output and fields
				$apcRequestPass =  'APC Passed, Response: ' . $output . ', ' . $apcFieldsReturn;
				$FormFields = 'Order Details: - APC AUTHORISED: ' . $apcRequestPass . ", Order Note 1: Nochex APC Passed, Response: " . $output . ", Order Note 2: Nochex Payment Status:" . $apc_transaction_status;
				$this->writeDebug($FormFields);	
				
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
				$this->writeDebug($FormFields);	
				
			}
		
		}
			
}else wp_die( "Nochex APC Page - Request Failed" );

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
