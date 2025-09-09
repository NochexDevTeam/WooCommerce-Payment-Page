<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType; 
use Automattic\WooCommerce\Blocks\Assets\Api;

final class Nochex_Payment_Gateway_For_Woocommerce_Blocks extends AbstractPaymentMethodType {

    private $gateway;
	
	private $asset_api;
	
    protected $name = 'nochex_payment_gateway_for_woocommerce';

    public function initialize() {
        $this->settings = get_option( 'woocommerce_wc_nochex_settings', [] );
        $this->gateway = new Nochex_Payment_Gateway_For_Woocommerce();
    }

   public function is_active() {		
		return filter_var( $this->get_setting( 'enabled', false ), FILTER_VALIDATE_BOOLEAN );
    } 

    public function get_payment_method_script_handles() {

        wp_register_script(
            'nochex_payment_gateway_for_woocommerce-blocks-integration',
            plugin_dir_url(__FILE__) . '../js/checkout.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            true,
            null
        );
        if( function_exists( 'wp_set_script_translations' ) ) {            
            wp_set_script_translations( 'nochex_payment_gateway_for_woocommerce-blocks-integration');
            
        }
        return [ 'nochex_payment_gateway_for_woocommerce-blocks-integration' ];
		
    }

    public function get_payment_method_data() {
        return [
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
			'supports'    => $this->get_supported_features(),
        ];
    }


	public function get_supported_features() {
		$gateway  = new nochex_payment_gateway_for_woocommerce();
		$features = array_filter( $gateway->supports, array( $gateway, 'supports' ) );

		return apply_filters( '__experimental_woocommerce_blocks_payment_gateway_features_list', $features, $this->get_name() );
	}
}
?>