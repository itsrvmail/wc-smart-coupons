<?php
class WCSC_Analytics {
    
    public static function ajax_track_event() {
        check_ajax_referer( 'wcsc_security_nonce', 'nonce' );

        global $wpdb;
        $table_name = $wpdb->prefix . 'wcsc_analytics';

        $code  = sanitize_text_field( $_POST['coupon_code'] );
        $event = sanitize_text_field( $_POST['event_type'] ); 

        $wpdb->insert(
            $table_name,
            [
                'coupon_code' => $code,
                'event_type'  => $event,
                'user_id'     => get_current_user_id(),
                'page_url'    => esc_url_raw( wp_get_referer() ),
                'created_at'  => current_time( 'mysql' )
            ]
        );
        wp_send_json_success();
    }

    public static function get_chart_data( $start_date = '', $end_date = '' ) {
        global $wpdb;
        $table = $wpdb->prefix . 'wcsc_analytics';
        
        if ( empty( $start_date ) ) $start_date = date( 'Y-m-d', strtotime( '-30 days' ) );
        if ( empty( $end_date ) )   $end_date   = date( 'Y-m-d' );

        $sql = $wpdb->prepare(
            "SELECT DATE(created_at) as date, event_type, COUNT(*) as count 
             FROM $table 
             WHERE DATE(created_at) >= %s AND DATE(created_at) <= %s
             GROUP BY DATE(created_at), event_type 
             ORDER BY date ASC",
            $start_date, $end_date
        );
        
        $results = $wpdb->get_results( $sql );
        
        $data = ['labels' => [], 'views' => [], 'copies' => []];
        foreach ($results as $row) {
            $date = date('M j', strtotime($row->date));
            if (!in_array($date, $data['labels'])) $data['labels'][] = $date;
        }
        
        foreach($data['labels'] as $label) {
            $v = 0; $c = 0;
            foreach($results as $row) {
                if(date('M j', strtotime($row->date)) === $label) {
                    if($row->event_type === 'view') $v = $row->count;
                    if($row->event_type === 'copy') $c = $row->count;
                }
            }
            $data['views'][] = $v; $data['copies'][] = $c;
        }
        return $data;
    }

    public static function get_top_coupons( $start_date = '', $end_date = '' ) {
        global $wpdb;
        $table = $wpdb->prefix . 'wcsc_analytics';
        if ( empty( $start_date ) ) $start_date = date( 'Y-m-d', strtotime( '-30 days' ) );
        if ( empty( $end_date ) )   $end_date   = date( 'Y-m-d' );

        $sql = $wpdb->prepare(
            "SELECT coupon_code, 
            SUM(CASE WHEN event_type = 'view' THEN 1 ELSE 0 END) as views,
            SUM(CASE WHEN event_type = 'copy' THEN 1 ELSE 0 END) as copies
            FROM $table WHERE DATE(created_at) >= %s AND DATE(created_at) <= %s
            GROUP BY coupon_code ORDER BY copies DESC LIMIT 5",
            $start_date, $end_date
        );
        return $wpdb->get_results($sql);
    }

    public static function get_recent_activity() {
        global $wpdb;
        $table = $wpdb->prefix . 'wcsc_analytics';
        return $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 10");
    }

    // NEW: Fetch Users who interacted
    public static function get_recent_users() {
        global $wpdb;
        $table = $wpdb->prefix . 'wcsc_analytics';
        // Only get 'copy' events to see real intent
        return $wpdb->get_results("SELECT * FROM $table WHERE event_type='copy' ORDER BY created_at DESC LIMIT 5");
    }

    public static function export_csv() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        header( 'Content-Type: text/csv' );
        header( 'Content-Disposition: attachment; filename="coupon-analytics-' . date('Y-m-d') . '.csv"' );
        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, [ 'ID', 'Coupon Code', 'Event Type', 'User ID', 'Page URL', 'Date' ] );
        global $wpdb;
        $table = $wpdb->prefix . 'wcsc_analytics';
        $rows = $wpdb->get_results( "SELECT * FROM $table ORDER BY created_at DESC", ARRAY_A );
        foreach ( $rows as $row ) {
            foreach($row as $k => $v) {
                if ( preg_match('/^[\=\+\-\@]/', $v) ) { $row[$k] = "'" . $v; }
            }
            fputcsv( $output, $row );
        }
        fclose( $output );
        exit;
    }

    public static function purge_data( $days ) {
        if ( ! current_user_can( 'manage_options' ) ) return;
        global $wpdb;
        $table = $wpdb->prefix . 'wcsc_analytics';
        $days = intval( $days );
        $wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)", $days ) );
    }
}