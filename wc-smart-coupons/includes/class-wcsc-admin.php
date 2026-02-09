<?php
class WCSC_Admin {
    public function add_plugin_admin_menu() {
        add_menu_page( 'Smart Coupons', 'Smart Coupons', 'manage_options', 'wcsc-dashboard', [ $this, 'display_dashboard' ], 'dashicons-chart-pie', 56 );
    }

    public function enqueue_styles() {
        if ( isset( $_GET['page'] ) && $_GET['page'] === 'wcsc-dashboard' ) {
            wp_enqueue_style( 'wp-color-picker' );
            wp_enqueue_script( 'wcsc-admin-js', WCSC_URL . 'assets/js/admin.js', ['jquery', 'chart-js', 'wp-color-picker'], WCSC_VERSION, true );
            wp_enqueue_style( 'wcsc-admin-css', WCSC_URL . 'assets/css/admin.css', [], WCSC_VERSION );
            wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.9.1', true );
        }
    }

    public function display_dashboard() {
        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'analytics';
        ?>
        <div class="wrap wcsc-wrap">
            <h1 class="wp-heading-inline">Smart Coupons Manager <span class="version-badge">v<?php echo WCSC_VERSION; ?></span></h1>
            <nav class="nav-tab-wrapper">
                <a href="?page=wcsc-dashboard&tab=analytics" class="nav-tab <?php echo $active_tab == 'analytics' ? 'nav-tab-active' : ''; ?>">Analytics</a>
                <a href="?page=wcsc-dashboard&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
                <a href="?page=wcsc-dashboard&tab=tools" class="nav-tab <?php echo $active_tab == 'tools' ? 'nav-tab-active' : ''; ?>">Tools</a>
            </nav>
            <div class="wcsc-content">
                <?php 
                if ( $active_tab === 'analytics' ) { $this->render_analytics_tab(); } 
                elseif ( $active_tab === 'settings' ) { $this->render_settings_tab(); } 
                else { $this->render_tools_tab(); }
                ?>
            </div>
        </div>
        <?php
    }

    private function render_analytics_tab() {
        $start = isset($_GET['start']) ? sanitize_text_field($_GET['start']) : date('Y-m-d', strtotime('-30 days'));
        $end = isset($_GET['end']) ? sanitize_text_field($_GET['end']) : date('Y-m-d');
        
        $data = WCSC_Analytics::get_chart_data($start, $end);
        $top = WCSC_Analytics::get_top_coupons($start, $end);
        $recent_users = WCSC_Analytics::get_recent_users();
        
        $views = array_sum($data['views']);
        $copies = array_sum($data['copies']);
        $rate = $views > 0 ? round(($copies/$views)*100, 1) : 0;
        ?>
        <script>var wcscChartData = <?php echo json_encode($data); ?>;</script>

        <div class="wcsc-filter-bar">
            <div class="filter-left">
                <h3>Overview</h3>
                <span class="date-badge"><?php echo esc_html($start . ' to ' . $end); ?></span>
            </div>
            <form method="get" class="filter-right">
                <input type="hidden" name="page" value="wcsc-dashboard"><input type="hidden" name="tab" value="analytics">
                <input type="date" name="start" value="<?php echo esc_attr($start); ?>"> 
                <span class="sep">&rarr;</span> 
                <input type="date" name="end" value="<?php echo esc_attr($end); ?>">
                <button type="submit" class="button button-secondary">Update</button>
            </form>
        </div>

        <div class="wcsc-metrics-row">
            <div class="wcsc-metric-card blue">
                <div class="metric-icon"><span class="dashicons dashicons-visibility"></span></div>
                <div class="metric-data">
                    <span class="metric-label">Total Impressions</span>
                    <span class="metric-value"><?php echo number_format($views); ?></span>
                </div>
            </div>
            <div class="wcsc-metric-card green">
                <div class="metric-icon"><span class="dashicons dashicons-tickets-alt"></span></div>
                <div class="metric-data">
                    <span class="metric-label">Coupons Copied</span>
                    <span class="metric-value"><?php echo number_format($copies); ?></span>
                </div>
            </div>
            <div class="wcsc-metric-card orange">
                <div class="metric-icon"><span class="dashicons dashicons-chart-line"></span></div>
                <div class="metric-data">
                    <span class="metric-label">Conversion Rate</span>
                    <span class="metric-value"><?php echo $rate; ?>%</span>
                </div>
            </div>
        </div>

        <div class="wcsc-dashboard-grid">
            <div class="wcsc-card full-width">
                <h3>Engagement Trend</h3>
                <div class="chart-container"><canvas id="wcscMainChart"></canvas></div>
            </div>
            
            <div class="wcsc-card">
                <h3>Event Distribution</h3>
                <div class="chart-container" style="height:220px;"><canvas id="wcscPieChart"></canvas></div>
            </div>

            <div class="wcsc-card">
                <h3>Recent Users</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead><tr><th>User</th><th>Code</th><th>Time</th></tr></thead>
                    <tbody>
                        <?php if($recent_users): foreach($recent_users as $u): 
                            $user_info = get_userdata($u->user_id);
                            $name = $user_info ? $user_info->display_name : 'Guest';
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($name); ?></strong></td>
                            <td><span class="code-pill"><?php echo esc_html($u->coupon_code); ?></span></td>
                            <td><?php echo date('M j, g:i a', strtotime($u->created_at)); ?></td>
                        </tr>
                        <?php endforeach; else: echo '<tr><td colspan="3">No recent activity.</td></tr>'; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    private function render_settings_tab() {
        if ( isset( $_POST['wcsc_save_settings'] ) ) {
            check_admin_referer( 'wcsc_settings_verify' );
            
            // General
            update_option( 'wcsc_enable_plugin', sanitize_text_field( $_POST['wcsc_enable_plugin'] ) );
            update_option( 'wcsc_display_style', sanitize_text_field( $_POST['wcsc_display_style'] ) );
            
            // Visibility (Preserved)
            update_option( 'wcsc_show_product', isset($_POST['wcsc_show_product']) ? 'yes' : 'no' );
            update_option( 'wcsc_show_cart', isset($_POST['wcsc_show_cart']) ? 'yes' : 'no' );
            update_option( 'wcsc_show_checkout', isset($_POST['wcsc_show_checkout']) ? 'yes' : 'no' );
            update_option( 'wcsc_show_minicart', isset($_POST['wcsc_show_minicart']) ? 'yes' : 'no' );

            // Hooks
            update_option( 'wcsc_product_hook', sanitize_text_field( $_POST['wcsc_product_hook'] ) );
            update_option( 'wcsc_cart_hook', sanitize_text_field( $_POST['wcsc_cart_hook'] ) );

            // Slider Advanced Options
            update_option( 'wcsc_slider_speed', sanitize_text_field( $_POST['wcsc_slider_speed'] ) );
            update_option( 'wcsc_slider_autoplay', isset($_POST['wcsc_slider_autoplay']) ? 'yes' : 'no' );
            update_option( 'wcsc_slider_dots', isset($_POST['wcsc_slider_dots']) ? 'yes' : 'no' );
            update_option( 'wcsc_slider_arrows', isset($_POST['wcsc_slider_arrows']) ? 'yes' : 'no' );
            update_option( 'wcsc_slider_card_width', sanitize_text_field( $_POST['wcsc_slider_card_width'] ) );
            update_option( 'wcsc_slider_gap', sanitize_text_field( $_POST['wcsc_slider_gap'] ) );
            
            // Colors & Templates
            update_option( 'wcsc_glass_blur', sanitize_text_field( $_POST['wcsc_glass_blur'] ) );
            update_option( 'wcsc_glass_opacity', sanitize_text_field( $_POST['wcsc_glass_opacity'] ) );
            update_option( 'wcsc_tag_hole', sanitize_text_field( $_POST['wcsc_tag_hole'] ) );
            update_option( 'wcsc_neumorph_radius', sanitize_text_field( $_POST['wcsc_neumorph_radius'] ) );
            update_option( 'wcsc_neon_color', sanitize_hex_color( $_POST['wcsc_neon_color'] ) );
            
            update_option( 'wcsc_color_primary', sanitize_hex_color( $_POST['wcsc_color_primary'] ) );
            update_option( 'wcsc_color_text', sanitize_hex_color( $_POST['wcsc_color_text'] ) );
            update_option( 'wcsc_color_bg', sanitize_hex_color( $_POST['wcsc_color_bg'] ) );
            
            echo '<div class="notice notice-success inline"><p>Settings Saved.</p></div>';
        }
        $style = get_option('wcsc_display_style', 'grid');
        ?>
        <div class="wcsc-card full-width">
            <form method="post" action="">
                <?php wp_nonce_field( 'wcsc_settings_verify' ); ?>
                <table class="form-table">
                    <tr><th scope="row">Visual Template</th>
                        <td>
                            <select name="wcsc_display_style" id="wcsc_style_selector" class="regular-text">
                                <optgroup label="Standard">
                                    <option value="grid" <?php selected( $style, 'grid' ); ?>>Standard Grid</option>
                                    <option value="slider" <?php selected( $style, 'slider' ); ?>>Slider Pro (Carousel)</option>
                                </optgroup>
                                <optgroup label="Compact">
                                    <option value="list" <?php selected( $style, 'list' ); ?>>Compact List</option>
                                    <option value="cyber_ticket" <?php selected( $style, 'cyber_ticket' ); ?>>Cyber Ticket</option>
                                    <option value="pill" <?php selected( $style, 'pill' ); ?>>Minimal Pill</option>
                                </optgroup>
                                <optgroup label="Premium">
                                    <option value="tag" <?php selected( $style, 'tag' ); ?>>Physical Tag</option>
                                    <option value="product_tag" <?php selected( $style, 'product_tag' ); ?>>Geometric Label</option>
                                    <option value="glass" <?php selected( $style, 'glass' ); ?>>Glassmorphism</option>
                                    <option value="neumorph" <?php selected( $style, 'neumorph' ); ?>>Neumorphism</option>
                                    <option value="neon" <?php selected( $style, 'neon' ); ?>>Neon Gradient</option>
                                </optgroup>
                            </select>
                        </td>
                    </tr>

                    <tr class="wcsc-adv-setting wcsc-setting-slider" style="display:none; background:#f9f9f9; border-left: 4px solid #007cba;">
                        <th scope="row">Slider Config</th>
                        <td>
                            <p><strong>Behavior:</strong></p>
                            <label><input type="checkbox" name="wcsc_slider_autoplay" value="yes" <?php checked(get_option('wcsc_slider_autoplay','yes'),'yes'); ?>> Autoplay</label> &nbsp;
                            <label><input type="checkbox" name="wcsc_slider_arrows" value="yes" <?php checked(get_option('wcsc_slider_arrows','yes'),'yes'); ?>> Show Arrows</label> &nbsp;
                            <label><input type="checkbox" name="wcsc_slider_dots" value="yes" <?php checked(get_option('wcsc_slider_dots','yes'),'yes'); ?>> Show Dots</label>
                            
                            <p><strong>View Mode:</strong></p>
                            <label>
                                <select name="wcsc_slider_card_width">
                                    <option value="280px" <?php selected(get_option('wcsc_slider_card_width'),'280px'); ?>>Fixed Card (280px)</option>
                                    <option value="100%" <?php selected(get_option('wcsc_slider_card_width'),'100%'); ?>>One Slide (100% Full)</option>
                                    <option value="80%" <?php selected(get_option('wcsc_slider_card_width'),'80%'); ?>>Peek View (80%)</option>
                                    <option value="50%" <?php selected(get_option('wcsc_slider_card_width'),'50%'); ?>>Two Column (50%)</option>
                                </select>
                            </label>
                            <br><br>
                            <label>Gap (px): <input type="number" name="wcsc_slider_gap" value="<?php echo esc_attr(get_option('wcsc_slider_gap','20')); ?>" class="small-text"></label> &nbsp;
                            <label>Speed (ms): <input type="number" name="wcsc_slider_speed" value="<?php echo esc_attr(get_option('wcsc_slider_speed','3000')); ?>" class="small-text"></label>
                        </td>
                    </tr>

                    <tr class="wcsc-adv-setting wcsc-setting-glass" style="display:none; background:#f9f9f9;">
                        <th scope="row">Glass Config</th>
                        <td><label>Blur (px): <input type="number" name="wcsc_glass_blur" value="<?php echo esc_attr(get_option('wcsc_glass_blur','10')); ?>" class="small-text"></label></td>
                    </tr>
                    <tr class="wcsc-adv-setting wcsc-setting-neumorph" style="display:none; background:#f9f9f9;">
                        <th scope="row">Neumorph Config</th>
                        <td><label>Radius (px): <input type="number" name="wcsc_neumorph_radius" value="<?php echo esc_attr(get_option('wcsc_neumorph_radius','15')); ?>" class="small-text"></label></td>
                    </tr>
                    <tr class="wcsc-adv-setting wcsc-setting-neon" style="display:none; background:#f9f9f9;">
                        <th scope="row">Neon Config</th>
                        <td><label>Color: <input type="text" name="wcsc_neon_color" value="<?php echo esc_attr(get_option('wcsc_neon_color','#00f3ff')); ?>" class="wcsc-color-field"></label></td>
                    </tr>

                    <tr><th scope="row">Show Coupons On</th>
                        <td>
                            <fieldset>
                                <label><input type="checkbox" name="wcsc_show_product" value="yes" <?php checked(get_option('wcsc_show_product','yes'),'yes'); ?>> Product Page</label> &nbsp;
                                <label><input type="checkbox" name="wcsc_show_cart" value="yes" <?php checked(get_option('wcsc_show_cart','yes'),'yes'); ?>> Cart Page</label> &nbsp;
                                <label><input type="checkbox" name="wcsc_show_checkout" value="yes" <?php checked(get_option('wcsc_show_checkout','yes'),'yes'); ?>> Checkout Page</label> &nbsp;
                                <label><input type="checkbox" name="wcsc_show_minicart" value="yes" <?php checked(get_option('wcsc_show_minicart','yes'),'yes'); ?>> Mini Cart / Drawer</label>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr><th scope="row">Position Hooks</th>
                        <td>
                            <p>Product: <select name="wcsc_product_hook"><option value="woocommerce_single_product_summary" <?php selected(get_option('wcsc_product_hook'),'woocommerce_single_product_summary'); ?>>Inside Summary</option><option value="woocommerce_after_single_product_summary" <?php selected(get_option('wcsc_product_hook'),'woocommerce_after_single_product_summary'); ?>>After Summary</option></select></p>
                            <p>Cart: <select name="wcsc_cart_hook"><option value="woocommerce_before_cart" <?php selected(get_option('wcsc_cart_hook'),'woocommerce_before_cart'); ?>>Top</option><option value="woocommerce_cart_collaterals" <?php selected(get_option('wcsc_cart_hook'),'woocommerce_cart_collaterals'); ?>>Collaterals</option></select></p>
                        </td>
                    </tr>

                    <tr><th scope="row">Theme Colors</th>
                        <td>
                            <label>Primary: <input type="text" name="wcsc_color_primary" value="<?php echo esc_attr(get_option('wcsc_color_primary','#333')); ?>" class="wcsc-color-field"></label><br>
                            <label>Accent: <input type="text" name="wcsc_color_text" value="<?php echo esc_attr(get_option('wcsc_color_text','#d63638')); ?>" class="wcsc-color-field"></label><br>
                            <label>Card BG: <input type="text" name="wcsc_color_bg" value="<?php echo esc_attr(get_option('wcsc_color_bg','#fff')); ?>" class="wcsc-color-field"></label>
                        </td>
                    </tr>
                </table>
                <?php submit_button( 'Save Settings', 'primary', 'wcsc_save_settings' ); ?>
            </form>
        </div>
        <script>jQuery(document).ready(function($){ $('.wcsc-color-field').wpColorPicker(); });</script>
        <?php
    }

    private function render_tools_tab() { 
        if ( isset( $_POST['wcsc_purge_data'] ) ) {
            check_admin_referer( 'wcsc_tools_verify' );
            $days = intval( $_POST['wcsc_purge_days'] );
            WCSC_Analytics::purge_data( $days );
            echo '<div class="notice notice-success inline"><p>Data purged.</p></div>';
        }
        ?>
        <div class="wcsc-card full-width">
            <h3>Database Maintenance</h3>
            <p>Delete old analytics data.</p>
            <form method="post" action="" onsubmit="return confirm('Are you sure?');">
                <?php wp_nonce_field( 'wcsc_tools_verify' ); ?>
                <table class="form-table">
                    <tr><th scope="row">Purge Data</th><td><select name="wcsc_purge_days"><option value="30">30 Days</option><option value="90">90 Days</option></select><button type="submit" name="wcsc_purge_data" class="button">Purge</button></td></tr>
                </table>
            </form>
        </div>
        <?php
    }
}