---
paths:
  - 'app/Livewire/Storefront/**'
---

# Livewire Storefront

## Never toArray() Scout search results — pass Eloquent models to Blade
Scout `search()->get()` returns Eloquent models (pgsql) or transformed records (Typesense). Calling `->toArray()` strips custom `Attribute` accessors that aren't in `$appends` (e.g. `featured_image_url` on Product) and loses loaded relations. Always pass the model collection directly to the view and eager-load relations via `->query(fn ($q) => $q->with([...]))`. This caused product images to vanish from hero search results in production.
