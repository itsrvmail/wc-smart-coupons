<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }

// 1. Delete Options
delete_option( 'wcsc_enable_plugin' );
delete_option( 'wcsc_display_style' );
delete_option( 'wcsc_product_hook' );
delete_option( 'wcsc_cart_hook' );

// 2. Drop Custom Table
global $wpdb;
$table_name = $wpdb->prefix . 'wcsc_analytics';
$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

// 3. Clear Transients
delete_transient( 'wcsc_active_coupons' );