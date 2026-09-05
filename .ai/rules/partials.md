---
paths:
  - 'resources/views/partials/portal-nav*'
---

# Partials

## Unified portal sidebar — single nav for /account and /hub
portal-nav.blade.php renders a unified sidebar for both /account/* and /hub/* routes. Seller section (orders, products, payouts, reviews, etc.) shown only when `auth()->user()->shop?->status === 'active'`. Buyer-only accounts see "Apply as Seller" link to /account/onboarding (route `account.onboarding` — onboarding is a buyer-side action). Accounts mid-onboarding see "Seller Onboarding" link. Settings links extracted to portal-nav-settings.blade.php for DRY use. Admin portal keeps its own dedicated nav — never merge admin nav into this partial.

## Unified portal sidebar — single nav for all non-admin users (Phase 6)
Phase 6 replaced the split hub/account navigation with a single unified sidebar. $portal is now either 'admin' or 'portal' (never 'hub' or 'account'). Seller section (Shop Profile, Products, Certification, Subscription) shown only when `$shop->status === 'active'`. Buyer section (Buyer Profile, My RFQs, Messages) always shown. Phase 6 disabled features (orders, rfq-queue, reviews, earnings, payouts) are still in the nav but wrapped in their feature flags. Canonical dashboard route is portal.dashboard (/portal/dashboard); account.dashboard and hub.hub-dashboard both redirect there. Admin portal keeps its own dedicated nav — never merge admin nav into this partial.
