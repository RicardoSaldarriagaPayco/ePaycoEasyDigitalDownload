<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, dashboard-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @author     ePayco <soporte@payco.com>
 */
class WPAS_EDD_Payco {

    private static $instance = null;

    public $gateway_id = 'wpas_edd_payco';

    protected $version = WPAS_EDD_PAYCO_VERSION;

    public static function instance() {

        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;

    }

    public function __construct() {
        $this->setup_globals();
        $this->includes();
        $this->setup_actions();
    }

    private function setup_globals() {
	    $this->version = WPAS_EDD_PAYCO_VERSION;
	    $this->plugin_file = WPAS_EDD_PAYCO_FILE;
    }

    private function includes() {

        if ( !is_admin() ) {
            return;
        }

        require_once( WPAS_EDD_PAYCO_PATH . '/admin/class-edd-payco-admin.php' );
        $this->admin = new WPAS_EDD_Payco_Admin();

    }

    private function setup_actions() {

        add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
        add_action( 'edd_payment_gateways', array( $this, 'add_gateway' ) );
        add_action( 'edd_wpas_edd_payco_cc_form', '__return_false' );
        add_action( 'edd_gateway_wpas_edd_payco', array( $this, 'process_payment') );
        add_action( 'edd_enabled_payment_gateways', array( $this, 'enabled_payment_gateways') );
        add_action( 'init', array( $this, 'payco_check_callback') );

        if ( !is_admin() ) {
            return;
        }

        register_activation_hook( WPAS_EDD_PAYCO_FILE, array( $this->admin, 'activate' ) );
        register_deactivation_hook( WPAS_EDD_PAYCO_FILE, array( $this->admin, 'deactivate' ) );

        add_filter( 'edd_settings_sections_gateways', array( $this->admin, 'register_gateway_section' ) );
        add_filter( 'edd_settings_gateways', array( $this->admin, 'gateway_settings' ) );
    }

    public function load_plugin_textdomain(){
        load_plugin_textdomain('wpas-edd-payco', FALSE, WPAS_EDD_PAYCO_PATH . '/languages/');
    }

    public function add_gateway( $gateways ) {

        $default_gateway_info = array(
            $this->gateway_id => array(
                'admin_label'    => __( 'ePayco', 'wpas-edd-payco' ),
                'checkout_label' => __( 'ePayco', 'wpas-edd-payco' ),
                'supports'       => array(),
            ),
        );

        $default_gateway_info = apply_filters( 'wpas_edd_register_payco_gateway', $default_gateway_info );
        $gateways = array_merge( $gateways, $default_gateway_info );

        return $gateways;
    }

    public function enabled_payment_gateways( $gateway_list ) {

        $supported_currency = apply_filters( 'wpas_edd_payco_supported_currencies', array( 'USD','COP' ) );

        if( !in_array( edd_get_currency(), $supported_currency)) {
            unset($gateway_list['wpas_edd_payco']);
        }

        return $gateway_list;
    }

    public function process_payment( $purchase_data ) {

        if( ! wp_verify_nonce( $purchase_data['gateway_nonce'], 'edd-gateway' ) ) {
            wp_die( __( 'Nonce verification has failed', 'easy-digital-downloads' ), __( 'Error', 'easy-digital-downloads' ), array( 'response' => 403 ) );
        }

        global $edd_options;

        $errors = edd_get_errors();

        if (!$errors) {

            $currency = edd_get_currency();

            $payment = array(
                'price' => $purchase_data['price'],
                'date' => $purchase_data['date'],
                'user_email' => $purchase_data['user_email'],
                'purchase_key' => $purchase_data['purchase_key'],
                'currency' => $currency,
                'downloads' => $purchase_data['downloads'],
                'cart_details' => $purchase_data['cart_details'],
                'user_info' => $purchase_data['user_info'],
                'status' => 'pending'
            );

            $paymentID = edd_insert_payment($payment);

            $redirect_text  = __( 'Redirecting to ePayco site, click on button if not redirected.', 'wpas-edd-payco' );
            $redirect_text  = apply_filters( 'wpas_edd_payco_redirect_text', $redirect_text, $purchase_data );
            $payco_public_key = !empty( $edd_options['payco_public_key'] ) ? $edd_options['payco_public_key'] :'';
	        $payco_environment =$edd_options['payco_environment'];
            $payco_checkout = $edd_options['payco_checkout'] ;
            $test= "false";
            if($payco_environment){
                $test= "true";
            }
            $external = "false";
            if($payco_checkout){
                $external= "true";
            }
            $referenceCode = apply_filters( 'wpas_payco_form_reference_code', $paymentID, $purchase_data );
            $amount = !empty( $purchase_data['price'] ) ? $purchase_data['price']: 0;
            $subtotal = !empty( $purchase_data['subtotal'] ) ? $purchase_data['subtotal']: 0;
            $tax = !empty( $purchase_data['tax'] ) ? $purchase_data['tax']: 0;
            $tax=round($tax,2);
            $confirmationUrl = add_query_arg( array( 'payment-confirm' => 'payco', 'payco_cb'=> 1, 'order-id' => $paymentID ), get_permalink( $edd_options['success_page'] ) );
            $responseUrl = add_query_arg( array( 'payment-confirm' => 'payco', 'payco_cb'=> 1, 'order-id' => $paymentID ), get_permalink( $edd_options['success_page'] ) );
	        $first_name = !empty( $purchase_data['user_info']['first_name'] ) ? $purchase_data['user_info']['first_name'] :'';
	        $last_name = !empty( $purchase_data['user_info']['last_name'] ) ? $purchase_data['user_info']['last_name'] :'';
	        $email_billing = !empty( $purchase_data['user_email'] ) ? $purchase_data['user_email'] :'';
            $name_billing = trim($first_name. ' '. $last_name);
            $name_purchase = $purchase_data["cart_details"][0]['name'];

            echo('
                <style>
                    .epayco-title{
                        max-width: 900px;
                        display: block;
                        margin:auto;
                        color: #444;
                        font-weight: 700;
                        margin-bottom: 25px;
                    }
                    .loader-container{
                        position: relative;
                        padding: 20px;
                        color: #ff5700;
                    }
                    .epayco-subtitle{
                        font-size: 14px;
                    }
                    .epayco-button-render{
                        transition: all 500ms cubic-bezier(0.000, 0.445, 0.150, 1.025);
                        transform: scale(1.1);
                        box-shadow: 0 0 4px rgba(0,0,0,0);
                    }
                    .epayco-button-render:hover {
                        /*box-shadow: 0 0 4px rgba(0,0,0,.5);*/
                        transform: scale(1.2);
                    }

                    .animated-points::after{
                        content: "";
                        animation-duration: 2s;
                        animation-fill-mode: forwards;
                        animation-iteration-count: infinite;
                        animation-name: animatedPoints;
                        animation-timing-function: linear;
                        position: absolute;
                    }
                    .animated-background {
                        animation-duration: 2s;
                        animation-fill-mode: forwards;
                        animation-iteration-count: infinite;
                        animation-name: placeHolderShimmer;
                        animation-timing-function: linear;
                        color: #f6f7f8;
                        background: linear-gradient(to right, #7b7b7b 8%, #999 18%, #7b7b7b 33%);
                        background-size: 800px 104px;
                        position: relative;
                        background-clip: text;
                        -webkit-background-clip: text;
                        -webkit-text-fill-color: transparent;
                    }
                    .loading::before{
                        -webkit-background-clip: padding-box;
                        background-clip: padding-box;
                        box-sizing: border-box;
                        border-width: 2px;
                        border-color: currentColor currentColor currentColor transparent;
                        position: absolute;
                        margin: auto;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        content: " ";
                        display: inline-block;
                        background: center center no-repeat;
                        background-size: cover;
                        border-radius: 50%;
                        border-style: solid;
                        width: 30px;
                        height: 30px;
                        opacity: 1;
                        -webkit-animation: loaderAnimation 1s infinite linear,fadeIn 0.5s ease-in-out;
                        -moz-animation: loaderAnimation 1s infinite linear, fadeIn 0.5s ease-in-out;
                        animation: loaderAnimation 1s infinite linear, fadeIn 0.5s ease-in-out;
                    }
                    @keyframes animatedPoints{
                        33%{
                            content: "."
                        }

                        66%{
                            content: ".."
                        }

                        100%{
                            content: "..."
                        }
                    }

                    @keyframes placeHolderShimmer{
                        0%{
                            background-position: -800px 0
                        }
                        100%{
                            background-position: 800px 0
                        }
                    }
                    @keyframes loaderAnimation{
                        0%{
                            -webkit-transform:rotate(0);
                            transform:rotate(0);
                            animation-timing-function:cubic-bezier(.55,.055,.675,.19)
                        }

                        50%{
                            -webkit-transform:rotate(180deg);
                            transform:rotate(180deg);
                            animation-timing-function:cubic-bezier(.215,.61,.355,1)
                        }
                        100%{
                            -webkit-transform:rotate(360deg);
                            transform:rotate(360deg)
                        }
                    }
                </style>
                ');



            echo sprintf('
                    <div class="loader-container">
                        <div class="loading"></div>
                    </div>

                    <p style="text-align: center;" class="epayco-title">
                    <span class="animated-points">Loading payment methods</span>
                    <br><small class="epayco-subtitle"> If they do not load automatically, click on the "Pay with ePayco" button</small>
                    </p>                        
                    <script type="text/javascript" src="https://checkout.epayco.co/checkout.js">   </script>
                    <center>
                    <a href="#" onclick="return theFunction();">
                    <img src="https://369969691f476073508a-60bf0867add971908d4f26a64519c2aa.ssl.cf5.rackcdn.com/btns/epayco/boton_de_cobro_epayco4.png " style="border-width:0px;" />
                    </a>
                    <script type="text/javascript">
                    var handler = ePayco.checkout.configure({
                                key: "%s",
                                test: "%s"
                            });
                    var data={
                          name: "%s",
                          description: "%s",
                          invoice: "%s",
                          currency: "%s",
                          amount: "%s",
                          tax: "%s",
                          tax_base: "%s",
                          country: "co",
                          external: "%s",
                          response: "%s",
                          confirmation: "%s",
                          email_billing: "%s",
                          name_billing: "%s",
                          lang: "en",
                          extra1: "asy Digital Downloads",
                          extra2: "%s",
                        }
                        
                      handler.open(data)
                    function theFunction () {
                            handler.open(data)
                    }
                    </script>
                    </center>
            ',$payco_public_key,$test,$name_purchase,$name_purchase,$referenceCode,$currency,$amount,$tax,$subtotal, $external,$responseUrl,$confirmationUrl,$email_billing,$name_billing,$referenceCode);
  

        }else{

            edd_record_gateway_error( __( 'Payment Error', 'easy-digital-downloads' ), sprintf( __( 'Payment creation failed while processing a ePayco payment gateway purchase. Payment data: %s', 'wpas-edd-payco' ), json_encode( $purchase_data ) ) );
            // if errors are present, send the user back to the purchase page so they can be corrected
            edd_send_back_to_checkout('?payment-mode=' . $purchase_data['post_data']['edd-gateway']);

        }

    }

    public function payco_check_callback() {
        @ob_clean();
        global $edd_options;

        if ( isset( $_GET[ 'payco_cb' ] ) && 1 == absint( $_GET[ 'payco_cb' ] ) ) {
            $referencia_payco = !empty($_REQUEST['ref_payco']) ? sanitize_text_field($_REQUEST['ref_payco']) : null;
            $x_ref_payco = !empty($_REQUEST['x_ref_payco']) ? sanitize_text_field($_REQUEST['x_ref_payco']) : null;
            if($referencia_payco){
                $ref_payco_ = $referencia_payco;
                $url = 'https://secure.epayco.co/validation/v1/reference/'.$ref_payco_;
                $responseData = $this->agafa_dades($url,false,$this->goter());
                $jsonData = @json_decode($responseData, true);
                $validationData = $jsonData['data'];
                $ref_payco = $validationData['x_ref_payco'];
            }else{
                if($x_ref_payco) {
                    $ref_payco = $x_ref_payco;
                    $validationData = $_REQUEST;
                }else{
                    echo 'Error: internal error server!';
                    die();
                }
            }
            $payco_p_cust_id_cliente = !empty( $edd_options['payco_p_cust_id_cliente'] ) ? $edd_options['payco_p_cust_id_cliente'] :'';
            $payco_p_key = !empty( $edd_options['payco_p_key'] ) ? $edd_options['payco_p_key'] :'';
            $signature = hash('sha256',
                trim($payco_p_cust_id_cliente).'^'
                .trim($payco_p_key).'^'
                .trim($ref_payco).'^'
                .trim($validationData['x_transaction_id']).'^'
                .trim($validationData['x_amount']).'^'
                .trim($validationData['x_currency_code'])
            );
            $payment_id =  $validationData['x_extra2'];
            $txnid = $validationData['x_ref_payco'];
            $x_signature=trim($validationData['x_signature']);
            if($signature == $x_signature) {
                switch ((int)$validationData['x_cod_response']) {
                    case 1:{
                        // Approved
                        edd_update_payment_status( $payment_id, 'publish' );
                        edd_insert_payment_note( $payment_id, sprintf(__('Payment done via ePayco with ref_payco %s', 'wpas-edd-payco'), $txnid));
                    }break;
                    case 2: {
                        // Rejected
                        edd_update_payment_status( $payment_id, 'failed' );
                        edd_insert_payment_note( $payment_id, sprintf(__('Payment Rejected via ePayco with ref_payco %s', 'wpas-edd-payco'), $txnid));
                    }break;
                    case 3:{
                        // Pending Payment
                        edd_update_payment_status( $payment_id, 'pending' );
                        edd_insert_payment_note( $payment_id, sprintf(__('Payment pending via ePayco with ref_payco %s', 'wpas-edd-payco'), $txnid));
                    }break;
                    default:{
                        // Error
                        edd_update_payment_status( $payment_id, 'failed' );
                        edd_insert_payment_note( $payment_id, sprintf(__('Payment failed via ePayco  with ref_payco %s', 'wpas-edd-payco'), $txnid));
                    }break;
                }
            }else{
                echo 'Error: the signature is in correct!';
                die();
            }
        }
    }

    public function agafa_dades($url) {
        if (function_exists('curl_init')) {
            $ch = curl_init();
            $timeout = 5;
            $user_agent='Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($ch,CURLOPT_TIMEOUT,$timeout);
            curl_setopt($ch,CURLOPT_MAXREDIRS,10);
            $data = curl_exec($ch);
            curl_close($ch);
            return $data;
        }else{
            $data =  @file_get_contents($url);
            return $data;
        }
    }
    public function goter(){
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'protocol_version' => 1.1,
                'timeout' => 10,
                'ignore_errors' => true
            )
        ));
    }

}