#!/usr/bin/env bash
# Explicit opt-in import; not run on startup or activation automatically.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"; cd "$ROOT"
STYLE="${1:-travel}"
case "$STYLE" in travel|daylight|immersive|departures) ;; *) echo 'Usage: ./scripts/import-demo.sh [travel|daylight|immersive|departures]';exit 2;; esac
if [[ ! -f local/.env ]]; then echo 'Start the local stack first: ./scripts/start-mac.sh';exit 1;fi
WP=(docker compose --env-file local/.env -f local/compose.yml run --rm wpcli)
"${WP[@]}" core is-installed >/dev/null || { echo 'Complete the WordPress installation in the browser.';exit 1; }
"${WP[@]}" theme activate bangkok-guru
"${WP[@]}" plugin activate bangkok-guru-bookings
"${WP[@]}" eval "\$result=bg_demo_seed_site('${STYLE}',true); if(is_wp_error(\$result)){WP_CLI::error(\$result->get_error_message());} WP_CLI::success('Demo website imported: homepage ${STYLE}');"
"${WP[@]}" rewrite flush
printf '\nPreview on http://localhost:8188\nAdmin: http://localhost:8188/wp-admin/\n'
