#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)";cd "$ROOT"
if [[ ! -f local/.env ]]; then echo 'Run ./scripts/start-mac.sh first';exit 1;fi
WP=(docker compose --env-file local/.env -f local/compose.yml run --rm wpcli)
if ! "${WP[@]}" core is-installed >/dev/null 2>&1;then echo 'Finish WordPress installer in browser first, then rerun.'; exit 1; fi
"${WP[@]}" theme activate bangkok-guru
"${WP[@]}" plugin activate bangkok-guru-bookings
"${WP[@]}" rewrite flush
if [[ "${BGX_INSTALL_WOO:-0}" == "1" ]];then "${WP[@]}" plugin install woocommerce --activate;fi
echo 'Theme + independent Booking plugin activated. Import demos via Appearance → Bangkok Guru Setup.'
echo 'Customer dashboard: /my-bookings/   Search: /find-trips/'
