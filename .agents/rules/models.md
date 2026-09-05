---
paths:
  - app/Models/Review.php
---

# Models

## reviewable_type stores fully qualified class names (morphTo convention)
The `reviewable_type` column stores full class names like `App\Models\Shop` and `App\Models\Product` (Laravel's default morphTo behavior). When comparing or filtering by type, always use the full class name (e.g., `Review::where('reviewable_type', Shop::class)`) — never short strings like 'shop' or 'product'. If morph maps are introduced later, update accordingly.
