---
paths:
  - 'app/Livewire/**'
---

# Livewire

## External URL redirects in Livewire components
Never use `$this->redirect($externalUrl)` for external domains — it breaks in Firefox with "'open' called on an object that does not implement interface Window". Instead dispatch a browser event and handle with Alpine.js:
- Component: `$this->dispatch('redirect-to-payment', url: $url);`
- Blade: `<div x-data x-on:redirect-to-payment.window="window.location.href = $event.detail.url">`

## Use Livewire-controlled state for modals in nested components, not \Flux\Flux::modal()
`\Flux\Flux::modal('name')->show()` does not work reliably from nested Livewire components (child embedded inside parent). Instead, use a Livewire boolean property (`$showModal = false`) toggled by a method, and bind it with `<flux:modal wire:model="showModal">` or `x-show`. This avoids cross-component Alpine scope issues.

## Full-page Livewire components MUST have #[Layout] attribute
Every full-page Livewire component (one that has its own route) must declare `#[Layout('layouts.app')]` (or the relevant layout) as a class attribute. Without it, Livewire renders the component without the app shell (no nav, no CSS, no scripts). This was a recurring bug in Phase 4. Check for this attribute on every new routed Livewire component.
