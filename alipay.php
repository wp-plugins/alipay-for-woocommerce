<?php
/*
 * Plugin Name: Alipay For Woocommerce
 * Plugin URI: http://www.codingpet.com
 * Description: Integrate the Chinese Alipay payment gateway with Woocommerce. Alipay is one of the most widely used payment method in China.
 * Version: 1.0
 * Author: CodingPet
 * Author URI: http://www.codingpet.com
 * Requires at least: 3.3
 * Tested up to: 3.4.2
 *
 * Text Domain: alipay
 * Domain Path: /lang/
 */
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

add_action('plugins_loaded', 'alipay_gateway_init', 0);

include 'class-alipay.php';
?>