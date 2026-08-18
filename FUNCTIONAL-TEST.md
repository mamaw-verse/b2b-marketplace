# Functional Testing Guide

## Prerequisites

```bash
# 1. Make sure containers are running
vendor/bin/sail up -d

# 2. Fresh database with seed data
vendor/bin/sail artisan migrate:fresh --seed

# 3. Build frontend assets
vendor/bin/sail npm run build
```

## Access Points

| Service | URL |
|---|---|
| App | http://localhost |
| Mailpit (emails) | http://localhost:8025 |
| Minio Console | http://localhost:8900 (user: `sail`, pass: `password`) |
| Typesense | http://localhost:8108 |

---

## Test Flow 1: Account Registration & Email Verification

1. Go to http://localhost/register
2. Fill: First name, Last name, Email, Password, Confirm password
3. Click "Create account"
4. You'll be redirected to the email verification notice page
5. Open http://localhost:8025 (Mailpit) → find the verification email → click the link
6. You're now verified and logged in

**Or skip registration** — login directly with the seeded account:
- Email: `test@example.com`
- Password: `password`

---

## Test Flow 2: Company Onboarding (as Seller)

1. Login at http://localhost/login
2. Navigate to http://localhost/hub/onboarding
3. Complete the 5-step wizard:
   - **Step 1**: Business name, structure, DTI/SEC number, TIN, address
   - **Step 2**: Product categories, FDA LTO, lead time, export readiness
   - **Step 3**: Add halal certificate (select body, registration number, expiry date, upload file)
   - **Step 4**: Select value chain positions + capabilities
   - **Step 5**: Upload required documents (DTI cert, business permit, BIR form, ingredient list)
4. Click Submit

---

## Test Flow 3: Admin Verification

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

---

## Test Flow 4: Product Catalog (after shop approved)

1. Login as the seller whose shop was approved
2. Navigate to http://localhost/hub/products
3. Click Create → fill product name, description, select categories
4. Add variants (SKU, price, MOQ, unit, etc.)
5. Upload gallery images
6. Attach documents
7. Publish the product

---

## Test Flow 5: Categories (Admin)

1. Login as admin
2. Navigate to http://localhost/admin/categories
3. Create categories (e.g., "Bakery Ingredients", "Halal Meat", "Spices")
4. Edit/delete categories

---

## Test Flow 6: NLS Search & Discovery (Public)

1. Navigate to http://localhost/storefront (no login needed)
2. Use the search bar to search for companies or products
3. Browse by category
4. Click a company → view profile (value chain, certs, capabilities)
5. Click a product → view detail (variants, pricing, gallery)

**Note**: Search requires indexed data in Typesense. After approving a shop or publishing a product, the index jobs need to run:

```bash
# Process queued index jobs
vendor/bin/sail artisan queue:work --once
```

---

## Test Flow 7: API Endpoints (via curl)

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
| Queue jobs not processing | `vendor/bin/sail artisan queue:work --once` or run worker: `vendor/bin/sail artisan queue:listen` |
| Search returns empty | Index needs data: approve a shop, publish products, then process queue |
| Email not received | Check Mailpit at http://localhost:8025 |
