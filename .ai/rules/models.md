---
paths:
  - app/Models/Review.php
  - 'app/Models/**'
---

# Models

## reviewable_type stores fully qualified class names (morphTo convention)
The `reviewable_type` column stores full class names like `App\Models\Shop` and `App\Models\Product` (Laravel's default morphTo behavior). When comparing or filtering by type, always use the full class name (e.g., `Review::where('reviewable_type', Shop::class)`) — never short strings like 'shop' or 'product'. If morph maps are introduced later, update accordingly.

## Keep storefront slug binding route-specific
Never override `getRouteKeyName()` globally on Shop or Product. Public storefront routes opt into slug binding explicitly; hub, admin, and API v1 model binding must remain ID-based.
