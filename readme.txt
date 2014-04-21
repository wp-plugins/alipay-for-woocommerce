=== Plugin Name ===
Contributors: codingpet
Donate link:
Tags: woocommerce, alipay
Requires at least: 3.7
Tested up to: 3.9
Stable tag: 1.2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Integrate the Chinese Alipay payment gateway with Woocommerce. Alipay is one of the most widely used payment method in China.

== Description ==

Integrate the Chinese Alipay payment gateway with Woocommerce. Alipay is one of the most widely used payment method in China.

该插件将支付宝接口集成到WooCommerce中，支持即时到帐、担保买卖和双功能三种支付方式。

Requirement: You must have <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> plugin installed in order to use this plugin.
<strong>This plugin is not intended for accounts of global.alipay.com</strong>

Features:

1. Support three payment methods of Alipay: Direct Payment,Escrow Payment and both.
2. Allow to set an exchange rate if the main currency is not Chinese Yuan.
3. Form submission method includes posting via a form or using a redirect/querystring.
4. WPML and WooCommerce Multilingual support.

== Installation ==

1. Make sure you already have Woocommerce installed and activated.
2. Upload the folder alipay-for-woocommerce to the `/wp-content/plugins/` directory
3. Activate the plugin named <strong>Alipay For WooCommerce</strong> through the 'Plugins' menu in WordPress
4. Setup your alipay account throuth <strong>Woocommerce -> Settings -> Payment Gateways -> Alipay</strong>.
5. If the main currency of your store is not Chinese Yuan, please also set the exchange rate so that Alipay can convert the price to Chinese Yuan.

== Frequently Asked Questions ==

<strong>What is Partner ID and Security Key?</strong>

Partner ID and Security Key are provided by alipay once you successfully registered and signed a contract with alipay. They are a must if you want to receive payment with alipay.

<strong>What do I need in order to receive money with Alipay?</strong>

If you live in China or have an agent in China, please visit the link below to see a full detailed instruction.<br />
http://help.alipay.com/lab/help_detail.htm?help_id=1503

If not, please check the link below and see if you are qualified to have an alipay account.<br />
http://help.alipay.com/lab/help_detail.htm?help_id=214379

One of the most basic requirement is that you have a bank account in Chinese mainland.

<strong>Alipay doesn't show up on the checkout page, why?</strong>

If the main currency of your store is not Chinese Yuan, please make sure you set the exchange rate through <strong>Woocommerce -> Settings -> Payment Gateways -> Alipay</strong> page .

<strong>I got an ILLEGAL_PARTNER error when placing order?</strong>

Please make sure your account is registered at www.alipay.com, this plugin doesn't support account from global.alipay.com.

<strong>I got an ILLEGAL_PARTNER_EXTERFACE error when placing order?</strong>

Please check the "Alipay Payment Gateway Type" option, if you are using the Direct Payment method and your Alipay account is not a business version, this error occurs. For personal account, it is recommended to use the Escrow Payment method.

<strong>I got an illegal_Sign error when placing order?</strong>

Please check the "Use form submission method" box and see if it solves your problem. If not, please leave us a message at our website.

== Screenshots ==

1. Alipay settings page in English
2. Alipay settings page in Chinese
3. Pay with alipay.

== Changelog ==
= 1.1 =
* [Added] Support WooCommerce 2.0

= 1.1.1 =
* [Fixed] Fix a bug that happens when disabling WooCommerce plugin.
* [Fixed] Make sure it supports WooCommerce that belows version 2.0

= 1.2 =
* [Added] When store currency is not RMB, allow entering an exchange rate to convert price on the checkout page to RMB when paying with Alipay.
* [Added] Support WPML + WooCommerce Multilingual plugins
* [Revised] Change the default payment method to Escrow Payment.

= 1.2.1 =
Some bug fixes.
* Order subject format is changed to 'buyer name|#order_id'
* [Fixed] A issue caused by RMB & CNY
* [Fixed] Ordre status not being updated after payment is made
* Updated language pack