# Bangkok Guru v0.13 — Theme + independent Booking Plugin

**Developer:** Min SiThu (itsmeminsithu)  
**Website:** https://minsithu.org  
**License:** GPL-2.0-or-later for original source code (see individual packages).  
**Status:** Developer / user-testing beta. Not certified for live payment processing.

## Live preview (static, not WordPress)

WordPress itself cannot run on GitHub Pages, so the live preview is a static showcase of this design:

**https://itsmeminsithu.github.io/Bangkok-Guru/**

![Home hero](docs/preview/screenshots/01-home-hero.png)
![Find your next journey](docs/preview/screenshots/02-find-journey.png)
![Places we love](docs/preview/screenshots/03-places-we-love.png)
![Footer](docs/preview/screenshots/04-footer.png)

## WordPress release packages (for WordPress users)

Download these two files from the [v0.13.0 release](https://github.com/itsmeminsithu/Bangkok-Guru/releases/tag/v0.13.0), then upload them in WordPress admin — no Git, no code:

1. **Theme:** `Bangkok-Guru-WordPress-Theme-v0.13.0.zip` — Appearance → Themes → Add New → Upload Theme → Activate
2. **Plugin:** `Bangkok-Guru-Bookings-Plugin-v0.13.0.zip` — Plugins → Add New → Upload Plugin → Activate (activate the theme first, then the plugin)

After activation, follow the demo import prompt in the theme to import the demo site, or use Appearance → Bangkok Guru Setup. The full source in this repo is for developers; WordPress users only need the two release files above.


## Source folders

- `wordpress-theme/bangkok-guru/` — independent WordPress theme, 4 home layouts, responsive Royal Blue/Black/White palette, custom logo, Elementor widgets, day/night, RTL and SEO basics.
- `wordpress-plugin/bangkok-guru-bookings/` — portable content types, inventory, booking/waitlist/enquiry, WooCommerce integration, date-and-seat search, customer dashboard, verified reviews, Thai/English/Myanmar public UI.
- `local/` + `scripts/` — one-command local development with Docker, WordPress, MariaDB, WP-CLI and Mailpit. Uses **8188** for site, **8189** for test email; does not occupy 3000/3100.
- `tests/` — mocked PHP state tests, source validation and local WooCommerce integration smoke (no real funds).
- `docs/` — migration, sandbox payment checklist, privacy/security notes and release gates.

## Preview on a Mac (Terminal)

1. Install Docker Desktop and open it (`open -a Docker`); ensure Engine Running.
2. Unzip the **GitHub Source** archive to `~/Downloads/Bangkok-Guru-v0.13` (or any folder).
3. Run:

```bash
cd ~/Downloads/Bangkok-Guru-v0.13
chmod +x scripts/*.sh
./scripts/start-mac.sh
```

4. Open **http://localhost:8188**. Complete WordPress's first-run installer (your chosen admin credentials).
5. Run:

```bash
./scripts/enable-theme.sh
```

6. In WordPress admin: **Appearance → Bangkok Guru Setup → Create four demo homepages**. You can then select your preferred homepage. Tour demos are **not bookable** until you configure price/date/seats.
7. For guest booking link emails: open **http://localhost:8189** (Mailpit). Emails are trapped locally and are not actually delivered.
8. Optional WooCommerce integration in this isolated local environment:

```bash
BGX_INSTALL_WOO=1 ./scripts/enable-theme.sh
./scripts/sandbox-check.sh
```

To stop without deleting your database: `./scripts/stop-mac.sh`. Docker may need first-time network downloads. Localhost on your Mac is **not** public hosting; it is not available from any device on the internet.

## WordPress installation on an existing site

**Migration order matters.** First back up both database and `wp-content`. Upgrade the active Bangkok Guru **theme to v0.13 first**, then install/activate **Bangkok Guru Bookings plugin v0.13**. Activating the plugin alongside an active old v0.11 theme is blocked to prevent duplicate function names. Existing `bg_*` content and `wp_bg_inventory` table are reused, not renamed or deleted.

- Appearance → Themes → Upload `Bangkok-Guru-WordPress-Theme-v0.13.0.zip` and activate/update.
- Plugins → Add New → Upload `Bangkok-Guru-Bookings-Plugin-v0.13.0.zip` and activate.
- Settings → Permalinks → Save Changes.
- Pages `My Bookings` and `Find Trips` are auto-created if absent.
- Tour content and requests remain in the database independently of future theme switches **as long as the Booking plugin remains active**. The alternative theme must still provide templates/styles or use the plugin shortcodes.
- Install WooCommerce **separately** only if using paid checkout. Use its sandbox gateway first, then test the full workflow with realistic refunds and late events.

## Features

### Four homepage styles
Curated Travel · Bright Day Experiences · Immersive Destinations · Next Departures. Editable from admin; Elementor widgets retain their design controls.

### Booking and Payments
Atomic seat holds, availability, waiting list, unique references, prices, early bird, deposit %, due date, balance invoice. WooCommerce uses `WC_Order` APIs for HPOS compatibility. Financial-state changes verify order ID, paid amount and currency. Refund/late/ambiguous scenarios go to manual review. WP-Cron requires a real scheduler on production for timely hold expiry.

### Search and customer access
Archives can filter keyword, place, budget, departure from/to dates, travellers, and seats available. The `[bangkok_guru_trip_search]` shortcode searches across all travel types. The `[bangkok_guru_my_bookings]` dashboard allows logged-in users to see their own bookings; guest users can receive a **15-minute, email-verified private access link**, and cannot enumerate bookings by guessing a public reference.

### Thai, English and Myanmar
Public booking/search/dashboard/review UI has a simple locale switcher and locale-specific fields editable on each trip. The default English content is retained when translations are missing. **Not every legacy theme demo sentence is translated;** translate homepage/footer content and other static Customizer fields separately, or use a dedicated multilingual plugin if you need complete multi-language WordPress site management and SEO hreflang. This feature does not translate user-entered text automatically.

### Verified reviews
Only authenticated users whose matching trip is completed and has status `paid` or `confirmed` can submit a review. One review per booking. Reviews remain **pending admin moderation**; public reviews and ratings include approved records only. A user with a guest booking can use the private lookup but must link their booking to an authenticated account via an admin-approved verification process before submitting a review. Schema ratings do not guarantee Google rich results.

### Elementor, Accessibility, SEO
Existing custom Elementor widgets with Style tabs, custom logo, 8 global colors, RTL-ready stylesheet, conditional styles, accessible focus states, featured image lazy loading, SEO metadata and structured tour information. Install one SEO plugin if you need multilingual sitemaps/canonical/hreflang at scale.

## Limitations and security

- Cannot certify Stripe/PayPal or other gateway without genuine sandbox credentials and an end-to-end running WooCommerce site.
- Local payments in `tests/woo-local-smoke.php` use `payment_complete()` on isolated local orders **for testing only; no real card is charged**.
- True high-concurrency booking, chargebacks and external gateway late-webhook behavior require staging load tests.
- Real SMTP delivery, email privacy and regional regulation depend on production host configuration.
- This package **does not embed commercial theme code** from Veya; design is original, reference only.

See `docs/RELEASE_GATES.md` and `docs/SECURITY_AND_MIGRATION.md` before accepting live bookings.

## v0.13 One-click opt-in Demo Website

On activation, Appearance → Bangkok Guru Setup prompts **Yes, install my demo website / No thanks**. Four homepages, 18 editorial travel entries (6+6+6), original travel illustrations, FAQ/About/Contact/Travel Journal and example articles are available. Existing content is not overwritten on rerun. Prices, dates and places are illustrative; demo bookings are explicitly blocked in UI and on the booking submission server-side.

Run from Mac terminal after `start-mac.sh` and `enable-theme.sh`:

```bash
./scripts/import-demo.sh travel
# or daylight / immersive / departures
```

See docs/DEMO_SETUP_MY.md and docs/DEMO_PUBLISH_CHECKLIST.md. Local Docker bind mounts are intentionally read-only; don't try to update the mounted theme from WordPress Dashboard. Customer-facing external previews require properly configured HTTPS hosting.
