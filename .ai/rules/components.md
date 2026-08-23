---
paths:
  - 'resources/views/components/**'
---

# Components

## Do NOT create wrapper components around Flux UI
Never create hs-* wrapper components that wrap flux:button, flux:input, flux:select, etc. Blaze (livewire/blaze) precompiles anonymous components and breaks on @php match() blocks or $attributes->merge() with runtime logic inside wrappers. Theme enforcement is done via CSS overrides on [data-flux-*] attributes in app.css (see the table, button, and input override blocks). The existing hs-* components (hs-badge, hs-avatar, hs-modal, hs-product-card, etc.) are custom UI pieces — not Flux wrappers.
