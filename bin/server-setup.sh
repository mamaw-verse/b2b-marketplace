#!/usr/bin/env bash
# =============================================================================
# bin/server-setup.sh
# One-time provisioning for 1halalstore.com on Ubuntu 24.04
# Run once on a fresh server as the ubuntu user.
#
# Usage (from your local machine):
#   chmod +x bin/server-setup.sh
#   ssh -i 1halal.pem ubuntu@54.169.199.14 'bash -s' < bin/server-setup.sh
# =============================================================================
set -euo pipefail

APP_DIR="/var/www/1halalstore"
APP_USER="www-data"
DOMAIN="1halalstore.com"
PHP_VERSION="8.4"
NODE_MAJOR="22"
PG_VERSION="17"

echo "==> [1/10] System update"
sudo apt-get update -qq

echo "==> [2/10] Add PPAs: PHP (Ondřej), PostgreSQL, Node"

# PHP 8.4 via ppa:ondrej/php
sudo apt-get install -y -qq software-properties-common curl gnupg lsb-release ca-certificates
sudo add-apt-repository -y ppa:ondrej/php

# PostgreSQL 17 official repo
curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc \
    | sudo gpg --dearmor -o /etc/apt/trusted.gpg.d/postgresql.gpg
echo "deb [signed-by=/etc/apt/trusted.gpg.d/postgresql.gpg] \
https://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" \
    | sudo tee /etc/apt/sources.list.d/pgdg.list > /dev/null

# Node 22 via NodeSource
curl -fsSL https://deb.nodesource.com/setup_${NODE_MAJOR}.x | sudo -E bash - > /dev/null 2>&1

sudo apt-get update -qq

echo "==> [3/10] Install Apache, PHP ${PHP_VERSION} + extensions"
sudo apt-get install -y -qq \
    apache2 \
    php${PHP_VERSION} \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-cli \
    php${PHP_VERSION}-pgsql \
    php${PHP_VERSION}-redis \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-bcmath \
    php${PHP_VERSION}-gd \
    php${PHP_VERSION}-intl \
    php${PHP_VERSION}-tokenizer \
    php${PHP_VERSION}-fileinfo \
    php${PHP_VERSION}-opcache \
    php${PHP_VERSION}-pcov

# PHP security hardening — "php2shell" disclosures (Oct 2026)
# CVE-2026-115560 (unpatched): RCE via session upload progress. PHP's official
# mitigation until the next bugfix release is session.upload_progress.enabled=0.
# Laravel never uses this feature. fatal_error_backtraces=0 additionally blocks
# the confirmed CVE-2026-109619 path on PHP 8.5 (no-op on <= 8.4).
for SAPI in fpm cli; do
    sudo tee "/etc/php/${PHP_VERSION}/${SAPI}/conf.d/99-hardening.ini" > /dev/null <<'HARDENING'
[PHP]
session.upload_progress.enabled = 0
fatal_error_backtraces = 0
HARDENING
done

# Upload limits — defaults (upload_max_filesize=2M, post_max_size=8M) reject
# product/certificate images. App-level validation still caps featured images
# at 5MB and Livewire temp uploads at 12MB; post_max_size must exceed
# upload_max_filesize to leave room for the rest of the request body.
sudo tee "/etc/php/${PHP_VERSION}/fpm/conf.d/99-uploads.ini" > /dev/null <<'UPLOADS'
upload_max_filesize = 10M
post_max_size = 12M
UPLOADS

sudo systemctl restart "php${PHP_VERSION}-fpm"

echo "==> [4/10] Install PostgreSQL ${PG_VERSION} client (RDS — server runs on AWS)"
# Only the client tools + libpq are needed; the actual PostgreSQL server
# runs as a managed AWS RDS instance.
sudo apt-get install -y -qq \
    postgresql-client-${PG_VERSION} \
    libpq-dev

echo "==> [5/10] Install Redis, Node, Composer, Certbot, Supervisor"
sudo apt-get install -y -qq \
    redis-server \
    nodejs \
    supervisor \
    certbot \
    python3-certbot-apache \
    git \
    unzip \
    acl

# Composer (latest stable)
if ! command -v composer &> /dev/null; then
    echo "    Installing Composer..."
    EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    ACTUAL_CHECKSUM="$(php -r 'echo hash_file("sha384", "composer-setup.php");')"
    if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
        echo "ERROR: Composer installer corrupt" >&2
        rm composer-setup.php
        exit 1
    fi
    sudo php composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
    rm composer-setup.php
fi

echo "==> [6/10] Configure Redis"
# Bind to localhost only, enable persistence
sudo sed -i 's/^bind .*/bind 127.0.0.1 ::1/' /etc/redis/redis.conf
sudo sed -i 's/^# maxmemory-policy.*/maxmemory-policy allkeys-lru/' /etc/redis/redis.conf
sudo systemctl enable redis-server
sudo systemctl restart redis-server

echo "==> [8/10] Configure Apache"
sudo a2enmod rewrite proxy proxy_http proxy_wstunnel headers ssl
sudo a2enconf php${PHP_VERSION}-fpm

# Disable the default vhosts that ship with Ubuntu (including the snakeoil
# self-signed SSL one) so they don't conflict with our vhost or Certbot.
sudo a2dissite 000-default.conf || true
sudo a2dissite default-ssl.conf  || true

# Create app document root
sudo mkdir -p "${APP_DIR}/public"
sudo chown -R "${APP_USER}:${APP_USER}" "${APP_DIR}"
# Let ubuntu deploy to it
sudo setfacl -R -m u:ubuntu:rwX "${APP_DIR}"
sudo setfacl -d -m u:ubuntu:rwX "${APP_DIR}"

# Apache virtual host (HTTP — Certbot will upgrade to HTTPS)
sudo tee /etc/apache2/sites-available/${DOMAIN}.conf > /dev/null <<VHOST
<VirtualHost *:80>
    ServerName ${DOMAIN}
    ServerAlias www.${DOMAIN}
    DocumentRoot ${APP_DIR}/public

    <Directory ${APP_DIR}/public>
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>

    # Proxy Laravel Reverb WebSocket traffic
    ProxyPreserveHost On
    ProxyPass /app/ ws://127.0.0.1:8080/app/
    ProxyPassReverse /app/ ws://127.0.0.1:8080/app/

    ErrorLog \${APACHE_LOG_DIR}/${DOMAIN}-error.log
    CustomLog \${APACHE_LOG_DIR}/${DOMAIN}-access.log combined
</VirtualHost>
VHOST

sudo a2ensite ${DOMAIN}.conf
sudo systemctl enable apache2
sudo systemctl restart apache2

echo "==> [9/10] Configure Supervisor (queue workers + Reverb)"

# Pre-create log directory so Supervisor doesn't error before first deploy
sudo mkdir -p "${APP_DIR}/storage/logs"
sudo chown -R "${APP_USER}:${APP_USER}" "${APP_DIR}/storage"
sudo chmod -R 775 "${APP_DIR}/storage"

sudo tee /etc/supervisor/conf.d/1halalstore.conf > /dev/null <<SUPERVISOR
[program:1halalstore-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${APP_DIR}/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --queue=default
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=${APP_USER}
numprocs=2
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/worker.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=5
stopwaitsecs=3600

[program:1halalstore-reverb]
command=php ${APP_DIR}/artisan reverb:start --host=0.0.0.0 --port=8080 --no-interaction
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=${APP_USER}
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/reverb.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=3

[program:1halalstore-scheduler]
command=bash -c 'while true; do php ${APP_DIR}/artisan schedule:run >> ${APP_DIR}/storage/logs/scheduler.log 2>&1; sleep 60; done'
autostart=true
autorestart=true
user=${APP_USER}
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/scheduler.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=3
SUPERVISOR

sudo supervisorctl reread
sudo supervisorctl update

echo "==> [10/10] SSL certificate via Let's Encrypt"
echo ""
echo "    Before running Certbot, make sure:"
echo "    - DNS A record for ${DOMAIN} points to this server's public IP"
echo "    - DNS A record for www.${DOMAIN} points to this server's public IP"
echo ""
read -rp "    DNS is ready. Run Certbot now? [y/N] " RUN_CERTBOT
if [[ "${RUN_CERTBOT}" =~ ^[Yy]$ ]]; then
    sudo certbot --apache \
        -d "${DOMAIN}" \
        -d "www.${DOMAIN}" \
        --non-interactive \
        --agree-tos \
        --email "ops@${DOMAIN}" \
        --redirect
    # Auto-renew
    sudo systemctl enable certbot.timer
    sudo systemctl start certbot.timer
    echo "    Let's Encrypt cert issued and auto-renewal enabled."
else
    echo "    Skipping Certbot. Run manually later:"
    echo "    sudo certbot --apache -d ${DOMAIN} -d www.${DOMAIN}"
fi

echo ""
echo "==================================================================="
echo " Server provisioning complete!"
echo ""
echo " Next steps:"
echo "  1. Run bin/env.production.sh to write the .env on the server"
echo "  2. Run bin/deploy.sh to push the app and run migrations"
echo "==================================================================="
