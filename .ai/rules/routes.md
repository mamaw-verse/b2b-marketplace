---
paths:
  - routes/web.php
---

# Routes

## Keep storefront slug binding route-specific
Declare storefront slug binding on individual public routes with `{shop:slug}` / `{product:slug}`. Never override `getRouteKeyName()` globally; hub, admin, and API v1 routes must continue binding by numeric ID.
