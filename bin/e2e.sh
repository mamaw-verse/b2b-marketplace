#!/usr/bin/env bash

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

BROWSERS_PATH="/var/www/html/storage/app/playwright"
BROWSER="${BROWSER:-chrome}"
ARGS=()

while [[ $# -gt 0 ]]; do
    case "$1" in
        --browser)
            BROWSER="$2"
            shift 2
            ;;
        --browser=*)
            BROWSER="${1#*=}"
            shift
            ;;
        *)
            ARGS+=("$1")
            shift
            ;;
    esac
done

case "$BROWSER" in
    chromium) BROWSER="chrome" ;;
    webkit) BROWSER="safari" ;;
esac

if ! vendor/bin/sail exec -T laravel.test curl -fsS http://minio:9000/minio/health/live >/dev/null 2>&1; then
    echo "MinIO is not reachable from the Sail app container (needed for video delivery tests)."
    echo "Start it with: vendor/bin/sail up -d minio"
    exit 1
fi

if [[ ! -d "$ROOT/storage/app/playwright" ]]; then
    echo "Installing Playwright browsers into storage/app/playwright..."
    vendor/bin/sail exec -T \
        -e PLAYWRIGHT_BROWSERS_PATH="$BROWSERS_PATH" \
        laravel.test npx playwright install chromium webkit
fi

vendor/bin/sail exec -T \
    -e PLAYWRIGHT_BROWSERS_PATH="$BROWSERS_PATH" \
    laravel.test ./vendor/bin/pest -c phpunit.browser.xml --browser "$BROWSER" "${ARGS[@]}"
