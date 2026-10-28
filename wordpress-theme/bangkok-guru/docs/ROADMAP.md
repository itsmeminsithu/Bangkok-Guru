# Recommended next priorities

## P0 — required before live payments

**Companion Booking Plugin**: move content types, booking tables, admin list, form handlers and WooCommerce lifecycle code out of theme so switching themes never hides critical bookings. Include DB versioned migrations, WP privacy exporter/eraser and admin activity/event logs; integrate proper refund and ticket inventory reconciliation.

## P1 — revenue & trust

1. Traveller account / secure booking-status lookup (email-verification or signed expiring tokens; do not expose public incremental IDs).
2. Full end-to-end booking funnel with itinerary, extras, per-departure seat policy, transparent cancellation/refund/tax and confirmation email.
3. WooCommerce supported official gateway sandbox suite, currency/tax/invoice rules and SMTP.
4. Optional WAF/Turnstile, dynamic security alerts and daily capacity reconciliation report.

## P2 — business growth

5. Search + filters: destination, dates, duration, group size, availability and price.
6. Multilingual EN/MM/TH content and SEO via WP multilingual plugin + appropriate `hreflang`.
7. Better marketing: optimized authentic photographs, reviews with verified moderation, travel guides and local landing pages (avoid fake ratings).
8. GA4/analytics as opt-in consent-aware integrations, Search Console, conversion funnel reporting.
