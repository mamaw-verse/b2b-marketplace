---
paths:
  - 'resources/views/components/**'
  - resources/views/components/hs-confirm.blade.php
  - resources/views/components/hs-alert.blade.php
  - resources/views/components/hs-modal.blade.php
  - 'resources/views/components/hs-confirm*'
  - 'resources/views/components/hs-alert*'
---

# Components

## Do NOT create wrapper components around Flux UI
Never create hs-* wrapper components that wrap flux:button, flux:input, flux:select, etc. Blaze (livewire/blaze) precompiles anonymous components and breaks on @php match() blocks or $attributes->merge() with runtime logic inside wrappers. Theme enforcement is done via CSS overrides on [data-flux-*] attributes in app.css (see the table, button, and input override blocks). The existing hs-* components (hs-badge, hs-avatar, hs-modal, hs-product-card, etc.) are custom UI pieces — not Flux wrappers.

## hs-confirm component API reference
hs-confirm is a confirmation dialog built on flux:modal. Props: `name` (required, unique modal name), `title`, `message`, `variant` (primary|danger|warning — controls icon/color), `confirmText`, `cancelText`. Required slot: `action` — the confirm button. Cancel is built-in via flux:modal.close. The component does NOT render a `<flux:button variant="warning">` — action slot buttons must use valid Flux variants (primary, filled, danger, ghost, subtle).

## hs-alert component API reference
hs-alert is an informational dialog built on flux:modal. Props: `name` (required, unique modal name), `title`, `message`, `variant` (info|success|warning|danger — controls icon/color), `buttonText` (default "OK"). Open via `<flux:modal.trigger name="...">` or `\Flux\Flux::modal('name')->show()` from Livewire.

## hs-modal component API with fixed header/footer
hs-modal has fixed header/footer with scrollable body. Props: `name`, `model` (wire:model), `title`, `subtitle`, `size` (sm|md|lg|xl|2xl). Optional named slot: `footer` — renders as a sticky bottom bar with top border. The default slot is the scrollable body area. Max height is 85vh.

## hs-confirm action slot must auto-close the modal on click
The action slot in hs-confirm is wrapped in `<div x-on:click="$flux.modal('{{ $name }}').close()">` so that clicking the confirm button closes the dialog immediately. Without this wrapper, the modal stays open after the Livewire action fires. Do not remove this wrapper div — it is intentional UX behavior.

## hs-alert is a MODAL component — never use for inline flash messages
hs-alert is built on flux:modal and requires a `name` prop. It is NOT an inline alert/banner. For inline flash messages or status banners, use plain styled divs with design tokens (e.g., `bg-[var(--hs-success-50)] border border-[var(--hs-success-200)] text-[var(--hs-success-700)]`). Never substitute hs-alert where you need an inline notification.
