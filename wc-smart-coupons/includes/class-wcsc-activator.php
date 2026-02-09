<?php
class WCSC_Activator {
    public static function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wcsc_analytics';
        $charset_collate = $wpdb->get_charset_collate();

        // Added 'device_type' column
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            coupon_code varchar(50) NOT NULL,
            event_type varchar(20) NOT NULL,
            user_id mediumint(9) DEFAULT 0,
            page_url varchar(255) DEFAULT '',
            device_type varchar(20) DEFAULT 'desktop',
            created_at datetime DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (id),
            KEY coupon_code (coupon_code),
            KEY event_type (event_type)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
}