---
name: fluxui-development
description: "Use this skill for Flux UI development in Livewire applications only. Trigger when working with <flux:*> components, building or customizing Livewire component UIs, creating forms, modals, tables, or other interactive elements. Covers: flux: components (buttons, inputs, modals, forms, tables, date-pickers, kanban, badges, tooltips, etc.), component composition, Tailwind CSS styling, Heroicons/Lucide icon integration, validation patterns, responsive design, and theming. Do not use for non-Livewire frameworks or non-component styling."
license: MIT
metadata:
  author: laravel
---

# Flux UI Development

## Documentation

Use `search-docs` for detailed Flux UI patterns and documentation.

## Basic Usage

This project uses the free edition of Flux UI, which includes all free components and variants but not Pro components.

Flux UI is a component library for Livewire built with Tailwind CSS. It provides components that are easy to use and customize.

Use Flux UI components when available. Fall back to standard Blade components when no Flux component exists for your needs.

<!-- Basic Button -->
```blade
<flux:button variant="primary">Click me</flux:button>
```

## Available Components (Free Edition)

Available: avatar, badge, brand, breadcrumbs, button, callout, card, checkbox, dropdown, field, heading, icon, input, modal, navbar, otp-input, pagination, profile, progress, radio, select, separator, skeleton, switch, table, text, textarea, toast, tooltip

## Icons

Flux includes [Heroicons](https://heroicons.com/) as its default icon set. Search for exact icon names on the Heroicons site - do not guess or invent icon names.

<!-- Icon Button -->
```blade
<flux:button icon="arrow-down-tray">Export</flux:button>
```

For icons not available in Heroicons, use [Lucide](https://lucide.dev/). Import the icons you need with the Artisan command:

```bash
vendor/bin/sail artisan flux:icon crown grip-vertical github
```

## Common Patterns

### Form Fields

<!-- Form Field -->
```blade
<flux:field>
    <flux:label>Email</flux:label>
    <flux:input type="email" wire:model="email" />
    <flux:error name="email" />
</flux:field>
```

### Modals

<!-- Modal -->
```blade
<flux:modal wire:model="showModal">
    <flux:heading>Title</flux:heading>
    <p>Content</p>
</flux:modal>
```

### Toasts (System Notifications)

When dispatching system notifications from Livewire actions, use `\Flux\Flux::toast()` instead of `session()->flash()`. Make sure the global toast component (`<flux:toast.group><flux:toast /></flux:toast.group>`) is present in your main layout (e.g. `layouts/portal.blade.php`).

<!-- Toast Example -->
```php
\Flux\Flux::toast('Product updated successfully.', variant: 'success'); // variants: success, danger, warning
```

## Dark Mode & Theming

### How the Adaptive Theme Works

This project uses an **adaptive dark theme** driven by `@fluxAppearance`. When the user's OS is in dark mode, the `<html>` element gets a `.dark` class. Flux UI's internal `dark:` utilities react to this class automatically.

**Key rule: never hardcode `dark:bg-zinc-*` or `dark:text-*` classes in application Blade files.** Instead, use `--hs-neutral-*` design system CSS variables which are calibrated in `resources/css/tokens.css` and automatically invert when `.dark` is active.

### Zinc Color Override

Flux UI internally uses `zinc` Tailwind colours (e.g. `dark:bg-zinc-700` for popover backgrounds). In this project, those zinc variables are **overridden inside `.dark`** in `tokens.css` so they map to the project's dark surface tokens automatically:

```css
/* Inside .dark in tokens.css */
--color-zinc-700: var(--hs-neutral-0);   /* Popover / Dropdown backgrounds */
--color-zinc-600: var(--hs-neutral-100); /* Active items / hover states */
```

This means **all Flux UI dropdowns, popovers, menus, and modals** automatically pick up the correct dark theme without any extra work.

### @fluxAppearance Directive

The `@fluxAppearance` directive must be present in every layout's `<head>` section (via `@include('partials.head')`). It reads `localStorage` and the OS preference to toggle `.dark` on `<html>`:

- `resources/views/partials/head.blade.php` — shared head partial (contains `@fluxAppearance`)
- `resources/views/layouts/portal.blade.php` — portal layout (also contains `@fluxAppearance`)

### Flux Stubs (Do NOT Modify)

The stub files in `resources/views/flux/` are Flux UI overrides — only modify them for non-theme customizations. The zinc-based dark mode inside these stubs is handled via the token override described above.

## Verification

1. Check component renders correctly
2. Test interactive states
3. Verify mobile responsiveness
4. Test in both light and dark mode (toggle OS dark mode)

## Common Pitfalls

- Trying to use Pro-only components in the free edition
- Not checking if a Flux component exists before creating custom implementations
- Forgetting to use the `search-docs` tool for component-specific documentation
- Not following existing project patterns for Flux usage
- Hardcoding `dark:bg-zinc-*` in application views — use `--hs-neutral-*` variables instead
- Adding `dark:` utility classes to Blade files — the design system tokens handle dark mode automatically
- Forgetting to add `<flux:toast.group><flux:toast /></flux:toast.group>` to new layout files
