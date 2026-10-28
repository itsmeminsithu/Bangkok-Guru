# WooCommerce Sandbox Acceptance Plan — human-run tests

The provided smoke script runs **real WooCommerce PHP order objects** if the WooCommerce plugin is installed locally, but manually marks local orders paid. It is **not a connection to any payment service**. Actual Stripe/PayPal sandbox tests require a separately installed gateway in test mode and your own test credentials.

## Isolated local demo

`./scripts/start-mac.sh` → finish WordPress installer → `BGX_INSTALL_WOO=1 ./scripts/enable-theme.sh` → `./scripts/sandbox-check.sh`. Track output `PASS` lines. Local mail should appear at `http://localhost:8189`.

## Gateway test checklist (real sandbox)

- Enable WooCommerce test/sandbox mode for chosen payment provider; use **test keys only**. Never paste credentials into this project or GitHub.
- Set correct store currency and create a future date trip with 3 seats, price, deposit %, balance days, early-bird terms.
- Deposit checkout success; reconcile request status `deposit_paid`, registered order ID, amount, currency, and reserved seats.
- Send balance invoice, complete test payment, reconcile `paid`; simulate duplicate webhook, verify idempotence.
- Full checkout success and a declined/failed card; ensure only the paid path confirms.
- Two customers try the last seat concurrently; no double booking; full departure offers waiting list.
- Expired unpaid checkout cleanup after scheduled job; verify seat release and no false confirmations.
- Delayed settlement after expiry and refund/chargeback: manual review; no automatic seat release without reconciliation.
- Verify receipt email and admin request history, order IDs and webhooks logged without full customer PII.
- Test accounts with guest/non-admin roles cannot see another booking or impersonate a verified reviewer.
- Repeat with HPOS **enabled and disabled** if your deployment supports both.

Never enable live money until these are verified on a staging environment with the same gateway and server configuration as production.
