---
paths:
  - resources/views/livewire/storefront/product-detail.blade.php
  - resources/views/livewire/storefront/review-list.blade.php
---

# Storefront

## Product detail page structural layout pattern
Two-column grid (gallery sticky left, info right). Gallery: padded white frame, thumbnails below, trust/certification strip at bottom. Info column order: badges → title → supplier line → rating → volume pricing ledger (tiered rows with dotted leaders, best tier highlighted green) → attribute pill selectors (solid green selected, neutral unselected, faded disabled) → description → supplier card (initials avatar + "View Shop" link) → dark CTA card → documents. Reviews section: large rating number + stars summary above the card-based review list.

## Review list renders as cards with avatar and seller replies
Each review renders as a bordered card with: circular avatar (gold-tinted initials), reviewer name, relative timestamp, star row, title (optional), body, and seller reply (green left-border accent box with responder name). The Livewire component must eager-load `reviews` with `response.responder` to avoid N+1.
