<?php
if (!defined('ABSPATH')) exit;

/**
 * Plugin Name: WPBDP CoinPayments.net Gateway
 * Plugin URI: https://www.coinpayments.net/
 * Description:  Provides a CoinPayments.net Payment Gateway.
 * Author: CoinPayments.net
 * Author URI: https://www.coinpayments.net/
 * Version: 2.0.0
 */

add_action('plugins_loaded', 'coinpayments_gateway_load', 0);

function coinpayments_gateway_load()
{

    class WPBDP_Gateway_Coinpayments_Plugin
    {

        public function __construct()
        {

            $this->includes();
            $this->filters();

        }

        public function includes()
        {
            require_once  dirname(__FILE__) . '/includes/class-wpbdp-gateway-coinpayments.php';
            require_once  dirname(__FILE__) . '/includes/class-wpbdp-gateway-coinpayments-api-handler.php';
        }

        public function filters()
        {
            add_filter('wpbdp_payment_gateways', [__CLASS__, 'add_gateway'], 0);
        }

        public static function add_gateway($gateways)
        {
            if (!in_array('WPBDP__Gateway__Coinpayments', $gateways)) {
                $gateways['WPBDP__Gateway__Coinpayments'] = new WPBDP__Gateway__Coinpayments();
            }
            return $gateways;
        }

    }

    new WPBDP_Gateway_Coinpayments_Plugin();
}
