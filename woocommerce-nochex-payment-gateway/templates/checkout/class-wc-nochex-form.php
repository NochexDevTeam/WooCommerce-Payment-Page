<?php
defined( 'ABSPATH' ) || exit;
?>
	<form action="https://secure.nochex.com/default.aspx" method="post" id="nochex_payment_form">
		<input type="hidden" name="merchant_id" value="<?php echo esc_html($nochexParams["Nochex_Settings"]["Merchant_id"]); ?>" />
		<input type="hidden" name="amount" value="<?php echo esc_html($nochexParams["order_info"]["amount"]); ?>" />
		<input type="hidden" name="Postage" value="<?php echo esc_html($nochexParams["Nochex_Settings"]["postage"]); ?>" />
		<input type="hidden" name="xml_item_collection" value="<?php echo esc_html($nochexParams["Nochex_Settings"]["xml_item_collection"]); ?>" />
		<input type="hidden" name="description" value="<?php echo esc_html($nochexParams["order_info"]["description"]); ?>" />
		<input type="hidden" name="order_id" value="<?php echo esc_html($nochexParams["order_info"]["order_id"]); ?>" />
		<input type="hidden" name="optional_1" value="<?php echo esc_html($nochexParams["order_info"]["optional_1"]); ?>" />
		<input type="hidden" name="optional_2" value="<?php echo esc_html($nochexParams["order_info"]["optional_2"]); ?>" />
		<input type="hidden" name="billing_fullname" value="<?php echo esc_html($nochexParams["customer_info"]["billing_fullname"]); ?>" />
		<input type="hidden" name="billing_address" value="<?php echo esc_html($nochexParams["customer_info"]["billing_address"]); ?>" />
		<input type="hidden" name="billing_city" value="<?php echo esc_html($nochexParams["customer_info"]["billing_city"]); ?>" />
		<input type="hidden" name="billing_country" value="<?php echo esc_html($nochexParams["customer_info"]["billing_country"]); ?>" />
		<input type="hidden" name="billing_postcode" value="<?php echo esc_html($nochexParams["customer_info"]["billing_postcode"]); ?>" />
		<input type="hidden" name="delivery_fullname" value="<?php echo esc_html($nochexParams["customer_info"]["delivery_fullname"]); ?>" />
		<input type="hidden" name="delivery_address" value="<?php echo esc_html($nochexParams["customer_info"]["delivery_address"]); ?>" />
		<input type="hidden" name="delivery_city" value="<?php echo esc_html($nochexParams["customer_info"]["delivery_city"]); ?>" />
		<input type="hidden" name="delivery_country" value="<?php echo esc_html($nochexParams["customer_info"]["delivery_country"]); ?>" />
		<input type="hidden" name="delivery_postcode" value="<?php echo esc_html($nochexParams["customer_info"]["delivery_postcode"]); ?>" />
		<input type="hidden" name="email_address" value="<?php echo esc_html($nochexParams["customer_info"]["email_address"]); ?>" />
		<input type="hidden" name="customer_phone_number" value="<?php echo esc_html($nochexParams["customer_info"]["customer_phone_number"]); ?>" />
		<input type="hidden" name="success_url" value="<?php echo esc_html($nochexParams["Nochex_Urls"]["success_url"]); ?>" />		
		<input type="hidden" name="hide_billing_details" value="<?php echo esc_html($nochexParams["Nochex_Settings"]["hide_billing_details"]); ?>" />		
		<input type="hidden" name="callback_url" value="<?php echo esc_html($nochexParams["Nochex_Urls"]["callback_url"]); ?>" />
		<input type="hidden" name="cancel_url" value="<?php echo esc_html($nochexParams["Nochex_Urls"]["cancel_url"]); ?>" />
		<input type="hidden" name="test_success_url" value="<?php echo esc_html($nochexParams["Nochex_Urls"]["test_success_url"]); ?>" />
		<input type="hidden" name="test_transaction" value="<?php echo esc_html($nochexParams["Nochex_Settings"]["test_transaction"]); ?>" />
		<p>If you are not transferred to Nochex shortly,<br /> Press the button below;</p>
		<input type="submit" style="background-color:#08c;color:#fff;" class="button-alt" id="submit_nochex_payment_form" value="Pay via Nochex" />
	</form>
<script type="text/javascript">
	window.onload = function() {
		document.getElementById("nochex_payment_form").submit();
	}
</script>
