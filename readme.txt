=== Plugin Name ===
Contributors: codingpet
Donate link:
Tags: woocommerce, alipay
Requires at least: 3.8.1
Tested up to: 3.9.2
Stable tag: 1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Integrate the Chinese Alipay payment gateway with Woocommerce. Alipay is one of the most widely used payment method in China.

== Description ==

Integrate the Chinese Alipay payment gateway with Woocommerce.

设置页面位于： WooCommerce &raquo; 设置 &raquo; 结账 &raquo; 支付宝


<strong>功能简介</strong>

1. 支持担保交易、双功能和即时到帐，即时到帐需要企业账户

2. 若主货币不是人民币，可设置汇率转成人民币付款，不影响其它支付方式

3. 当订单全部为虚拟可下载物品时，会自动发货


<strong>1.3版新增功能及修改</strong>

1. 虚拟可下载物品自动发货

2. 订单名称可设置为: 客户姓名、订单中第一个产品的名称或客户姓名+网站名称

3. 客户将支付宝中的款项释放给卖家后，订单状态自动变为“已完成”

4. 网站订单号变为WooCommerce的订单ID

5. 记录支付宝交易号，且在后台编辑订单页面和客户订单收据中显示


<strong>Features:</strong>

1. Support three payment methods of Alipay: Direct Payment,Escrow Payment and both.

2. Allow to set an exchange rate if the main currency is not Chinese Yuan.

3. Automatic delivery for products that are both vitual and downloadable.



<strong>Requirement:</strong>

1. You must have <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> plugin installed in order to use this plugin.

2. You must have an account at www.alipay.com.


== Installation ==

1. Make sure you already have Woocommerce installed and activated.
2. Upload the folder alipay-for-woocommerce to the `/wp-content/plugins/` directory
3. Activate the plugin named <strong>Alipay For WooCommerce</strong> through the 'Plugins' menu in WordPress
4. Setup your alipay account throuth <strong>Woocommerce -> Settings -> Payment Gateways -> Alipay</strong>.
5. If the main currency of your store is not Chinese Yuan, please also set the exchange rate so that Alipay can convert the price to Chinese Yuan.

== Frequently Asked Questions ==

<strong>支持的产品及申请地址</strong>

担保交易、即时到帐和双功能，即时到帐需要企业账号。
产品介绍和申请地址：https://b.alipay.com/order/productSet.htm


<strong>异步通知失败</strong>

客户付款后，支付宝会向网站发送异步通知，站点接收后会更新相应订单状态。例如，担保交易时，客户付款后订单状态变为“处理中”,客户释放款项后订单状态变为“完成”。

若订单状态无法更新，可能是异步通知失败，请检查服务器的访问日志，看是否有支付宝发来的请求，返回的状态码是什么。

支付宝请求的User Agent是Mozilla/4.0，referer为空，请检查服务器是否有屏蔽这两个特征的规则。已知hostgator会屏蔽Mozilla/4.0 User Agent。


<strong>关于自动发货</strong>

若销售虚拟产品，且订单产品同时具备虚拟(virtual)和可下载(Downloadable)两种属性，则客户付款后支付宝订单状态就会变为“卖家已发货，等待买家确认”。


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
2. 支付宝中文设置页面
3. 使用支付宝付款
4. 根据订单备注查看订单在支付宝的状态
5. 支付宝交易号作为自定义字段存储在WordPress中，可通过自定义栏目修改

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

= 1.3 =
* [Fixed] 防止订单状态重复更新
* [Fixed] 客户释放支付宝中的款项后，订单状态变为"Completed"
* [Fixed] 记录支付宝交易号，且在后台编辑订单页面以及客户收据中显示
* [Added] 更新支付宝核心文件到2014年版本
* [Added] 订单状态在同步通知和异步通知时都可以更新
* [Added] 虚拟可下载物品自动发货功能
* [Added] 提供三个订单名称选择 