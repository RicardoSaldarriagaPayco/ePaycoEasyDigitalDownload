<?php
/**
 * The dashboard-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @since      1.0.0
 * @author     ePayco <soporte@payco.com>
 */
class WPAS_EDD_Payco_Admin{

    public function __construct() {

    }

    public function activate(){

    }

    public function deactivate(){

    }

    public function register_gateway_section( $gateway_sections ){
        $gateway_sections['wpas_edd_payco'] = __( 'ePayco', 'wpas-edd-payco' );

        return $gateway_sections;
    }

    public function gateway_settings( $gateway_settings ) {

        $default_settings = array(
            'wpas_edd_payco' => array(
                'id'   => 'wpas_edd_payco',
                'name' => '<strong>' . __( 'ePayco Payments Settings', 'wpas-edd-paycon' ) . '</strong>',
                'type' => 'header',
            ),
            'payco_p_cust_id_cliente' => array(
                'id'   => 'payco_p_cust_id_cliente',
                'name' => __( 'P_CUST_ID_CLIENTE', 'wpas-edd-payco' ),
                'desc' => '',
                'type' => 'text',
                'size' => 'regular',
            ),
            'payco_public_key' => array(
                'id'   => 'payco_public_key',
                'name' => __( 'PUBLIC_KEY', 'wpas-edd-payco' ),
                'desc' => '',
                'type' => 'text',
                'size' => 'regular',
            ),
            'payco_p_key' => array(
                'id'   => 'payco_p_key',
                'name' => __( 'P_KEY', 'wpas-edd-payco' ),
                'desc' => '',
                'type' => 'text',
                'size' => 'regular',
            ),
            'payco_environment' => array(
                'id'   => 'payco_environment',
                'name' => __('Environment', 'wpas-edd-payco' ),
                'type'        => 'select',
                'class'       => 'wc-enhanced-select',
                'description' => __('mode prueba/producción'),
                'desc_tip' => true,
                'default' => true,
                'options'     => array(
                    false    => __( 'Production' ),
                    true => __( 'Test' ),
                ),
            ),
            'payco_checkout' => array(
                'id'   => 'payco_checkout',
                'name' => __('Checkout', 'wpas-edd-payco' ),
                'type'        => 'select',
                'desc' => '',
                'default' => true,
                'options'     => array(
                    false    => __( 'One Page' ),
                    true => __( 'Standard' ),
                ),
            ),
        );

        $default_settings    = apply_filters( 'wpas_edd_default_payco_settings', $default_settings );

        $gateway_settings['wpas_edd_payco'] = $default_settings;

        return $gateway_settings;
    }
}