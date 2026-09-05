---
paths:
  - resources/css/app.css
---

# Css

## Flux UI theme overrides use CSS data-attribute selectors in app.css
Flux UI theming is applied via three layers: (1) @theme block remaps zinc→hs-neutral and sets --color-accent to hs-green-600, (2) tokens.css .dark block auto-inverts, (3) CSS overrides on [data-flux-*] attributes for gaps (danger buttons, input radius, ghost hover). To add more Flux style overrides, append to the end of app.css using the same [data-flux-*] selector pattern. Never use !important class overrides inside Blade components — use CSS specificity targeting data attributes instead.
