# Portal Mobile UI/UX Audit & Implementation Plan

> **Progress:** ✅ = done, unmarked = pending. Work is done and pushed by group. Changes are not yet verified with Pint, `npm run build` or the browser test (no PHP toolchain in the authoring environment); run them before release.

| Group | Scope | Status |
|-------|-------|--------|
| 1 | Seller Critical: S1, S2, S3 | ✅ Done |
| 2 | Seller High: S4–S8 | Pending |
| 3 | Seller Medium: S9–S14 | Pending |
| 4 | Seller Low: S15–S20 + colour tokens | Pending |
| 5 | Admin Part 1: queue tables, filters, date picker, colour tokens | Pending |

Covers the authenticated portals at mobile widths. Part 1 is the admin portal (`/admin/*`); Part 2 is the seller portal (`/portal/*` and `/hub/*`).

| Part | Portal | Method | Status |
|------|--------|--------|--------|
| 1 | Admin (`/admin/*`) | Source review of Blade views | Plan written, implementation pending |
| 2 | Seller (`/portal/*`, `/hub/*`) | Screenshot review (iPhone 15, 390px) + source confirmation | Implementation in progress: Group 1 (Critical S1–S3) done |

---

# Part 1 — Admin Portal

## Summary

All `/admin/*` list/queue views render tables that force horizontal scroll on mobile. This document catalogs every affected file, the specific issues, and the fix plan.

**Fix pattern:** For each table view:
1. Wrap the existing table in `hidden lg:block` (desktop only)
2. Add a mobile card list with `lg:hidden` (mobile only) — stacked card per row with label/value pairs
3. Fix any raw Tailwind color violations → replace with `var(--hs-*)` design tokens
4. Fix any native `<input type="date">` → `flux:date-picker`

---

## Issue #1 — Tables force horizontal scroll on mobile

Flux's `<flux:table>` wraps in `<ui-table-scroll-area class="overflow-auto">` with `table-fixed` and `min-w-full` — wide tables overflow horizontally on narrow viewports. Raw HTML `<table>` elements have no wrapper at all and bleed past the viewport edge.

**Solution:** Show a stacked card list on mobile (`lg:hidden`), keep the table for desktop only (`hidden lg:block`).

### Affected files (17 tables across 16 views)

| # | File | Table type | Columns | Notes |
|---|------|-----------|---------|-------|
| 1 | `subscription-queue.blade.php` | flux:table ×2 | 5, 5 | Two tabs: subscriptions + pending payments |
| 2 | `onboarding-queue.blade.php` | flux:table | 5 | Row click navigates to detail |
| 3 | `certification-queue.blade.php` | flux:table ×2 | 5, 3 | Two tables: certs + applications |
| 4 | `sourcing-queue.blade.php` | flux:table | 6 | |
| 5 | `merchants/merchant-list.blade.php` | flux:table | 7 | Filter row also wraps badly on mobile |
| 6 | `products/product-moderation-list.blade.php` | flux:table | 5 | Has inline confirm modals per row |
| 7 | `finance/financial-ledger.blade.php` | raw `<table>` | 6 | Also has native `input type=date` |
| 8 | `rfq/rfq-list.blade.php` | flux:table | 5 | |
| 9 | `disputes/dispute-queue.blade.php` | raw `<table>` | 6 | No overflow wrapper at all |
| 10 | `payouts/payout-queue.blade.php` | raw `<table>` | 7 | No overflow wrapper at all |
| 11 | `payouts/eligible-payouts.blade.php` | raw `<table>` | 4 | No overflow wrapper at all |
| 12 | `payout-accounts/payout-account-queue.blade.php` | flux:table | 6 | |
| 13 | `sponsored-content/sponsored-content-list.blade.php` | flux:table | 6 | Also a table inside the tier-settings modal |
| 14 | `payouts/payout-detail.blade.php` | raw `<table>` ×2 | 5, 4 | Payout items + disbursements |
| 15 | `category-manager.blade.php` | flux:table | 4 | Parent/child category tree rows |
| — | `sponsored-content/sponsored-content-tier-settings.blade.php` | flux:table | 5 | Separate page; lower priority |
| — | `reviews/review-report-queue.blade.php` | no table | — | Already uses stacked card list ✓ |

### Views that are already mobile-safe (no changes needed)

- `onboarding-review.blade.php` — detail page, grid stacks at `xl:`
- `sourcing-request-detail.blade.php` — detail page, grid stacks at `xl:`
- `disputes/dispute-arbitration.blade.php` — 2-col grid stacks at `lg:`
- `payout-settings.blade.php` — single-column form, already responsive
- `merchants/merchant-show.blade.php` — detail page, grid stacks at `md:`
- `analytics-dashboard.blade.php` — KPI cards already use `grid-cols-2` on mobile
- `category-create.blade.php` / `category-edit.blade.php` — forms, already responsive
- `sponsored-content/sponsored-content-composer.blade.php` — form, already responsive
- `rfq/rfq-detail.blade.php` — detail page, grid stacks at `md:`
- `document-viewer.blade.php` — modal, already responsive

---

## Issue #2 — Raw HTML tables have no overflow wrapper

These raw `<table>` elements have no `overflow-x-auto` wrapper, so they directly overflow the viewport:

- `finance/financial-ledger.blade.php`
- `disputes/dispute-queue.blade.php`
- `payouts/payout-queue.blade.php`
- `payouts/eligible-payouts.blade.php`
- `payouts/payout-detail.blade.php` (both tables)

**Fix:** Wrap in `hidden lg:block overflow-x-auto` + add mobile card list.

---

## Issue #3 — Native `<input type="date">` in financial-ledger

`financial-ledger.blade.php` uses `<flux:input type="date">` for the date filters. The project rules state: "No native `<select>` or `<input type="date">` — use Flux UI components. Always use `<flux:date-picker>` for date inputs."

**Fix:** Replace `<flux:input type="date" ...>` with `<flux:date-picker ...>`.

Also present in `analytics-dashboard.blade.php` (custom range picker) — but that view uses it for a Chart.js dashboard filter, and `flux:date-picker` may not be the right UX there. Flagged but lower priority.

---

## Issue #4 — Raw Tailwind color violations

162 instances across 14 files. The views rules require `var(--hs-*)` design tokens instead of raw `text-zinc-*`, `text-red-*`, `text-green-*`, `text-blue-*`, `text-yellow-*`, `bg-zinc-*`, etc.

### Files with violations (by count)

| File | Count | Key violations |
|------|-------|----------------|
| `analytics-dashboard.blade.php` | 58 | `text-zinc-500`, `text-zinc-900`, `text-white`, `dark:text-zinc-400`, `dark:text-white`, `border-zinc-200`, `dark:border-zinc-700`, `text-red-600`, `dark:text-red-400`, `hover:ring-amber-400/60` |
| `merchants/merchant-show.blade.php` | 45 | `text-zinc-500`, `text-zinc-900`, `dark:text-white` |
| `rfq/rfq-detail.blade.php` | 22 | `text-zinc-500`, `text-zinc-900`, `dark:text-white` |
| `payouts/payout-detail.blade.php` | 6 | `text-red-600`, `text-green-700` |
| `merchants/merchant-list.blade.php` | 5 | `text-zinc-900`, `dark:text-white`, `text-zinc-500` |
| `products/product-moderation-list.blade.php` | 4 | `text-blue-600`, `text-yellow-600`, `hover:bg-yellow-50`, `text-zinc-500` |
| `rfq/rfq-list.blade.php` | 4 | `text-zinc-900`, `dark:text-white`, `text-zinc-500` |
| `payout-settings.blade.php` | 4 | `text-red-500` (validation errors — acceptable per rules) |
| `category-create.blade.php` | 4 | `text-red-500` (validation errors — acceptable per rules) |
| `category-edit.blade.php` | 4 | `text-red-500` (validation errors — acceptable per rules) |
| `subscription-queue.blade.php` | 2 | `text-red-500` (validation errors — acceptable per rules) |
| `sponsored-content/sponsored-content-composer.blade.php` | 2 | `text-red-600` (on remove button overlays) |
| `payout-accounts/payout-account-queue.blade.php` | 1 | `text-red-500` (validation error — acceptable) |
| `reviews/review-report-queue.blade.php` | 1 | `text-yellow-400` (star rating) |

### Token mapping

| Raw class | Design token replacement |
|-----------|-------------------------|
| `text-zinc-500` | `text-[var(--hs-neutral-500)]` |
| `text-zinc-900` | `text-[var(--hs-neutral-900)]` |
| `text-zinc-400` | `text-[var(--hs-neutral-400)]` |
| `text-zinc-700` | `text-[var(--hs-neutral-700)]` |
| `text-zinc-600` | `text-[var(--hs-neutral-600)]` |
| `text-zinc-300` | `text-[var(--hs-neutral-300)]` |
| `dark:text-zinc-400` | (remove — tokens auto-invert) |
| `dark:text-white` | (remove — tokens auto-invert) |
| `dark:text-zinc-500` | (remove — tokens auto-invert) |
| `text-red-500` | (keep — validation error, acceptable per rules) |
| `text-red-600` | `text-[var(--hs-danger-700)]` |
| `text-green-700` | `text-[var(--hs-green-700)]` |
| `text-blue-600` | `text-[var(--hs-green-700)]` (or `--hs-info-700` for info links) |
| `text-yellow-600` | `text-[var(--hs-warning-700)]` |
| `text-yellow-400` | `text-[var(--hs-warning-500)]` (star rating) |
| `hover:bg-yellow-50` | `hover:bg-[var(--hs-warning-50)]` |
| `hover:text-yellow-700` | `hover:text-[var(--hs-warning-700)]` |
| `border-zinc-200` | `border-[var(--hs-neutral-200)]` |
| `dark:border-zinc-700` | (remove — tokens auto-invert) |
| `hover:ring-amber-400/60` | `hover:ring-[var(--hs-warning-500)]/60` |
| `text-red-600 dark:text-red-400` | `text-[var(--hs-danger-700)]` |
| `text-white` (on KPI values) | `text-[var(--hs-neutral-900)]` |

### Notes on acceptable violations

- `text-red-500` on `@error` blocks is explicitly acceptable per the views rules
- `text-white` on solid brand buttons is fine per the rules exception
- The `bg-white/90` + `text-red-600` on the sponsored-content-composer image overlay buttons are inside a dark overlay context — these may need to stay for contrast on the image, but the `text-red-600` should become `text-[var(--hs-danger-700)]`

---

## Issue #5 — Filter rows that wrap badly on mobile

### `merchants/merchant-list.blade.php`

The filter row uses `flex gap-2` with three `w-40`/`w-44` selects side by side. On mobile this doesn't wrap cleanly — it needs `flex-col` on mobile and `flex-row` on `md+`, and the fixed widths should be removed on mobile.

```blade
{{-- Current --}}
<div class="flex gap-2">
    <flux:select class="w-40">...</flux:select>
    <flux:select class="w-40">...</flux:select>
    <flux:select class="w-44">...</flux:select>
</div>

{{-- Fix --}}
<div class="flex flex-col gap-2 md:flex-row">
    <flux:select class="md:w-40 w-full">...</flux:select>
    <flux:select class="md:w-40 w-full">...</flux:select>
    <flux:select class="md:w-44 w-full">...</flux:select>
</div>
```

### `sponsored-content/sponsored-content-list.blade.php`

The filter row uses `flex-col sm:flex-row` which is fine, but the search input has `max-w-xs` that may be too narrow. Low priority.

### `finance/financial-ledger.blade.php`

The filter row uses `flex flex-wrap items-end gap-4` — this wraps but the date inputs are not full width on mobile. Fix alongside the date picker replacement.

---

## Implementation Order

1. **subscription-queue.blade.php** — 2 tables, both flux:table
2. **onboarding-queue.blade.php** — 1 flux:table
3. **certification-queue.blade.php** — 2 flux:tables
4. **sourcing-queue.blade.php** — 1 flux:table
5. **merchant-list.blade.php** — 1 flux:table + filter row fix
6. **product-moderation-list.blade.php** — 1 flux:table
7. **financial-ledger.blade.php** — 1 raw table + date picker fix
8. **rfq-list.blade.php** — 1 flux:table
9. **dispute-queue.blade.php** — 1 raw table
10. **payout-queue.blade.php** — 1 raw table
11. **eligible-payouts.blade.php** — 1 raw table
12. **payout-account-queue.blade.php** — 1 flux:table
13. **sponsored-content-list.blade.php** — 1 flux:table
14. **payout-detail.blade.php** — 2 raw tables
15. **category-manager.blade.php** — 1 flux:table (tree structure)
16. **Color token fixes** — all files with raw Tailwind colors
17. **Pint + build verification**

---

## Mobile Card List Pattern

Standard pattern to use for each table → card conversion:

```blade
{{-- Mobile: stacked card list (hidden on lg+) --}}
<div class="lg:hidden divide-y divide-[var(--hs-neutral-100)]">
    @forelse ($items as $item)
        <div class="px-6 py-4">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-medium text-[var(--hs-neutral-900)]">{{ $item->name }}</p>
                    <p class="text-sm text-[var(--hs-neutral-500)]">{{ $item->subtitle }}</p>
                </div>
                {{-- Status badge --}}
                <flux:badge :color="$statusColor">{{ $statusLabel }}</flux:badge>
            </div>
            <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                <div>
                    <dt class="text-xs text-[var(--hs-neutral-500)]">{{ __('Field Label') }}</dt>
                    <dd class="text-[var(--hs-neutral-900)]">{{ $item->field }}</dd>
                </div>
                {{-- More fields... --}}
            </dl>
            {{-- Action buttons row (if any) --}}
            <div class="mt-3 flex gap-2">
                <flux:button variant="ghost" size="sm" href="..." wire:navigate>
                    {{ __('View') }}
                </flux:button>
            </div>
        </div>
    @empty
        <div class="px-6 py-10 text-center text-sm text-[var(--hs-neutral-500)]">
            {{ __('No items found.') }}
        </div>
    @endforelse
</div>

{{-- Desktop: table (hidden below lg) --}}
<div class="hidden overflow-x-auto lg:block">
    <flux:table>
        {{-- ...existing table markup... --}}
    </flux:table>
</div>
```

### Key rules for card lists
- Use `px-6` padding to match the card header
- Use `divide-y` for row separators
- Primary identifier (business name, title) goes at top with `font-medium`
- Status badges go top-right
- Secondary fields in a 2-col `<dl>` grid
- Action buttons in a bottom row, not a dropdown menu
- Empty state: simple centered text message
- Pagination: shared between both views (place outside the table/card split)

---

# Part 2 — Seller Portal

## Summary

25 screenshots from `tests/Browser/Feature/SellerPortalE2EBrowserTest.php` (iPhone 15 viewport, 390px wide, logged in as a seller with an active shop) were reviewed page by page. Findings were then confirmed against the Blade sources in `resources/views/livewire/company-hub/` and `resources/views/livewire/shared/`.

Overall the portal shell (header, hamburger nav, card layout, `--hs-*` tokens) holds up well on mobile, and the product list, RFQ queue and customers list already use mobile-first patterns. The problems cluster in three places: **pages that break the viewport** (certificates, messages), **data tables that are clipped** (order items, payout breakdown), and **long forms and navigation** (product tabs, shop profile, order builder).

| Severity | Count | Meaning |
|----------|-------|---------|
| Critical | 3 | Layout breaks or content is unreachable |
| High | 5 | Core data clipped, rule violations, or poor usability on a main flow |
| Medium | 6 | Usability friction or inconsistency |
| Low | 6 | Polish and content |

### Screenshot coverage

| Screenshot | Page | Result |
|------------|------|--------|
| `seller-portal-dashboard` / `login-landing` | `/portal/dashboard` | OK, see S15 |
| `seller-portal-products` | `/hub/products` | Issue S6 |
| `seller-portal-product-create` / `product-edit` | `/hub/products/create`, `/{id}/edit` | Issue S11 |
| `seller-portal-product-variants` | `/hub/products/{id}/variants` | Issue S3 (tabs) |
| `seller-portal-product-gallery` | `/hub/products/{id}/gallery` | Issue S3 (tabs) |
| `seller-portal-product-documents` | `/hub/products/{id}/documents` | Issue S3 (active tab hidden) |
| `seller-portal-product-video` | `/hub/products/{id}/video` | Issue S3 (active tab hidden) |
| `seller-portal-shop-profile` | `/hub/shop/profile` | Issue S12 |
| `seller-portal-certifications` | `/hub/certifications` | **Issue S1 (viewport overflow)** |
| `seller-portal-earnings` | `/hub/earnings` | Issues S7, S14 |
| `seller-portal-payout-account` | `/hub/payout-account` | OK, see S19 |
| `seller-portal-payouts` | `/hub/payouts` | OK |
| `seller-portal-payout-detail` | `/hub/payouts/{id}` | Issue S4 |
| `seller-portal-customers` | `/hub/customers` | OK, see S17 |
| `seller-portal-reports` | `/hub/reports` | Issue S13 |
| `seller-portal-reviews` | `/hub/reviews` | OK |
| `seller-portal-orders` | `/hub/orders` | Issue S9 |
| `seller-portal-order-create` | `/hub/orders/create` | Issue S10 |
| `seller-portal-order-detail` | `/hub/orders/{id}` | Issue S5 |
| `seller-portal-rfqs` | `/hub/rfqs` | OK |
| `seller-portal-messages` | `/hub/messages` | **Issue S2 (broken layout)** |
| `seller-portal-team` | `/hub/team` | Issue S16 |
| `seller-portal-subscription` | `/hub/subscription` | OK (empty state only) |

---

## Critical

### S1 — Certificates page overflows the viewport ✅ DONE

`certificate-list.blade.php` (header, lines ~5–17). The page renders 515px wide inside a 390px viewport. The header is a single `flex items-center justify-between` row holding the title block plus two buttons ("Add Certificate" and "Apply for Certificate"). Nothing can wrap, so "Apply for Certificate" is pushed outside the card and the whole page scrolls sideways.

**Fix:** stack the header on mobile and let the buttons wrap.

```blade
<div class="flex flex-col gap-3 border-b border-[var(--hs-neutral-100)] px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
    <div class="min-w-0">...</div>
    <div class="flex flex-wrap items-center gap-2">
        <flux:button variant="primary" icon="plus" class="flex-1 sm:flex-none" ...>
        <flux:button variant="ghost" icon="check-badge" class="flex-1 sm:flex-none" ...>
    </div>
</div>
```

Check other `px-6 py-5 ... flex items-center justify-between` page headers for the same pattern (product-list has it too, and only fits because it has one short button).

### S2 — Messages two-pane layout does not collapse ✅ DONE

`resources/views/livewire/shared/chat-inbox.blade.php`. The conversation list is a fixed `w-[280px] shrink-0` sidebar and the thread pane is `flex-1`. At 390px the thread pane gets about 77px, so "Select a conversation" is clipped to "conversatio", the empty-state copy wraps one word per line, and the text refers to "the left" even though nothing is to the left. Once a conversation is open, the thread (header, thread tabs, composer) would be squeezed the same way. The container also uses `h-[calc(100vh-9rem)]`, which is wrong on mobile browsers with a collapsing URL bar.

This component is in `shared/`, so the buyer inbox is affected too.

**Fix:** use a master/detail pattern below `lg`.

- List pane: `w-full lg:w-[280px]`, and `hidden lg:flex` when a conversation is selected.
- Thread pane: `hidden lg:flex` when nothing is selected, full width otherwise.
- Add a back button (chevron + "Messages") in the thread header, visible below `lg` only.
- Hide the "Select a conversation" empty state below `lg`.
- Replace `100vh` with `100dvh`.

### S3 — Product sub-tabs hide the active tab ✅ DONE

`partials/product-tabs.blade.php`. There are five tabs (Basic Info, Variants, Gallery, Documents, Video) in a `flex gap-6 overflow-x-auto` row. At 390px only three fit. On the Documents and Video pages the active tab is off-screen, so **no tab is highlighted** and the seller cannot tell where they are. There is no scroll hint (no fade, no cut-off label), so Documents and Video are easy to miss entirely.

**Fix (any of):**
- Scroll the active tab into view on load (`x-init="$el.querySelector('[aria-current]')?.scrollIntoView({inline:'center', block:'nearest'})"`).
- Add an edge fade or partially visible last tab as a scroll cue.
- Below `sm`, show icons only with a visually-hidden label, or collapse to a `flux:select` that navigates.

---

## High

### S4 — Payout detail: order breakdown table is clipped

`payouts/payout-detail.blade.php` (table at ~lines 33–51). The table has `min-w-[400px]` inside `overflow-x-auto`. At 390px minus card padding, only Order, Escrowed and part of Fee are visible; Net is off-screen with no scroll cue. Net per order is the column a seller cares about most. The page also has no back link to Payout History.

The summary grid (Gross, Transaction Fees, Commission Fees, Net) is fine.

**Fix:** card list below `sm` using the pattern from Part 1 (order number + net amount on the first line, `<dl>` for escrowed and fees). Add a back link.

### S5 — Order detail: items table is clipped

`orders/order-detail.blade.php` (table at ~lines 146–185, `min-w-[480px]`). Visible columns at 390px are #, Description, Qty, Unit, and the start of Unit Price. Line total is off-screen. Same fix as S4: card list below `sm`, one card per line item with description, qty × unit price and line total.

Note: the screenshot shows "Items · 0" alongside a non-zero subtotal. That is a factory data mismatch in the browser test, not a UI bug, but it means the populated items table has **not been visually verified** on mobile (see Coverage gaps).

### S6 — Product list: actions crush the product name

`product-list.blade.php` mobile card (~lines 84–113). Each card puts the name block and a row of three labelled actions (View, Edit, Archive) side by side. The actions are `shrink-0`, so the name is truncated to "Ver…" and the status badge, variants count and share count wrap into a narrow column underneath.

**Fix:** move actions to their own row under the content (as the Part 1 pattern does), or collapse to icon buttons with a `flux:dropdown` for Archive. Let the name wrap to two lines (`line-clamp-2`) instead of truncating.

### S7 — Earnings: native date inputs

`earnings/earnings-overview.blade.php` (lines ~25 and ~28) use `<flux:input type="date">`, which renders the native `mm/dd/yyyy` control. This breaks the project rule to always use `flux:date-picker` (same finding as admin Issue #3). The two inputs also sit at about 60% width, stacked.

**Fix:** `flux:date-picker`, in a `grid grid-cols-2 gap-3` so From and To sit side by side at full width.

### S8 — Inconsistent currency formatting

Earnings, payouts and payout detail format amounts as `₱1,234.00`. Reports, orders, order detail and the order builder format as `PHP 1,234.00` (hard-coded `PHP {{ number_format(...) }}` in `hub/reports.blade.php`). A seller moving between Earnings and Reports sees the same kind of value written two ways.

**Fix:** one shared formatter (for example `Number::currency($amount, 'PHP')` or a `Money` Blade component) and replace the hard-coded prefixes. This is not mobile-specific but it was visible on nearly every screenshot.

---

## Medium

### S9 — Orders: status tabs clip without a cue, and the row is crowded

`orders/order-queue.blade.php`. The status pill bar scrolls horizontally (`overflow-x-auto scrollbar-hide`), and "Pending Payment" is cut at the edge with no fade, so the remaining statuses are hidden. The RFQ queue uses wrapping chips for the same job, so the two list pages behave differently. In the row itself, the order number and amount sit on one line and nearly touch.

**Fix:** pick one filter pattern for list pages. If keeping the scrolling bar, auto-scroll the active tab into view and add an edge fade. On `<sm`, put the amount on the second line next to the status badge.

### S10 — Order builder: form length, tap targets, action layout

`orders/order-builder.blade.php`.
- The create route shows "Edit ORD-202611-00002", which suggests the builder creates a draft order as soon as the page opens. Confirm this is intended, because every visit to `/hub/orders/create` may leave an empty draft behind.
- Billing and shipping are two full address blocks with no "Same as billing" toggle.
- "+ Add Item" and "+ Add Fee" are small text links, well below the 44px tap target.
- The footer has Cancel and Save Draft on one row and Submit for Payment alone on the next, at different widths.

**Fix:** add a "Shipping same as billing" `flux:checkbox` that hides the shipping block; turn the add links into `variant="subtle"` buttons; use a sticky bottom action bar (`sticky bottom-0`, safe-area padding) with Save Draft and Submit.

### S11 — Product form: length, empty Categories, weak action row

`product-form.blade.php` (create at 1,667px tall, edit at 2,199px).
- The **Categories** label renders with no visible control beneath it on both create and edit. This may just be missing seed data in the test, but a seller with no categories sees an empty field with no explanation. Verify, and add an empty-state message.
- The slug input placeholder is truncated ("Auto-generated from the product na…").
- The rich-text editor is about 300px tall and its toolbar wraps to two rows.
- Edit shows Update, Archive and Cancel wrapping onto two lines; the Update/Archive pair is easy to mis-tap.
- The Sharing card at the bottom of Edit squeezes "Last 30 days / All time" into a cramped three-column row (the Instagram row wraps).

**Fix:** sticky action bar with Update as the only primary action and Archive moved into an overflow menu; shorter placeholder; sharing rows as a 2-col `<dl>` below `sm`.

### S12 — Shop profile: very long form and small checkboxes

`shop-profile-form.blade.php` renders as a single 3,074px column. It has 17 checkboxes (value chain positions and capabilities) with about 16px hit areas and 8–10px gaps, and the Save button is only at the very bottom.

**Fix:** group sections into collapsible `flux:accordion` items (Identity, Contact, Operations, Capabilities), make each checkbox row a full-width label with `py-2.5` (44px minimum), and add a sticky Save bar.

### S13 — Reports: orphan KPI tiles and overflow risk

`hub/reports.blade.php`. KPI tiles use a 2-column grid. Five tiles leave "Net Sales" alone in the last row, and three tiles leave "Total Released" alone. Values are large (`PHP 0.00`), and with real figures such as `PHP 1,234,567.89` they will exceed the roughly 130px tile width and overflow or wrap.

**Fix:** let the first tile in each group span both columns (`col-span-2`), reduce the value size on mobile (`text-lg sm:text-2xl`), add `truncate`/`tabular-nums`, and consider compact notation for values over 1M.

### S14 — Earnings: KPI cards stack four high

`earnings/earnings-overview.blade.php` line ~8 uses `grid-cols-1 sm:grid-cols-2 lg:grid-cols-4`, so four KPI cards take about 400px before any content. Use `grid-cols-2` from the start and reduce card padding, as Reports already does.

---

## Low

- **S15 — Dashboard is buyer-centric for sellers.** `portal/dashboard.blade.php` shows "Published Products", "My Request for Quotation", a Halal Certification tile with a bare "—", and Browse Businesses / Browse Products / Submit RFQ shortcuts. A seller does not see pending orders, new RFQs received, pending payout or unread messages. The layout itself is clean on mobile. Consider a seller KPI row and quick links to Orders, RFQs and Messages.
- **S16 — Team page uses a different layout.** `team-manager.blade.php` has a bare page title and three separate cards at 32px side margin, while every other seller page uses one card with a header at 16px margin. Align it with the standard page card.
- **S17 — Customers row can collide on long emails.** The email and the text-only "New Order" button share one row with no `min-w-0`/`truncate`. Make the email truncate and give the button an outline style so it reads as a button.
- **S18 — Upload hint copy and size.** Dropzone hints are about 11px grey text. Size formatting is inconsistent: "5MB" and "20MB" appear without a space, "50 MB" with one. Standardise to one format and raise the hint to 12–13px.
- **S19 — Payout account:** Bank Code is a free-text field alongside a Bank select. Auto-fill it from the selected bank (read-only) and make sure the account number field has `inputmode="numeric"`; verify, since this is not visible in the screenshot.
- **S20 — No back navigation on detail pages.** Payout detail, order detail and the product sub-pages rely on the browser back button or the hamburger menu. Add a `← Payouts` / `← Orders` link above the card on mobile.

### Raw colour violations seen in the seller portal

Same rule as admin Issue #4. Confirmed so far in `payouts/payout-detail.blade.php` (`text-red-600` on fee amounts at lines ~18, 22, 46 and `text-green-700` on net amounts at lines ~26, 47) and `product-list.blade.php` (`text-yellow-600 bg-yellow-50` in the confirm modal, line ~297). Map with the table in Part 1: `text-red-600` becomes `text-[var(--hs-danger-700)]`, `text-green-700` becomes `text-[var(--hs-green-700)]`, `text-yellow-600` becomes `text-[var(--hs-warning-700)]`. A full `grep` of `company-hub/` and `portal/` is still needed.

---

## Implementation Order

1. ✅ **S1** `certificate-list.blade.php` — header stack/wrap (smallest change, fixes the only viewport overflow). Also applied `flex-wrap gap-3` to the same header pattern in document-manager, gallery-manager and product-list
2. ✅ **S3** `partials/product-tabs.blade.php` — `aria-current` on active tab, auto scroll into view, right-edge fade cue (affects 5 pages)
3. ✅ **S2** `shared/chat-inbox.blade.php` — master/detail below `lg`, back button (`closeConversation()` in `ChatInbox`), `100dvh` (also fixes the buyer inbox)
4. **S6** `product-list.blade.php` — action row
5. **S4** `payouts/payout-detail.blade.php` — card list, back link, token colours
6. **S5** `orders/order-detail.blade.php` — card list for line items
7. **S7 + S14** `earnings/earnings-overview.blade.php` — date picker and KPI grid
8. **S8** shared currency formatter, then replace the hard-coded `PHP` prefixes
9. **S9 + S10** order queue filter pattern and order builder (sticky actions, same-as-billing)
10. **S11 + S12** product form and shop profile (sticky actions, accordions, larger checkboxes)
11. **S13** reports KPI grid
12. **S15–S20** polish pass
13. Pint, `npm run build`, rerun the browser test and compare screenshots

## Coverage Gaps

The browser test is a good start but the screenshots only show part of the portal. These are **not yet audited**:

- **Populated states.** Variants, gallery, documents, video, RFQs, reviews, messages, team and certifications were all captured empty. The variant table, certificate cards, review cards and chat thread are unverified.
- **Modals and drawers.** The add-certificate modal, variant create/edit modal, delete/archive confirmations and the open mobile navigation drawer.
- **Pages not in the test.** RFQ detail, quotation builder, onboarding wizard, subscription payment pages, review response form, dispute panel and fulfilment panel inside order detail.
- **Other widths.** Only 390px was captured. Add a 360px (small Android) and 320px run, since several fixed widths (`min-w-[400px]`, `min-w-[480px]`, `w-[280px]`) are close to the limit.
- **Overflow assertion.** Add a check to the browser test that `document.documentElement.scrollWidth <= window.innerWidth` on every page. It would have caught S1 automatically.
