#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)";cd "$ROOT"
WP=(docker compose --env-file local/.env -f local/compose.yml run --rm wpcli)
"${WP[@]}" plugin is-active woocommerce >/dev/null || { echo 'WooCommerce is not active. Install with: BGX_INSTALL_WOO=1 ./scripts/enable-theme.sh';exit 1; }
"${WP[@]}" eval-file /bg-tests/woo-local-smoke.php
