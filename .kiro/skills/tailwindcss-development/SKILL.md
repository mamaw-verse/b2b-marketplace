---
name: tailwindcss-development
description: "Always invoke when the user's message includes 'tailwind' in any form. Also invoke for: building responsive grid layouts (multi-column card grids, product grids), flex/grid page structures (dashboards with sidebars, fixed topbars, mobile-toggle navs), styling UI components (cards, tables, navbars, pricing sections, forms, inputs, badges), adding dark mode variants, fixing spacing or typography, and Tailwind v3/v4 work. The core use case: writing or fixing Tailwind utility classes in HTML templates (Blade, JSX, Vue). Skip for backend PHP logic, database queries, API routes, JavaScript with no HTML/CSS component, CSS file audits, build tool configuration, and vanilla CSS."
license: MIT
metadata:
  author: laravel
---

# Tailwind CSS Development

## Documentation

Use `search-docs` for detailed Tailwind CSS v4 patterns and documentation.

## Basic Usage

- Use Tailwind CSS classes to style HTML. Check and follow existing Tailwind conventions in the project before introducing new patterns.
- Offer to extract repeated patterns into components that match the project's conventions (e.g., Blade, JSX, Vue).
- Consider class placement, order, priority, and defaults. Remove redundant classes, add classes to parent or child elements carefully to reduce repetition, and group elements logically.

## Tailwind CSS v4 Specifics

- Always use Tailwind CSS v4 and avoid deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.

### CSS-First Configuration

In Tailwind v4, configuration is CSS-first using the `@theme` directive — no separate `tailwind.config.js` file is needed:

<!-- CSS-First Config -->
```css
@theme {
  --color-brand: oklch(0.72 0.11 178);
}
```

### Import Syntax

In Tailwind v4, import Tailwind with a regular CSS `@import` statement instead of the `@tailwind` directives used in v3:

<!-- v4 Import Syntax -->
```diff
- @tailwind base;
- @tailwind components;
- @tailwind utilities;
+ @import "tailwindcss";
```

### Replaced Utilities

Tailwind v4 removed deprecated utilities. Use the replacements shown below. Opacity values remain numeric.

| Deprecated | Replacement |
|------------|-------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |

## Spacing

Use `gap` utilities instead of margins for spacing between siblings:

<!-- Gap Utilities -->
```html
<div class="flex gap-8">
    <div>Item 1</div>
    <div>Item 2</div>
</div>
```

## Dark Mode — Project-Specific Adaptive Theme

> **IMPORTANT: This project does NOT use `dark:` utility classes in application Blade files.** Read this section before adding any dark mode styling.

### How It Works

This project uses an **adaptive CSS variable-based dark theme** defined in `resources/css/tokens.css`. The `.dark` class (toggled by `@fluxAppearance` based on OS preference) overrides all `--hs-neutral-*` variables to their dark equivalents.

**The rule:** Use `--hs-neutral-*` and `--hs-green-*` CSS variables for all backgrounds, borders, and text. They automatically invert in dark mode without any `dark:` classes.

### Design System Token Usage (Required Pattern)

```html
<!-- ✅ Correct — adapts automatically -->
<div class="bg-[var(--hs-neutral-0)] text-[var(--hs-neutral-900)] border border-[var(--hs-neutral-200)]">
    Card surface adapts to light and dark
</div>

<!-- ❌ Wrong — broken in dark mode -->
<div class="bg-white text-gray-900 border border-gray-200">
    This will be invisible text in dark mode
</div>

<!-- ❌ Wrong — hardcoded dark: classes are not the pattern used here -->
<div class="bg-white dark:bg-zinc-800 text-gray-900 dark:text-white">
    Do not do this
</div>
```

### Token Reference (Light → Dark)

| Token | Light Value | Dark Value | Use For |
|-------|-------------|------------|---------|
| `--hs-neutral-0` | `#FFFFFF` | `#1A2421` | Card / elevated surface |
| `--hs-neutral-50` | `#F8FAF9` | `#121917` | Page background |
| `--hs-neutral-100` | `#F1F5F3` | `#242D2A` | Hover states, secondary bg |
| `--hs-neutral-200` | `#E2E8E5` | `#2A3632` | Borders |
| `--hs-neutral-300` | `#C8D1CC` | `#374240` | Prominent borders |
| `--hs-neutral-500` | `#6B7A73` | `#6B7A73` | Placeholder text |
| `--hs-neutral-600` | `#4A5650` | `#9CA8A2` | Muted text |
| `--hs-neutral-700` | `#374240` | `#C8D1CC` | Tertiary text |
| `--hs-neutral-800` | `#242D2A` | `#E2E8E5` | Secondary text |
| `--hs-neutral-900` | `#121917` | `#F8FAF9` | Primary text |

### CSS Variable Specificity Rule

When overriding CSS variables for dark mode, **do NOT place them inside `@layer theme`**. Variables in unlayered `:root` have higher specificity than anything inside `@layer`. Always write `.dark { ... }` at the top level:

```css
/* ✅ Correct — unlayered, overrides :root */
.dark {
    --hs-neutral-0: #1A2421;
}

/* ❌ Wrong — @layer theme has lower priority than unlayered :root */
@layer theme {
    .dark {
        --hs-neutral-0: #1A2421;
    }
}
```

### Exceptions: When `text-white` Is OK

`text-white` is acceptable and correct when placed **on top of a brand green background** (e.g. buttons, hero banners, active step indicators) because white will always be readable there regardless of dark mode:

```html
<!-- ✅ OK: white text on green button -->
<button class="bg-[var(--hs-green-600)] text-white">Submit</button>
```

## Common Patterns

### Flexbox Layout

<!-- Flexbox Layout -->
```html
<div class="flex items-center justify-between gap-4">
    <div>Left content</div>
    <div>Right content</div>
</div>
```

### Grid Layout

<!-- Grid Layout -->
```html
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div>Card 1</div>
    <div>Card 2</div>
    <div>Card 3</div>
</div>
```

## Common Pitfalls

- Using deprecated v3 utilities (bg-opacity-*, flex-shrink-*, etc.)
- Using `@tailwind` directives instead of `@import "tailwindcss"`
- Trying to use `tailwind.config.js` instead of CSS `@theme` directive
- Using margins for spacing between siblings instead of gap utilities
- **Using `bg-white` in application views** — use `bg-[var(--hs-neutral-0)]` instead
- **Adding `dark:` utility classes** — this project uses adaptive CSS variables, not `dark:` utilities
- **Placing `.dark` overrides inside `@layer theme`** — they will be ignored due to CSS specificity rules
