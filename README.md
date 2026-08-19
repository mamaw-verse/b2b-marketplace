# B2B Marketplace

A B2B Halal Marketplace application built with Laravel.

## Features & Tech Stack
- Laravel 11 / PHP 8.3
- Frontend: Livewire, Alpine.js, TailwindCSS
- Dockerized environment via Laravel Sail
- Services: PostgreSQL, Redis, Typesense (Search), Minio (S3-compatible Object Storage), ClamAV (Antivirus), Mailpit

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
2. Navigate to http://localhost/hub/onboarding
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

## Troubleshooting

| Issue | Fix |
|---|---|
| Blank page / Vite error | `vendor/bin/sail npm run build` |
| File uploads failing on frontend | Ensure Minio is running. Confirm `LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=local` in your `.env`. |
| Queue jobs not processing | Run worker: `vendor/bin/sail artisan queue:listen` or `vendor/bin/sail artisan queue:work --once` |
| Documents stuck in 'Scanning...' | Ensure your Queue Worker is running to process the ClamAV job. Check Sail logs for ClamAV connectivity. |
| Search returns empty | Index needs data: approve a shop, publish products, then process queue |
| Email not received | Check Mailpit at http://localhost:8025 |
