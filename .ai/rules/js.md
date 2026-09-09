---
paths:
  - 'resources/js/**'
---

# Js

## Re-apply Flux appearance on every wire:navigate
@fluxAppearance only runs on the initial page load. Livewire's wire:navigate SPA navigation morphs the <html> element and drops the dark class. The livewire:navigated event listener in app.js re-reads localStorage('flux.appearance') and re-applies the dark/light/system class on every navigation. Never remove this listener — dark mode will break on page transitions.
