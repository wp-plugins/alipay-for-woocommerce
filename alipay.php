<?php
/*
 * Plugin Name: Alipay For WooCommerce
 * Plugin URI: http://www.codingpet.com
 * Description: Integrate the Chinese Alipay payment gateway with Woocommerce. Alipay is one of the most widely used payment method in China.
 * Version: 1.3.4
 * Author: CodingPet
 * Author URI: http://www.codingpet.com
 * Requires at least: 3.9
 * Tested up to: 4.0
 *
 * Text Domain: alipay
 * Domain Path: /lang/
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function wc_alipay_gateway_init() {

    if( !class_exists('WC_Payment_Gateway') )  return;

    load_plugin_textdomain( 'alipay', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/'  );

    require_once( plugin_basename( 'class-wc-alipay.php' ) );

    add_filter('woocommerce_payment_gateways', 'woocommerce_alipay_add_gateway' );

    add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_alipay_plugin_edit_link' );

}
add_action( 'plugins_loaded', 'wc_alipay_gateway_init' );

/**
 * Add the gateway to WooCommerce
 *
 * @access  public
 * @param   array $methods
 * @package WooCommerce/Classes/Payment
 * @return  array
 */
function woocommerce_alipay_add_gateway( $methods ) {

    $methods[] = 'WC_Alipay';
    return $methods;
}

/**
 * Display Alipay Trade No. for customer
 * 
 *
 * The function is put here because the alipay class 
 * is not called on order-received page
 *
 * @param array $total_rows
 * @param mixed $order
 * @return array
 */
function wc_alipay_display_order_meta_for_customer( $total_rows, $order ){
    $trade_no = get_post_meta( $order->id, 'Alipay Trade No.', true );
    
    if( !empty( $trade_no ) ){
        $new_row['alipay_trade_no'] = array(
            'label' => __( 'Alipay Trade No.:', 'alipay' ),
            'value' => $trade_no
        );
        // Insert $new_row after shipping field
        $total_rows = array_merge( array_splice( $total_rows,0,2), $new_row, $total_rows );
    }
    return $total_rows;
}
add_filter( 'woocommerce_get_order_item_totals', 'wc_alipay_display_order_meta_for_customer', 10, 2 );

function wc_alipay_plugin_edit_link( $links ){
    return array_merge(
        array(
            'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_alipay') . '">'.__( 'Settings', 'alipay' ).'</a>'
        ),
        $links
    );
}
?>