=== WC Smart Coupons & Analytics ===
Contributors: raviraj
Tags: woocommerce, coupons, discount, analytics, gamification
Requires at least: 5.8
Tested up to: 6.4
Stable tag: 2.0.0
License: GPLv2 or later

Dynamic coupon display with gamified progress bars, premium glassmorphism UI, and deep analytics.

== Description ==

Boost your WooCommerce conversion rates by displaying available coupons directly on Product and Cart pages. 

**Features:**
* **Dynamic Display:** Automatically lists active coupons.
* **Gamification:** Shows "Add $X more to unlock" progress bars to increase average order value.
* **Analytics:** Track exactly which coupons are Viewed vs Copied with a custom dashboard.
* **Styles:** Choose from Grid, Slider, List, or Glassmorphism templates.
* **AJAX Support:** Fully compatible with Mini-Cart drawers and AJAX checkout.

== Installation ==

1. Upload `wc-smart-coupons` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Smart Coupons** in the dashboard sidebar to configure settings.

== Frequently Asked Questions ==

= Does this work with Elementor? =
Yes, use the shortcode `[wc_smart_coupons]` anywhere.

= Where is the data stored? =
We use a custom lightweight table `wp_wcsc_analytics` to ensure your site remains fast.

== Screenshots ==

1. Admin Dashboard with Analytics.
2. Frontend Coupon Grid with Progress Bar.
3. Glassmorphism Style.

== Changelog ==

= 2.0.0 =
* Production release.
* Added CSV Export security hardening.
* Added Data Purge tools.
* Added Confetti animation.