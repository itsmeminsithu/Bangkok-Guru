# Production QA and release gate (mandatory)

- [ ] Fresh WordPress staging theme install/activate with PHP 8+ and MySQL/MariaDB InnoDB table engine.
- [ ] Existing v0.9 booking database back up and migration/rollback dry-run.
- [ ] Manual and browser-automated test of 4 homepages / 3 layouts / Elementor widgets.
- [ ] Light/dark original logo and contrast, mobile/RTL keyboard and touch screen accessibility.
- [ ] WooCommerce/HPOS sandbox: deposit, full, balance, late, duplicate callback, currency mismatch, order refund/failed/chargeback.
- [ ] Concurrent reservation load with real DB: 20 seats, requests 15 + 10, one goes to waiting list.
- [ ] Test admin editing capacity/deleting a booked departure while 10 seats are reserved.
- [ ] Confirm cron reliably releases expired 30/45-minute holds (test no web traffic).
- [ ] SMTP provider + email deliverability with missing/invalid customer addresses.
- [ ] Configure privacy notice, retention/export/erasure for PII and backup policy.
- [ ] Verify Google Search Console, OG preview, sitemap and no duplicate SEO plugin metadata.
- [ ] Lighthouse/Core Web Vitals with representative images, not only demo gradients.
- [ ] Run Theme Check / PHPCS WordPress coding standard and OWASP security tests on staging.
- [ ] Do NOT enable real payments before all critical checks have passed.
