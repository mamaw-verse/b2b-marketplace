---
paths:
  - config/marketplace.php
---

# Config

## subscriptions_enabled defaults to false — system is free until explicitly enabled
The marketplace is free by default. Set MARKETPLACE_SUBSCRIPTIONS_ENABLED=true in .env when you are ready to charge sellers. When false: no subscription gating on products/storefront, no trial created on approval, the Subscription nav/UI is hidden. The plan's trial_days field controls trial length when enabled.
