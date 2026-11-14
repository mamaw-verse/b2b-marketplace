---
paths:
  - 'app/Models/{Shop,Product,User,Media}.php'
  - app/Jobs/ReconcileMediaOrphansJob.php
  - app/Services/MediaUploadService.php
  - config/marketplace.php
  - config/livewire.php
---

# Slugs, product video, and seller staff

## Slug routing
Keep `Shop` and `Product` route binding ID-based by default. Only public storefront routes opt into slugs with `{shop:slug}` or `{product:slug}`. Do not override `getRouteKeyName()`; hub, admin, and API v1 routes continue using IDs. Generate and backfill slugs with `artisan slugs:backfill` before deploying the migration that makes them non-null.

## Owner and staff shop context
`User::shop` means the shop the user owns. Use `User::activeShop` for seller workflows where an authorized staff member may act for their shop. Keep billing, payout, legal profile, subscription, certification, and team management owner-only.

## Product video storage
Video media is referenced through `products.video_media_id`. When adding media foreign keys, include every referencing table and column in `ReconcileMediaOrphansJob::REFERENCES`; otherwise its cleanup can delete media that is still in use. Keep video MIME rules separate from the general document allow-list.

## Upload limits and feature flags
Product video is disabled by default. Its application limit is `MARKETPLACE_PRODUCT_VIDEO_MAX_KB` (default 51200 KB); Livewire's temporary upload cap, PHP-FPM limits, and proxy/CDN body limit must all allow the intended upload size. Configure `AWS_TEMPORARY_URL` to the browser-reachable S3/MinIO endpoint so presigned video URLs retain a valid signature and support byte ranges.

