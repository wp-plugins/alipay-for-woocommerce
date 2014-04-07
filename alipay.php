<?php
/*
 * Plugin Name: Alipay For WooCommerce
 * Plugin URI: http://www.codingpet.com
 * Description: Integrate the Chinese Alipay payment gateway with Woocommerce. Alipay is one of the most widely used payment method in China.
 * Version: 1.2.1
 * Author: CodingPet
 * Author URI: http://www.codingpet.com
 * Requires at least: 3.3
 * Tested up to: 3.5.1
 *
 * Text Domain: alipay
 * Domain Path: /lang/
 */
if( preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF']) ) { die('You are not allowed to call this page directly.'); }

add_action( 'plugins_loaded', 'alipay_gateway_init' );
function alipay_gateway_init() {
    if( !class_exists('WC_Payment_Gateway') ) 
        return;
    load_plugin_textdomain( 'alipay', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/'  );
    require_once( plugin_basename( 'class-wc-alipay.php' ) );
    add_filter('woocommerce_payment_gateways', 'woocommerce_alipay_add_gateway' );
}
 /**
 * Add the gateway to WooCommerce
 *
 * @access public
 * @param array $methods
 * @package		WooCommerce/Classes/Payment
 * @return array
 */
function woocommerce_alipay_add_gateway( $methods ) {
    $methods[] = 'WC_Alipay';
    return $methods;
}
?>