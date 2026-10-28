#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)";cd "$ROOT"
docker compose --env-file local/.env -f local/compose.yml down
echo 'Stopped containers; database and site data retained.'
