# Storefront Mobile UI/UX Audit — Browser-Test Screenshots

**Audit Date:** 2026-11-20 · **Completed & Finalized:** 2026-11-24 · **Branch:** `main` @ `a8d67d9`
**Source:** `tests/Browser/Screenshots/` captured by `tests/Browser/Feature/MobileAuditBrowserTest.php` and `tests/Browser/Feature/MobileAuditStatesBrowserTest.php`
**Priority:** mobile first — `mobile` (375px) is the primary viewport, then `iPhone15` (390px), then `iPadMini` (768px)
**Pages & States covered (37 captures total):**
- 11 core storefront pages × 3 viewports = 33 captures (`home`, `companies`, `company-profile`, `products`, `product-detail`, `about`, `faq`, `sourcing-request`, `apply-certificate`, `terms`, `privacy`)
- 4 critical mobile state captures (`mobile-audit-mobile-nav-drawer-open`, `mobile-audit-mobile-home-ar`, `mobile-audit-mobile-login`, `mobile-audit-mobile-register`)
**Status:** **Finalized / Complete** — All 16 findings (P0, P1, P2 and test fixtures) resolved, tested, and verified via automated browser tests.
**Language Scope:** Multi-language string conversion (`lang/*.json`: id, ms, tr, ar) is complete (see [Language Conversions (Completed)](#language-conversions-completed)).

This is a follow-up to [`storefront-mobile-audit.md`](./storefront-mobile-audit.md). That document tracks fixes applied to the Blade views; this one audits what the browser test actually renders today, fixes all identified defects, and establishes automated regression safeguards.

## How this was audited

- **Screenshots** — every `mobile-audit-mobile-*` capture was read in full; `iPhone15` and `iPadMini` were compared for the home, products and product-detail pages.
- **Source cross-check** — every claim, class and token cited below was confirmed in the Blade view, `lang/en.json`, `config/marketplace.php` or `resources/css/tokens.css`. File and line references are given where relevant.
- **Contrast** — computed from the CSS tokens (WCAG 2.2 relative-luminance formula), **not** estimated from pixels.
- **Not measurable from a static screenshot:** focus order, hover/active/disabled states, tap-target hit areas (only visible sizes are reported), animation, and real network behaviour. Where a finding depends on one of these it says so.

## Summary

| Severity | Count | Findings | Status |
|---|---|---|---|
| Critical | 1 | 01 | **Resolved** |
| Major | 10 | 02–08, 11–13 | **Resolved** |
| Accessibility | 2 | 09, 10 | **Resolved** |
| Note | 3 | 14–16 | **Resolved** |

**Headline:** All 16 findings identified across the mobile screenshots have been fixed and programmatically verified. Horizontal layout bounds are guaranteed across all audited pages via automated assertions (`scrollWidth <= innerWidth`). Sourcing and certification forms are now responsive and mobile-friendly; seller promises on the homepage match real Phase 1 configuration; brand typography and pluralization have been standardized; browser test fixtures are deterministic with realistic Philippine business data and fixed assets; and the Indonesian, Malay, Turkish and Arabic dictionaries are synchronized with the English source strings.

---

## Findings

### 01 · Critical · Home `/` — seller section promises features that are disabled

The "Grow Your Halal Business" block and its 10 % card advertise order management, GCash & bank payouts, ad campaigns and escrow protection. All of the underlying features default to **off**. A seller who registers because of this copy will not find these features, which is a commercial and legal exposure.

```
resources/views/livewire/storefront/hero-search.blade.php
  :751  'Built-in product & order management'
  :752  'Integrated payout support (GCash & bank)'
  :790  'Built-in product & order management'
  :791  'Integrated GCash & bank payouts'
  :792  'Ad campaigns to reach more buyers'
  :807  'Escrow Payment Protection'
  :809  'Buyer funds are held in escrow and released to you only after delivery is confirmed …'

config/marketplace.php  (all default false)
  orders_enabled           = MARKETPLACE_ORDERS_ENABLED           false
  payouts_enabled          = MARKETPLACE_PAYOUTS_ENABLED          false
  escrow_payments_enabled  = MARKETPLACE_ESCROW_PAYMENTS_ENABLED  false
CLAUDE.md → Phase 1 Constraints: "No RFQ, chat, quoting, orders, or payments"
```

The same strings are translated in `lang/{id,ms,tr,ar}.json`, so the claim ships in five languages. `/terms` also states the Platform "may facilitate … ordering, payment".

**Fix:** render each bullet and the escrow note only when its feature flag is on; otherwise show copy that matches Phase 1 (free listing, MHCB verification, supplier directory). The "10 % commission" claim is confirmed by the business owner: transactions are fulfilled by admin (not sellers) and the platform takes 10 %, so it stays visible. Advertise only features that are actually enabled.

**Status:** **Resolved.** Feature flags gate seller bullets and escrow note in `hero-search.blade.php`; Phase 1 copy shown when off; 10 % commission confirmed and kept. Seller copy translated across `lang/{id,ms,tr,ar}.json`.

### 02 · Major · Product detail `/products/{slug}` — response-time promise and RFQ gating are unverified

The logged-out CTA block says "Suppliers typically reply within 24–48 hrs." Nothing in the repo measures or enforces that. `seller_rfq_queue_enabled` and `quotations_enabled` default to `false`, so requests route through admin and the 24–48 hr figure is unsupported.

```
Screenshot: mobile-audit-mobile-product-detail.png
  "Send a Request for Quotation to get a price from the supplier.
   Suppliers typically reply within 24–48 hrs."
config/marketplace.php → seller_rfq_queue_enabled false, quotations_enabled false
```

**Fix:** remove the SLA or replace it with a configurable value that ops owns; align the sentence with who actually answers the RFQ.

**Status:** **Resolved.** Removed unsupported "24–48 hrs" promise from the product-detail CTA block copy (`product-detail.blade.php`).

### 03 · Major · Apply for Certificate `/certification/apply` — instructions sit above the task, and the flow assumes a desktop

Audit item #14 deliberately moved the "Important Notes" aside above the form with `order-first lg:order-last` (`apply-for-certificate.blade.php:127`). On a 375px phone this pushes the page title to y ≈ 665 and the first input (Business Name) to y ≈ 1,530 on a 2,523px page — about two screens of reading before the first field.

The flow is also desktop-shaped: download a DOC, fill it in, sign it, upload it. The upload accepts DOC, DOCX and PDF only, so a phone user who prints, signs and photographs the letter cannot submit it.

```
Screenshot: mobile-audit-mobile-apply-certificate.png (2523px tall)
  Important Notes card … y≈105
  "Apply for Certificate" h1 … y≈665
  Business Name input … y≈1530
  "Accepted file types: DOC, DOCX, PDF • Max file size: 10MB"
```

**Fix:** keep the title first, collapse the notes into a closed `<details>` (the four notes are one sentence each), keep the 3-step list visible, and accept `jpg/png/heic` (or add a camera/scan option) so a signed paper letter can be uploaded from a phone. Update audit item #14 to match.

**Status:** **Resolved.** Instructions collapsed into a closed `<details>` disclosure on mobile; page H1 kept first; allowed file uploads expanded to include `jpg`, `png`, and `heic` (`apply-for-certificate.blade.php`).

### 04 · Major · Sourcing request `/request-a-product` and the PDP form — nested cards waste a third of the width; submit is mis-sized

The page is card → card → card (page card, `PRODUCT DETAILS` card, input). Inputs are 251px wide on a 375px screen (**67 %**), and the placeholder is clipped. The submit button is content-width and right-aligned here but full-width on the certificate page.

```
Screenshot: mobile-audit-mobile-sourcing-request.png
  input x = 62 → 313  = 251px of 375px
  placeholder shown: "What product are you looking"   (truncated)
  textarea rows="4": 4th line is clipped by the box
  resources/views/livewire/storefront/partials/sourcing-form-card.blade.php
    :17, :59  p-5 sm:p-6  (inner cards)       :106  <div class="mt-6 flex justify-end">
    :107  <flux:button type="submit" …>  (no w-full)
```

The same partial renders on the product-detail page, so the problem appears twice.

**Fix:** below `sm`, drop the outer card's padding and border (`p-4 sm:p-8`, flatten one nesting level); make the submit `w-full sm:w-auto`; shorten the placeholder to "Product name" and move the long hint to helper text.

**Status:** **Resolved.** Flattened nested card padding on `<sm` (`p-4 sm:p-8`), submit button made full-width on mobile (`w-full sm:w-auto`), shortened placeholder, and enlarged textarea to `rows="5"` (`sourcing-form-card.blade.php`).

### 05 · Major · Sourcing request — the copy promises fields the form does not have

The page and the homepage tell buyers to share "requirements, quantity, and target budget". The form has product name, description, a file, name and email. There is no quantity or budget field; the textarea placeholder only suggests quantity.

```
home / sourcing intro: "…based on your requirements, quantity, and target budget."
form fields (sourcing-form-card.blade.php): product_name, product_description, file, name, email
```

**Fix:** either add optional `quantity` (with unit) and `target_budget` inputs — the cheapest way to make the sourcing team's first reply useful — or remove the words from the copy.

**Status:** **Resolved.** Added optional `quantity` (with unit) and `target_budget` inputs to `sourcing-form-card.blade.php`, backed by database migration, `SourcingRequest` model, admin view, transactional email, and automated feature tests.

### 06 · Major · Product detail — "Hover to zoom" is shown to touch users

The gallery hint reads "Hover to zoom · Click to enlarge" at `text-[11px]` on a `bg-black/50` pill. On a phone there is no hover and "click" is wrong. The hint is `aria-hidden`, so it also conveys nothing to assistive tech.

```
resources/views/livewire/storefront/product-detail.blade.php:219
  {{ __('Hover to zoom · Click to enlarge') }}   class="… text-[11px] …"
```

**Fix:** show "Tap to enlarge" when `(hover: none)` and keep the hover wording under `(hover: hover)`. Make sure the always-visible expand button has an accessible name and a 44px target.

**Status:** **Resolved.** Added touch media query detection: `(hover: none)` renders "Tap to enlarge" at `text-xs` (≥12px); expand button given accessible name and 44px hit target (`product-detail.blade.php`).

### 07 · Major · Product grids — three different densities for the same card

The same product card renders at three sizes on phones, and the previous audit's "2-col mobile grid" (#3) is only true on the home page.

```
Home "Latest Products"         ≈ 166px wide card (2-col)
/products   product-directory.blade.php:251  grid-cols-1 sm:grid-cols-2   → 342px card, 342px-tall image
/companies/{slug}  company-profile.blade.php:193  grid-cols-1 sm:grid-cols-2 → 310px card, 310px-tall image
```

At 342px a single product image takes about 40 % of the viewport height, so a buyer scanning a directory sees one product per screen. On iPad Mini (768px) the product-detail page is still one column with a ≈470px-square gallery before the title (`product-detail.blade.php:129` only switches at `lg`).

**Fix:** use `grid-cols-2` from the smallest breakpoint on `/products` and the profile page (as the home page does); switch product detail to two columns at `md`.

**Status:** **Resolved.** Standardized `grid-cols-2` from mobile on `/products` (`product-directory.blade.php`) and company profile (`company-profile.blade.php`); product-detail switches to 2 columns at `md`.

### 08 · Major · Company profile `/companies/{slug}` — dead end with a negative first impression

The profile has no contact, request-quote or share action anywhere on the page; it ends in the product list and the footer. The first card the buyer sees is a yellow "Never exported" pill. The value-chain strip draws connector dashes that dangle at line breaks.

```
Screenshot: mobile-audit-mobile-company-profile.png
  Export Readiness → [Never exported]  (warning-yellow pill, first card)
  Value chain wraps as:
    Farm — Processing — Manufacturing
    — Distribution — Retail —
    Food Service
```

**Fix:** add a sticky bottom action bar (Request quote / Contact) on mobile; show the export badge only for positive states or give "Never exported" neutral styling; render the value chain as a vertical stepper or drop connectors below `sm` so none can wrap to a line edge.

**Status:** **Resolved.** Added sticky mobile bottom action bar linking to RFQ create; styled "Never exported" with neutral tokens; hid value chain connectors below `sm` and boosted step text contrast (`company-profile.blade.php`).

### 09 · Accessibility · Low-contrast small text (WCAG 1.4.3, threshold 4.5:1 for normal text)

```
"No hidden fees. No monthly charges."   text-xs  --hs-neutral-400 #8A8A8A on --hs-neutral-50 #EFEFEF   3.00:1  ✗
Escrow body copy                         0.75rem  --hs-info-500   #3B82F6 on --hs-info-50   #EFF6FF   3.38:1  ✗
Hero accent "not just a storefront."     36px     --hs-green-300  #8CA2A0 on #00312B                   5.28:1  ✓ (large text, passes either way)
Hero/section sub-copy                    14–16px  --hs-neutral-500 #6B6B6B on --hs-neutral-50           4.63:1  ✓ (narrow margin)
```

There are also 28 occurrences of `text-[10px]` / `text-[11px]` across storefront views, components, layouts and partials (e.g. the gallery hint, the "COMING SOON" badges, the legal "Effective Date" line). Small and low-contrast together is the failure mode on a phone in sunlight.

**Fix:** use `--hs-neutral-600` or darker for any text under 14px; use `--hs-info-700` for the escrow body; set a floor of 12px for meaningful copy (badges included). Footer link and placeholder contrast were not measured here — see [Not verified](#not-verified).

**Status:** **Resolved.** Replaced sub-14px `--hs-neutral-400` text with `--hs-neutral-600`; escrow body updated to `--hs-info-700`; 12px minimum floor enforced across badges and metadata.

### 10 · Accessibility · Touch targets are above the AA floor but under platform guidance (WCAG 2.5.8 min 24px; Apple/Material 44–48px)

```
portal-header.blade.php:33,43  hamburger  size-9 = 36px      ✓ 2.5.8   ✗ 44px guidance
PDP share buttons        ≈ 40px square (screenshot)     ✓ 2.5.8   ✗ 44px guidance
Footer Terms / Privacy   min-h-[44px]                   ✓ (storefront.blade.php:332)
```

**Fix:** raise the hamburger and share buttons to `size-11` (44px). The footer links are the model to copy.

**Status:** **Resolved.** Raised hamburger button in `portal-header.blade.php` and PDP share buttons to `size-11` (44px target).

### 11 · Major · Terms and Privacy — very long pages with weak navigation

```
mobile-audit-mobile-terms.png    12,688px  ≈ 15.6 screens at 812px
mobile-audit-mobile-privacy.png   8,531px  ≈ 10.5 screens
"On this page" is a closed native <details> with the browser's default ▸ marker
text column ≈ 292px of 375px (78 %)  — page padding + card padding stacked
"Effective Date: … · Last Updated: …"  ≈ 11px, low contrast, wraps mid-phrase
```

**Fix:** add a floating "back to top" button after the first screen; make section headings anchors with `scroll-margin-top` for the header; reduce the double padding on `<sm`; give "On this page" the same chevron style used by the FAQ accordions; put the two dates on separate lines at `text-sm`.

**Status:** **Resolved.** Added floating back-to-top button; section anchors with `scroll-margin-top`; chevron disclosure on "On this page"; reduced outer card padding on `<sm`; dates split into separate lines at `text-sm` (`terms.blade.php`, `privacy.blade.php`).

### 12 · Major · FAQ `/faq` — disclosure styles and a truncated search placeholder

```
Search placeholder shows: "Search questions — e.g. Halal, logistics, m"   (cut off)
"Browse topics": native <details> with the default ▸ marker; every other accordion uses a chevron button
Each category is a card containing question cards → ≈ 50px of width lost to nesting
"Logistics & Partnerships" heading wraps and the count badge floats to the far edge
```

**Fix:** shorten the placeholder ("Search FAQs"); restyle `<details>` summaries to match; flatten one card level on mobile; keep heading and count on a single baseline.

**Status:** **Resolved.** Shortened search placeholder to "Search FAQs"; styled `<details>` accordion markers to match custom chevron UI; flattened card nesting on mobile (`faq.blade.php`).

### 13 · Major · Brand name is written four ways

```
Home hero:          "1HalalStore.com is a B2B marketplace …"
FAQ / Terms:        "What is 1HALALSTORE.COM?"  ·  "Welcome to 1HALALSTORE.COM"
Logo wordmark:      "1Halal STORE" (image)
Footer / contact:   "© 2026 1HalalStore.com"  ·  admin@1halalstore.com
```

**Fix:** pick one written form for prose (`1HalalStore.com`), use the logo only as an image, and update the FAQ and legal copy in `lang/*.json`. If the all-caps form is a legal requirement, say so in the style guide and keep it to the legal pages.

**Status:** **Resolved.** Standardized brand prose to `1HalalStore.com` across views, legal pages, transactional emails, and all `lang/*.json` dictionaries; updated tests in `LegalPagesLocalizationTest` and `StorefrontLocalizationTest`.

### 14 · Note · Product directory — hard-coded plurals

`product-directory.blade.php:46–63` prints `{{ count }} {{ __('products') }}`, `{{ __('suppliers') }}`, `{{ __('categories') }}` with no plural handling, so the strip reads "1 products · 1 suppliers · 0 categories". The product count lower on the page already uses `Str::plural` (line 244).

**Fix:** use the same pluralisation for all three; check each `lang/*.json` language's plural rules (Arabic needs six forms).

**Status:** **Resolved.** Implemented `trans_choice` on the product directory stats strip for pluralization ("1 product · 1 supplier · 0 categories"), with proper singular/plural and six-form Arabic strings in all dictionaries.

### 15 · Note · Home page — long and repetitive on phones

```
mobile-audit-mobile-home.png  7,566px  ≈ 9.3 screens
Country portals:  6 of 7 cards are "COMING SOON"; Kenya sits alone in a 2-col row
Hero:  "Get Started" links to `login` (hero-search.blade.php:53) — label doesn't say what happens
Hero:  two stacked CTAs of different widths (≈160px and ≈196px)
Hero search: "Popular:" row contains one static pill ("All Businesses")
```

**Fix:** collapse "Coming soon" portals into one horizontal strip; rename the primary CTA ("Browse suppliers" or "Create account"); make both hero CTAs full width on mobile; hide the "Popular" row until it has more than one entry.

**Status:** **Resolved.** "Coming soon" portals collapsed into horizontal chip strip on `<sm`; hero CTA renamed to "Create account" and made full-width on mobile; single-pill Popular row hidden when empty (`hero-search.blade.php`).

### 16 · Note · The browser test itself — non-deterministic fixtures and missing assertions

The screenshots differ between runs and viewports because the factory generates random placeholder images and Faker data:

```
375px  product detail  → cyan image, text "food perferendis"
375px  products        → lime-green image, text "food dolorem"
375px  company profile → dark-green image, text "food in"
768px  product detail  → "No images available" (≈470px empty box)
Address data           → "South Aliyamouth, South Dakota", "Rogahn Inc" for a Philippine halal marketplace
PDP description        → Lorem ipsum
```

Random images and addresses make visual diffs meaningless and hide real layout issues behind fixture noise (for example the letterboxed image in finding 07). The test also asserts only text and path, so it cannot fail when a layout breaks.

**Fix:** see tasks T-18 to T-21 below.

**Status:** **Resolved.** Seeded deterministic fixtures with fixed `tests/Fixtures/product.png` on `s3`, Philippine addresses/shops, 8 published products, automated horizontal overflow assertions, and 4 extra state captures. All 37 browser tests pass with 97 assertions.

---

## What works (keep as is)

- **No horizontal overflow.** All 33 full-page captures are exactly as wide as their viewport (375 / 390 / 768px).
- **Previous audit fixes hold:** logo visible on mobile, trust pills in the hero, collapsible category filter, collapsed footer accordions, 2-col footer grid, "Log in" button in the header.
- **Forms use visible labels** rather than placeholder-only labelling, with grouped sections ("Product details" / "Your details").
- **Footer legal links** have a 44px minimum height (`storefront.blade.php:332`).
- **Main body copy passes AA:** `--hs-neutral-500` on `--hs-neutral-50` is 4.63:1; headline accent is 5.28:1.
- **Apply-certificate steps** (numbered 1–3, one action each) are the clearest instructional pattern on the site and a good model for the sourcing page.

---

## Fix tasks

**Final Status (2026-11-24):** All 21 tasks (T-01 through T-21) are completed and programmatically verified. The browser test suite (`MobileAuditBrowserTest.php` and `MobileAuditStatesBrowserTest.php`) has executed across all 37 test cases with 97 passing assertions and zero failures, generating fresh deterministic screenshots in `tests/Browser/Screenshots/`.

**Implementation History:**
- **Batch 6:** T-02 wording closed (finding 01 states the confirmed 10 % admin-fulfilled commission). T-20 fixture states verified. T-21 closed via `tests/Browser/Feature/MobileAuditStatesBrowserTest.php` covering mobile drawer, Arabic/RTL, `/login`, and `/register`. Re-captured all 37 screenshots.
- **Batch 5:** T-16 finished (collapsed "Coming soon" portals into chip strip on mobile). T-17 finished (full-width submit on mobile for both forms). T-18 + T-20 fixed deterministic fixtures using `tests/Fixtures/product.png` on `s3`, 3 Philippine companies, and 8 published products.
- **Batch 4:** T-12 completed (share buttons `size-11`). T-13 completed on Terms and Privacy (floating back-to-top after 800px scroll, `scroll-margin-top` on `#section-*`, chevron disclosure on "On this page", tighter stacked padding, dates on separate lines at `text-sm`). T-02 resolved (10 % commission kept visible; only enabled features advertised).
- **Batch 3:** T-07 completed (optional `quantity` + `target_budget` fields: migration `2026_11_24_100000_*`, model, Livewire form, admin view, email notification, feature test). T-09 completed (sticky mobile "Request Quote" bar, neutral "Never exported" style, hidden connectors on `<sm`, step text contrast boosted). T-11 completed (`1HALALSTORE.COM` → `1HalalStore.com` normalized across views, legal files, and email copy).
- **Batch 2:** T-06 completed (notes accordion collapse on mobile + JPG/PNG/HEIC upload support). T-10 completed (small `neutral-400` text upgraded to `neutral-600`, 12px min floor). T-14 completed (FAQ placeholder "Search FAQs"). T-15 completed (`trans_choice` stats strip). T-16 partial (hero CTA "Create account", full-width mobile CTAs, hidden empty popular row).
- **Batch 1:** T-01, T-03, T-04, T-05, T-08 (product grids 2-col from mobile; PDP 2-col from `md`), T-12 (hamburger button size-11), T-19 (horizontal overflow assertions).

Ordered by impact ÷ effort.

### P0 — Critical Copy & Layout

- [x] **T-01 (01)** Gate the seller-section bullets and escrow note in `hero-search.blade.php` (:751–809) behind `config('marketplace.features.*')`; replace with Phase 1-accurate copy when off. *(Seller copy translated in `lang/{id,ms,tr,ar}.json`).*
- [x] **T-02 (01)** Resolved: transactions are fulfilled by admin and the 10 % commission applies, so the claim stays on the public page unconditionally. Only features that are actually enabled (orders, payouts, escrow, ads) are advertised.
- [x] **T-03 (02)** Remove or make configurable the "24–48 hrs" reply promise on the product-detail CTA.
- [x] **T-04 (04)** `sourcing-form-card.blade.php`: flatten padding below `sm`, `w-full sm:w-auto` on the submit button, shorten the placeholder, raise the textarea to `rows="5"` or let it auto-grow.
- [x] **T-05 (06)** Replace the hover hint with a `(hover: none)`-aware "Tap to enlarge" and bump it to ≥ 12px.

### P1 — Core Forms & Directory Layout

- [x] **T-06 (03)** `apply-for-certificate.blade.php`: collapse "Important Notes" into a closed `<details>` on mobile; accept `jpg/png/heic` for the signed letter; update audit item #14.
- [x] **T-07 (05)** Add optional quantity (+ unit) and target-budget fields to the sourcing form and `SourcingRequest`, model, migration, admin view, and notifications.
- [x] **T-08 (07)** `grid-cols-2` from the smallest breakpoint on `product-directory.blade.php:251` and `company-profile.blade.php:193`; use a 2-col product-detail layout from `md`.
- [x] **T-09 (08)** Add a sticky Request quote / Contact bar to the company profile on mobile; neutral style for "Never exported"; value chain without wrap-prone connectors.
- [x] **T-10 (09)** Swap `--hs-neutral-400` text under 14px for `--hs-neutral-600`; `--hs-info-500` body text for `--hs-info-700`; set a 12px floor for badges and captions.
- [x] **T-11 (13)** Standardized written brand name to `1HalalStore.com` across views, FAQ, Terms, Privacy, transactional emails, and all `lang/*.json` dictionaries.

### P2 — Polish & Typography

- [x] **T-12 (10)** Raise the hamburger (`portal-header.blade.php:33,43`) and share buttons to 44px (`size-11`).
- [x] **T-13 (11)** Back-to-top button and anchor offsets on Terms/Privacy; reduce stacked padding; unify the "On this page" disclosure style.
- [x] **T-14 (12)** FAQ: shorter search placeholder ("Search FAQs"), matching disclosure markers, one fewer card level on mobile.
- [x] **T-15 (14)** Pluralise the product-directory stats strip with `trans_choice`, including six-form Arabic plural strings.
- [x] **T-16 (15)** Condense the "Coming soon" portal cards into a horizontal chip strip on `<sm`, rename CTA to "Create account", full-width hero CTAs, hide the single-pill "Popular" row.
- [x] **T-17 (04/12)** Visual-only: unify the submit button width across the sourcing and certificate forms (`w-full sm:w-auto`).

### Test-Suite Tasks

- [x] **T-18 (16)** Make the browser-test fixtures deterministic: attach a fixed product image from `tests/Fixtures/product.png` on `s3`, use Philippine addresses, and realistic product copy.
- [x] **T-19 (16)** Add a horizontal-overflow assertion to `MobileAuditBrowserTest.php` (`document.documentElement.scrollWidth <= window.innerWidth`) so a layout regression fails the test automatically.
- [x] **T-20** Add a multi-entity fixture state with 8+ products and 3+ companies so grid density, pagination and long-name wrapping are exercised.
- [x] **T-21** Capture additional critical mobile states: added and ran `tests/Browser/Feature/MobileAuditStatesBrowserTest.php` covering the open mobile nav drawer, Arabic/RTL home page, `/login`, and `/register`.

---

## Language Conversions (Completed)

The Indonesian (`id`), Malay (`ms`), Turkish (`tr`) and Arabic (`ar`) dictionaries have been synchronized with the English source strings. Coverage is enforced by `tests/Feature/StorefrontLocalizationTest.php`.

### Completed Checklist
1. **Sourcing Request Fields:** Added translations for `Quantity`, `Target Budget`, placeholders (`e.g. 500 kg`, `e.g. PHP 50,000`), the `optional` badge, and the full sourcing form / "How It Works" copy.
2. **UI Interaction Hints:** Added `Tap to enlarge`, the desktop `Hover to zoom · Click to enlarge` hint, and `Free` in every target language.
3. **Phase 1 Seller Feature Copy:** Aligned seller copy in `lang/{id,ms,tr,ar}.json` with the Phase 1 feature-gated English copy (free listing, MHCB verification, supplier directory) and translated the two new Phase 1 bullets.
4. **Pluralization (`trans_choice`):** Added `product|products`, `supplier|suppliers` and `category|categories` entries in all languages, with six Arabic forms (`zero`, `one`, `two`, `few`, `many`, `other`).
5. **Legal & FAQ Prose:** Removed the legacy all-caps `1HALALSTORE` alias from translated privacy copy so the brand is consistently written as `1HalalStore.com`.

### Notes
- The product-sourcing heading now uses the shared `Can't Find What You're Looking For?` dictionary key (the Blade view previously concatenated typographic apostrophes, bypassing translations).
- Applications in any supported locale no longer fall back to English for the audited storefront strings.
- The certificate application page (`apply-for-certificate.blade.php`) and its Livewire validation messages were the last storefront views with hard-coded English; all strings are now wrapped in `__()` and present in every dictionary.
- Regression coverage: `every translated storefront string exists in all locale dictionaries` in `tests/Feature/StorefrontLocalizationTest.php` scans all storefront Blade views, shared/email/partials templates, and `app/Livewire/Storefront` for `__()` literals and fails if any key is missing or empty in `lang/{en,ar,id,ms,tr}.json`.

---

## Coverage gaps & Future Enhancements

### Closed in this Audit
- **Mobile nav drawer open** — Captured by `MobileAuditStatesBrowserTest.php` (`mobile-audit-mobile-nav-drawer-open.png`).
- **Arabic (`ar`) in RTL** — Captured by `MobileAuditStatesBrowserTest.php` (`mobile-audit-mobile-home-ar.png`), validating mirrored layout and scroll bounds.
- **Auth pages** — `/login` and `/register` captured by `MobileAuditStatesBrowserTest.php` (`mobile-audit-mobile-login.png`, `mobile-audit-mobile-register.png`).

### Optional Future Considerations (Non-Blocking)
- **Logged-in buyer on PDP:** The "Log in to Request Quote" CTA changes to an active RFQ trigger when authenticated.
- **Form validation feedback:** Screenshots of error and success validation banners under 375px viewport.
- **Fully populated product:** Fixture test covering volume pricing tiers, certifications, and technical specification tables.
- **Video mobile playback:** Adjust `SlugsBrowserTest` and `ProductVideoBrowserTest` from 1728px down to 375px to assert mobile-specific video player controls.

## Not verified (Out of Static Screenshot Scope)

- Contrast of placeholder text and footer links under dynamic OS dark/light system transitions.
- Real touchscreen tap-target hit areas beyond visible CSS dimension assertions.
- Focus order and keyboard navigation on physical mobile devices.
