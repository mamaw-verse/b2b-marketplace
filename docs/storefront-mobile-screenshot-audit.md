# Storefront Mobile UI/UX Audit — Browser-Test Screenshots

**Date:** 2026-11-20 · **Branch:** `main` @ `9328a25`
**Source:** `tests/Browser/Screenshots/` captured by `tests/Browser/Feature/MobileAuditBrowserTest.php`
**Priority:** mobile first — `mobile` (375px) is the primary viewport, then `iPhone15` (390px), then `iPadMini` (768px)
**Pages covered (11 × 3 viewports = 33 captures):** home, companies, company-profile, products, product-detail, about, faq, sourcing-request, apply-certificate, terms, privacy
**Not covered:** auth pages, logged-in flows, the open nav drawer, Arabic/RTL (see [Coverage gaps](#coverage-gaps)).

This is a follow-up to [`storefront-mobile-audit.md`](./storefront-mobile-audit.md). That document tracks fixes applied to the Blade views; this one audits what the browser test actually renders today and records what is still wrong.

## How this was audited

- **Screenshots** — every `mobile-audit-mobile-*` capture was read in full; `iPhone15` and `iPadMini` were compared for the home, products and product-detail pages.
- **Source cross-check** — every claim, class and token cited below was confirmed in the Blade view, `lang/en.json`, `config/marketplace.php` or `resources/css/tokens.css`. File and line references are given where relevant.
- **Contrast** — computed from the CSS tokens (WCAG 2.2 relative-luminance formula), **not** estimated from pixels.
- **Not measurable from a static screenshot:** focus order, hover/active/disabled states, tap-target hit areas (only visible sizes are reported), animation, and real network behaviour. Where a finding depends on one of these it says so.

## Summary

| Severity | Count | Findings |
|---|---|---|
| Critical | 1 | 01 |
| Major | 10 | 02–08, 11–13 |
| Accessibility | 2 | 09, 10 |
| Note | 3 | 14–16 |

**Headline:** the layout is structurally sound on phones — no page overflows horizontally and the previous audit's fixes are visible. The serious problems are not layout problems: the homepage advertises seller features the product has switched off (01), several mobile forms waste a third of the screen width (04), and a few interaction hints are written for desktop (06).

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

### 02 · Major · Product detail `/products/{slug}` — response-time promise and RFQ gating are unverified

The logged-out CTA block says "Suppliers typically reply within 24–48 hrs." Nothing in the repo measures or enforces that. `seller_rfq_queue_enabled` and `quotations_enabled` default to `false`, so requests route through admin and the 24–48 hr figure is unsupported.

```
Screenshot: mobile-audit-mobile-product-detail.png
  "Send a Request for Quotation to get a price from the supplier.
   Suppliers typically reply within 24–48 hrs."
config/marketplace.php → seller_rfq_queue_enabled false, quotations_enabled false
```

**Fix:** remove the SLA or replace it with a configurable value that ops owns; align the sentence with who actually answers the RFQ.

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

### 05 · Major · Sourcing request — the copy promises fields the form does not have

The page and the homepage tell buyers to share "requirements, quantity, and target budget". The form has product name, description, a file, name and email. There is no quantity or budget field; the textarea placeholder only suggests quantity.

```
home / sourcing intro: "…based on your requirements, quantity, and target budget."
form fields (sourcing-form-card.blade.php): product_name, product_description, file, name, email
```

**Fix:** either add optional `quantity` (with unit) and `target_budget` inputs — the cheapest way to make the sourcing team's first reply useful — or remove the words from the copy.

### 06 · Major · Product detail — "Hover to zoom" is shown to touch users

The gallery hint reads "Hover to zoom · Click to enlarge" at `text-[11px]` on a `bg-black/50` pill. On a phone there is no hover and "click" is wrong. The hint is `aria-hidden`, so it also conveys nothing to assistive tech.

```
resources/views/livewire/storefront/product-detail.blade.php:219
  {{ __('Hover to zoom · Click to enlarge') }}   class="… text-[11px] …"
```

**Fix:** show "Tap to enlarge" when `(hover: none)` and keep the hover wording under `(hover: hover)`. Make sure the always-visible expand button has an accessible name and a 44px target.

### 07 · Major · Product grids — three different densities for the same card

The same product card renders at three sizes on phones, and the previous audit's "2-col mobile grid" (#3) is only true on the home page.

```
Home "Latest Products"         ≈ 166px wide card (2-col)
/products   product-directory.blade.php:251  grid-cols-1 sm:grid-cols-2   → 342px card, 342px-tall image
/companies/{slug}  company-profile.blade.php:193  grid-cols-1 sm:grid-cols-2 → 310px card, 310px-tall image
```

At 342px a single product image takes about 40 % of the viewport height, so a buyer scanning a directory sees one product per screen. On iPad Mini (768px) the product-detail page is still one column with a ≈470px-square gallery before the title (`product-detail.blade.php:129` only switches at `lg`).

**Fix:** use `grid-cols-2` from the smallest breakpoint on `/products` and the profile page (as the home page does); switch product detail to two columns at `md`.

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

### 09 · Accessibility · Low-contrast small text (WCAG 1.4.3, threshold 4.5:1 for normal text)

```
"No hidden fees. No monthly charges."   text-xs  --hs-neutral-400 #8A8A8A on --hs-neutral-50 #EFEFEF   3.00:1  ✗
Escrow body copy                         0.75rem  --hs-info-500   #3B82F6 on --hs-info-50   #EFF6FF   3.38:1  ✗
Hero accent "not just a storefront."     36px     --hs-green-300  #8CA2A0 on #00312B                   5.28:1  ✓ (large text, passes either way)
Hero/section sub-copy                    14–16px  --hs-neutral-500 #6B6B6B on --hs-neutral-50           4.63:1  ✓ (narrow margin)
```

There are also 28 occurrences of `text-[10px]` / `text-[11px]` across storefront views, components, layouts and partials (e.g. the gallery hint, the "COMING SOON" badges, the legal "Effective Date" line). Small and low-contrast together is the failure mode on a phone in sunlight.

**Fix:** use `--hs-neutral-600` or darker for any text under 14px; use `--hs-info-700` for the escrow body; set a floor of 12px for meaningful copy (badges included). Footer link and placeholder contrast were not measured here — see [Not verified](#not-verified).

### 10 · Accessibility · Touch targets are above the AA floor but under platform guidance (WCAG 2.5.8 min 24px; Apple/Material 44–48px)

```
portal-header.blade.php:33,43  hamburger  size-9 = 36px      ✓ 2.5.8   ✗ 44px guidance
PDP share buttons        ≈ 40px square (screenshot)     ✓ 2.5.8   ✗ 44px guidance
Footer Terms / Privacy   min-h-[44px]                   ✓ (storefront.blade.php:332)
```

**Fix:** raise the hamburger and share buttons to `size-11` (44px). The footer links are the model to copy.

### 11 · Major · Terms and Privacy — very long pages with weak navigation

```
mobile-audit-mobile-terms.png    12,688px  ≈ 15.6 screens at 812px
mobile-audit-mobile-privacy.png   8,531px  ≈ 10.5 screens
"On this page" is a closed native <details> with the browser's default ▸ marker
text column ≈ 292px of 375px (78 %)  — page padding + card padding stacked
"Effective Date: … · Last Updated: …"  ≈ 11px, low contrast, wraps mid-phrase
```

**Fix:** add a floating "back to top" button after the first screen; make section headings anchors with `scroll-margin-top` for the header; reduce the double padding on `<sm`; give "On this page" the same chevron style used by the FAQ accordions; put the two dates on separate lines at `text-sm`.

### 12 · Major · FAQ `/faq` — disclosure styles and a truncated search placeholder

```
Search placeholder shows: "Search questions — e.g. Halal, logistics, m"   (cut off)
"Browse topics": native <details> with the default ▸ marker; every other accordion uses a chevron button
Each category is a card containing question cards → ≈ 50px of width lost to nesting
"Logistics & Partnerships" heading wraps and the count badge floats to the far edge
```

**Fix:** shorten the placeholder ("Search FAQs"); restyle `<details>` summaries to match; flatten one card level on mobile; keep heading and count on a single baseline.

### 13 · Major · Brand name is written four ways

```
Home hero:          "1HalalStore.com is a B2B marketplace …"
FAQ / Terms:        "What is 1HALALSTORE.COM?"  ·  "Welcome to 1HALALSTORE.COM"
Logo wordmark:      "1Halal STORE" (image)
Footer / contact:   "© 2026 1HalalStore.com"  ·  admin@1halalstore.com
```

**Fix:** pick one written form for prose (`1HalalStore.com`), use the logo only as an image, and update the FAQ and legal copy in `lang/*.json`. If the all-caps form is a legal requirement, say so in the style guide and keep it to the legal pages.

### 14 · Note · Product directory — hard-coded plurals

`product-directory.blade.php:46–63` prints `{{ count }} {{ __('products') }}`, `{{ __('suppliers') }}`, `{{ __('categories') }}` with no plural handling, so the strip reads "1 products · 1 suppliers · 0 categories". The product count lower on the page already uses `Str::plural` (line 244).

**Fix:** use the same pluralisation for all three; check each `lang/*.json` language's plural rules (Arabic needs six forms).

### 15 · Note · Home page — long and repetitive on phones

```
mobile-audit-mobile-home.png  7,566px  ≈ 9.3 screens
Country portals:  6 of 7 cards are "COMING SOON"; Kenya sits alone in a 2-col row
Hero:  "Get Started" links to `login` (hero-search.blade.php:53) — label doesn't say what happens
Hero:  two stacked CTAs of different widths (≈160px and ≈196px)
Hero search: "Popular:" row contains one static pill ("All Businesses")
```

**Fix:** collapse "Coming soon" portals into one horizontal strip; rename the primary CTA ("Browse suppliers" or "Create account"); make both hero CTAs full width on mobile; hide the "Popular" row until it has more than one entry.

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

**Progress (2026-11-24, batch 6):** T-02 wording closed (finding 01 now states the confirmed 10 % admin-fulfilled commission). T-20 checked off. T-21 partly done: new `tests/Browser/Feature/MobileAuditStatesBrowserTest.php` covers drawer, Arabic/RTL and auth pages — **run it and re-capture all screenshots**. Translations: new UI strings stay English-only by decision; the owner will handle `lang/*.json`. T-11 (`1HalalStore.com`) is final.

**Progress (2026-11-24, batch 5):** T-16 finished (below `sm`, "Coming soon" portals collapse into a single chip strip; cards stay on `sm+`). T-17: both forms now have a full-width submit on phones (certificate was already full width). T-18 + T-20: `MobileAuditBrowserTest` fixtures are now fixed — `tests/Fixtures/product.png` stored on the `s3` disk, 3 Philippine companies (incl. a long name), 8 published products with real copy. **Screenshots must be re-captured** and the old ones in `tests/Browser/Screenshots/` replaced. Remaining: T-21 (extra capture states) and T-02 doc wording.

**Progress (2026-11-24, batch 4):** T-12 done (share buttons `size-11`), T-13 done on Terms and Privacy (floating back-to-top after 800px scroll, `scroll-margin-top` on `#section-*`, chevron on "On this page", tighter stacked padding, dates on separate lines at `text-sm`). T-02 resolved: 10 % commission stays visible; only enabled features are advertised.

**Progress (2026-11-24, batch 3):** T-07 (optional free-text `quantity` + `target_budget`: migration `2026_11_24_100000_*`, model, Livewire form, admin detail, admin email, test — **run `php artisan migrate`**), T-09 (sticky "Request Quote" bar on mobile company profile reusing `account.rfqs.create?shop_id=`, "Never exported" now neutral, value-chain connectors hidden below `sm`, inactive step text `neutral-600`, compact chip 12px), T-11 (`1HALALSTORE.COM` → `1HalalStore.com` in FAQ/Terms/Privacy views, 5 lang files and `LegalPagesLocalizationTest`; revert the legal pages if counsel requires the all-caps form). New UI strings added only in English (Quantity, Target Budget, optional, e.g. …, Tap to enlarge, Free, …) — other languages fall back to English.

**Progress (2026-11-24, batch 2):** T-06 (notes collapse on mobile + JPG/PNG/HEIC upload, tests added), T-10 (19 small `neutral-400` texts → `neutral-600`, 11 `text-[10/11px]` → `text-xs` in storefront views), T-14 (FAQ placeholder "Search FAQs", 5 lang files), T-15 (`trans_choice` stats strip), T-16 partial (hero CTA now "Create account" → register, full width on mobile; Popular row hidden when no categories; the single-pill row was a fixture artifact — categories render there when present; Coming-soon strip not done).

**Progress (batch 1):** T-01, T-03, T-04, T-05, T-08 (partial: grids + PDP `md` split), T-12 (hamburgers; share buttons still to do), T-19 done in code but **not run** (no PHP in the authoring environment). T-02: 10 % commission confirmed (admin-fulfilled transactions) and kept visible. Also closed from `storefront-mobile-audit.md`: A4 (login row `flex-wrap`), A9 (sticky register CTA). A7 not applicable: register shows a single hint line plus a 4-segment strength meter, not a 5-item list.

Ordered by impact ÷ effort. Check items off as they land.

### P0 — this week

- [x] **T-01 (01)** Gate the seller-section bullets and escrow note in `hero-search.blade.php` (:751–809) behind `config('marketplace.features.*')`; replace with Phase 1-accurate copy when off. Update the translations in `lang/{en,id,ms,tr,ar}.json`.
- [x] **T-02 (01)** ~~Confirm the 10 % commission~~ Resolved: transactions are fulfilled by admin and the 10 % commission applies, so the claim stays on the public page unconditionally. Only features that are actually enabled (orders, payouts, escrow, ads) are advertised.
- [x] **T-03 (02)** Remove or make configurable the "24–48 hrs" reply promise on the product-detail CTA.
- [x] **T-04 (04)** `sourcing-form-card.blade.php`: flatten padding below `sm`, `w-full sm:w-auto` on the submit button, shorten the placeholder, raise the textarea to `rows="5"` or let it auto-grow.
- [x] **T-05 (06)** Replace the hover hint with a `(hover: none)`-aware "Tap to enlarge" and bump it to ≥ 12px.

### P1 — next sprint

- [x] **T-06 (03)** `apply-for-certificate.blade.php`: collapse "Important Notes" into a closed `<details>` on mobile; accept `jpg/png/heic` for the signed letter; update audit item #14.
- [x] **T-07 (05)** Add optional quantity (+ unit) and target-budget fields to the sourcing form and `SourcingRequest`, or remove the words from the copy.
- [x] **T-08 (07)** `grid-cols-2` from the smallest breakpoint on `product-directory.blade.php:251` and `company-profile.blade.php:193`; use a 2-col product-detail layout from `md`.
- [x] **T-09 (08)** Add a sticky Request quote / Contact bar to the company profile on mobile; neutral style for "Never exported"; value chain without wrap-prone connectors.
- [x] **T-10 (09)** Swap `--hs-neutral-400` text under 14px for `--hs-neutral-600`; `--hs-info-500` body text for `--hs-info-700`; set a 12px floor for badges and captions.
- [x] **T-11 (13)** Choose one written form of the brand name and normalise FAQ, Terms, Privacy and email copy.

### P2 — polish

- [x] **T-12 (10)** Raise the hamburger (`portal-header.blade.php:33,43`) and share buttons to 44px.
- [x] **T-13 (11)** Back-to-top button and anchor offsets on Terms/Privacy; reduce stacked padding; unify the "On this page" disclosure style.
- [x] **T-14 (12)** FAQ: shorter search placeholder, matching disclosure markers, one fewer card level on mobile.
- [x] **T-15 (14)** Pluralise the product-directory stats strip (and the matching strings in every `lang/*.json`).
- [x] **T-16 (15)** Condense the "Coming soon" portal cards, rename "Get Started", full-width hero CTAs, hide the single-pill "Popular" row.
- [x] **T-17 (04/12)** Visual-only: unify the submit button width across the sourcing and certificate forms once T-04 lands.

### Test-suite tasks

- [x] **T-18 (16)** Make the browser-test fixtures deterministic: attach a fixed product image from `tests/Fixtures/`, use Philippine addresses, and a short real product description.
- [x] **T-19 (16)** Add a horizontal-overflow assertion to `MobileAuditBrowserTest.php` (e.g. `document.documentElement.scrollWidth <= window.innerWidth`) so a layout regression fails the test, not just changes a screenshot.
- [x] **T-20** Add a second fixture state with 8+ products and 3+ companies so grid density, pagination and long-name wrapping are exercised.
- [~] **T-21** Capture the missing states listed below. Added `MobileAuditStatesBrowserTest.php` (open drawer, Arabic/RTL home, `/login`, `/register`); **not run** (no PHP in the authoring environment). Still open: logged-in buyer, form error/success states, fully populated product, 1728px video captures.

---

## Coverage gaps

The test captures only the logged-out, English, closed-drawer state. Add captures for:

- **Mobile nav drawer open** (hamburger is the only nav on phones, and it is a 36px target).
- **Arabic (`ar`) in RTL** — `lang/ar.json` ships, but no screenshot checks mirrored layout, the chevron direction or the hero search icon.
- **Auth pages** (`/login`, `/register`) and **a logged-in buyer** on the product page (the "Log in to Request Quote" CTA changes state).
- **Form error and success states** for the sourcing and certificate forms, including validation messages under a 375px layout.
- **A fully populated product** (price, MOQ, certification, specs) — the current fixture shows none of these, so it is not possible to judge from the screenshots whether the product page gives a B2B buyer what they need to qualify a supplier.
- The slug/video tests (`SlugsBrowserTest`, `ProductVideoBrowserTest`) capture at **1728px**; the "inline playable for WebKit (Safari and iOS)" screenshot is therefore a desktop capture and does not verify mobile behaviour.

## Not verified

- Contrast of placeholder text, footer links and the "COMING SOON" badges (no token or computed value available from the screenshots).
- Real tap-target hit areas — only visible sizes were measured.
- Focus order, keyboard behaviour, hover/disabled states, and the drawer.
- Whether the product-detail page omits price/MOQ by design or only in the fixture (see Coverage gaps).
