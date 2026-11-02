#!/usr/bin/env bash
# =============================================================================
# bin/php-security-patch.sh
# Emergency patch for the "php2shell" PHP vulnerabilities (Oct 2026):
#   CVE-2026-109618 — RCE via OOM corruption          (fixed in 8.4.27)
#   CVE-2026-109619 — RCE via stale global state      (fixed in 8.4.27)
#   CVE-2026-115560 — RCE via session upload progress (unpatched; mitigated
#                     via session.upload_progress.enabled=0 drop-in below)
#
# Upgrades all php8.4-* packages on the production server to >= 8.4.27,
# installs a hardening drop-in for fpm + cli, and gracefully reloads PHP-FPM.
#
# Usage (from project root):
#   bin/php-security-patch.sh
# =============================================================================
set -euo pipefail

SERVER_IP="54.169.199.14"
SERVER_USER="ubuntu"
SERVER_PORT="22"
PHP="php8.4"
MIN_VERSION="8.4.27"

PEM_KEY="${PEM_KEY:-${PWD}/1halal.pem}"
SSH_OPTS="-i ${PEM_KEY} -p ${SERVER_PORT} -o StrictHostKeyChecking=accept-new -o BatchMode=yes -o ConnectTimeout=15"
SSH="ssh ${SSH_OPTS} ${SERVER_USER}@${SERVER_IP}"

log() { echo ""; echo "==> $*"; }
die() { echo ""; echo "ERROR: $*" >&2; exit 1; }

[ -f "${PEM_KEY}" ] || die "PEM key not found: ${PEM_KEY}"
chmod 600 "${PEM_KEY}"

log "[1/4] Checking current PHP version on ${SERVER_IP}"
$SSH "php -v | head -1" || die "Cannot reach ${SERVER_IP} — check network/security group"

log "[2/4] Upgrading ${PHP} packages to >= ${MIN_VERSION}"
$SSH "sudo apt-get update -qq \
    && sudo DEBIAN_FRONTEND=noninteractive apt-get install -y -qq --only-upgrade '${PHP}-*' \
    && php -v | head -1"

log "[3/4] Installing hardening drop-in (session.upload_progress.enabled=0)"
$SSH "for sapi in fpm cli; do \
        printf '[PHP]\nsession.upload_progress.enabled = 0\nfatal_error_backtraces = 0\n' \
        | sudo tee /etc/php/8.4/\$sapi/conf.d/99-hardening.ini > /dev/null; \
      done \
    && sudo systemctl reload ${PHP}-fpm \
    && echo '    php-fpm reloaded (graceful)'"

log "[4/4] Verifying"
$SSH "php -v | head -1 \
    && php -i | grep -i 'upload_progress' \
    && sudo systemctl is-active ${PHP}-fpm apache2 supervisor"

PHPV=$($SSH "php -r 'echo PHP_VERSION;'" | tr -d '[:space:]')
if $SSH "php -r 'exit(version_compare(PHP_VERSION, \"${MIN_VERSION}\", \">=\") ? 0 : 1);'"; then
    echo ""
    echo "==================================================================="
    echo " Patched: PHP ${PHPV} >= ${MIN_VERSION}"
    echo " CVE-2026-109618 + CVE-2026-109619: fixed"
    echo " CVE-2026-115560: mitigated (session.upload_progress.enabled=0)"
    echo "==================================================================="
else
    die "PHP is still ${PHPV} (< ${MIN_VERSION}) — the ondrej PPA may not have the update yet; retry later or check 'apt-cache policy ${PHP}-cli'"
fi
