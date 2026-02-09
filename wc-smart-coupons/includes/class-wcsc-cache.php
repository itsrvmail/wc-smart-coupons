<?php
class WCSC_Cache {
    public static function get_active_coupons() {
        $coupons = get_transient( 'wcsc_active_coupons' );
        if ( false === $coupons ) {
            $args = [ 'post_type' => 'shop_coupon', 'posts_per_page' => 10, 'post_status' => 'publish' ];
            $coupons = get_posts( $args );
            set_transient( 'wcsc_active_coupons', $coupons, 12 * HOUR_IN_SECONDS );
        }
        return $coupons;
    }
    public static function clear_coupon_cache() { delete_transient( 'wcsc_active_coupons' ); }
}