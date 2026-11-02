# Admin Mobile UI/UX Audit & Implementation Plan

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
