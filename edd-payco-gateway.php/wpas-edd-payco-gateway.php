<?php
/**
 * Plugin Name: Payment Gateway for ePayco on Easy Digital Downloads
 * Description: A ePayco payment gateway addon for Easy Digital Downloads WordPress plugin
 * Version: 1.0.0
 * Author: ePayco
 * Author URI: https://epayco.co/
 * Text Domain: wpas-edd-payco
 */

/**
 * If this file is called directly, abort.
 */
if ( ! defined( 'WPINC' ) ) {
    die;
}

if ( is_admin() ) {

    if ( !function_exists( 'is_plugin_active' ) ) {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    }

    if ( !is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' ) ) {

        deactivate_plugins( plugin_basename( __FILE__ ) );

        function wpas_payco_requires_edd_plugin() {
            echo '<div class="notice notice-warning is-dismissible"><p><strong>' . sprintf( __( '%s requires to install the %sEasy Digital Downloads%s plugin.', 'wpas-edd-payco' ), 'Easy Digital Downloads - ePayco Payment Gateway', '<a href="https://wordpress.org/plugins/easy-digital-downloads/" target="_blank">', '</a>' ) . '</strong></p></div>';
        }

        add_action( 'admin_notices', 'wpas_payco_requires_edd_plugin' );
        return;
    }
}

if ( !defined( 'WPAS_EDD_PAYCO_VERSION' ) ) {
    define( 'WPAS_EDD_PAYCO_VERSION', '1.0.0' );
}

if ( !defined( 'WPAS_EDD_PAYCO_PATH' ) ) {
    define( 'WPAS_EDD_PAYCO_PATH', plugin_dir_path( __FILE__ ) );
}

if ( !defined( 'WPAS_EDD_PAYCO_FILE' ) ) {
    define( 'WPAS_EDD_PAYCO_FILE', __FILE__ );
}

if ( !defined( 'WPAS_EDD_PAYCO_PLUGIN_URL' ) ) {
    define( 'WPAS_EDD_PAYCO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

require_once WPAS_EDD_PAYCO_PATH . '/includes/class-edd-payco.php';

function init_wpas_payco() {

    global $wpas_payco;
    $wpas_payco = WPAS_EDD_Payco::instance();

}
add_action( 'plugins_loaded', 'init_wpas_payco' );
