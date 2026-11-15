---
paths:
  - resources/views/livewire/storefront/product-detail.blade.php
  - resources/views/livewire/storefront/review-list.blade.php
  - 'resources/views/livewire/storefront/**'
---

# Storefront

## Product detail page structural layout pattern
Two-column grid (gallery sticky left, info right). Gallery: padded white frame, thumbnails below, share buttons at bottom. Halal certification claim is represented exclusively by the "Halal Certified" pill in the badge row (gated on active shop certificates). Info column order: badges → title → supplier line → rating → volume pricing ledger (tiered rows with dotted leaders, best tier highlighted green) → attribute pill selectors (solid green selected, neutral unselected, faded disabled) → description → supplier card (initials avatar + "View Shop" link) → dark CTA card → documents. Reviews section: large rating number + stars summary above the card-based review list.

## Review list renders as cards with avatar and seller replies
Each review renders as a bordered card with: circular avatar (gold-tinted initials), reviewer name, relative timestamp, star row, title (optional), body, and seller reply (green left-border accent box with responder name). The Livewire component must eager-load `reviews` with `response.responder` to avoid N+1.

## Always use x-hs-product-card for product listings — never hand-roll inline
Product search results and featured products both use `x-hs-product-card`. Do not hand-roll inline card markup with a hardcoded placeholder icon — the component checks `featured_image_path` and renders the actual `<img>` via the `featured_image_url` accessor route, plus shop name from the eager-loaded `shop` relation. The card's "Halal" badge is strictly gated on active shop certificates (`$product->shop?->certificates?->contains('status', 'active')`), so queries and components providing products to catalog views must eager-load `shop.certificates` to prevent N+1 queries. Any new storefront view that lists products should use this component.
