<?php
class WCSC_Loader {
    public function run() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->load_textdomain();
    }

    private function load_dependencies() {
        require_once WCSC_PATH . 'includes/class-wcsc-admin.php';
        require_once WCSC_PATH . 'includes/class-wcsc-frontend.php';
        require_once WCSC_PATH . 'includes/class-wcsc-analytics.php';
        require_once WCSC_PATH . 'includes/class-wcsc-cache.php';
    }

    private function load_textdomain() {
        add_action( 'init', function() {
            load_plugin_textdomain( 'wc-smart-coupons', false, dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages' );
        });
    }

    private function define_admin_hooks() {
        $admin = new WCSC_Admin();
        add_action( 'admin_menu', [ $admin, 'add_plugin_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $admin, 'enqueue_styles' ] );
        
        // Cache Clearing
        add_action( 'save_post_shop_coupon', [ 'WCSC_Cache', 'clear_coupon_cache' ] );
        add_action( 'woocommerce_coupon_options_save', [ 'WCSC_Cache', 'clear_coupon_cache' ] );

        // NEW: CSV Export Hook
        add_action( 'admin_post_wcsc_export_csv', [ 'WCSC_Analytics', 'export_csv' ] );
    }

    private function define_public_hooks() {
        $frontend = new WCSC_Frontend();
        
        add_action( 'wp_enqueue_scripts', [ $frontend, 'enqueue_scripts' ] );

        // AJAX Actions
        add_action( 'wp_ajax_wcsc_track_event', [ 'WCSC_Analytics', 'ajax_track_event' ] );
        add_action( 'wp_ajax_nopriv_wcsc_track_event', [ 'WCSC_Analytics', 'ajax_track_event' ] );
        add_action( 'wp_ajax_wcsc_get_coupons', [ $frontend, 'ajax_get_coupons' ] );
        add_action( 'wp_ajax_nopriv_wcsc_get_coupons', [ $frontend, 'ajax_get_coupons' ] );

        // Dynamic Hooks & Shortcode
        $frontend->register_dynamic_hooks();
        add_shortcode( 'wc_smart_coupons', [ $frontend, 'render_shortcode' ] );
    }
}