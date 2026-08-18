---
name: dark-theme-system
description: "Activate when working on dark mode, theming, CSS variables, design tokens, adaptive UI, or color system changes in this project. Covers the adaptive token-based dark theme system, how Flux UI zinc colors are overridden, how @fluxAppearance works, how to add new components that support dark mode correctly, and pitfalls to avoid."
license: MIT
metadata:
  author: team
---

# Adaptive Dark Theme System

This project uses a **CSS variable-based adaptive dark theme** driven by OS preference detection. No `dark:` Tailwind utility classes are used in application Blade files — the color tokens invert automatically.

## Architecture Overview

```
OS Dark Mode Preference
        ↓
@fluxAppearance (in partials/head.blade.php)
        ↓
<html class="dark"> toggled
        ↓
.dark { --hs-neutral-*: ... } in tokens.css overrides :root
        ↓
All bg-[var(--hs-neutral-*)] classes adapt automatically
```

## File Locations

| File | Purpose |
|------|---------|
| `resources/css/tokens.css` | All design system tokens + `.dark` overrides |
| `resources/css/app.css` | Tailwind v4 `@theme` config, Flux CSS import, table overrides |
| `resources/views/partials/head.blade.php` | Contains `@fluxAppearance` directive |
| `resources/views/layouts/portal.blade.php` | Contains `@fluxAppearance` directive |

## Design Token Palette

### Neutrals (light → dark)

| Token | Light | Dark | Use |
|-------|-------|------|-----|
| `--hs-neutral-0` | `#FFFFFF` | `#1A2421` | Elevated card surface |
| `--hs-neutral-50` | `#F8FAF9` | `#121917` | Page background |
| `--hs-neutral-100` | `#F1F5F3` | `#242D2A` | Secondary bg / hover |
| `--hs-neutral-200` | `#E2E8E5` | `#2A3632` | Subtle borders |
| `--hs-neutral-300` | `#C8D1CC` | `#374240` | Prominent borders |
| `--hs-neutral-400` | `#9CA8A2` | `#4A5650` | Disabled / muted icon |
| `--hs-neutral-500` | `#6B7A73` | `#6B7A73` | Placeholder text |
| `--hs-neutral-600` | `#4A5650` | `#9CA8A2` | Muted text |
| `--hs-neutral-700` | `#374240` | `#C8D1CC` | Tertiary text |
| `--hs-neutral-800` | `#242D2A` | `#E2E8E5` | Secondary text |
| `--hs-neutral-900` | `#121917` | `#F8FAF9` | Primary text |

### Brand Greens (dark mode remapped for readability)

In dark mode, deep greens used as text colours (`--hs-green-700`, `800`, `900`) are remapped to brighter readable greens, while light greens used as backgrounds (`--hs-green-50`, `100`, `200`) are remapped to deep forest greens.

| Token | Light | Dark |
|-------|-------|------|
| `--hs-green-50` | `#ECFDF5` | `#05291B` (deep forest bg) |
| `--hs-green-100` | `#D1FAE5` | `#083D26` |
| `--hs-green-200` | `#A7F3D0` | `#0D5E3A` |
| `--hs-green-700` | `#117A4C` | `#34D399` (bright readable text) |
| `--hs-green-800` | `#0D5E3A` | `#6EE7B7` |
| `--hs-green-900` | `#083D26` | `#A7F3D0` |

### Semantic Backgrounds (dark mode)

| Token | Dark Value |
|-------|-----------|
| `--hs-success-50` | `#05291B` |
| `--hs-warning-50` | `#422006` |
| `--hs-danger-50` | `#450A0A` |
| `--hs-info-50` | `#172554` |

### Shadows (dark mode — black-based)

```css
--hs-shadow-xs: 0 1px 2px rgba(0, 0, 0, 0.4);
--hs-shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.5);
--hs-shadow-md: 0 4px 14px rgba(0, 0, 0, 0.6);
--hs-shadow-lg: 0 12px 32px rgba(0, 0, 0, 0.7);
```

## Flux UI Zinc Override

Flux UI components use Tailwind `zinc` colours internally (e.g., dropdowns use `dark:bg-zinc-700`). To make these adapt seamlessly to the project's dark surfaces, the zinc Tailwind variables are remapped inside `.dark` in `tokens.css`:

```css
.dark {
    --color-zinc-600: var(--hs-neutral-100); /* Active item hover */
    --color-zinc-700: var(--hs-neutral-0);   /* Popover / menu bg */
    --color-zinc-800: var(--hs-neutral-0);   /* Modals */
    --color-zinc-900: var(--hs-neutral-0);
    /* ... etc */
}
```

This means the user popover, dropdowns, menus, and any other Flux UI popup will automatically use the project's dark surface without touching any Flux stub files.

## Critical Rules

### DO

- Use `bg-[var(--hs-neutral-0)]` for card/elevated surfaces
- Use `bg-[var(--hs-neutral-50)]` for page backgrounds
- Use `text-[var(--hs-neutral-900)]` for primary text
- Use `text-[var(--hs-neutral-600)]` for muted text
- Use `border-[var(--hs-neutral-200)]` for borders
- Use `text-white` on top of solid green backgrounds (buttons, badges, steps)
- Place `.dark {}` overrides at the **top level** (not inside `@layer`)

### DO NOT

- Use `bg-white` in application Blade files — always `bg-[var(--hs-neutral-0)]`
- Use `dark:bg-zinc-*` or `dark:text-*` in application Blade files
- Use `text-black` or `text-gray-*` — use `text-[var(--hs-neutral-900)]`
- Use `border-gray-*` — use `border-[var(--hs-neutral-200)]`
- Put `.dark` overrides inside `@layer theme` — they will be silently ignored

## Adding a New Component

When building a new Blade component:

```blade
{{-- ✅ Correct template for a dark-mode-ready card --}}
<div class="rounded-[var(--hs-radius-card)]
            bg-[var(--hs-neutral-0)]
            border border-[var(--hs-neutral-200)]
            shadow-[var(--hs-shadow-sm)]
            p-6">

    <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)]">
        Title
    </h2>
    <p class="mt-1 text-sm text-[var(--hs-neutral-600)]">
        Muted description text
    </p>
</div>
```

## After Making Changes

Always rebuild the CSS after modifying any `.css` file:

```bash
vendor/bin/sail npm run build
```

Users must **hard refresh** (Cmd+Shift+R or Ctrl+F5) to clear cached CSS after a rebuild.
