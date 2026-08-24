---
paths:
  - 'resources/views/partials/portal-nav*'
---

# Partials

## Unified portal sidebar — single nav for /account and /hub
portal-nav.blade.php renders a unified sidebar for both /account/* and /hub/* routes. Seller section (orders, products, payouts, reviews, etc.) shown only when `auth()->user()->shop?->status === 'active'`. Buyer-only accounts see "Apply as Seller" link to /hub/onboarding. Accounts mid-onboarding see "Seller Onboarding" link. Settings links extracted to portal-nav-settings.blade.php for DRY use. Admin portal keeps its own dedicated nav — never merge admin nav into this partial.
