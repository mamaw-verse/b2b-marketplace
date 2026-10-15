#!/usr/bin/env bash
# =============================================================================
# bin/env.production.sh
# Prompts for all credentials, writes a .env file locally,
# then uploads it to the server in a single SCP connection.
#
# Usage (from project root):
#   bin/env.production.sh
# =============================================================================
set -euo pipefail

SERVER_IP="54.169.199.14"
SERVER_USER="ubuntu"
SERVER_PORT="22"
APP_DIR="/var/www/1halalstore"

PEM_KEY="${PEM_KEY:-${PWD}/1halal.pem}"
SSH_OPTS="-i ${PEM_KEY} -p ${SERVER_PORT} -o StrictHostKeyChecking=accept-new -o ConnectTimeout=15"
SSH="ssh ${SSH_OPTS} ${SERVER_USER}@${SERVER_IP}"
SCP="scp -i ${PEM_KEY} -P ${SERVER_PORT} -o StrictHostKeyChecking=accept-new -o ConnectTimeout=15"

LOCAL_ENV_FILE="/tmp/.env.1halalstore.production"

# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------
log()     { echo ""; echo "==> $*"; }
prompt()  { read -rp "    $1: " "$2"; }
promptp() { read -rsp "    $1: " "$2"; echo ""; }

# ---------------------------------------------------------------------------
# Preflight
# ---------------------------------------------------------------------------
[ -f "${PEM_KEY}" ] || { echo "ERROR: PEM key not found: ${PEM_KEY}" >&2; exit 1; }
chmod 600 "${PEM_KEY}"

echo ""
echo "=================================================================="
echo "  Production .env setup for 1halalstore.com"
echo "  Credentials are collected locally, then uploaded in one step."
echo "=================================================================="

# ---------------------------------------------------------------------------
# App key
# ---------------------------------------------------------------------------
log "App settings"
prompt "APP_KEY (leave blank to generate)" APP_KEY
if [ -z "${APP_KEY:-}" ]; then
    APP_KEY="base64:$(openssl rand -base64 32)"
    echo "    Generated: ${APP_KEY}"
fi

prompt "APP_TIMEZONE (default: Asia/Manila)" APP_TIMEZONE
APP_TIMEZONE="${APP_TIMEZONE:-Asia/Manila}"

# ---------------------------------------------------------------------------
# Database
# ---------------------------------------------------------------------------
log "PostgreSQL credentials (AWS RDS)"
echo "    The database/user must already exist on your RDS instance."
echo ""
prompt "DB_HOST (RDS endpoint, e.g. mydb.xxxx.ap-southeast-1.rds.amazonaws.com)" DB_HOST
prompt "DB_PORT (default: 5432)" DB_PORT
DB_PORT="${DB_PORT:-5432}"
prompt "DB_DATABASE (default: halalstore)" DB_DATABASE
DB_DATABASE="${DB_DATABASE:-halalstore}"
prompt "DB_USERNAME (default: halalstore)" DB_USERNAME
DB_USERNAME="${DB_USERNAME:-halalstore}"
promptp "DB_PASSWORD" DB_PASSWORD

# ---------------------------------------------------------------------------
# Redis
# ---------------------------------------------------------------------------
log "Redis"
prompt "REDIS_PASSWORD (leave blank if none)" REDIS_PASSWORD
REDIS_PASSWORD="${REDIS_PASSWORD:-null}"

# ---------------------------------------------------------------------------
# Reverb
# ---------------------------------------------------------------------------
log "Laravel Reverb WebSocket"
REVERB_APP_ID_DEFAULT="1halalstore-prod-$(openssl rand -hex 4)"
REVERB_APP_KEY_DEFAULT="$(openssl rand -hex 16)"
REVERB_APP_SECRET_DEFAULT="$(openssl rand -hex 32)"

prompt "REVERB_APP_ID (default: ${REVERB_APP_ID_DEFAULT})" REVERB_APP_ID
REVERB_APP_ID="${REVERB_APP_ID:-${REVERB_APP_ID_DEFAULT}}"
prompt "REVERB_APP_KEY (default: auto-generated)" REVERB_APP_KEY
REVERB_APP_KEY="${REVERB_APP_KEY:-${REVERB_APP_KEY_DEFAULT}}"
promptp "REVERB_APP_SECRET (default: auto-generated)" REVERB_APP_SECRET
REVERB_APP_SECRET="${REVERB_APP_SECRET:-${REVERB_APP_SECRET_DEFAULT}}"

# ---------------------------------------------------------------------------
# Mail
# ---------------------------------------------------------------------------
log "Mail (SES — configure later; defaulting to log driver)"
prompt "MAIL_FROM_ADDRESS (default: noreply@1halalstore.com)" MAIL_FROM_ADDRESS
MAIL_FROM_ADDRESS="${MAIL_FROM_ADDRESS:-noreply@1halalstore.com}"
prompt "MAIL_ADMIN_ADDRESS (default: admin@1halalstore.com)" MAIL_ADMIN_ADDRESS
MAIL_ADMIN_ADDRESS="${MAIL_ADMIN_ADDRESS:-admin@1halalstore.com}"

# ---------------------------------------------------------------------------
# Turnstile
# ---------------------------------------------------------------------------
log "Cloudflare Turnstile (CAPTCHA)"
prompt "TURNSTILE_SITE_KEY (leave blank to fill later)" TURNSTILE_SITE_KEY
TURNSTILE_SITE_KEY="${TURNSTILE_SITE_KEY:-your_site_key_here}"
promptp "TURNSTILE_SECRET_KEY (leave blank to fill later)" TURNSTILE_SECRET_KEY
TURNSTILE_SECRET_KEY="${TURNSTILE_SECRET_KEY:-your_secret_here}"

# ---------------------------------------------------------------------------
# DLT Payment
# ---------------------------------------------------------------------------
log "DLT Payment Gateway (leave blank to skip for now)"
prompt "DLT_CLIENT_ID" DLT_CLIENT_ID
DLT_CLIENT_ID="${DLT_CLIENT_ID:-}"
promptp "DLT_CLIENT_SECRET" DLT_CLIENT_SECRET
DLT_CLIENT_SECRET="${DLT_CLIENT_SECRET:-}"
prompt "DLT_MERCHANT_ACCOUNT_ID" DLT_MERCHANT_ACCOUNT_ID
DLT_MERCHANT_ACCOUNT_ID="${DLT_MERCHANT_ACCOUNT_ID:-}"

# ---------------------------------------------------------------------------
# Geocoding
# ---------------------------------------------------------------------------
log "Geocoding (optional)"
prompt "GOOGLE_GEOCODING_API_KEY (leave blank to skip)" GEOCODING_API_KEY
GEOCODING_API_KEY="${GEOCODING_API_KEY:-}"

# ---------------------------------------------------------------------------
# Write .env locally to /tmp
# ---------------------------------------------------------------------------
log "Writing .env locally to ${LOCAL_ENV_FILE}..."
cat > "${LOCAL_ENV_FILE}" <<ENV
APP_NAME="1 Halal Store"
APP_ENV=production
APP_KEY=${APP_KEY}
APP_DEBUG=false
APP_URL=https://1halalstore.com
APP_TIMEZONE=${APP_TIMEZONE}

MARKETPLACE_INSTANCE_COUNTRY=PH
MARKETPLACE_INITIAL_SUBSCRIPTION_TRIAL_DAYS=180
MARKETPLACE_PAYMENT_METHOD=dlt

TURNSTILE_SITE_KEY=${TURNSTILE_SITE_KEY}
TURNSTILE_SECRET_KEY=${TURNSTILE_SECRET_KEY}

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
PHP_CLI_SERVER_WORKERS=4
BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT}
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=.1halalstore.com

BROADCAST_CONNECTION=reverb
FILESYSTEM_DISK=local
LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=local

# S3 — uncomment after configuring
# FILESYSTEM_DISK=s3
# AWS_ACCESS_KEY_ID=
# AWS_SECRET_ACCESS_KEY=
# AWS_DEFAULT_REGION=ap-southeast-1
# AWS_BUCKET=1halalstore-prod
# AWS_USE_PATH_STYLE_ENDPOINT=false

QUEUE_CONNECTION=redis
CACHE_STORE=redis

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=${REDIS_PASSWORD}
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_FROM_ADDRESS="${MAIL_FROM_ADDRESS}"
MAIL_FROM_NAME="1 Halal Store"
MAIL_ADMIN_ADDRESS="${MAIL_ADMIN_ADDRESS}"

# SES — uncomment after configuring
# MAIL_MAILER=ses
# AWS_ACCESS_KEY_ID=
# AWS_SECRET_ACCESS_KEY=
# AWS_DEFAULT_REGION=ap-southeast-1
# SES_CONFIGURATION_SET=

SCOUT_DRIVER=pgsql

CLAMAV_ENABLED=false

REVERB_APP_ID=${REVERB_APP_ID}
REVERB_APP_KEY=${REVERB_APP_KEY}
REVERB_APP_SECRET=${REVERB_APP_SECRET}
REVERB_HOST=1halalstore.com
REVERB_PORT=443
REVERB_SCHEME=https

DLT_BASE_URL=https://checkout.dxp.dtic.com.ph
DLT_CLIENT_ID=${DLT_CLIENT_ID}
DLT_CLIENT_SECRET=${DLT_CLIENT_SECRET}
DLT_MERCHANT_ACCOUNT_ID=${DLT_MERCHANT_ACCOUNT_ID}
DLT_SERVICE_CODE=APN-COLLECTION

GEOCODING_PROVIDER=google
GEOCODING_API_KEY=${GEOCODING_API_KEY}

VITE_APP_NAME="1 Halal Store"
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="1halalstore.com"
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
ENV

echo "    Done."

# ---------------------------------------------------------------------------
# Upload .env to server in a single SCP connection
# ---------------------------------------------------------------------------
log "Uploading .env to server..."
$SCP "${LOCAL_ENV_FILE}" "${SERVER_USER}@${SERVER_IP}:/home/${SERVER_USER}/.env.upload"

# Move into place and fix ownership in one SSH call
$SSH "sudo mkdir -p ${APP_DIR} \
    && sudo mv /home/${SERVER_USER}/.env.upload ${APP_DIR}/.env \
    && sudo chown www-data:www-data ${APP_DIR}/.env \
    && sudo chmod 640 ${APP_DIR}/.env \
    && echo '    .env installed at ${APP_DIR}/.env'"

# Clean up local temp file
rm -f "${LOCAL_ENV_FILE}"
echo "    Local temp file removed."

# ---------------------------------------------------------------------------
# Done
# ---------------------------------------------------------------------------
echo ""
echo "=================================================================="
echo " .env is on the server at ${APP_DIR}/.env"
echo ""
echo " Verify: ssh -i 1halal.pem ubuntu@${SERVER_IP} 'sudo cat ${APP_DIR}/.env'"
echo " Next:   bin/deploy.sh"
echo "=================================================================="
