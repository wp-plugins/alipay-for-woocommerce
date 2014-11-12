<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Alipay Payment Gateway
 *
 * Provides an Alipay Payment Gateway.
 *
 * @class       WC_Alipay
 * @extends     WC_Payment_Gateway
 * @version     1.3.3
 */

class WC_Alipay extends WC_Payment_Gateway {

    var $current_currency;
    var $multi_currency_enabled;
    var $supported_currencies;
    var $lib_path;
    var $charset;

    /**
     * Constructor for the gateway.
     *
     * @access public
     * @return void
     */
    public function __construct() {

        // WPML + Multi Currency related settings
        $this->current_currency       = get_option('woocommerce_currency');
        $this->multi_currency_enabled = in_array( 'woocommerce-multilingual/wpml-woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) && get_option( 'icl_enable_multi_currency' ) == 'yes';
        $this->supported_currencies   = array( 'RMB', 'CNY' );
        $this->lib_path               = plugin_dir_path( __FILE__ ) . 'lib';

        $this->charset                = strtolower( get_bloginfo( 'charset' ) );
        if( !in_array( $this->charset, array( 'gbk', 'utf-8') ) ) {
            $this->charset = 'utf-8';
        }

        // WooCommerce required settings
        $this->id                     = 'alipay';
        $this->icon                   = apply_filters( 'woocommerce_alipay_icon', plugins_url( 'images/alipay.png', __FILE__ ) );
        $this->has_fields             = false;
        $this->method_title           = __( 'Alipay', 'alipay' );
        $this->order_button_text      = __( 'Proceed to Alipay', 'alipay' );
        $this->notify_url             = WC()->api_request_url( 'WC_Alipay' );

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->title                  = $this->get_option( 'title' );
        $this->description            = $this->get_option( 'description' );
        $this->alipay_account         = $this->get_option( 'alipay_account' );
        $this->partnerID              = $this->get_option( 'partnerID' );
        $this->secure_key             = $this->get_option( 'secure_key' );
        $this->payment_method         = $this->get_option( 'payment_method' );
        $this->debug                  = $this->get_option( 'debug' );
        $this->form_submission_method = $this->get_option( 'form_submission_method' ) == 'yes' ? true : false;
        $this->order_title_format     = $this->get_option( 'order_title_format' );
        $this->exchange_rate          = $this->get_option( 'exchange_rate' );
        $this->order_prefix           = $this->get_option( 'order_prefix' );
        
        // Logs
        if ( 'yes' == $this->debug ) {
            $this->log = new WC_Logger();
        }

        // Actions
        add_action( 'admin_notices', array( $this, 'requirement_checks' ) );        
        add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) ); // WC <= 1.6.6
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) ); // WC >= 2.0
        add_action( 'woocommerce_thankyou_alipay', array( $this, 'thankyou_page' ) );
        add_action( 'woocommerce_receipt_alipay', array( $this, 'receipt_page' ) );

        // Payment listener/API hook
        add_action( 'woocommerce_api_wc_alipay', array( $this, 'check_alipay_response' ) );

        // Display Alipay Trade No. in the backend.
        add_action( 'woocommerce_admin_order_data_after_billing_address',array( $this, 'wc_alipay_display_order_meta_for_admin' ) );
    }

    /**
     * Check if this gateway is enabled and available for the selected main currency
     *
     * @access public
     * @return bool
     */
    function is_available() {

        $is_available = ( 'yes' === $this->enabled ) ? true : false;

        if ($this->multi_currency_enabled) {
            if ( !in_array( get_woocommerce_currency(), array( 'RMB', 'CNY') ) && !$this->exchange_rate) {
                $is_available = false;
            }
        } else if ( !in_array( $this->current_currency, array( 'RMB', 'CNY') ) && !$this->exchange_rate) {
            $is_available = false;
        }

        return $is_available;
    }

    /**
     * Check if requirements are met and display notices
     *
     * @access public
     * @return void
     */
    function requirement_checks() { 
        if ( !in_array( $this->current_currency, array( 'RMB', 'CNY') ) && !$this->exchange_rate ) {
            echo '<div class="error"><p>' . sprintf( __('Alipay is enabled, but the store currency is not set to Chinese Yuan. Please <a href="%1s">set the %2s against the Chinese Yuan exchange rate</a>.', 'alipay' ), admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_alipay#woocommerce_alipay_exchange_rate' ), $this->current_currency ) . '</p></div>';
        }
    }

    /**
     * Admin Panel Options
     * - Options for bits like 'title' and account etc.
     *
     * @access public
     * @return void
     */
    public function admin_options() {

        ?>
        <h3><?php _e('Alipay', 'alipay'); ?></h3>
        <p><?php _e('Alipay is a simple, secure and fast online payment method, customer can pay via debit card, credit card or alipay balance.', 'alipay'); ?></p>
       
        <table class="form-table">
            <?php
                // Generate the HTML For the settings form.
                $this->generate_settings_html();
            ?>
        </table><!--/.form-table-->
        <?php
    }
    
    /**
     * Initialise Gateway Settings Form Fields
     *
     * @access public
     * @return void
     */
    function init_form_fields() {

        $this->form_fields = array(
            'enabled' => array(
                'title'     => __('Enable/Disable', 'alipay'),
                'type'      => 'checkbox',
                'label'     => __('Enable Alipay Payment', 'alipay'),
                'default'   => 'no'
            ),
            'title' => array(
                'title'       => __('Title', 'alipay'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'alipay'),
                'default'     => __('Alipay', 'alipay'),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __('Description', 'alipay'),
                'type'        => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'alipay'),
                'default'     => __('Pay via Alipay, if you don\'t have an Alipay account, you can also pay with your debit card or credit card', 'alipay'),
                'desc_tip'    => true,
            ),
            'payment_method' => array(
                'title'       => __('Alipay Payment Gateway Type', 'alipay'),
                'type'        => 'select',
                'description' => __('Please choose a payment method, note that the Dual requires a corporate account', 'alipay'),
                'options'     => array(
                    'escrow'  => __('Escrow Payment', 'alipay'),
                    'dualfun' => __('Dual(Direct Payment + Escrow payment)', 'alipay'),
                    'direct'  => __('Direct Payment', 'alipay')
                ),
                'desc_tip'    => true,
            ),
            'partnerID' => array(
                'title'       => __('Partner ID', 'alipay'),
                'type'        => 'text',
                'description' => __('Please enter the partner ID<br />If you don\'t have one, <a href="https://b.alipay.com/newIndex.htm" target="_blank">click here</a> to get.', 'alipay'),
                'css'         => 'width:400px'
            ),
            'secure_key' => array(
                'title'       => __('Security Key', 'alipay'),
                'type'        => 'text',
                'description' => __('Please enter the security key<br />If you don\'t have one, <a href="https://b.alipay.com/newIndex.htm" target="_blank">click here</a> to get.', 'alipay'),
                'css'         => 'width:400px'
            ),
            'alipay_account' => array(
                'title'       => __('Alipay Account', 'alipay'),
                'type'        => 'text',
                'description' => __('Please enter your Alipay account ( Email or Phone Number ); this is needed in order to take payment.', 'alipay'),
                'css'         => 'width:200px',
                'desc_tip'    => true,
            ),
            'order_prefix' => array(
                'title'       => __( 'Order No. Prefix', 'alipay' ),
                'type'        => 'text',
                'description' => __( 'eg.WC-. If you <strong>use your Alipay account for multiple stores</strong>, Please enter this prefix and make sure it is unique as Alipay will not allow orders with the same merchant order number.', 'alipay' ),
                'default'     => 'WC-'
            ),
            'form_submission_method' => array(
                'title'       => __('Submission method', 'alipay'),
                'type'        => 'checkbox',
                'label'       => __('Use form submission method.', 'alipay'),
                'description' => __('Enable this to post order data to Alipay via a form instead of using a redirect/querystring.', 'alipay'),
                'default'     => 'no',
                'desc_tip'    => true,
            ),
            'order_title_format' => array(
                'title'       => __('Preferred format for order title', 'alipay'),
                'type'        => 'select',
                'label'       => __('Select your preferred order title format', 'alipay'),
                'description' => __('Select the format of order title when making payment at Alipay', 'alipay'),
                'options'     => array(
                    'customer_name' => __('Customer Full Name|#Order ID', 'alipay'),
                    'product_title' => __('Name of the first Product|#Order ID', 'alipay'),
                    'shop_name'     => sprintf( __( '[Customer Full Name]\'s Order From %s|#Order ID', 'alipay' ), get_bloginfo('name') )
                ),
                'desc_tip'    => true,
            ),
            'debug' => array(
                'title'       => __('Debug Log', 'alipay'),
                'type'        => 'checkbox',
                'label'       => __('Enable logging', 'alipay'),
                'default'     => 'no',
                'description' => __('Log Alipay events, such as trade status, inside <code>woocommerce/logs/alipay.txt</code>', 'alipay')
            )
        );

        // For WC2.2+
        if(  function_exists( 'wc_get_log_file_path' ) ){
             $this->form_fields['debug']['description'] = sprintf(__('Log Alipay events, such as trade status, inside <code>%s</code>', 'alipay'), wc_get_log_file_path( 'alipay' ) );
        }

        if (!in_array( $this->current_currency, array( 'RMB', 'CNY') )) {

            $this->form_fields['exchange_rate'] = array(
                'title'       => __('Exchange Rate', 'alipay'),
                'type'        => 'text',
                'description' => sprintf(__("Please set the %s against Chinese Yuan exchange rate, eg if your currency is US Dollar, then you should enter 6.19", 'alipay'), $this->current_currency),
                'css'         => 'width:80px;',
                'desc_tip'    => true,
            );
        }
    }

    /**
     * Get Alipay Args
     *
     * @access public
     * @param mixed $order
     * @return array
     */
    function get_alipay_args( $order ) {

        global $wpdb;

        $order_id = $order->id;

        if ( 'yes' == $this->debug ) {
            $this->log->add('alipay', 'Generating payment form for order #' . $order_id . '. Notify URL: ' . $this->notify_url);
        }

        // Use filter woocommerce_alipay_order_name to change the order subject 
        $subject = $this->format_order_title( $order );
        

        // Service parameter that decide the payment type
        if ( $this->payment_method == 'direct' ){
            $service = 'create_direct_pay_by_user';
        } else if ( $this->payment_method == 'dualfun' ){
            $service = 'trade_create_by_buyer';
        } else if ( $this->payment_method == 'escrow' ){
            $service = 'create_partner_trade_by_buyer';
        }

        // Order total price
        $total_fee = $order->get_total();
        
        //Multi-currency supported by WooCommerce Multilingual plugin
        If ($this->multi_currency_enabled && $this->exchange_rate) {
            
            if ( !in_array(get_woocommerce_currency(), $this->supported_currencies ) && $this->current_currency != get_woocommerce_currency() ) {
               
                $sql = "SELECT (value) FROM " . $wpdb->prefix . "icl_currencies WHERE code = '" . get_woocommerce_currency() . "'";
                $currency = $wpdb->get_results($sql, OBJECT);

                if ( $currency ) {
                    $exchange_rate = $currency[0]->value;
                    $total_fee = round( ( $total_fee / $exchange_rate ) * $this->exchange_rate, 2 );
                }
                
            } else if ( $this->current_currency == get_woocommerce_currency() ) {
                $total_fee = round( $total_fee * $this->exchange_rate, 2 );
            }
            
        } else {
            if ( !in_array( $this->current_currency, $this->supported_currencies ) && $this->exchange_rate ) {
                $total_fee = round( $total_fee * $this->exchange_rate, 2 );
            }
        }

        // Fullfill the alipay args array
        $alipay_args = array(
            "service"           => $service,
            "partner"           => $this->partnerID,
            "payment_type"      => "1",
            "notify_url"        => urldecode( $this->notify_url ),                //Avoid double encoding
            "return_url"        => urldecode( $this->get_return_url( $order ) ),  //Avoid double encoding
            "seller_email"      => $this->alipay_account,
            "out_trade_no"      => $this->order_prefix . ltrim( $order->get_order_number(), '#' ),
            "subject"           => $subject,
            "price"             => $total_fee,
            "quantity"          => 1,          
            "_input_charset"    => $this->charset,
        );
        if ($this->payment_method != 'direct') {
            $add_args = array(
                "logistics_fee"     => '0.00',
                "logistics_type"    => 'EXPRESS', //optional EXPRESS（快递）、POST（平邮）、EMS（EMS）
                "logistics_payment" => 'SELLER_PAY', //optional SELLER_PAY（卖家承担运费）、BUYER_PAY（买家承担运费）               
            );

            if( !empty($buyer_name) )                   
                $add_args['receive_name']       = $this->clean( $buyer_name );
            if( !empty($order->billing_address_1) )     
                $add_args['receive_address']    = $this->clean( $order->billing_address_1 );
            if( !empty($order->shipping_postcode) )     
                $add_args['receive_zip']        = $order->shipping_postcode;
            if( !empty($order->billing_phone) )         
                $add_args['receive_phone']      = $order->billing_phone;
            if( !empty($order->billing_phone) )        
                $add_args['receive_mobile']     = $order->billing_phone;

            if( empty($add_args['receive_address']) ) unset($add_args['receive_address']);

            $alipay_args = array_merge($alipay_args, $add_args);
        }

        $alipay_args = apply_filters( 'woocommerce_alipay_args', $alipay_args );

        return $alipay_args;
    }

    /**
     * Get Alipay configuration
     *
     * @access public
     * @param mixed $order
     * @return array
     */
    function get_alipay_config() {        
        $alipay_config = array();
        $alipay_config['partner']       = trim( $this->partnerID );
        $alipay_config['key']           = trim( $this->secure_key );
        $alipay_config['sign_type']     = 'MD5';
        $alipay_config['input_charset'] = $this->charset;
        $alipay_config['cacert']        = $this->lib_path . DIRECTORY_SEPARATOR . 'cacert.pem';
        $alipay_config['transport']     = 'http';

        // SSL support
        if( is_ssl() || get_option('woocommerce_force_ssl_checkout') == 'yes' ){
            $alipay_config['transport'] = 'https';
        }

        $alipay_config = apply_filters( 'woocommerce_alipay_config_args', $alipay_config );

        return $alipay_config;
    }

    /**
     * Build Alipay Query String for redirection to Alipay using GET method
     *
     * @access public
     * @param mixed $order
     * @return string
     */
    function build_alipay_string( $order ) {

        require_once( "lib/alipay_submit.class.php");

        // Get alipay args
        $alipay_args    = $this->get_alipay_args( $order );
        $alipay_config  = $this->get_alipay_config();

        $alipaySubmit   = new AlipaySubmit( $alipay_config );

        // Build query string
        $query_string   = $alipaySubmit->buildRequestParaToString( $alipay_args );
        $alipay_string  = $alipaySubmit->alipay_gateway_new . $query_string;

        return $alipay_string;
    }

    /**
     * Return page of Alipay, show Alipay Trade No. 
     *
     * @access public
     * @param mixed Sync Notification
     * @return void
     */
    function thankyou_page( $order_id ) {

        $_GET = stripslashes_deep( $_GET );

        if ( isset( $_GET['trade_status'] ) && !empty( $_GET['trade_status'] ) ) {

            require_once("lib/alipay_notify.class.php");

            $aliapy_config  = $this->get_alipay_config();
            $alipayNotify   = new AlipayNotify( $aliapy_config );
            $order_key      = $_GET['key'];

            // Delete parameters added by WC from $_GET
            if( isset( $_GET['page_id'] ) )         unset( $_GET['page_id'] );          // When using default permlaink format
            if( isset( $_GET['order-received'] ) )  unset( $_GET['order-received'] );   // When using default permlaink format
            unset( $_GET['order'] );
            unset( $_GET['key'] );

            if ( $this->debug == 'yes' ){
                $log = true;
            }

            $verify_result = $alipayNotify->verifyReturn( $log );

            if ( $verify_result ) {

                $trade_no      = $_GET['trade_no'];         // Alipay Order Number
                $out_trade_no  = $_GET['out_trade_no'];     // Merchant Order Number

                // Check order ID
                if( is_numeric( $out_trade_no ) ){
                    if( !empty( $this->order_prefix ) ){
                        $check  = (int) str_replace( $this->order_prefix, '', $out_trade_no );
                    } else {
                        $check  = (int) $out_trade_no;
                    }
                } else {
                    $check = (int) str_replace( $this->order_prefix, '', $out_trade_no );
                }

                if( $order_id != $check ){
                    // We have an invalid $order_id, probably because order_prefix has changed
                    $check  = wc_get_order_id_by_order_key( $order_key );
                    if( $order_id != $check ){
                        _e( "<p><strong>ERROR:</strong>The order ID doesn't match!</p>", 'alipay' );
                        return;
                    }
                }

                // Order ID is correct.
                $order = new WC_Order( $order_id );

                echo '<ul class="order_details">
                        <li class="alipayNo">' . __('Your Alipay Trade No.: ', 'alipay') . '<strong>' . $trade_no . '</strong></li>
                    </ul>';


                // Update the order according to data carried with the return_url
                $trade_status = $_GET['trade_status'];
                switch( $trade_status ){

                    case 'WAIT_SELLER_SEND_GOODS' :

                        $order_needs_updating = ( in_array( $order->status, array('processing', 'completed') ) ) ? false : true;
                        $status               = apply_filters( 'woocommerce_alipay_payment_successful_status', 'processing', $order); 

                        if( $order_needs_updating ){

                            // Update order status if IPN has not process the order
                            update_post_meta( $order_id, 'Alipay Trade No.', wc_clean( $trade_no ) );
                            $order->update_status( $status, __( "Payment received, awaiting fulfilment. ", 'alipay' ) );
                            
                            // Send deliver notification to alipay if the order is vitural and downloadable.
                            $success = $this->send_goods_confirm( wc_clean( $trade_no ), $order );
                            if( strpos( $success, 'error' ) !== false ){
                                // Failed to update status
                                if ( 'yes' == $this->debug ){
                                    $message = sprintf( __('ERROR: Failed to send goods automatically for order %d, ', 'alipay' ), $order_id ) ;
                                    $message .= $success;
                                    $this->log->add( 'alipay',  $message );
                                }
                            } else if( $success === true ) {
                                $order->add_order_note( __( 'Your order has been shipped, awaiting buyer\'s confirmation', 'alipay' ) );                               
                            }

                            // Reduce stock level
                            $order->reduce_order_stock();
                        }
                        break;

                    case 'TRADE_FINISHED':
                    case 'TRADE_SUCCESS' :

                        if( $order->status != 'completed' && $order->status != 'processing' ){
                            $order->payment_complete();
                        }                        
                        update_post_meta( $order_id, 'Alipay Trade No.', wc_clean( $trade_no ) );
                        break;

                    default :
                        break;
                }
            }
        }
    }

    /**
     * Generate the alipay button link (POST method)
     *
     * @access public
     * @param mixed $order_id
     * @return string
     */
    function generate_alipay_form( $order_id ) {

        $order = new WC_Order($order_id);
        require_once( "lib/alipay_submit.class.php");

        $alipay_args    = $this->get_alipay_args( $order );
        $alipay_config  = $this->get_alipay_config();
        $alipaySubmit   = new AlipaySubmit( $alipay_config );
        $alipay_adr     = $alipaySubmit->alipay_gateway_new;
        $para           = $alipaySubmit->buildRequestPara($alipay_args, $alipay_config);

        $alipay_args_array = array();
        foreach ($para as $key => $value) {
            $alipay_args_array[] = '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';
        }

        wc_enqueue_js( '
            $.blockUI({
                    message: "' . esc_js( __( 'Thank you for your order. We are now redirecting you to Alipay to make payment.', 'alipay' ) ) . '",
                    baseZ: 99999,
                    overlayCSS:
                    {
                        background: "#fff",
                        opacity: 0.6
                    },
                    css: {
                        padding:        "20px",
                        zindex:         "9999999",
                        textAlign:      "center",
                        color:          "#555",
                        border:         "3px solid #aaa",
                        backgroundColor:"#fff",
                        cursor:         "wait",
                        lineHeight:     "24px",
                    }
                });
            jQuery("#submit_alipay_payment_form").click();
        ' );
        
        return '<form id="alipaysubmit" name="alipaysubmit" action="' . $alipay_adr . '_input_charset=' . trim( strtolower($alipay_config['input_charset'] ) ) . '" method="post" target="_top">' . implode('', $alipay_args_array) . '
                    <!-- Button Fallback -->
                    <div class="payment_buttons">
                        <input type="submit" class="button-alt" id="submit_alipay_payment_form" value="' . __('Pay via Alipay', 'alipay') . '" /> <a class="button cancel" href="' . esc_url($order->get_cancel_order_url()) . '">' . __('Cancel order &amp; restore cart', 'alipay') . '</a>
                    </div>
                    <script type="text/javascript">
                        jQuery(".payment_buttons").hide();
                    </script>
                </form>';
    }

    /**
     * Process the payment and return the result
     *
     * @access public
     * @param  int $order_id
     * @return array
     */
    function process_payment( $order_id ) {

        $order = new WC_Order( $order_id );

        if ( !$this->form_submission_method ) {

            $redirect = $this->build_alipay_string( $order );

            if ( 'yes' == $this->debug ){
                $this->log->add( 'alipay', 'Query string: ' . $redirect );
            }

            return array(
                'result'   => 'success',
                'redirect' => $redirect
            );

        } else {

            return array(
                'result'   => 'success',
                'redirect' => $order->get_checkout_payment_url( true )
            );
        }
    }

    /**
     * Output for the order received page.
     *
     * @access public
     * @return void
     */
    function receipt_page( $order ) {

        echo '<p>' . __('Thank you for your order, please click the button below to pay with Alipay.', 'alipay') . '</p>';

        echo $this->generate_alipay_form( $order );
    }

    /**
     * Check for Alipay IPN Response
     *
     * @access public
     * @return void
     */

    function check_alipay_response() {

        $_POST = stripslashes_deep( $_POST );

        @ob_clean();

        if ( isset( $_POST['seller_id'] ) && $_POST['seller_id'] == $this->partnerID ) {

            // Get order id
            $out_trade_no = $_POST['out_trade_no'];

            if( is_numeric( $out_trade_no ) ){
                if( !empty( $this->order_prefix ) ){
                    $order_id  = (int) str_replace( $this->order_prefix, '', $out_trade_no );
                } else {
                    $order_id  = (int) $out_trade_no;
                }
            } else {
                $order_id = (int) str_replace( $this->order_prefix, '', $out_trade_no );
            }

            if ( !$order_id || !is_numeric( $order_id ) ){
                 wp_die( 'Invalid Order ID' );
            }

            // Get alipay config
            $order         = new WC_Order( $order_id );
            $alipay_config = $this->get_alipay_config();

            // Delete any parameter that doesn't belong to alipay
            unset( $_POST['wc-api'] );

            // Verify alipay's notification
            require_once( "lib/alipay_notify.class.php" );
            $alipayNotify = new AlipayNotify( $alipay_config );

            // Log verification
            if ( 'yes' == $this->debug ) $log = true;
                
            $verify_result = $alipayNotify->verifyNotify( $log );

            if ( $this->debug == 'yes' ) {
                $debug_verify_result = $verify_result ? 'Valid' : 'Invalid';
                $this->log->add('alipay', 'Verification result: ' . $debug_verify_result);                    
            }

            // Stop here if verification failed
            if( !$verify_result ) wp_die( 'Fail' );
            
            // Avoid duplicate order comments
            $order_trade_status = get_post_meta( $order_id, '_alipay_trade_current_status', true );
            if( empty( $order_trade_status ) ) $order_trade_status = 0;

            if ( $this->payment_method == 'direct' ) {

                if ( ( $_POST['trade_status'] == 'TRADE_FINISHED' || $_POST['trade_status'] == 'TRADE_SUCCESS' ) && $order->status != 'completed' ) {

                    $order->payment_complete();                   

                    if( isset( $_POST['trade_no'] ) && !empty( $_POST['trade_no'] ) ){
                        update_post_meta( $order_id, 'Alipay Trade No.', wc_clean( $_POST['trade_no'] ) );
                    }  
                    $this->successful_request( $_POST, $order_id );
                }

            } else {

                switch( $_POST['trade_status'] ){

                    case 'WAIT_BUYER_PAY' :

                        if( $order_trade_status == 0 ){
                            update_post_meta( $order_id, '_alipay_trade_current_status',  1 );
                            $order->add_order_note( __( 'Order received, awaiting payment. ', 'alipay' ) );                        
                        }
                        $this->successful_request( $_POST, $order_id ); 
                        break;

                    case 'WAIT_SELLER_SEND_GOODS' :

                        $order_needs_updating = ( in_array( $order->status, array('processing', 'completed') ) ) ? false : true;
                        $status = apply_filters( 'woocommerce_alipay_payment_successful_status', 'processing', $order); 

                        if( $order_needs_updating && $order_trade_status == 1 ){
                             // Update order status
                            update_post_meta( $order_id, '_alipay_trade_current_status', 2 );
                            $order->update_status( $status, __( 'Payment received, awaiting fulfilment. ', 'alipay' ) );
                            
                            if( isset($_POST['trade_no']) && !empty($_POST['trade_no']) ){
                                update_post_meta( $order_id, 'Alipay Trade No.', wc_clean( $_POST['trade_no'] ) );
                                $success = $this->send_goods_confirm( wc_clean( $_POST['trade_no'] ), $order );
                            }

                            // Reduce stock levels
                            $order->reduce_order_stock();
                        }
                        $this->successful_request( $_POST, $order_id );
                        break;

                    case 'WAIT_BUYER_CONFIRM_GOODS' :

                        if( $order_trade_status == 2 ){
                            update_post_meta( $order_id, '_alipay_trade_current_status', 3 );
                            $order->add_order_note( __( 'Your order has been shipped, awaiting buyer\'s confirmation. ', 'alipay' ) );  
                        }                        
                        $this->successful_request( $_POST, $order_id );
                        break;

                    case 'TRADE_FINISHED' :

                        if( $order_trade_status == 3 ){
                            update_post_meta( $order_id, '_alipay_trade_current_status', 4 );
                            add_filter( 'woocommerce_valid_order_statuses_for_payment_complete', array( $this, 'valid_order_statuses' ) );
                            add_filter( 'woocommerce_payment_complete_reduce_order_stock', '__return_false' );
                            add_filter( 'woocommerce_payment_complete_order_status', array( $this, 'complete_order_status' ) );
                            $order->payment_complete();

                        } else if( $order_trade_status == 1 && $this->payment_method == 'dualfun' ){
                            update_post_meta( $order_id, '_alipay_trade_current_status', 4 );                        
                            $order->payment_complete();
                        }
                        $this->successful_request( $_POST, $order_id );
                        break;

                    default :

                        $this->successful_request( $_POST, $order_id );
                }                    
            }

        } else {

            wp_die( 'Alipay Notification Request Failure' );
        }
    }

    /**
     * Allow completing order when status is processing
     *
     * @param array $statuses    
     * @since 1.3.4
     * @return array
     */
    function valid_order_statuses( $statuses ){
        return array( 'processing' );
    }

    function complete_order_status( $new_order_status ){
        return 'completed';
    }

    /**
     * Change order status to WAIT_BUYER_CONFIRM_GOODS
     *
     * @param string $trade_no
     * @param bool $force_send_goods
     * @param bool $require_shipping
     * @since 1.3
     * @return string
     */
    function send_goods_confirm( $trade_no, $order, $force_send_goods = false, $require_shipping = false ){

        if( empty( $trade_no ) ){
            return 'error: ' . __( 'Trade No. is not provided.', 'alipay' );
        } else if( !function_exists('curl_version') ){
            return 'error: ' . __( 'cURL is not installed on this server', 'alipay' );
        }

        // Decide if goodes need to be send automatically
        if( !in_array( $order->status, array( 'pending', 'failed', 'on-hold') ) ){

            $send_goods = false;

            if( $force_send_goods ){

                $send_goods = true;

            } else if ( sizeof( $order->get_items() ) > 0 ) {

                foreach( $order->get_items() as $item ) {
                    if ( $item['product_id'] > 0 ) {
                        $_product = $order->get_product_from_item( $item );
                        if ( false !== $_product && ( $_product->is_downloadable() && $_product->is_virtual() ) ) {
                            $send_goods = true;
                            continue;
                        }
                    }
                    $send_goods = false;
                    break;
                }
            }
        }

        if( !$send_goods ) return false;

        // Send requrest to alipay
        require_once( "lib/alipay_submit.class.php");

        if( !$require_shipping ){

            $parameter = array(
                "service"           => "send_goods_confirm_by_platform",
                "partner"           => $this->partnerID,
                "trade_no"          => $trade_no,
                "logistics_name"    => 'ZJS',
                "transport_type"    => 'DIRECT',
                "_input_charset"    => $this->charset
            );

        } else {
            // Develop function of sending non-digital goods from WordPress backend
            // Currently this part is not used.
        }

        $alipay_config = $this->get_alipay_config();

        $alipaySubmit = new AlipaySubmit( $alipay_config );
        $html_text = $alipaySubmit->buildRequestHttp( $parameter );

        if( !empty( $html_text ) ){

            $doc = new DOMDocument();        
            $doc->loadXML( $html_text );

            $success = $doc->getElementsByTagName('is_success');

            if( $success->item(0)->nodeValue == 'F' ){
                // Failed to update status
                $error = $doc->getElementsByTagName('error');
                return 'error: ' . $error->item(0)->nodeValue;
            } else {
                return true;
            }       
            
        } else {
            return 'Error: Request Failed';
        }
        
    }
    

    /**
     * Successful Payment!
     *
     * @access public
     * @param array $posted
     * @return void
     */
    function successful_request( $posted, $order_id ) {

        if ( 'yes' == $this->debug ){
            $this->log->add('alipay', 'Trade Status Received: '.$posted['trade_status'].', Order ID: ' . $order_id );
        }

        header('HTTP/1.1 200 OK');
        echo "success";
        exit;
    }

    /**
     * Format order title
     *
     * @access public
     * @param mixed $order
     * @param int $length
     * @since 1.3
     * @return string
     */
    function format_order_title( $order, $length = 256 ){

        $order_id = $order->id;

        if( empty($this->order_title_format) ){
            $this->order_title_format = 'customer_name';
        }

        $title = '';

        switch ( $this->order_title_format ){

            case 'customer_name' :

                if( !empty( $order->billing_last_name ) || !empty( $order->billing_first_name ) ){
                    $title = $order->billing_last_name . $order->billing_first_name.'|#'.$order_id;
                }                
                break;

            case 'product_title' :

                $line_items = $order->get_items();

                if( count($line_items) > 0 ){
                    foreach( $line_items as $line_item ){
                        $title = $line_item['name'];
                        break;
                    }
                }
                if ( strlen( $title ) > $length ) {
                    $title = mb_strimwidth( $title, 0, ($length-3), '...' );
                }               

                if( count($line_items) > 1 ){
                    $title .= __( ' etc.', 'alipay');
                }

                $title .= '|#'.$order_id;
                break;

            case 'shop_name' :
                if( !empty( $order->billing_last_name ) || !empty( $order->billing_first_name ) ){
                    $customer_name = $order->billing_last_name . $order->billing_first_name;
                }
                if( !empty($customer_name) ){
                    $title = sprintf( __( "Order of %1s from %2s", 'alipay'), $customer_name, get_bloginfo( 'name' ) );
                } else{
                    $title = sprintf( __( 'Your order from %s', 'alipay' ) , get_bloginfo( 'name' ) );
                }               
                $title .= '|#'.$order_id;
                break;

            default :
                break;
        }

        $title = $this->clean( $title );
        if( empty( $title ) ) $title = '#'.$order_id;

        $title = apply_filters( 'woocommerce_alipay_order_name', $title, $title, $order );

        return $title;
    }

    /**
     * Sanitize user input
     *
     * @access public
     * @param string $str
     * @since 1.3
     * @return string
     */
    function clean( $str = ''){
        $clean = str_replace( array('%'), '', $str );
        $clean = sanitize_text_field( $clean );
        $clean = html_entity_decode(  $clean , ENT_NOQUOTES );
        return $clean;
    }

    /**
     * Display Alipay Trade No. in the backend.
     * 
     * @access public
     * @param mixed $order
     * @since 1.3
     * @return void
     */
    function wc_alipay_display_order_meta_for_admin( $order ){
        $trade_no = get_post_meta( $order->id, 'Alipay Trade No.', true );
        if( !empty($trade_no ) ){
            echo '<p><strong>' . __( 'Alipay Trade No.:', 'alipay') . '</strong><br />' .$trade_no. '</p>';
        }
    }
}
?>