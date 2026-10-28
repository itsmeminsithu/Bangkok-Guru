# Changelog

## [0.12.0] — 2026-10-29

### Architecture
- Travel content, inventory, public forms, booking lifecycle, payments and privacy handlers moved from WordPress theme into independent `bangkok-guru-bookings` plugin.
- Existing custom post type names, request references and inventory table preserved for migration.
- Theme now focuses on page rendering, Elementor, design tokens and SEO; inactive plugin produces graceful fallback.

### Features
- Cross-content tours search with departure date range, available seats, destination and budget.
- Authenticated customer dashboard and 15-minute private email-link guest lookup.
- Thai, English, Myanmar language UI and localized per-trip fields editable in admin.
- Booking-verified reviews after completed trips with manual admin approval and aggregate rating schema.
- Docker-powered Mac preview on port 8188, optional WooCommerce + Mailpit preview inbox on 8189.
- Local-only mocked gateway order smoke script for deposit/balance, plus external gateway acceptance checklist.

### Security
- Upgrade guard against v0.11 theme/plugin duplicate declarations.
- Authenticated booking lookup, short-lived token hashes, nonce/capability checks and explicit local sandbox guard.
- No auto-seat-release on refund; financial state verification retains v0.11 hardening.

### Known limitations
- Actual WooCommerce payment-gateway integration, live card test and high-concurrency tests require staging.
- Translations cover new UI and custom trip fields; not automatically all legacy content.

## v0.13.0 — Guided Demo Site Import
- Activation welcome prompt (Yes/No) and opt-in setup wizard for four homepage layouts.
- 18 high-detail editable sample entries, 5 editable pages, 3 editorial posts, and 12 local original illustrated image covers.
- Idempotent import: existing entries and personal edits are preserved; older untouched placeholder samples are upgraded in place.
- Example departure dates, prices and seats remain **non-bookable** via both frontend and server enforcement until an operator explicitly reviews and enables a live trip.
- Footer clearly credits Min SiThu (itsmeminsithu) and minsithu.org.
- WordPress plugin auto-install (with explicit administrator permission) from bundled local plugin ZIP; WordPress media and navigation setup.
- Terminal-friendly local demo command, privacy/publishing checklist, demo images and new screenshot preview.
