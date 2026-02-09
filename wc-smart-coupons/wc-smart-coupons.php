<?php
/**
 * Plugin Name:       WC Smart Coupons & Analytics
 * Description:       The definitive coupon solution. Features 9 premium templates, advanced Slider customization, and a modern Analytics Command Center.
 * Version:           3.0.0
 * Author:            Ravi Raj
 * Text Domain:       wc-smart-coupons
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'WCSC_VERSION', '3.0.0' );
define( 'WCSC_PATH', plugin_dir_path( __FILE__ ) );
define( 'WCSC_URL', plugin_dir_url( __FILE__ ) );

require_once WCSC_PATH . 'includes/class-wcsc-activator.php';
register_activation_hook( __FILE__, [ 'WCSC_Activator', 'activate' ] );

register_deactivation_hook( __FILE__, function() {
    delete_transient( 'wcsc_active_coupons' );
});

require_once WCSC_PATH . 'includes/class-wcsc-loader.php';

function run_wc_smart_coupons() {
    $plugin = new WCSC_Loader();
    $plugin->run();
}
add_action( 'plugins_loaded', 'run_wc_smart_coupons' );