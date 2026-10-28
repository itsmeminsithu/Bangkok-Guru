# Bangkok Guru v0.12 — Security & migration notes

## Historical data

Existing `bg_journey`, `bg_destination`, `bg_experience`, `bg_request` post types, `_bg_...` metadata and `${wpdb->prefix}bg_inventory` are **preserved**. Do not uninstall the old theme first; update v0.12 theme, then activate the plugin. If you switch to a different theme, use the plugin shortcodes for account and booking functionality.

### Recommended site upgrade order

1. Take tested backup snapshots of database + wp-content and staging clone.
2. Make the site read-only or stop accepting new bookings during upgrade to avoid parallel booking table writes.
3. Update the Bangkok Guru theme to 0.12, then activate Booking plugin 0.12. Never have old theme booking functions and new plugin active concurrently.
4. Check post types and existing inventory rows, booking references, old deposits, saved orders and hold expiry; compare counts before and after.
5. Confirm WooCommerce HPOS mode and order IDs, permalinks, email, scheduled jobs, and payment-callback signatures in the *gateway plugin*.
6. Reopen new bookings only after inventory and payment reconciliation.

## Booking and payment trust boundaries

The browser cannot set prices, balances, capacity or payment status. Always recalculate from server-side trip meta. Inventory claim uses a single conditional SQL update. All WooCommerce callbacks verify registered order ID, stage, amount, currency, and paid status. Expired/released seats never auto-confirm. Refunds go to manual reconciliation. WP-Cron depends on traffic; deploy a real scheduled worker for at-most-five-minute expiry checks.

## Public input defenses

Sanitization and output escaping, WP nonces, capabilities on admin actions, honeypot and rate limits, private customer record access by login or verified email token. Guest booking lookup hides existence of booking. Email access tokens are SHA-256 hashed at rest, valid for 15 minutes and sent only to the booking email. Host the public site on HTTPS and never expose full tokens in analytics or logs; configure `Referrer-Policy: no-referrer` and exclude dashboard lookup URLs from CDN caches.

## Reviews

Only paid/confirmed, already departed authenticated bookings qualify. Submitted reviews are pending manual moderation and linked to a booking record; ratings are shown only on published verified reviews. Prevent self-service edits of author/verified meta; admins should moderate spam and personally identifying data before publishing.

## Important remaining risks

- No exhaustive production load simulation of truly concurrent payment notifications, stock changes and cancellations.
- No live hosted gateway test for refunds, dispute, authorization/capture or currency rounding and asynchronous 3D-Secure flows.
- Custom theme admin fields and translated content require normal WordPress role restrictions.
- First-party plugin keeps booking data; disabling it will hide custom post types and stop expiry scheduler (data is not erased).
- Database writes by a second booking system or manual direct SQL may violate capacity. Only use one active booking engine.
- Search inventory is pre-filtered with a bounded result set; very large catalogs should move to a dedicated indexed search service.
- Language switcher translates UI and selected content fields, not all customizer text and not automatic localization of images/hreflang.
- Local test mail is trapped in Mailpit and not delivered; stage real SMTP before production.

Do not use real personal/card details during development.
