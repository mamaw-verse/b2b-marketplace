# B2B Marketplace

A B2B Halal Marketplace application built with Laravel.

## Features & Tech Stack
- Laravel 11 / PHP 8.3
- Frontend: Livewire, Alpine.js, TailwindCSS
- Dockerized environment via Laravel Sail
- Services: PostgreSQL (+PostGIS, pg_trgm for native search), Redis, Minio (S3-compatible Object Storage), Mailpit — plus optional Typesense (Search, dormant: `sail up --profile search`) and ClamAV (Antivirus, dormant: `sail up --profile scanning`)

## Prerequisites
- Docker Desktop, OrbStack, or Podman
- Composer
- Node.js & NPM

## Setup Instructions

1. **Install Composer dependencies:**
   ```bash
   composer install
   ```

2. **Setup environment variables:**
   ```bash
   cp .env.example .env
   ```
   *Note: Our application uses Minio for S3-compatible storage. Make sure `LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=local` is set in your `.env` to prevent temporary upload issues, while final storage goes to S3/Minio.*

3. **Start Docker services (Sail):**
   ```bash
   vendor/bin/sail up -d
   ```
   *Upon startup, a `minio-createbuckets` service will automatically run to ensure the `halal-b2b` bucket is created in Minio.*

4. **Generate Application Key:**
   ```bash
   vendor/bin/sail artisan key:generate
   ```

5. **Run Migrations & Seeders:**
   ```bash
   vendor/bin/sail artisan migrate:fresh --seed
   ```

6. **Build Frontend Assets:**
   ```bash
   vendor/bin/sail npm install
   vendor/bin/sail npm run build
   ```

7. **Start the Queue Worker:**
   To process background jobs like ClamAV document scanning and Typesense indexing, run:
   ```bash
   vendor/bin/sail artisan queue:listen
   ```

---

## Access Points

| Service | URL | Credentials |
|---|---|---|
| App | http://localhost | |
| Mailpit (emails) | http://localhost:8025 | |
| Minio Console | http://localhost:8900 | user: `sail`, pass: `password` |
| Typesense | http://localhost:8108 | |

---

## Functional Testing Guide

### Test Flow 1: Account Registration & Email Verification

1. Go to http://localhost/register
2. Fill: First name, Last name, Email, Password, Confirm password
3. Click "Create account"
4. You'll be redirected to the email verification notice page
5. Open http://localhost:8025 (Mailpit) → find the verification email → click the link
6. You're now verified and logged in

**Or skip registration** — login directly with the seeded account:
- Email: `test@example.com`
- Password: `password`

### Test Flow 2: Company Onboarding (as Seller)

1. Login at http://localhost/login
2. Navigate to http://localhost/account/onboarding
3. Complete the 5-step wizard:
   - **Step 1**: Business name, structure, DTI/SEC number, TIN, address
   - **Step 2**: Product categories, FDA LTO, lead time, export readiness
   - **Step 3**: Add halal certificate (select body, registration number, expiry date, upload file)
   - **Step 4**: Select value chain positions + capabilities
   - **Step 5**: Upload required documents (DTI cert, business permit, BIR form, ingredient list)
4. Click Submit
   *(Note: Document scans are run in the background by ClamAV. You can click "Refresh Status" on Step 5 if scans are pending. The queue worker must be running.)*

### Test Flow 3: Admin Verification

Since there's no admin user seeded by default, create one via tinker:

```bash
vendor/bin/sail artisan tinker --execute '
$admin = App\Models\User::factory()->create([
    "first_name" => "Admin",
    "last_name" => "User",
    "email" => "admin@example.com",
    "password" => "password",
]);
$admin->assignRole("admin");
echo "Admin created: admin@example.com / password\n";
'
```

Then:
1. Login as `admin@example.com` / `password`
2. Navigate to http://localhost/admin/onboardings
3. You'll see pending onboarding submissions
4. Click one → review fields, docs, certificates
5. Approve / Request Revision / Reject

### Test Flow 4: Product Catalog (after shop approved)

1. Login as the seller whose shop was approved
2. Navigate to http://localhost/hub/products
3. Click Create → fill product name, description, select categories
4. Add variants (SKU, price, MOQ, unit, etc.)
5. Upload gallery images
6. Attach documents
7. Publish the product

### Test Flow 4b: Subscription Payment (DLT — default PH flow)

After a shop is approved, the seller must subscribe to go live:

1. Login as the approved seller
2. Navigate to http://localhost/hub/subscription
3. Choose a plan and click Subscribe
4. Select a payment brand (card or e-wallet)
5. Fill in billing details and click **Continue to Payment**
6. You'll be redirected to the DLT-hosted checkout page
7. Complete payment — you'll be returned to the app and the subscription activated

> Requires `MARKETPLACE_PAYMENT_METHOD=dlt` (default) and valid DLT credentials in `.env`.

### Test Flow 4c: Subscription Payment (Manual — non-DLT instances)

For country instances that don't use DLT (e.g. `MARKETPLACE_PAYMENT_METHOD=manual`):

1. Login as the approved seller
2. Navigate to http://localhost/hub/subscription
3. Choose a plan and click Subscribe
4. Enter the **bank/transaction reference number** from your bank transfer
5. Upload your **payment receipt** (JPEG, PNG, or PDF, max 5 MB)
6. Click **Submit Payment** — the admin is notified by email
7. Admin logs in → http://localhost/admin/subscriptions → **Pending Payments** tab
8. Admin verifies the payment in the bank portal, then clicks **Activate** (or **Cancel**)

> Set `MARKETPLACE_PAYMENT_METHOD=manual` in `.env` to use this flow. DLT credentials are not required.

### Test Flow 5: Categories (Admin)

1. Login as admin
2. Navigate to http://localhost/admin/categories
3. Create categories (e.g., "Bakery Ingredients", "Halal Meat", "Spices")
4. Edit/delete categories

### Test Flow 6: NLS Search & Discovery (Public)

1. Navigate to http://localhost/storefront (no login needed)
2. Use the search bar to search for companies or products
3. Browse by category
4. Click a company → view profile (value chain, certs, capabilities)
5. Click a product → view detail (variants, pricing, gallery)

**Note**: Search requires indexed data in Typesense. After approving a shop or publishing a product, the index jobs need to run. If your queue worker isn't running automatically, trigger jobs manually:

```bash
# Process queued index jobs
vendor/bin/sail artisan queue:work --once
```

### Test Flow 7: API Endpoints (via curl)

```bash
# Public: List categories
curl http://localhost/api/v1/categories | jq

# Public: Search companies
curl "http://localhost/api/v1/search/companies?q=halal" | jq

# Public: Get a company profile (replace {id})
curl http://localhost/api/v1/companies/1 | jq

# Public: Get a product detail (replace {id})
curl http://localhost/api/v1/products/1 | jq
```

---

## Production Deployment (1halalstore.com)

Server: `54.169.199.14` (Ubuntu 24.04, Apache, PHP 8.4)
SSH key: `1halal.pem` (keep in project root, never commit)

All scripts are in `bin/`. Run them from the **project root**.

### Prerequisites

- DNS `A` records for `1halalstore.com` and `www.1halalstore.com` must point to `54.169.199.14` before running Certbot
- `1halal.pem` must be in the project root with permissions `600`
- `rsync` must be installed locally (`brew install rsync`)

---

### Step 1 — Provision the server (run once)

```bash
ssh -i 1halal.pem ubuntu@54.169.199.14 'bash -s' < bin/server-setup.sh
```

Installs and configures:
- Apache 2 (with `mod_proxy`, `mod_rewrite`, `mod_ssl`, PHP-FPM)
- PHP 8.4 + all required extensions (pgsql, redis, mbstring, xml, curl, zip, bcmath, gd, intl, opcache)
- PostgreSQL 17 + PostGIS 3 + pg_trgm
- Redis (bound to localhost)
- Node.js 22 + Composer
- Supervisor (2× queue workers, Reverb WebSocket server, scheduler loop)
- Certbot (Let's Encrypt)

At the end, Certbot will prompt you to issue the SSL cert. Ensure DNS is live before confirming.

> **Note:** If Supervisor errors about missing log directory, run:
> ```bash
> ssh -i 1halal.pem ubuntu@54.169.199.14 '
>   sudo mkdir -p /var/www/1halalstore/storage/logs
>   sudo chown -R www-data:www-data /var/www/1halalstore/storage
>   sudo chmod -R 775 /var/www/1halalstore/storage
>   sudo supervisorctl reread && sudo supervisorctl update
> '
> ```

---

### Step 2 — Write the production `.env` (run once, or to update secrets)

```bash
bin/env.production.sh
```

Prompts you interactively for all credentials (DB, Redis, Reverb, Turnstile, DLT, mail, geocoding). The `.env` is written locally to `/tmp`, uploaded via a single SCP connection, then installed at `/var/www/1halalstore/.env`. Nothing is stored on your Mac permanently.

Optionally creates the PostgreSQL database + enables PostGIS and pg_trgm extensions.

Verify success:
```bash
ssh -i 1halal.pem ubuntu@54.169.199.14 'sudo cat /var/www/1halalstore/.env'
```

---

### Step 3 — Deploy (run on every release)

```bash
bin/deploy.sh
```

Does the following in order:
1. Builds frontend assets locally (`npm run build`)
2. `rsync`s code to server (excludes `.env`, `vendor`, `node_modules`, `storage`, `*.pem`)
3. Fixes file permissions (`www-data` ownership, `775` on storage)
4. `composer install --no-dev --optimize-autoloader`
5. `php artisan migrate --force`
6. Warms caches: `config:cache`, `route:cache`, `view:cache`, `event:cache`, `icons:cache`
7. Restarts Supervisor workers, PHP-FPM, and Apache

---

### SSL Certificate (Let's Encrypt)

Certbot runs as part of `server-setup.sh`. To renew manually or re-issue:

```bash
ssh -i 1halal.pem ubuntu@54.169.199.14 '
  sudo certbot --apache \
    -d 1halalstore.com \
    -d www.1halalstore.com \
    --non-interactive \
    --agree-tos \
    --email ops@1halalstore.com \
    --redirect
  sudo systemctl enable certbot.timer
  sudo systemctl start certbot.timer
'
```

> DNS must point to the server IP before running Certbot. If behind Cloudflare, temporarily disable the proxy (grey cloud) during cert issuance.

---

### Adding SES (email) later

Edit `.env` on the server:
```bash
ssh -i 1halal.pem ubuntu@54.169.199.14 'sudo nano /var/www/1halalstore/.env'
```
Uncomment and fill in the SES block, then restart:
```bash
ssh -i 1halal.pem ubuntu@54.169.199.14 'cd /var/www/1halalstore && sudo -u www-data php8.4 artisan config:cache'
```

### Adding S3 (file storage) later

Same as above — uncomment the S3 block in `.env`, set `FILESYSTEM_DISK=s3`, then run `config:cache`.

---

### Production Troubleshooting

| Issue | Fix |
|---|---|
| 502 Bad Gateway | `sudo systemctl status php8.4-fpm` — likely FPM is down |
| WebSocket not connecting | `sudo supervisorctl status 1halalstore-reverb` |
| Queue jobs not processing | `sudo supervisorctl status 1halalstore-worker_00` |
| Scheduler not running | `sudo supervisorctl status 1halalstore-scheduler` |
| View/config changes not reflecting | `ssh ... 'cd /var/www/1halalstore && sudo -u www-data php8.4 artisan config:cache && php8.4 artisan view:cache'` |
| Check app logs | `ssh ... 'sudo tail -f /var/www/1halalstore/storage/logs/laravel.log'` |
| Certbot DNS error (404 challenge) | Ensure DNS A record points to server IP, not Cloudflare proxy |

---

## Troubleshooting

| Issue | Fix |
|---|---|
| Blank page / Vite error | `vendor/bin/sail npm run build` |
| File uploads failing on frontend | Ensure Minio is running. Confirm `LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=local` in your `.env`. |
| Queue jobs not processing | Run worker: `vendor/bin/sail artisan queue:listen` or `vendor/bin/sail artisan queue:work --once` |
| Documents stuck in 'Scanning...' | Ensure your Queue Worker is running to process the ClamAV job. Check Sail logs for ClamAV connectivity. |
| Search returns empty | Index needs data: approve a shop, publish products, then process queue |
| Email not received | Check Mailpit at http://localhost:8025 |
