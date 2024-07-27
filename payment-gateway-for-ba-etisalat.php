<?php
/*
Plugin Name: Payment Gateway for BA via Etisalat
Plugin URI: https://wordpress.org/plugins/payment-gateway-for-ba-etisalat/
Description: Integrates Etisalat payments into BA Book Everything plugin
Author: Usama Saeed
Version: 1.0.0
*/

define( 'BAETISALAT__FILE__', __FILE__ );
define( 'BAETISALAT_PATH', plugin_dir_path( BAETISALAT__FILE__ ) );

Class BAETISALAT_PAYMENT_GATEWAY {

    public function __construct() {

        add_action( 'babe_payment_methods_init', array( $this, 'init_payment_method' ) );

        add_action( 'babe_settings_payment_method_etisalat', array( $this, 'add_settings_etisalat' ), 10, 3);

        if (class_exists('BABE_Settings')){
            add_filter( 'babe_sanitize_'.BABE_Settings::$option_name, array( $this, 'sanitize_settings' ), 10, 2);
        }

        add_filter( 'babe_checkout_payment_title_etisalat', array( $this, 'payment_method_title'), 10, 3);

        add_filter( 'babe_checkout_payment_fields_etisalat', array( $this, 'payment_method_fields_html'), 10, 3);

        add_action( 'babe_order_to_pay_by_etisalat', array( $this, 'order_to_pay_etisalat'), 10, 4);

        add_action( 'babe_payment_server_etisalat_response', array( $this, 'etisalat_server_response'), 10);

        add_filter( 'babe_refund_etisalat', array( $this, 'refund_etisalat'), 10, 5);

    }

    function etisalat_server_response_endpoint_init() {
        add_rewrite_endpoint( 'etisalat-server-response', EP_ROOT );
    }
    
    // Flush rewrite rules on plugin activation
    function etisalat_server_response_endpoint_activate() {
        custom_endpoint_init();
        flush_rewrite_rules();
    }

    // Flush rewrite rules on plugin deactivation
    function etisalat_server_response_endpoint_deactivate() {
        flush_rewrite_rules();
    }

    // Handle custom endpoint request
    function custom_endpoint_handler() {
        // Your custom endpoint logic goes here
        // You can access the endpoint URL parameters using the $_GET or $_POST global variables

        // Example response
        $response = array( 'message' => 'Hello from custom endpoint!' );

        // Convert the response to JSON format
        wp_send_json( $response );
    }
    

    public function init_payment_method() {
        if ( ! isset( $payment_methods['etisalat'] ) ) {
            BABE_Payments::add_payment_method( 'etisalat', esc_html__( 'Etisalat', 'payment-gateway-for-ba-etisalat' ) );
        }
    }


    public static function add_settings_etisalat($section_id, $option_menu_slug, $option_name) {

        add_settings_field(
            'etisalat_method_title', // ID
            esc_html__('Method title', 'payment-gateway-for-ba-etisalat'), // Title
            array( __CLASS__, 'etisalat_method_title_field_callback' ), // Callback
            $option_menu_slug, // Page
            $section_id,  // Section
            array('option' => 'etisalat_method_title', 'settings_name' => $option_name) // Args array
        );

        add_settings_field(
            'etisalat_transaction_hint', // ID
            esc_html__('Transaction Hint', 'payment-gateway-for-ba-etisalat'), // Title
            array( __CLASS__, 'etisalat_transaction_hint_callback' ), // Callback
            $option_menu_slug, // Page
            $section_id,  // Section
            array('option' => 'etisalat_transaction_hint', 'settings_name' => $option_name) // Args array
        );

        add_settings_field(
            'etisalat_merchant_name', // ID
            esc_html__('Merchant Name', 'payment-gateway-for-ba-etisalat'), // Title
            array( __CLASS__, 'etisalat_merchant_name_callback' ), // Callback
            $option_menu_slug, // Page
            $section_id,  // Section
            array('option' => 'etisalat_merchant_name', 'settings_name' => $option_name) // Args array
        );

        add_settings_field(
            'etisalat_store', // ID
            esc_html__('Store', 'payment-gateway-for-ba-etisalat'), // Title
            array( __CLASS__, 'etisalat_store_callback' ), // Callback
            $option_menu_slug, // Page
            $section_id,  // Section
            array('option' => 'etisalat_store', 'settings_name' => $option_name) // Args array
        );

        add_settings_field(
            'etisalat_terminal', // ID
            esc_html__('Terminal', 'payment-gateway-for-ba-etisalat'), // Title
            array( __CLASS__, 'etisalat_terminal_callback' ), // Callback
            $option_menu_slug, // Page
            $section_id,  // Section
            array('option' => 'etisalat_terminal', 'settings_name' => $option_name) // Args array
        );

        add_settings_field(
            'etisalat_epg_url', // ID
            esc_html__('EPG URL', 'payment-gateway-for-ba-etisalat'), // Title
            array( __CLASS__, 'etisalat_epg_url_callback' ), // Callback
            $option_menu_slug, // Page
            $section_id,  // Section
            array('option' => 'etisalat_epg_url', 'settings_name' => $option_name) // Args array
        );

        add_settings_field(
            'etisalat_username', // ID
            esc_html__('UserName', 'payment-gateway-for-ba-etisalat'), // Title
            array( __CLASS__, 'etisalat_username_callback' ), // Callback
            $option_menu_slug, // Page
            $section_id,  // Section
            array('option' => 'etisalat_username', 'settings_name' => $option_name) // Args array
        );

        add_settings_field(
            'etisalat_password', // ID
            esc_html__('Password', 'payment-gateway-for-ba-etisalat'), // Title
            array( __CLASS__, 'etisalat_password_callback' ), // Callback
            $option_menu_slug, // Page
            $section_id,  // Section
            array('option' => 'etisalat_password', 'settings_name' => $option_name) // Args array
        );      

    }


    public static function setting_activate_callback($args){

        $check = isset(BABE_Settings::$settings[$args['option']]) ?  BABE_Settings::$settings[$args['option']] : 0;

        $checked1 = $check ? 'checked' : '';
        $checked2 = !$check ? 'checked' : '';

        echo '<p><input id="'.$args['option'].'1" name="'.$args['settings_name'].'['.$args['option'].']" type="radio" value="1" '.$checked1.'/><label for="'.$args['option'].'1">'. esc_html__('Yes', 'payment-gateway-for-ba-etisalat').'</label></p>';
        echo '<p><input id="'.$args['option'].'2" name="'.$args['settings_name'].'['.$args['option'].']" type="radio" value="0" '.$checked2.'/><label for="'.$args['option'].'2">'. esc_html__('No', 'payment-gateway-for-ba-etisalat').'</label></p>';

    }

    public static function etisalat_method_title_field_callback($args){
        $add_class = isset($args['translate']) ? ' class="q_translatable"' : '';

        printf(
            '<input type="text"'.$add_class.' id="'.$args['option'].'" name="'.$args['settings_name'].'['.$args['option'].']" value="%s" />',
            isset( BABE_Settings::$settings[$args['option']] ) ? esc_attr( BABE_Settings::$settings[$args['option']]) : 'Custom Payment'
        );
    }

    public static function etisalat_transaction_hint_callback($args){
        $add_class = isset($args['translate']) ? ' class="q_translatable"' : '';

        printf(
            '<input type="text"'.$add_class.' id="'.$args['option'].'" name="'.$args['settings_name'].'['.$args['option'].']" value="%s" />',
            isset( BABE_Settings::$settings[$args['option']] ) ? esc_attr( BABE_Settings::$settings[$args['option']]) : 'Transaction Hint'
        );
    }

    public static function etisalat_merchant_name_callback($args){
        $add_class = isset($args['translate']) ? ' class="q_translatable"' : '';

        printf(
            '<input type="text"'.$add_class.' id="'.$args['option'].'" name="'.$args['settings_name'].'['.$args['option'].']" value="%s" />',
            isset( BABE_Settings::$settings[$args['option']] ) ? esc_attr( BABE_Settings::$settings[$args['option']]) : 'Merchant Name'
        );
    }

    public static function etisalat_store_callback($args){
        $add_class = isset($args['translate']) ? ' class="q_translatable"' : '';

        printf(
            '<input type="text"'.$add_class.' id="'.$args['option'].'" name="'.$args['settings_name'].'['.$args['option'].']" value="%s" />',
            isset( BABE_Settings::$settings[$args['option']] ) ? esc_attr( BABE_Settings::$settings[$args['option']]) : ''
        );
    }

    public static function etisalat_terminal_callback($args){
        $add_class = isset($args['translate']) ? ' class="q_translatable"' : '';

        printf(
            '<input type="text"'.$add_class.' id="'.$args['option'].'" name="'.$args['settings_name'].'['.$args['option'].']" value="%s" />',
            isset( BABE_Settings::$settings[$args['option']] ) ? esc_attr( BABE_Settings::$settings[$args['option']]) : ''
        );
    }

    public static function etisalat_epg_url_callback($args){
        $add_class = isset($args['translate']) ? ' class="q_translatable"' : '';

        printf(
            '<input type="text"'.$add_class.' id="'.$args['option'].'" name="'.$args['settings_name'].'['.$args['option'].']" value="%s" />',
            isset( BABE_Settings::$settings[$args['option']] ) ? esc_attr( BABE_Settings::$settings[$args['option']]) : ''
        );
    }

    public static function etisalat_username_callback($args){
        $add_class = isset($args['translate']) ? ' class="q_translatable"' : '';

        printf(
            '<input type="text"'.$add_class.' id="'.$args['option'].'" name="'.$args['settings_name'].'['.$args['option'].']" value="%s" />',
            isset( BABE_Settings::$settings[$args['option']] ) ? esc_attr( BABE_Settings::$settings[$args['option']]) : 'UserName'
        );
    }

    public static function etisalat_password_callback($args){
        $add_class = isset($args['translate']) ? ' class="q_translatable"' : '';

        printf(
            '<input type="password"'.$add_class.' id="'.$args['option'].'" name="'.$args['settings_name'].'['.$args['option'].']" value="%s" />',
            isset( BABE_Settings::$settings[$args['option']] ) ? esc_attr( BABE_Settings::$settings[$args['option']]) : 'Password'
        );
    }


    public static function payment_method_title($method_title, $args, $input_fields_name){

        return $method_title;

    }

    public static function payment_method_fields_html($fields, $args, $input_fields_name){

        $fields = '
        <div class="etisalat-payment-description">
           <img class="booking_payment_img" src="'. plugin_dir_url( __FILE__ ).'images/etisalat-logo.jpg" border="0" alt="'. esc_html__('Etisalat payment gateway', 'payment-gateway-for-ba-etisalat').'">
           <h4>'. esc_html__( 'Continue with Etisalat', 'payment-gateway-for-ba-etisalat' ).'</h4>
        </div>';

        return $fields;

    }

    public static function init_settings() {
        $setting_data = [];

        $setting_data['etisalat_method_title'] = 'Custom Payment';
        $setting_data['etisalat_transaction_hint'] = 'Transaction Hint';
        $setting_data['etisalat_merchant_name'] = 'Merchant Name';
        $setting_data['etisalat_store'] = 'Store';
        $setting_data['etisalat_terminal'] = 'Terminal';
        $setting_data['etisalat_epg_url'] = 'EPG URL';
        $setting_data['etisalat_username'] = 'UserName';
        $setting_data['etisalat_password'] = 'Password';

        if (class_exists('BABE_Settings')){

            $setting_data['etisalat_method_title'] = isset(BABE_Settings::$settings['etisalat_method_title']) ? BABE_Settings::$settings['etisalat_method_title'] : '';
            $setting_data['etisalat_transaction_hint'] = isset(BABE_Settings::$settings['etisalat_transaction_hint']) ? BABE_Settings::$settings['etisalat_transaction_hint'] : 0;
            $setting_data['etisalat_merchant_name'] = isset(BABE_Settings::$settings['etisalat_merchant_name']) ? BABE_Settings::$settings['etisalat_merchant_name'] : '';
            $setting_data['etisalat_store'] = isset(BABE_Settings::$settings['etisalat_store']) ? BABE_Settings::$settings['etisalat_store'] : '';
            $setting_data['etisalat_terminal'] = isset(BABE_Settings::$settings['etisalat_terminal']) ? BABE_Settings::$settings['etisalat_terminal'] : '';
            $setting_data['etisalat_epg_url'] = isset(BABE_Settings::$settings['etisalat_epg_url']) ? BABE_Settings::$settings['etisalat_epg_url'] : '';
            $setting_data['etisalat_username'] = isset(BABE_Settings::$settings['etisalat_username']) ? BABE_Settings::$settings['etisalat_username'] : '';
            $setting_data['etisalat_password'] = isset(BABE_Settings::$settings['etisalat_password']) ? BABE_Settings::$settings['etisalat_password'] : '';
        }

        return $setting_data;

    }

    public static function sanitize_settings($new_input, $input) {

        $new_input['etisalat_method_title'] = isset($input['etisalat_method_title']) ? sanitize_text_field($input['etisalat_method_title']) : '';
        $new_input['etisalat_transaction_hint'] = isset($input['etisalat_transaction_hint']) ? sanitize_text_field($input['etisalat_transaction_hint']) : ''; //intval
        $new_input['etisalat_merchant_name'] = isset($input['etisalat_merchant_name']) ? sanitize_text_field($input['etisalat_merchant_name']) : '';
        $new_input['etisalat_store'] = isset($input['etisalat_store']) ? sanitize_text_field($input['etisalat_store']) : '';
        $new_input['etisalat_terminal'] = isset($input['etisalat_terminal']) ? sanitize_text_field($input['etisalat_terminal']) : '';
        $new_input['etisalat_epg_url'] = isset($input['etisalat_epg_url']) ? sanitize_text_field($input['etisalat_epg_url']) : '';
        $new_input['etisalat_username'] = isset($input['etisalat_username']) ? sanitize_text_field($input['etisalat_username']) : '';
        $new_input['etisalat_password'] = isset($input['etisalat_password']) ? sanitize_text_field($input['etisalat_password']) : '';

        return $new_input;
    }

    public static function etisalat_server_response(){

        $data_settings = self::init_settings();
        $transaction_id = $_POST['TransactionID'];
        
        $ch = curl_init($data_settings['etisalat_epg_url']); 
		$arr_sendRequest = array();
		$arr_sendRequest['TransactionID'] = $transaction_id;
		$arr_sendRequest['Customer'] = $data_settings['etisalat_merchant_name'];
		$arr_sendRequest['UserName'] = $data_settings['etisalat_username'];
		$arr_sendRequest['Password'] = $data_settings['etisalat_password'];
		$jonRequest = json_encode(array('Finalization' => $arr_sendRequest), JSON_FORCE_OBJECT);		
		
		curl_setopt($ch, CURLOPT_HEADER, 0); /* Set to 1 to see HTTP headers, otherwise 0 or XML reading will not work */
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json','Accept:text/xml-standard-api'));
		curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($ch, CURLOPT_PORT, 2443);
		curl_setopt($ch, CURLOPT_SSLVERSION, 6);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $jonRequest );
		curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . "/ca.crt"); /* Location in same folder as this file */
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response_curl = curl_exec($ch);
		
		if (curl_errno($ch)) 
		{
			$response_curlerror = curl_error($ch);
			custom_logs("--------Error While Processing the Transaction---------");
			print "<br />Error While Processing the Transaction, Please try again<br />";
			curl_close($ch);
			exit();	
		}
		curl_close($ch);
		
		$response = json_decode($response_curl, true);
        $order_id = $_GET['OrderID'];
        $instance = new self();
        $instance->custom_logs("BA Order Id - ".$_GET['OrderID']);
        $instance->custom_logs("Response Code - ".$response['Transaction']['ResponseCode']);
		$instance->custom_logs("Response Description - ".$response['Transaction']['ResponseDescription']);
        $instance->custom_logs("Response OrderID - ".$response['Transaction']['OrderID']);
        $instance->custom_logs("Response UniqueID - ".$response['Transaction']['UniqueID']);
        $instance->custom_logs("=========================================================");
        if (isset($response['Transaction']['ResponseCode']) 
			&& isset($response['Transaction']['ApprovalCode'])
			&& ($response['Transaction']['ResponseCode'] == 0))
			{ 
				BABE_Payments::do_complete_order($_GET['OrderID'], 'etisalat', $transaction_id, $response['Transaction']['Amount']['Value']);
                $orderHash = BABE_Order::get_order_hash($order_id);
		        $ReturnPath = get_site_url() . '/confirmation/?order_id='.$order_id.'&order_num='.$response['Transaction']['OrderID'].'&order_hash='.$orderHash.'&current_action=to_confirm';
                header("Location: $ReturnPath"); exit;
                return;
			}			
			else 	
			{
				BABE_Order::update_order_status($page->ID, 'draft');
			}
        return;
    }

    function custom_logs($logmessage) 
    { 
        echo 'logmessage ==='. $logmessage.'<br>';
        if(is_array($logmessage)) 
        { 
            $logmessage = json_encode($logmessage); 
        } 
        $file = fopen(plugin_dir_path(__FILE__) . "Transactionlogs.log","a"); 
        echo fwrite($file, "\n" . date('Y-m-d h:i:s') . " : " . $logmessage); 
        fclose($file); 
    }

    public static function order_to_pay_etisalat($order_id, $args, $current_url, $success_url){

        $amount = isset($args['payment']['amount_to_pay']) && $args['payment']['amount_to_pay'] == 'deposit' ? BABE_Order::get_order_prepaid_amount($order_id) : BABE_Order::get_order_total_amount($order_id);
        $data_settings = self::init_settings();

        $ch = curl_init($data_settings['etisalat_epg_url']);	
        $etisalat_args = array(
            'Currency'          => BABE_Currency::get_currency(),
            'TransactionHint'   => $data_settings['etisalat_transaction_hint'],
            'OrderID'           => BABE_Order::get_order_number($order_id),
            'OrderName'         => "Order Name",
            'OrderInfo'         => $order_id,
            'Channel'           => "Web",
            'Amount'            => $amount,
            'Customer'          => $data_settings['etisalat_merchant_name'],
            'Store'             => $data_settings['etisalat_store'],
            'Terminal'          => $data_settings['etisalat_terminal'],
            'UserName'          => $data_settings['etisalat_username'], //BABE_Order::get_order_number($order_id),
            'Password'          => $data_settings['etisalat_password'], //BABE_Payments::get_payment_server_response_page_url('etisalat'),
            'ReturnPath'        => BABE_Payments::get_payment_server_response_page_url('etisalat').'?OrderID='.$order_id
        );

        $jonRequest = json_encode(array('Registration' => $etisalat_args), JSON_FORCE_OBJECT);

        curl_setopt($ch, CURLOPT_HEADER, 0); 
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json','Accept:text/xml-standard-api'));
		curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($ch, CURLOPT_PORT, 2443);
		curl_setopt($ch, CURLOPT_SSLVERSION, 6);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $jonRequest );
		curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . "/ca.crt");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response_curl = curl_exec($ch);
				
		if (curl_errno($ch)) 
		{
			$response_curlerror = curl_error($ch);
			print "<br />Error While Processing the Registration Request, Please try again<br />";
			curl_close($ch);
			exit();	
		}
		curl_close($ch);
		
		$response = json_decode($response_curl, true);
		
        if (isset($response['Transaction']['ResponseCode']) 
        && ($response['Transaction']['ResponseCode'] == 0))
        { 	
            echo '<center>You are being re-directed to Etisalat Payment Gateway.</b></center><form method="post" id="etisalat-redirect-form" name="redirect" action="'.$response['Transaction']['PaymentPage'].'"/><input type="Hidden" name="TransactionID" value="'.$response['Transaction']['TransactionID'].'"/></form><script type="text/javascript">document.redirect.submit();</script>';
        }
        else
        {
            print "<br />Error Details:<br />";
            print "Response Code: " .$response['Transaction']['ResponseCode'] . "<br />"; 
            print "Desctiption: " .$response['Transaction']['ResponseDescription'] . "<br />"; 
            print "Class Desctiption: " .$response['Transaction']['ResponseClassDescription'] . "<br />"; 
            echo "<br>";
            echo '<a href="javascript:window.history.back()">Go Back</a>';
        }
    }

    public static function refund_etisalat($refunded, $order_id, $amount = 0, $token_arr = array()){

        $refunded = 0;

        $data_settings = self::init_settings();

        if ($amount){

            $api = $data_settings['sandbox_mode'] ? $data_settings['etisalat_api']['test'] : $data_settings['etisalat_api']['live'];

            $currency = BABE_Order::get_order_currency($order_id);

            $transaction_id = isset($token_arr['token']) ? $token_arr['token'] : ''; //// get saved payment transaction id

            $apiContext = new \Etisalat\Rest\ApiContext(
                new \Etisalat\Auth\OAuthTokenCredential(
                    $api['client_id'],
                    $api['secret']
                )
            );

            $amt = new Paypal_Amount();
            $amt->setTotal($amount)
                ->setCurrency($currency);

            $refund = new Paypal_Refund();
            $refund->setAmount($amt);

            $sale = new Paypal_Sale();
            $sale->setId($transaction_id);

            $refundedSale = '';

            try {

                $refundedSale = $sale->refund($refund, $apiContext);

            } catch (Etisalat\Exception\EtisalatConnectionException $ex) {

                return $refunded;

            } catch (Exception $ex) {
                return $refunded;
            }

            $refunded = $amount;

            BABE_Payments::do_after_refund_order($order_id, 'etisalat', $transaction_id, $refunded, $token_arr);

        }

        return $refunded;

    }
}

add_action( 'plugin_loaded', function () {
    new BAETISALAT_PAYMENT_GATEWAY();
} );