---
paths:
  - 'app/Livewire/**'
---

# Livewire

## External URL redirects in Livewire components
Never use `$this->redirect($externalUrl)` for external domains — it breaks in Firefox with "'open' called on an object that does not implement interface Window". Instead dispatch a browser event and handle with Alpine.js:
- Component: `$this->dispatch('redirect-to-payment', url: $url);`
- Blade: `<div x-data x-on:redirect-to-payment.window="window.location.href = $event.detail.url">`
