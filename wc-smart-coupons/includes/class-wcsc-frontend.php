<?php
class WCSC_Frontend {

    public function enqueue_scripts() {
        wp_enqueue_style( 'wcsc-style', WCSC_URL . 'assets/css/frontend.css', [], WCSC_VERSION );
        wp_enqueue_script( 'wcsc-script', WCSC_URL . 'assets/js/frontend.js', [ 'jquery' ], WCSC_VERSION, true );
        
        $primary = get_option( 'wcsc_color_primary', '#333333' );
        $text    = get_option( 'wcsc_color_text', '#d63638' );
        $bg      = get_option( 'wcsc_color_bg', '#ffffff' );
        
        // Slider Customization
        $slide_speed = get_option( 'wcsc_slider_speed', '3000' );
        $slide_width = get_option( 'wcsc_slider_card_width', '280px' );
        $slide_gap   = get_option( 'wcsc_slider_gap', '20' ) . 'px';
        
        $blur    = get_option( 'wcsc_glass_blur', '10' ) . 'px';
        $opacity = get_option( 'wcsc_glass_opacity', '0.7' );

        $custom_css = ":root { 
            --wcsc-primary: {$primary}; --wcsc-text: {$text}; --wcsc-bg: {$bg};
            --wcsc-blur: {$blur}; --wcsc-opacity: {$opacity};
            --wcsc-slide-width: {$slide_width}; --wcsc-slide-gap: {$slide_gap};
        }";
        wp_add_inline_style( 'wcsc-style', $custom_css );

        wp_localize_script( 'wcsc-script', 'wcsc_vars', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'wcsc_security_nonce' ),
            'slider'   => [
                'speed'    => $slide_speed,
                'autoplay' => get_option( 'wcsc_slider_autoplay', 'yes' )
            ]
        ]);
    }

    public function register_dynamic_hooks() {
        if ( get_option('wcsc_show_product', 'yes') === 'yes' ) add_action( get_option( 'wcsc_product_hook', 'woocommerce_single_product_summary' ), [ $this, 'render_placeholder' ], 20 );
        if ( get_option('wcsc_show_cart', 'yes') === 'yes' ) add_action( get_option( 'wcsc_cart_hook', 'woocommerce_before_cart' ), [ $this, 'render_placeholder' ], 20 );
        if ( get_option('wcsc_show_checkout', 'yes') === 'yes' ) add_action( 'woocommerce_before_checkout_form', [ $this, 'render_placeholder' ] );
        if ( get_option('wcsc_show_minicart', 'yes') === 'yes' ) add_action( 'woocommerce_widget_shopping_cart_before_buttons', [ $this, 'render_mini_cart_placeholder' ], 5 );
    }

    public function render_placeholder() {
        if ( 'no' === get_option( 'wcsc_enable_plugin', 'yes' ) ) return;
        $data_attr = is_product() ? 'data-product-id="' . get_the_ID() . '"' : '';
        echo '<div class="wcsc-coupon-loader" ' . $data_attr . ' data-location="standard"><span class="wcsc-spinner"></span></div>';
    }

    public function render_mini_cart_placeholder() {
        if ( 'no' === get_option( 'wcsc_enable_plugin', 'yes' ) ) return;
        echo '<div class="wcsc-coupon-loader" data-location="minicart"><span class="wcsc-spinner small"></span></div>';
    }

    public function ajax_get_coupons() {
        $coupons = WCSC_Cache::get_active_coupons();
        $location = isset($_POST['location']) ? sanitize_text_field($_POST['location']) : 'standard';
        $style = ( $location === 'minicart' ) ? 'list' : get_option( 'wcsc_display_style', 'grid' );
        $prod_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        
        if ( empty( $coupons ) ) { wp_send_json_error( [ 'html' => '' ] ); }
        $applied_coupons = WC()->cart ? WC()->cart->get_applied_coupons() : [];

        ob_start();
        echo '<div class="wcsc-container wcsc-style-' . esc_attr( $style ) . '">';
        
        if ( $style === 'slider' ) echo '<button class="wcsc-nav wcsc-prev">&lsaquo;</button><div class="wcsc-slider-track">';

        foreach ( $coupons as $coupon_post ) {
            $coupon = new WC_Coupon( $coupon_post->post_title );
            if ( $coupon->get_date_expires() && $coupon->get_date_expires()->getTimestamp() < time() ) continue;
            if ( ! $this->check_visibility( $coupon, $prod_id ) ) continue;

            $code = strtoupper( $coupon->get_code() );
            $expiry = $coupon->get_date_expires() ? $coupon->get_date_expires()->getTimestamp() : 0;
            $min_spend = $coupon->get_minimum_amount();
            $cart_total = WC()->cart ? WC()->cart->get_subtotal() : 0;
            
            $is_applied = in_array( strtolower($code), array_map('strtolower', $applied_coupons) );
            $is_eligible = ( $min_spend == 0 || $cart_total >= $min_spend );
            $missing = $min_spend - $cart_total;
            $pct = $min_spend > 0 ? min(100, ($cart_total / $min_spend) * 100) : 100;
            
            $usage_limit = $coupon->get_usage_limit();
            $scarcity_text = ($usage_limit > 0 && ($usage_limit - $coupon->get_usage_count()) < 50) ? 'High Demand' : '';

            $amount = $coupon->get_amount();
            $label  = $coupon->get_discount_type() === 'percent' ? "$amount% OFF" : wc_price($amount) . " OFF";
            
            include WCSC_PATH . 'templates/coupon-card.php';
        }

        if ( $style === 'slider' ) echo '</div><button class="wcsc-nav wcsc-next">&rsaquo;</button>';
        echo '</div>';
        $html = ob_get_clean();

        wp_send_json_success( [ 'html' => $html ] );
    }
    
    private function check_visibility($coupon, $pid) { return true; }
}