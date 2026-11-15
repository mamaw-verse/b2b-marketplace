# Design — Slugs, Product Video, Seller Staff

Companion to `requirements.md` (IDs like F1.7 refer to it). Code samples are sketches that follow this repo's conventions (`declare(strict_types=1)`, `final` classes, `Actions/*Action::execute()`, Livewire computed props + `#[Locked]` ids, `Gate::authorize`/policies, PostgreSQL enums, queued side effects after commit). Items marked **VERIFY** are things I could not confirm without running the app (`vendor/` is not installed in this checkout); each has a matching spike task.

---

## 0. Principles

1. **Additive only.** New columns/tables, no destructive migrations (Phase 6 "disabled, not deleted").
2. **Model-level behaviour over call-site edits.** Slugs are generated in a model trait so `ApproveOnboardingAction`, factories, seeders and `ProductForm` need no changes.
3. **Don't change global route keys.** Overriding `getRouteKeyName()` would silently re-key hub routes and Livewire components. Slug binding is opt-in per route (`{product:slug}`).
4. **Owner vs. member is an explicit concept.** `User::shop` keeps meaning *"the shop I own"*; a new `User::activeShop` means *"the shop I work in (owner or staff)"*. Every existing `->shop` read is classified (§3.5), not blanket-replaced.
5. **Flags.** `product_video_enabled` and `staff_enabled` in `config/marketplace.php`, default `false`.

---

## 1. F1 — Slugs

### 1.1 Schema (two migrations, see rollout §1.5)

```php
// 2026_11_16_000001_add_slug_to_shops_and_products.php  (Deploy A)
Schema::table('shops',    fn (Blueprint $t) => $t->string('slug', 100)->nullable());
Schema::table('products', fn (Blueprint $t) => $t->string('slug', 100)->nullable());

// 2026_11_16_000002_create_slug_redirects_table.php     (Deploy A)
Schema::create('slug_redirects', function (Blueprint $t) {
    $t->id();
    $t->string('sluggable_type', 100);      // FQCN, per .ai/rules/models.md morph convention
    $t->unsignedBigInteger('sluggable_id');
    $t->string('slug', 100);
    $t->timestamps();
    $t->unique(['sluggable_type', 'slug']);
    $t->index(['sluggable_type', 'sluggable_id']);
});

// 2026_11_20_000001_enforce_slug_constraints.php        (Deploy B, after backfill)
DB::statement('ALTER TABLE shops ALTER COLUMN slug SET NOT NULL');
DB::statement('ALTER TABLE products ALTER COLUMN slug SET NOT NULL');
DB::statement('CREATE UNIQUE INDEX shops_slug_unique ON shops (slug)');       // NOT partial:
DB::statement('CREATE UNIQUE INDEX products_slug_unique ON products (slug)'); // includes soft-deleted (F1.3)
```

Add `slug` to `$fillable` on `Shop` and `Product`, and to `@property` docblocks. The generated `search_vector` columns are untouched. Optionally add `'slug'` to `toSearchableArray()` (Typesense mode only; additive).

### 1.2 Generation

`app/Support/Slugs/SlugGenerator.php`

```php
final class SlugGenerator
{
    public function generate(string $modelClass, string $source, ?int $ignoreId = null): string
    {
        $fallback = $modelClass === Shop::class ? 'store' : 'product';
        $base = Str::of(Str::slug($source, '-', app()->getLocale()))->limit(90, '')->trim('-')->toString();

        if ($base === '' || ctype_digit($base) || in_array($base, config('marketplace.slugs.reserved'), true)) {
            $base = $fallback.'-'.Str::lower(Str::random(6));        // F1.4 / F1.5
        }

        $candidate = $base;
        for ($n = 2; $this->taken($modelClass, $candidate, $ignoreId); $n++) {
            $candidate = "{$base}-{$n}";
        }

        return $candidate;
    }

    private function taken(string $class, string $slug, ?int $ignoreId): bool
    {
        // withTrashed(): soft-deleted rows keep their slug. slug_redirects also reserves old slugs.
        return $class::withTrashed()->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()
            || SlugRedirect::where('sluggable_type', $class)->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('sluggable_id', '!=', $ignoreId))->exists();
    }
}
```

Race handling: the unique index is the real guard. The trait catches `UniqueConstraintViolationException` on insert and retries generation (max 5).

`app/Models/Concerns/HasSlug.php`

```php
trait HasSlug
{
    abstract public function slugSource(): string;      // Shop: storefront_name · Product: name

    protected static function bootHasSlug(): void
    {
        static::creating(function (Model $m): void {
            if (blank($m->slug)) {
                $m->slug = app(SlugGenerator::class)->generate(static::class, $m->slugSource());
            }
        });

        static::updating(function (Model $m): void {        // F1.11
            if ($m->isDirty('slug') && filled($m->getOriginal('slug'))) {
                SlugRedirect::updateOrCreate(
                    ['sluggable_type' => static::class, 'slug' => $m->getOriginal('slug')],
                    ['sluggable_id' => $m->getKey()],
                );
                SlugRedirect::where(['sluggable_type' => static::class, 'slug' => $m->slug])->delete(); // owner reverted
            }
        });
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if ($field !== 'slug') {
            return parent::resolveRouteBinding($value, $field);   // hub/admin/API bind by id exactly as before
        }

        return $this->newQuery()->where('slug', $value)->first()
            ?? $this->findViaRedirect($value)                     // old slug
            ?? (ctype_digit((string) $value) ? $this->newQuery()->find((int) $value) : null); // legacy id (F1.7)
    }
}
```

Names never auto-regenerate slugs (F1.9): nothing hooks `name` changes.

### 1.3 Routing and canonical redirect

```php
// routes/web.php
Route::livewire('/companies/{shop:slug}', 'storefront.company-profile')
    ->middleware('canonical.slug:shop,storefront.company')->name('storefront.company');
Route::livewire('/products/{product:slug}', 'storefront.product-detail')
    ->middleware('canonical.slug:product,storefront.product')->name('storefront.product');
```

- `UrlGenerator::toRoute` substitutes `$model->{bindingField}` when the route declares `:slug`, so all 17 existing `route('storefront.*', $model)` calls now emit slugs. **VERIFY** in a spike (`route()` output for both routes), including from inside Livewire views.
- `EnsureCanonicalSlug` middleware (alias `canonical.slug`, registered in `bootstrap/app.php`): after `SubstituteBindings`, compare the raw URL segment (`$request->segment(2)`) with `$model->slug`; if different (legacy id or old slug), `redirect()->to(route($name, $model).($qs), 301)` preserving the query string. Because the redirect decision is in middleware, the Livewire components (`ProductDetail`, `CompanyProfile`) need **no changes**, and their `mount(Product $product)` binding still works. **VERIFY** Livewire 4 honours `:slug` for mount-parameter binding.
- Visibility rules (`abort_unless($visible, 404)`) are unchanged. The redirect fires before them, which is harmless because the target is then evaluated by the same rules. Do not redirect for models that would 404 (middleware may check `shop->status` cheaply, or accept the extra hop).
- SEO: in `product-detail.blade.php` / `company-profile.blade.php` set `$ogUrl = route('storefront.product', $product)` instead of `url()->current()` and add `<link rel="canonical" href="{{ $ogUrl }}">` (F1.12).
- API v1 `/api/v1/products/{product}` and `/companies/{shop}` stay ID-bound; add `slug` (and the canonical URL) to their public payloads (additive).

### 1.4 Editing and validation

`app/Rules/ValidSlug.php`: regex `^[a-z0-9]+(?:-[a-z0-9]+)*$`, `max:100`, not `ctype_digit`, not in `config('marketplace.slugs.reserved')`, unique in the table **and** in `slug_redirects` (ignoring the current record).

- `ProductForm`: add `public string $slug = ''`; prefill on edit; on create leave blank to auto-generate; rule `['nullable', new ValidSlug(Product::class, $this->product?->id)]`.
- `ShopProfileForm`: same, **owner-only** (it is already owner-only).
- Show the final URL preview under the field (`/products/{slug}`) and a note that changing it keeps the old link working.

### 1.5 Backfill and rollout

1. **Deploy A:** nullable columns + `slug_redirects` + trait (new rows get slugs) + `php artisan slugs:backfill`.
   `slugs:backfill` is idempotent: `Model::withTrashed()->whereNull('slug')->chunkById(500, …)`, ordered by `id` so older records get the cleaner slugs; it logs counts and exits non-zero if any row still null.
2. **Deploy B:** constraints migration + route changes + middleware + views. The migration aborts with a clear message if any `slug IS NULL` remains.
3. Rollback: revert routes to `{shop}`/`{product}` (ID binding still works because `resolveRouteBinding` only changes behaviour for `slug`); the columns can stay.

Pre-flight: `grep -rn "/products/\|/companies/" tests` for literal ID URLs; those now receive a 301 instead of 200 and should be updated to `route(...)`.

---

## 2. F2 — Product video

### 2.1 Schema

Because the rule is "exactly one per product", use a nullable FK on `products` rather than a join table: the database enforces F2.1 with no extra index, and joins stay trivial.

```php
Schema::table('products', function (Blueprint $t) {
    $t->unsignedBigInteger('video_media_id')->nullable();
    $t->foreign('video_media_id')->references('id')->on('media')->onDelete('restrict');
    $t->index('video_media_id');
});
```

Models: `Product::video(): BelongsTo` (Media), `Media::videoProducts(): HasMany`, `$fillable[] = 'video_media_id'`, and a `videoUrl` accessor beside `featuredImageUrl` (see §2.4).

### 2.2 Upload

**Do not extend `MediaUploadService::ALLOWED_MIMES`.** That list backs documents, certificates, disputes, RFQs and onboarding; adding video types would open all of them to video. Add a dedicated method with its own allow-list, sharing a private `persist()` extracted from `store()`:

```php
private const array VIDEO_MIMES = ['video/mp4' => ['mp4'], 'video/webm' => ['webm']];

public function storeVideo(UploadedFile $file, int $uploaderId, string $description = 'Product video'): Media
{
    $this->validateVideo($file);        // sniffed MIME ∈ VIDEO_MIMES, extension matches, size ≤ config('marketplace.video.max_kb')
    return $this->persist($file, $uploaderId, $description, optimizeImage: false);
}
```

`persist()` keeps existing behaviour (S3 put, `Media::create`, scan dispatch when enabled) and skips `OptimizeImageJob` for videos. Existing `store()` behaviour is unchanged (regression-tested).

**Limits (this is the real work).** Today a video can't even reach the app:

| Layer | Today | Needed (50 MB cap) |
|---|---|---|
| PHP (`bin/server-setup.sh`) | `upload_max_filesize=10M`, `post_max_size=12M` | `52M` / `56M`; restart php-fpm |
| Livewire temp endpoint (`config/livewire.php`) | default `max:12288` | `'rules' => ['required','file','max:51200']`, `'middleware' => 'throttle:20,1'` |
| Livewire `max_upload_time` | 5 min | OK (≈1.4 Mbps uplink minimum); keep |
| Component validation | n/a | `['required','file','mimes:mp4,webm','mimetypes:video/mp4,video/webm','max:51200']` (request-layer `mimes:` per `.ai/rules/app.md`) |
| Edge proxy / CDN | unknown | confirm body-size limit ≥ 56 MB (Cloudflare Free/Pro default is 100 MB) |

The Livewire rule change is **global**: every component's temp upload may now be up to 50 MB *until its own validation rejects it*. Persisted outcomes for other features are unchanged (each already re-validates, e.g. images `max:5120`), temp files are purged after 24 h, and the endpoint is throttled. If that trade-off is unacceptable, use **D3-B**: a dedicated `VideoUploadController` that mints a presigned multipart/PUT URL, the browser uploads straight to S3, then calls a confirm endpoint which sniff-checks the object (`HEAD` + first bytes), creates the `Media` row and attaches it. That needs S3 CORS and is more code, so it is the scale-up path rather than the default.

`LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=local` (README) stays; confirm the web server has ≥ 100 MB free temp space and that, if there are multiple app servers, uploads aren't split across them.

### 2.3 Hub component

`app/Livewire/CompanyHub/VideoManager.php` — route `hub/products/{product}/video`, name `hub.products.video`, inside the existing `auth,verified` hub group and an extra `feature:product_video_enabled`.

- `#[Layout('layouts.portal', ['portal' => 'hub'])]`, `WithFileUploads`.
- Follows the Livewire convention: `#[Locked] public int $productId` + `#[Computed] product()` (note: sibling `GalleryManager` still exposes `public Product $product`; don't copy that).
- `mount()`: `$this->authorize('update', $product)`.
- `uploadVideo(MediaUploadService $uploader)`: validate → `storeVideo` → `$product->update(['video_media_id' => $media->id, 'updated_by_id' => Auth::id()])` inside a transaction → toast. Replacement is the same code path (F2.5). The previous `Media` is left for the orphan job.
- `removeVideo()`: set `video_media_id = null` (confirm with `x-hs-confirm`, like `remove-image-confirm`).
- `wire:poll.5s` only while `scan_status = 'pending'` (copy GalleryManager's pattern, and its `scan_status` overlay states).
- Add a "Video" link to `livewire/company-hub/partials/product-tabs.blade.php` (gated by the flag).
- `x-hs-dropzone` accept `video/mp4,video/webm`, hint `MP4 or WebM, up to 50 MB`.

### 2.4 Delivery and visibility

**`media.view` (routes/web.php) must learn about videos**, otherwise anonymous viewers get 403:

```php
$isPublicProductVideo = (bool) config('marketplace.features.product_video_enabled')
    && $media->scan_status === 'clean'
    && Product::marketplaceVisible()->where('video_media_id', $media->id)->exists();  // reuse the scope
```

Use the existing `Product::marketplaceVisible()` scope rather than copying the inline subscription logic (that inline copy accepts `trialing`, while the scope and `.ai/rules/api-v1.md` say only `active` counts; the new check should follow the rule, and I'd file the inconsistency separately). Add the video to `$isProductOwner` (`Product::where('video_media_id', $media->id)->where('shop_id', $activeShopId)`), and require `scan_status = 'clean'` for the public case (F2.7).

**Range support / streaming.** The current `Storage::disk('s3')->response()` proxy ships whole bodies through PHP and (**VERIFY**) does not answer `Range` requests. iOS Safari refuses to play such `<video>`. So for `video/*` media:

```php
if (str_starts_with($media->mime_type, 'video/')) {
    $url = Storage::disk('s3')->temporaryUrl($media->storage_path, now()->addMinutes(30),
        ['ResponseContentType' => $media->mime_type]);
    return redirect()->away($url)->header('Cache-Control', 'private, max-age=300');
}
```

S3/MinIO then serves ranges natively and PHP never streams video. Authorization has already happened above. The existing comment about MinIO signature host mismatches is exactly what `AWS_TEMPORARY_URL` (already read in `config/filesystems.php`) is for: set it to the browser-reachable endpoint in dev (`http://localhost:9000`) and prod. **If presigning is not viable in some environment**, fall back to a Range-aware responder using the SDK client (`Storage::disk('s3')->getClient()->getObject(['Range' => …])`) returning `206` + `Accept-Ranges`/`Content-Range`.

### 2.5 Storefront rendering

- `ProductDetail::mount()`: add `'video'` to the `load([...])`.
- `product-detail.blade.php`: under the gallery column (do **not** retrofit the Alpine `imageGallery`; v1 is a separate block), render when `config('marketplace.features.product_video_enabled') && $product->video?->scan_status === 'clean'`:

```blade
<video controls preload="metadata" playsinline class="w-full rounded-[var(--hs-radius-card)]"
       poster="{{ $product->featured_image_url }}">
    <source src="{{ route('media.view', $product->video) }}" type="{{ $product->video->mime_type }}">
</video>
```

- Admin: link/player in `product-moderation-list.blade.php` (F2.9).

### 2.6 Orphan job and scanning

`ReconcileMediaOrphansJob` must reference `products.video_media_id`, or videos are deleted after 7 days. Replace the hand-written chain with a data-driven list so it stops drifting:

```php
private const REFERENCES = [
  ['shop_onboarding_documents','media_id'], ['shop_documents','media_id'], ['product_documents','media_id'],
  ['product_gallery','media_id'], ['shop_onboarding_certificates','media_id'], ['shop_certificates','media_id'],
  ['products','video_media_id'],
  // existing gaps (see requirements §2 adjacent bug): confirm columns, then add
  ['rfq_attachments','media_id'], ['quotation_attachments','media_id'], ['message_attachments','media_id'],
  ['dispute_media','media_id'], ['sponsored_content','media_id'], ['sponsored_content','wide_media_id'],
  ['sponsored_content','tall_media_id'], ['payment_attempts','receipt_media_id'],
];
```

Guard it with a Pest test that queries `information_schema` for every FK referencing `media(id)` and fails if one is missing from `REFERENCES`.

`ScanUploadedFileJob` (**only matters when `CLAMAV_ENABLED=true`**): it loads the whole object with `Storage::get()` and slices with `mb_strlen/mb_substr`, which counts characters, not bytes, on binary data. Before enabling ClamAV in production with video: stream via `Storage::readStream()` and `fread()`/`strlen()` in 2 MB byte chunks.

---

## 3. F3 — Staff

### 3.1 Schema

```php
// enum, following 0000/…_create_enum_types.php style
DB::statement("CREATE TYPE shop_staff_status AS ENUM ('invited','active','revoked','left')");

Schema::create('shop_staff', function (Blueprint $t) {
    $t->id();
    $t->unsignedBigInteger('shop_id');
    $t->unsignedBigInteger('user_id')->nullable();           // set on acceptance
    $t->string('email', 255);                                // lower-cased invited address
    $t->char('token_hash', 64)->nullable()->unique();        // sha256 of the emailed token
    $t->timestamp('expires_at')->nullable();
    $t->unsignedBigInteger('invited_by_id');
    $t->timestamp('accepted_at')->nullable();
    $t->timestamp('removed_at')->nullable();
    $t->unsignedBigInteger('removed_by_id')->nullable();
    $t->timestamps();
    $t->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
    $t->foreign('user_id')->references('id')->on('users')->nullOnDelete();
    $t->foreign('invited_by_id')->references('id')->on('users')->onDelete('restrict');
    $t->index(['shop_id']);
});
DB::statement("ALTER TABLE shop_staff ADD COLUMN status shop_staff_status NOT NULL DEFAULT 'invited'");
// one active membership per user (extends "one company per user"):
DB::statement("CREATE UNIQUE INDEX shop_staff_active_user_unique ON shop_staff (user_id) WHERE status = 'active'");
// one pending invite per email per shop:
DB::statement("CREATE UNIQUE INDEX shop_staff_pending_email_unique ON shop_staff (shop_id, lower(email)) WHERE status = 'invited'");

Schema::table('products', fn (Blueprint $t) => $t->unsignedBigInteger('updated_by_id')->nullable()); // F3.12, FK users nullOnDelete
```

"Expired" is derived (`status='invited' AND expires_at < now()`), so no cron is needed; expired rows don't occupy a seat. A weekly prune of expired rows is optional.

### 3.2 Models and helpers

```php
// Shop
public function staff(): HasMany            { return $this->hasMany(ShopStaff::class); }
public function activeStaff(): HasMany      { return $this->staff()->where('status', 'active'); }
public function staffSeatsUsed(): int       // active + pending-and-unexpired
{
    return $this->staff()->where(fn ($q) => $q->where('status', 'active')
        ->orWhere(fn ($q) => $q->where('status', 'invited')->where('expires_at', '>', now())))->count();
}
public function isManagedBy(User $user): bool { return $user->activeShop?->is($this) ?? false; }
/** @return Collection<int,User> owner + active staff — operational notifications (F3.11) */
public function operationalRecipients(): Collection { … }

// User
public function staffMembership(): HasOne   { return $this->hasOne(ShopStaff::class)->where('status', 'active'); }
public function activeShop(): Attribute     // owner's shop, else the shop I'm staff of; memoized per request
{ return Attribute::get(fn () => $this->shop ?? $this->staffMembership?->shop)->shouldCache(); }
public function isShopOwner(): bool         { return $this->shop !== null; }
public function isShopStaff(): bool         { return $this->shop === null && $this->staffMembership !== null; }
```

### 3.3 Actions (`app/Actions/Staff/`)

| Action | Key behaviour |
|---|---|
| `InviteStaffAction` | `DB::transaction`: `Shop::whereKey()->lockForUpdate()` → flag/owner check → normalise email → eligibility (not an owner, admin, or staff anywhere; no pending invite here) → `staffSeatsUsed() < config('marketplace.limits.staff_per_shop')` else `ValidationException` → create row with `token_hash = hash('sha256', $token)`, `expires_at = +7d` → after commit `Notification::route('mail', $email)->notify(new StaffInvitationNotification($shop, $inviter, $token))`. |
| `AcceptStaffInvitationAction` | Lock the invite row; re-check status/expiry; user email (verified) equals invite email (case-insensitive); user is not an owner/admin/staff elsewhere; set `user_id`, `status='active'`, `accepted_at`, null `token_hash`. |
| `RevokeStaffAction` | Owner or admin; `status='revoked'` (invite) or `'revoked'` + `removed_*` (active); instant effect because access resolves from this row on every request. |
| `ResendStaffInvitationAction` | Only for `invited`; new token + expiry, old hash overwritten (old link dies). Throttled. |
| `LeaveShopAction` | Staff only; `status='left'`. |

Cross-account eligibility failures return one **generic** message ("This email can't be invited to your team") so an owner cannot probe which addresses have accounts or own shops. Cap violations show the specific seats message.

The `lockForUpdate` on the shop row is what makes "two simultaneous invites, one seat" safe (F3.3); the partial unique indexes are the backstop.

### 3.4 Invitation and hub UI

- Mail: `StaffInvitationNotification` (mail channel only, queued, branded layout, localized with `__()`).
- Route `GET /staff/invitations/{token}` → `Livewire\Staff\InvitationPage` (`#[Layout('layouts.storefront')]`, `throttle:10,1`, feature-gated):
  - invalid/expired/revoked → friendly state with "ask the owner to resend";
  - guest → keep token in session, `redirect()->setIntendedUrl(url)`, show **Sign in** / **Create account** with `email` prefilled. Registration already requires Turnstile and terms; Google sign-in must land back on the intended URL (**VERIFY** `GoogleAuthController` honours `url.intended`);
  - authenticated but unverified → existing `verified` flow; mismatched email → show "signed in as X, invited as Y" + log-out link;
  - matching → **Join {shop}** button calls `AcceptStaffInvitationAction`, then redirects to `portal.dashboard`.
- `Livewire\CompanyHub\TeamManager` at `hub/team` (`hub.team`), owner-only (`$this->authorize('manageStaff', $shop)`): seat meter, invite form (email), lists of active/pending, revoke/resend/remove with `x-hs-confirm` (same modal pattern as other hub pages). Per `.ai/rules/livewire.md`, store ids not models, and use computed properties.
- Nav: add "Team" to `portal-nav` and `portal-nav-mobile` for owners when the flag is on. Staff see a reduced seller section.

### 3.5 Authorization changes (the large part)

**Policies**

| Policy | Change |
|---|---|
| `ProductPolicy` `view/update/delete/publish`, `ProductVariantPolicy` | `$product->shop->isManagedBy($user)` (publish keeps the shop-active/subscription checks) |
| `ShopPolicy` | `update/delete` stay owner-only; add `manageStaff` (owner only); `createProduct` → `isManagedBy` + active |
| `OrderPolicy`, `QuotationPolicy`, `RfqRecipientPolicy`, `FulfilmentPolicy`, `DisputePolicy` (seller side), `ReviewPolicy` (respond) | seller-side checks use `isManagedBy` (all feature-flagged off today, so low risk, but keep consistent) |
| `SellerPayoutAccountPolicy`, `PayoutPolicy` | **unchanged, owner-only** |
| `Gate accessHub` (`AppServiceProvider`) | based on `activeShop` (active, not deleted) |
| new `Gate manageShop` | owner's shop active; used by owner-only API controllers |

**Chat & broadcast** (flagged): `Conversation::scopeForUser` and `hasParticipant` and the `order.{id}`/`conversation.{id}` channels in `routes/channels.php` must include active staff of `conversation.shop`.

**Classification of existing `->shop` reads** (from `grep` of `user()->shop` / `$user->shop`; audit each when editing):

| Keep owner-only (`->shop`; staff get 404 because it's `null`) | Switch to `activeShop` (shared) |
|---|---|
| `ShopProfileForm`, `SubscriptionPage`, `SubscriptionPaymentPage`, `ManualSubscriptionPaymentPage`, `SubscriptionPaymentCallbackController` | `ProductForm`, `ProductList`, `VariantManager`, `GalleryManager`/`DocumentManager` (via policy) |
| `PayoutAccountForm`, `UpsertPayoutAccountRequest`, `Payouts/*`, `Earnings/EarningsOverview` | `Hub/Dashboard`, `Hub/CustomersList`, `Hub/Reports`, `Portal/Dashboard` |
| `CertificateList`, `Storefront/ApplyForCertificate`, `OnboardingWizard` | `Orders/OrderBuilder`, `Orders/OrderQueue`, `Reviews/ReviewList`, `RfqQueue`, `Storefront/CompanyProfile` (own-shop checks, F3.13) |
| API: `HubEarningsController`, `HubFeeRatesController`, `HubPayoutAccountController`, `HubPayoutController` | API: `HubProductController`, `HubOrderController`, `Hub/RfqRecipientController`, `SellerHubController` (per method) |
| | `routes/web.php` `media.view` (`$isProductOwner`, `$isRfqRecipient`), `product-detail.blade.php`, `portal-header`, `portal-nav`, `portal-nav-mobile` |

**Route middleware.** Add `shop.owner` (alias in `bootstrap/app.php`) to `hub/shop/profile`, `hub/subscription*`, `hub/certifications`, `hub/payout-account`, `hub/payouts*`, `hub/earnings`, `hub/team`. It 403s staff explicitly instead of relying on `->shop === null` side effects.

**Staff must not become sellers (F3.10).** Because `$user->shop === null` is how the app recognises a buyer-who-could-onboard, staff would otherwise see "Register Business". Guard in three places: `OnboardingWizard::mount()` (redirect with message), the nav/CTAs (hidden when `isShopStaff()`), and `ApproveOnboardingAction` (throws if the onboarding user is active staff, so an admin can't create the conflict). `AcceptStaffInvitationAction` rejects the reverse case.

**Notifications.** 18 sites call `$shop->user->notify(...)`.

| Operational → owner + staff via `$shop->operationalRecipients()` | Owner only (unchanged) |
|---|---|
| `QuotationExpiryJob`, `RfqAutoCloseJob`, `NotifySellerQuotation{Rejected,Awarded}`, `NotifySellerRfqClosed`, `NotifyCounterpartOrderCancelled`, `NotifySellerOrder{Completed,Paid}`, `NotifySellerDisputeOpened`, `NotifyPartiesDisputeResolved`, `CreateReviewAction` | `ScanCertificateExpiryJob`, `ScanSubscriptionRenewalsJob`, `SubscriptionQueue` (activated), `ProductModerationList` (flagged), `RejectPayoutAccountAction`, `VerifyPayoutAccountAction`, `ReleasePayoutAction` |

Use `Notification::send($recipients, $notification)`. Notification classes are unchanged.

### 3.6 Audit trail

`products.updated_by_id` set in `ProductForm::save()`, `VideoManager`, and the API product endpoints; show "Last edited by" in the hub product list (Should). Media already records `uploaded_by_id`. Invitation/removal history lives on `shop_staff` rows (`invited_by_id`, `accepted_at`, `removed_by_id`, `removed_at`). Admin `merchant-show` lists staff with a remove action (F3.16).

---

## 4. Config additions (`config/marketplace.php`)

```php
'features' => [
    // …existing…
    'product_video_enabled' => (bool) env('MARKETPLACE_PRODUCT_VIDEO_ENABLED', false),
    'staff_enabled'         => (bool) env('MARKETPLACE_STAFF_ENABLED', false),
],
'limits' => ['staff_per_shop' => (int) env('MARKETPLACE_MAX_STAFF_PER_SHOP', 2)],
'video'  => ['max_kb' => (int) env('MARKETPLACE_VIDEO_MAX_KB', 51200)],
'slugs'  => ['reserved' => ['new','create','edit','search','api','admin','hub','null','undefined']],
```

Add the env keys to `.env.example`, a rule note in `.ai/rules/config.md`, and a short `.ai/rules` entry for each trap (slug binding, owner vs activeShop, video orphan references).

---

## 5. Test plan (Pest, `tests/Feature/…`)

| Area | Tests |
|---|---|
| **Slugs** | generator (collision suffix, numeric/empty/reserved fallback, Arabic/Turkish input, soft-deleted reuse blocked); `ApproveOnboardingAction` and factories produce slugs; `route('storefront.*')` emits slug; id → 301; old slug → 301; unknown → 404; query string preserved; hidden product still 404; slug edit validation + redirect creation; `slugs:backfill` idempotent; hub/API routes still bind by id |
| **Video** | upload ok; replace keeps one; reject `.mov`, oversize, spoofed MIME, `.exe` renamed; flag off → 404/no UI; `media.view`: anon gets published product's clean video, 403 for draft/pending/infected, owner/staff/admin allowed; orphan job keeps referenced video and the schema-introspection coverage test; `MediaUploadService::store()` unchanged for documents (video type still rejected there) |
| **Staff** | invite → accept happy path (new and existing user); cap with pending invites; concurrent-invite race (two DB connections or `lockForUpdate` assertion); email mismatch; expired/revoked/resent tokens; owner/admin/staff-elsewhere ineligible with generic message; staff allowed/denied matrix per §5.3 across hub routes, `media.view`, and API v1; revoked staff loses access next request; staff blocked from onboarding; notification routing split; suspended shop blocks staff |
| **Regression** | existing `HubProductTest`, `MediaViewTest`, `PortalNavigationTest`, `OnboardingWizardTest`, `ApproveOnboardingActionTest`, `FeatureFlagTest` still pass |

Run via the project's `composer ci:check` (CI runs it) plus `vendor/bin/sail artisan test --filter=…` locally.

---

## 6. Risks and mitigations

| Risk | Mitigation |
|---|---|
| Staff change touches ~47 files / ~56 ownership comparisons | Classification table + `Shop::isManagedBy`/`User::activeShop` helpers; owner-only pages stay on `->shop`; ship behind `staff_enabled`; test matrix |
| Orphan job deleting video (and, today, other attachments) | §2.6 data-driven list + schema-introspection test; verify production data first |
| iOS can't play proxied video | Presigned redirect (or Range-aware proxy) + real-device test |
| Raising Livewire/PHP upload limits globally | Throttle, per-component validation, 24 h temp cleanup; D3-B if unacceptable |
| Slug race / ambiguity with legacy ids | Unique index as arbiter + retry; slugs can't be numeric; two-deploy rollout |
| Email enumeration through invite errors | Generic message for cross-account conflicts |
| Livewire 4 / `route()` slug behaviour differs from expectation | Spikes S1/S2 in `tasks.md` before wide edits |

## 7. Out of scope follow-ups

Video transcoding/poster extraction (needs ffmpeg), store videos, plan-based seat counts (D5), custom staff permissions, localized slugs, sitemap.xml.
