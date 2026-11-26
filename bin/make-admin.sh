#!/usr/bin/env bash
# =============================================================================
# bin/make-admin.sh
# Assign the `admin` role to a user account on the production server.
# Runs `php artisan tinker` over SSH — same connection config as deploy.sh.
#
# Usage (from project root):
#   bin/make-admin.sh                      # defaults to jgueruela@gmail.com
#   bin/make-admin.sh someone@example.com  # any other account
# =============================================================================
set -euo pipefail

# ---------------------------------------------------------------------------
# Config — same as deploy.sh
# ---------------------------------------------------------------------------
SERVER_IP="54.169.199.14"
SERVER_USER="ubuntu"
SERVER_PORT="22"
APP_DIR="/var/www/1halalstore"
PHP="php8.4"

PEM_KEY="${PEM_KEY:-${PWD}/1halal.pem}"

SSH_OPTS="-i ${PEM_KEY} -p ${SERVER_PORT} -o StrictHostKeyChecking=accept-new -o BatchMode=yes -o ConnectTimeout=15"
SSH="ssh ${SSH_OPTS} ${SERVER_USER}@${SERVER_IP}"

# ---------------------------------------------------------------------------
# Args
# ---------------------------------------------------------------------------
EMAIL="${1:-jgueruela@gmail.com}"

# Basic email sanity check so the value can't break out of the tinker string.
if [[ ! "$EMAIL" =~ ^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$ ]]; then
    echo "ERROR: invalid email address: ${EMAIL}" >&2
    exit 1
fi

log()  { echo ""; echo "==> $*"; }
die()  { echo ""; echo "ERROR: $*" >&2; exit 1; }

# ---------------------------------------------------------------------------
# Preflight
# ---------------------------------------------------------------------------
log "[preflight] Checking local requirements"
[ -f "${PEM_KEY}" ] || die "PEM key not found: ${PEM_KEY}"
chmod 600 "${PEM_KEY}"
command -v ssh > /dev/null || die "ssh not found"

log "[preflight] Testing SSH connection to ${SERVER_USER}@${SERVER_IP}"
$SSH "echo '    SSH OK'" || die "Cannot connect to server. Check IP, user, and PEM key."

# ---------------------------------------------------------------------------
# Assign admin role
# ---------------------------------------------------------------------------
log "Assigning 'admin' role to ${EMAIL} on production"

$SSH "cd ${APP_DIR} && ${PHP} artisan tinker --execute \"
\\\$user = App\\\\Models\\\\User::where('email', '${EMAIL}')->first();
if (! \\\$user) { echo 'ERROR: no user with email ${EMAIL}'; exit(1); }
\\\$user->assignRole('admin');
echo 'OK: ' . \\\$user->email . ' roles=' . \\\$user->getRoleNames()->implode(',');
\""

log "Done. ${EMAIL} now has the 'admin' role."
