<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType; 

final class Nochex_Payment_Gateway_For_Woocommerce_Blocks extends AbstractPaymentMethodType {

    private $gateway;
	
    protected $name = 'nochex_payment_gateway_for_woocommerce';

    public function initialize() {
        $this->settings = get_option( 'woocommerce_nochex_payment_gateway_for_woocommerce_settings', [] );
        if ( function_exists( 'WC' ) && WC()->payment_gateways() ) {
            $gateways = WC()->payment_gateways()->payment_gateways();
            $this->gateway = $gateways[ $this->name ] ?? null;
        }
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
            '3.0.2',
            true
        );
        if( function_exists( 'wp_set_script_translations' ) ) {
            wp_set_script_translations( 'nochex_payment_gateway_for_woocommerce-blocks-integration', 'nochex-payment-gateway-for-woocommerce' );
            
        }
        return [ 'nochex_payment_gateway_for_woocommerce-blocks-integration' ];
		
    }

    public function get_payment_method_data() {
        if ( ! $this->gateway ) {
            return [];
        }
        return [
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
			'supports'    => $this->get_supported_features(),
        ];
    }


	public function get_supported_features() {
		if ( ! $this->gateway ) {
			return array( 'products' );
		}
		$gateway  = $this->gateway;
		$features = array_filter( $gateway->supports, array( $gateway, 'supports' ) );

		return apply_filters( '__experimental_woocommerce_blocks_payment_gateway_features_list', $features, $this->get_name() );
	}
}
?>