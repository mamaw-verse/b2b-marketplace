# Theme Unification — CSS Override Approach (Implemented)

**Status:** ✅ Complete

## How Theme Unification Works

The storefront and portals share the same visual language through three layers:

### Layer 1: `@theme` in `app.css` (already existed before this task)
- Remaps Tailwind `zinc-*` → `--hs-neutral-*` (green-tinted neutrals)
- Sets `--color-accent: var(--hs-green-600)` (Flux primary = storefront green)
- Sets `--color-accent-foreground: var(--hs-neutral-0)`
- Remaps shadows, border-radius tokens

### Layer 2: `tokens.css` `.dark` block (already existed)
- Inverts all `--hs-*` values for dark mode
- Flux inherits these automatically through the zinc/accent remap

### Layer 3: CSS overrides in `app.css` (added by this task)
Targets Flux `data-flux-*` attributes — same pattern as the existing table overrides.

**Added:**
1. **Danger buttons** — `[data-flux-button].bg-red-500` → `var(--hs-danger-500)` (+ hover + dark)
2. **Input border-radius** — `[data-flux-control]` → `var(--hs-radius-sm)` (6px instead of 8px)
3. **Ghost/subtle hover** — `[data-flux-button].hover:bg-zinc-800/5` → `var(--hs-green-50)` (branded tint)

## What NOT to Do

- ❌ **No wrapper components around Flux** — Blaze (livewire/blaze) precompiles anonymous components and breaks on `@php match()` blocks or complex `$attributes->merge()` patterns. Thin pass-through wrappers add churn with no benefit since the theme is applied at CSS level.
- ❌ **No view migrations** — keep using `<flux:button>`, `<flux:input>`, etc. directly.
- ❌ **No `@blaze(false)`** — doesn't prevent parent-level inlining.

## Existing Custom Components (Not Flux Wrappers)

These are purpose-built UI components that use `--hs-*` tokens directly. They are NOT theme-enforcement wrappers:

| Component | Purpose |
|-----------|---------|
| `hs-avatar` | Initials circle with green bg |
| `hs-badge` | Variant pill (verified/halal/gold/tag/warning/danger/info/neutral) |
| `hs-cert-badge` | Certificate status → badge mapper |
| `hs-company-card` | Storefront company card layout |
| `hs-product-card` | Storefront product card layout |
| `hs-empty-state` | Icon + title + subtitle empty state |
| `hs-modal` | Consistent modal with title/subtitle header + size presets |
| `hs-status-pill` | Status string → badge variant mapper |
| `hs-unit-select` | Alpine unit picker with "Other" fallback |
| `hs-value-chain` | Value chain step visualization |

## Files Modified

- `resources/css/app.css` — added ~35 lines of CSS overrides after the table section
