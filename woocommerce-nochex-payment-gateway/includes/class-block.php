<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

//use WC_Nochex;
use Automattic\WooCommerce\Blocks\Assets\Api;

final class WC_Nochex_Blocks extends AbstractPaymentMethodType {

    private $gateway;
	
	private $asset_api;
	
    protected $name = 'wc_nochex';// your payment gateway name

    public function initialize() {
        $this->settings = get_option( 'woocommerce_wc_nochex_settings', [] );
        $this->gateway = new WC_Nochex();
    }

   public function is_active() {		
		return filter_var( $this->get_setting( 'enabled', false ), FILTER_VALIDATE_BOOLEAN );
    } 

    public function get_payment_method_script_handles() {

        wp_register_script(
            'wc_nochex-blocks-integration',
            plugin_dir_url(__FILE__) . '../js/checkout.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            null,
            true
        );
        if( function_exists( 'wp_set_script_translations' ) ) {            
            wp_set_script_translations( 'wc_nochex-blocks-integration');
            
        }
        return [ 'wc_nochex-blocks-integration' ];
		
    }

    public function get_payment_method_data() {
        return [
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
			'supports'    => $this->get_supported_features(),
        ];
    }


	public function get_supported_features() {
		$gateway  = new wc_nochex();
		$features = array_filter( $gateway->supports, array( $gateway, 'supports' ) );

		/**
		 * Filter to control what features are available for each payment gateway.
		 *
		 * @since 4.4.0
		 *
		 * @example See docs/examples/payment-gateways-features-list.md
		 *
		 * @param array $features List of supported features.
		 * @param string $name Gateway name.
		 * @return array Updated list of supported features.
		 */
		return apply_filters( '__experimental_woocommerce_blocks_payment_gateway_features_list', $features, $this->get_name() );
	}
}
?>