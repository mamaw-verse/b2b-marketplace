#!/usr/bin/env bash
# =============================================================================
# bin/deploy.sh
# Deploy 1halalstore.com to Ubuntu server via rsync.
# Runs: rsync → composer install → npm build → migrate → cache → restart workers
#
# Usage (from project root):
#   bin/deploy.sh
#
# Prerequisites:
#   - bin/server-setup.sh has been run on the server
#   - bin/env.production.sh has been run (or .env exists at APP_DIR/.env)
#   - 1halal.pem is in the project root (or set PEM_KEY env var)
# =============================================================================
set -euo pipefail

# ---------------------------------------------------------------------------
# Config — override via environment variables if needed
# ---------------------------------------------------------------------------
SERVER_IP="54.169.199.14"
SERVER_USER="ubuntu"
SERVER_PORT="22"
APP_DIR="/var/www/1halalstore"
APP_USER="www-data"
PHP="php8.4"
COMPOSER="/usr/local/bin/composer"

# Path to PEM key — can be overridden: PEM_KEY=/path/to/key.pem bin/deploy.sh
PEM_KEY="${PEM_KEY:-${PWD}/1halal.pem}"

SSH_OPTS="-i ${PEM_KEY} -p ${SERVER_PORT} -o StrictHostKeyChecking=accept-new -o BatchMode=yes"
SSH="ssh ${SSH_OPTS} ${SERVER_USER}@${SERVER_IP}"
SCP_OPTS="-i ${PEM_KEY} -P ${SERVER_PORT} -o StrictHostKeyChecking=accept-new"

LOCAL_ROOT="$(cd "$(dirname "$0")/.." && pwd)"

# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------
log()  { echo ""; echo "==> $*"; }
info() { echo "    $*"; }
die()  { echo ""; echo "ERROR: $*" >&2; exit 1; }

# ---------------------------------------------------------------------------
# Preflight checks
# ---------------------------------------------------------------------------
log "[preflight] Checking local requirements"
[ -f "${PEM_KEY}" ] || die "PEM key not found: ${PEM_KEY}"
chmod 600 "${PEM_KEY}"
command -v rsync > /dev/null || die "rsync not found. Install with: brew install rsync"
command -v ssh   > /dev/null || die "ssh not found"

log "[preflight] Testing SSH connection to ${SERVER_USER}@${SERVER_IP}"
$SSH "echo '    SSH OK'" || die "Cannot connect to server. Check IP, user, and PEM key."

# Check .env exists on server
$SSH "test -f ${APP_DIR}/.env" \
    || die ".env not found on server at ${APP_DIR}/.env — run bin/env.production.sh first"

# ---------------------------------------------------------------------------
# Step 1: Build assets inside Sail before upload
# ---------------------------------------------------------------------------
log "[1/7] Building frontend assets (via Sail)"
cd "${LOCAL_ROOT}"

# Build inside the Sail container — node_modules contains Linux-specific
# optional deps (e.g. @rolldown/binding-*), so host-side npm builds fail on
# macOS. Requires `vendor/bin/sail up -d` to be running.
#
# .env.production is committed with the correct production VITE_* values
# (VITE_REVERB_HOST, VITE_REVERB_PORT, etc.). Vite loads it automatically
# during a production build, overriding the local .env defaults.

if [ ! -d "node_modules" ]; then
    info "Installing npm dependencies..."
    vendor/bin/sail npm ci --silent
fi

info "Running npm build..."
vendor/bin/sail npm run build

# ---------------------------------------------------------------------------
# Step 2: Wipe app directory and copy fresh
# ---------------------------------------------------------------------------
log "[2/7] Wiping server files and copying fresh"
info "Backing up .env..."
$SSH "sudo cp ${APP_DIR}/.env /tmp/.env.backup 2>/dev/null || true"

info "Wiping ${APP_DIR}..."
$SSH "sudo rm -rf ${APP_DIR} \
    && sudo mkdir -p ${APP_DIR} \
    && sudo chown -R ubuntu:ubuntu ${APP_DIR} \
    && sudo chmod 755 ${APP_DIR}"

info "Restoring .env..."
$SSH "cp /tmp/.env.backup ${APP_DIR}/.env 2>/dev/null || true"

info "Copying files via rsync..."
$SSH "mkdir -p ${APP_DIR}/storage/logs ${APP_DIR}/bootstrap/cache"

rsync -rltz \
    -e "ssh ${SSH_OPTS}" \
    --exclude='/.git' \
    --exclude='/.env' \
    --exclude='/.env.*' \
    --exclude='/.gitignore' \
    --exclude='/.gitattributes' \
    --exclude='/.editorconfig' \
    --exclude='/.npmrc' \
    --exclude='/.devcontainer' \
    --exclude='/.github' \
    --exclude='.DS_Store' \
    --exclude='/.ai' \
    --exclude='/.agents' \
    --exclude='/.kiro' \
    --exclude='/.claude' \
    --exclude='/.mcp.json' \
    --exclude='/node_modules' \
    --exclude='/vendor' \
    --exclude='/storage/logs/*' \
    --exclude='/storage/framework/cache/*' \
    --exclude='/storage/framework/sessions/*' \
    --exclude='/storage/framework/views/*' \
    --exclude='/storage/app/public/*' \
    --exclude='/bootstrap/cache/*' \
    --exclude='*.pem' \
    --exclude='/tmp/' \
    --exclude='/mockup/' \
    --exclude='/specs/' \
    --exclude='/tests/' \
    --exclude='/phpunit.xml' \
    --exclude='/phpstan.neon' \
    --exclude='/AGENTS.md' \
    --exclude='/CLAUDE.md' \
    --exclude='/TEST_FAILURES.md' \
    --exclude='/hero-revision.md' \
    --exclude='/compose.yaml' \
    --exclude='/boost.json' \
    --exclude='/start=0' \
    --exclude='/bin/server-setup.sh' \
    --exclude='/bin/server-uninstall-postgres.sh' \
    --exclude='/bin/env.production.sh' \
    "${LOCAL_ROOT}/" \
    "${SERVER_USER}@${SERVER_IP}:${APP_DIR}/"

info "rsync complete."

# ---------------------------------------------------------------------------
# Step 3: Fix permissions
# ---------------------------------------------------------------------------
log "[3/7] Setting permissions"
$SSH "sudo chown -R ubuntu:www-data ${APP_DIR} \
    && sudo chmod -R 775 ${APP_DIR}/storage ${APP_DIR}/bootstrap/cache \
    && sudo chmod +x ${APP_DIR}/artisan"
info "Permissions set."

# ---------------------------------------------------------------------------
# Step 4: Composer install (production, no dev deps)
# ---------------------------------------------------------------------------
log "[4/7] Installing Composer dependencies (production)"
$SSH "cd ${APP_DIR} \
    && ${COMPOSER} install \
        --no-dev \
        --optimize-autoloader \
        --no-interaction \
        --prefer-dist \
        --quiet"
info "Composer install done."

# ---------------------------------------------------------------------------
# Step 5: Run database migrations
# ---------------------------------------------------------------------------
log "[5/7] Running database migrations"
$SSH "cd ${APP_DIR} \
    && ${PHP} artisan migrate --force --no-interaction"
info "Migrations done."

# ---------------------------------------------------------------------------
# Step 6: Cache config / routes / views
# ---------------------------------------------------------------------------
log "[6/7] Caching config, routes, views, events"
$SSH "cd ${APP_DIR} \
    && ${PHP} artisan config:cache \
    && ${PHP} artisan route:cache \
    && ${PHP} artisan view:cache \
    && ${PHP} artisan event:cache"
info "Caches warmed."

# ---------------------------------------------------------------------------
# Step 7: Restart workers + services
# ---------------------------------------------------------------------------
log "[7/7] Restarting Supervisor workers and PHP-FPM"
$SSH "sudo supervisorctl restart all \
    && sudo systemctl reload php8.4-fpm \
    && sudo systemctl reload apache2"
info "Workers and services restarted."

# ---------------------------------------------------------------------------
# Done
# ---------------------------------------------------------------------------
echo ""
echo "==================================================================="
echo " Deployment complete!"
echo " https://1halalstore.com"
echo "==================================================================="
echo ""
echo " Supervisor worker status:"
$SSH "sudo supervisorctl status" | sed 's/^/    /'
echo ""
