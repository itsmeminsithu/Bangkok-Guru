# Technical Notes

**WordPress APIs:** classic PHP templates, `theme.json`, `register_post_type`, Customizer, `admin-post.php`, `wp_mail`, `wp_nonce_field`, optional Elementor `elementor/widgets/register`, optional WooCommerce `wc_create_order`, order status hooks.

**Seats:** `wp_bg_inventory` (using actual `$wpdb->prefix`) holds `entry_id`, `departure`, `capacity`, `reserved`; unique key `(entry_id,departure)`. Inventory reservations use `UPDATE ... SET reserved=reserved+N ... reserved+N<=capacity`. A second conditional `wp_postmeta` update prevents duplicate hold release when two concurrent requests cancel or expire the same reservation.

**Known edge cases:** this is a beta theme, not a full enterprise booking engine. A failed WooCommerce payment may cancel the request; gateway-specific `pending`/`failed` semantics vary. Capacities changed below reserved count require manual admin reconciliation; removing a departure with existing bookings is unsafe. Bookings stored as theme CPT will not appear after switching themes until a compatible plugin or the theme registers them again.

**Do not copy ThemeForest Veya copyrighted assets**. Current theme does not include stock imagery, WP core, Elementor, WooCommerce or commercial typefaces.
