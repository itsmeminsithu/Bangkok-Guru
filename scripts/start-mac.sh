#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
if ! command -v docker >/dev/null; then echo 'Docker Desktop is required: https://www.docker.com/products/docker-desktop/'; exit 1; fi
if ! docker info >/dev/null 2>&1; then echo 'Docker engine is not running. On Mac: open -a Docker'; exit 1; fi
if [[ ! -f local/.env ]]; then cp local/.env.example local/.env; echo 'Created local/.env — development-only credentials. Never use these in production.'; fi
docker compose --env-file local/.env -f local/compose.yml up -d db wordpress mailpit
PORT="$(grep '^WORDPRESS_PORT=' local/.env | cut -d= -f2)";PORT="${PORT:-8188}"
MAIL="$(grep '^MAILPIT_PORT=' local/.env | cut -d= -f2)";MAIL="${MAIL:-8189}"
echo "WordPress: http://localhost:$PORT"
echo "Test email inbox: http://localhost:$MAIL"
echo 'On first launch, complete WordPress setup in your browser, then run ./scripts/enable-theme.sh'
