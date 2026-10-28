# Bangkok Guru WordPress Theme — 0.12.0

Developed by Min SiThu (itsmeminsithu), https://minsithu.org. Original Veya-inspired, independently written theme.

**Important:** Booking, Tours, Requests, WooCommerce handlers, customer dashboard, reviews and multilingual entry fields are now delivered by the independent **Bangkok Guru Bookings** plugin (separate ZIP). This theme can show public pages without it but **booking features are disabled**.

When upgrading from v0.11: backup database and wp-content; install/update v0.12 **theme first**, then activate v0.12 **Bookings plugin**. Never load v0.11 booking functions together with the new plugin.

Appearance → Bangkok Guru Setup creates four sample homepages. Edit original content in Gutenberg, each entry's meta fields or Elementor. Custom Logo and design tokens are configured in WordPress Customizer. All sample tours have no priced departure inventory until explicitly configured.

Dashboard entry: WordPress Admin → Journeys / Destinations / Day Experiences (plugin required). Shortcodes include `[bangkok_guru_my_bookings]`, `[bangkok_guru_trip_search]`, `[bangkok_guru_enquiry]`, `[bangkok_guru_reviews id="123"]`.

WordPress 6.3+ / PHP 8.0+. WooCommerce and Elementor are optional; live payments need actual gateway sandbox testing.
