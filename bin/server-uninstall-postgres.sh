#!/usr/bin/env bash
# =============================================================================
# bin/server-uninstall-postgres.sh
# Removes the locally installed PostgreSQL 17 server (and its apt repo) that
# was installed by the original server-setup.sh. Not needed since the DB is
# hosted on AWS RDS. The PHP pgsql extension only needs libpq, which is
# available from Ubuntu's default repos — no pgdg repo required.
#
# Usage:
#   ssh -i 1halal.pem ubuntu@54.169.199.14 'bash -s' < bin/server-uninstall-postgres.sh
# =============================================================================
set -euo pipefail

PG_VERSION="17"

echo "==> Stopping and disabling PostgreSQL service..."
sudo systemctl stop postgresql || true
sudo systemctl disable postgresql || true

echo "==> Removing PostgreSQL server packages..."
sudo apt-get remove -y --purge \
    postgresql-${PG_VERSION} \
    postgresql-${PG_VERSION}-postgis-3 \
    postgresql-${PG_VERSION}-pgrouting \
    postgresql-client-${PG_VERSION} \
    postgresql-contrib \
    libpq-dev || true

sudo apt-get autoremove -y --purge || true

echo "==> Removing PostgreSQL data and config directories..."
sudo rm -rf /etc/postgresql
sudo rm -rf /var/lib/postgresql

echo "==> Removing pgdg apt repo and GPG key..."
sudo rm -f /etc/apt/sources.list.d/pgdg.list
sudo rm -f /etc/apt/trusted.gpg.d/postgresql.gpg
sudo apt-get update -qq

echo "==> Installing libpq from Ubuntu default repos (needed by PHP pgsql extension)..."
sudo apt-get install -y -qq libpq5

echo ""
echo "Done. PostgreSQL server, PostGIS, pgrouting, and pgdg repo removed."
echo "libpq5 (runtime only) kept for PHP pgsql extension."
