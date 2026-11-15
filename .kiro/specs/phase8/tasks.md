# Share Buttons + Share Counts — Implementation Tasks

| | |
|---|---|
| **Repo** | `mamaw-verse/b2b-marketplace` @ `bb62e1c` (slugs, product video, staff are merged) |
| **Feature** | Product detail page share bar: Copy link, Facebook, Messenger, Instagram (via native share), X, plus share counts |
| **Suggested location** | `.kiro/specs/phase8/tasks.md` |
| **Estimate** | ≈ 3.5 engineer-days |

I read the code at this commit but have not run it (`vendor/` isn't installed here). Items marked **VERIFY** are checked by a task below.

---

## 0. Scope and decisions

### What already exists (no work needed)
- `product-detail.blade.php` already emits `canonical`, `og:*` and `twitter:card=summary_large_image`, and the URL is the slug URL (`route('storefront.product', $product)`). Share previews on Facebook and X depend on these, so they are covered.
- `Product::featured-image` is served by `route('products.featured-image', $product)`; falls back to `images/brand-logo.png`.
- The title row (`{{-- Title --}}`, ~line 634) is where the bar goes.

### Decisions (change in review if you disagree)

| # | Decision |
|---|---|
| S1 | **No Facebook App ID** (your choice). Messenger is a mobile deep link only (`fb-messenger://share/?link=`) and is **hidden on desktop**. |
| S2 | **Instagram has no web share URL for links.** The Instagram button calls `navigator.share()` and is shown only where the browser supports it (mobile). Desktop users get Copy link, Facebook and X. |
| S3 | A "share" means **a click on a share action** (platforms don't report completion), except `native`, which counts only when `navigator.share()` resolves (not on cancel). Label it "shares" in the UI; do not claim "completed shares". |
| S4 | Channels recorded: `copy`, `facebook`, `messenger`, `x`, `native`. **Instagram cannot be counted separately**, because the native sheet doesn't say what the user picked. UI shows "Instagram" as the button, stats show it under "Mobile share sheet". |
| S5 | Counts are stored as **daily aggregates per product and channel** (no IP, no user id stored) plus a denormalised `products.share_count` total. |
| S6 | **Sellers/staff** see a total in the product list and a per-channel breakdown (all-time and last 30 days) on the product edit page. **Public** sees a total only when it reaches `share_count_public_min` (default 5), to avoid showing "0 shares". |
| S7 | Tracking is a Livewire `#[Renderless]` action on `ProductDetail`, not a new route/controller, so CSRF and bindings come for free. |

### Out of scope
Share counts on product cards or the company page, click-through attribution (UTM, referrals), WhatsApp/LinkedIn/Telegram buttons, and Facebook App ID features.

---

## T1. Config and flag (0.25 d)

**Files:** `config/marketplace.php`, `.env.example`

- [x] Add under `features`: `'product_share_enabled' => (bool) env('MARKETPLACE_PRODUCT_SHARE_ENABLED', false),`
- [x] Add a `share` block:
  ```php
  'share' => [
      'public_min'   => (int) env('MARKETPLACE_SHARE_PUBLIC_MIN', 5),   // S6
      'dedupe_minutes' => (int) env('MARKETPLACE_SHARE_DEDUPE_MINUTES', 10),
      'channels'     => ['copy', 'facebook', 'messenger', 'x', 'native'],
  ],
  ```
- [x] Add the two env keys to `.env.example`.

**Done when:** `config('marketplace.features.product_share_enabled')` is `false` by default and `FeatureFlagTest` still passes.

---

## T2. Data model for counts (0.5 d)

**Files:** 2 migrations, `app/Models/ProductShareStat.php`, `app/Models/Product.php`

- [x] Migration `create_product_share_stats_table`:
  ```php
  Schema::create('product_share_stats', function (Blueprint $t) {
      $t->id();
      $t->unsignedBigInteger('product_id');
      $t->string('channel', 20);
      $t->date('shared_on');
      $t->unsignedInteger('count')->default(0);
      $t->timestamps();
      $t->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
      $t->unique(['product_id', 'channel', 'shared_on']);
      $t->index(['product_id', 'shared_on']);
  });
  ```
- [x] Migration `add_share_count_to_products`: `unsignedInteger('share_count')->default(0)`; add `'share_count'` to `$casts` as `integer` and the docblock. **Do not** add it to `$fillable` (only the tracker writes it).
- [x] `ProductShareStat` model (`final`, `declare(strict_types=1)`, `$fillable`, `product(): BelongsTo`); `Product::shareStats(): HasMany`.
- [x] Factory `ProductShareStatFactory` for tests.

**Done when:** `php artisan migrate` and rollback both run clean on Postgres.

---

## T3. Tracking action (0.5 d)

**Files:** `app/Actions/Storefront/RecordProductShareAction.php`, `app/Livewire/Storefront/ProductDetail.php`

- [x] `RecordProductShareAction::execute(Product $product, string $channel, string $fingerprint): bool`
  1. Return `false` if the channel is not in `config('marketplace.share.channels')`.
  2. De-duplicate: `Cache::add("share:{$product->id}:{$channel}:{$fingerprint}", 1, now()->addMinutes(config('marketplace.share.dedupe_minutes')))`; if it returns `false`, return `false` (counted already).
  3. In one `DB::transaction`: upsert the daily row and bump the total.
     ```php
     DB::table('product_share_stats')->upsert(
         [['product_id' => $product->id, 'channel' => $channel, 'shared_on' => today()->toDateString(),
           'count' => 1, 'created_at' => now(), 'updated_at' => now()]],
         ['product_id', 'channel', 'shared_on'],
         ['count' => DB::raw('product_share_stats.count + 1'), 'updated_at' => now()],
     );
     Product::whereKey($product->id)->increment('share_count');   // query-builder increment: no model events, no reindex
     ```
  4. Return `true`.
- [x] `fingerprint` = `sha1(($user?->id ?? $ip).'|'.$userAgent)`. No raw IP or user id is stored anywhere except the short-lived cache key.
- [x] In `ProductDetail` add:
  ```php
  #[Renderless]
  public function trackShare(string $channel, RecordProductShareAction $action): void
  {
      if (! config('marketplace.features.product_share_enabled')) { return; }

      $key = 'product-share:'.(auth()->id() ?? request()->ip());
      if (RateLimiter::tooManyAttempts($key, 30)) { return; }     // 30/min per visitor, silent drop
      RateLimiter::hit($key, 60);

      $action->execute($this->product, $channel, sha1((auth()->id() ?? request()->ip()).'|'.request()->userAgent()));
  }
  ```
  Visibility is already enforced in `mount()` (404 for unpublished/hidden products), so only visible products can be tracked.
- [x] Never throw to the client: wrap in `try/catch` and `report($e)`. A tracking failure must never break sharing.

**Done when:** calling `trackShare('facebook')` twice within the dedupe window adds 1 to `share_count` and to the daily row; an unknown channel or a disabled flag adds 0.

---

## T4. Share bar component (1 d)

**Files:** `resources/views/components/hs-share-buttons.blade.php`, `resources/views/livewire/storefront/product-detail.blade.php`

- [x] Create `<x-hs-share-buttons :url :title :text />` (Alpine, follows the other `x-hs-*` components; use the same CSS variables, e.g. `var(--hs-neutral-200)`, `var(--hs-radius-md)`, and the focus-ring style used in this file):

  | Button | Behaviour | Shown |
  |---|---|---|
  | Copy link | `navigator.clipboard.writeText(url)`; on failure fall back to a hidden `<textarea>` + `document.execCommand('copy')`. Show inline "Link copied" for 2 s in an `aria-live="polite"` span. | always |
  | Facebook | `<a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($url) }}" target="_blank" rel="noopener noreferrer">` | always |
  | X | `<a href="https://x.com/intent/post?url={{ urlencode($url) }}&text={{ urlencode($text) }}" …>` | always |
  | Messenger | `<a href="fb-messenger://share/?link={{ urlencode($url) }}">` | **touch devices only** (S1): `x-show="isTouch"` where `isTouch = matchMedia('(pointer: coarse)').matches` |
  | Instagram | button calling `navigator.share({ title, text, url })` | only when `navigator.share` exists (S2); the label/aria-label is "Instagram", the tooltip "Share via your phone's share sheet" |

- [x] Brand icons: add inline SVGs for Facebook, X, Messenger, Instagram and a `link` icon (Flux has no brand icons). Each button is icon-only with `aria-label`, `size-10`, same hover/focus classes as the lightbox buttons.
- [x] Every user action calls `$wire.trackShare('<channel>')` **without awaiting it**. For `native`, call it only inside the `.then()` of `navigator.share()` and ignore `AbortError`.
- [x] Hide the whole bar when `config('marketplace.features.product_share_enabled')` is `false`.
- [x] Place it in `product-detail.blade.php` directly under the Title row (inside the `{{-- Title --}}` block's parent, before `{{-- Supplier line --}}`). Props: `url="{{ $ogUrl }}"`, `title="{{ $ogTitle }}"`, `text="{{ $product->name }}"`.
- [x] Public count (S6): next to the buttons render `{{ trans_choice(':count share|:count shares', $product->share_count) }}` only when `$product->share_count >= config('marketplace.share.public_min')`.
- [x] RTL: use logical utilities (`ms-*`, `me-*`, `start-*`), the file already supports `ar`.
- [x] Mobile: the bar wraps (`flex-wrap`), buttons stay ≥ 40 px tall.
- [x] Localisation: new strings `Share`, `Share this product`, `Copy link`, `Link copied`, `Share on Facebook`, `Share on X`, `Share on Messenger`, `Share on Instagram`, `Share via your phone's share sheet`, `:count share|:count shares` added to **all five** `lang/{en,ar,id,ms,tr}.json` (en copy = key; translate the others, or copy en with a `TODO` ticket for translators if you want to ship before translation).

**Done when:** the bar renders on a published product, all five actions behave per the table, nothing renders when the flag is off, and the page still passes its existing tests.

---

## T5. Seller-facing stats (0.75 d)

**Files:** `app/Services/ShopAnalyticsService.php`, `app/Livewire/CompanyHub/ProductList.php` + view, `app/Livewire/CompanyHub/ProductForm.php` + view

- [x] `ShopAnalyticsService::productShareBreakdown(Product $product, int $days = 30): array` returning
  `['total' => int, 'last_days' => int, 'by_channel' => ['copy' => ['all' => n, 'recent' => n], …]]` using two grouped queries on `product_share_stats`.
- [x] `ProductList`: add a "Shares" column (`flux:table.column`) before "Created" showing `$product->share_count`; no extra query (denormalised).
- [x] `ProductForm` edit page: add a small read-only "Sharing" card, shown only when editing and the flag is on, with per-channel rows (label `native` as "Mobile share sheet (incl. Instagram)") and the 30-day totals. Authorization is already handled by the existing `ProductPolicy` (owner and staff).
- [x] Eager-select `share_count` only; do not load stat rows in the list.

**Done when:** owner and staff see the column and card for their own products; another shop's product returns the same 403/404 as today.

---

## T6. Tests (0.75 d)

Pest, in the existing style (`RefreshDatabase`, `Product::factory()->published()`).

**`tests/Feature/ProductShareTrackingTest.php`**
- [x] counts a valid channel: row created, `share_count` = 1
- [x] repeat within window not counted; counted after `Cache::flush()` / time travel past the window
- [x] two different channels both count
- [x] unknown channel ignored; flag off ignored
- [x] rate limit: the 31st call within a minute is dropped
- [x] unpublished / hidden product page returns 404 so nothing can be tracked
- [x] no IP or user id is persisted (assert table columns)
- [x] tracking failure (mock action to throw) does not surface an exception

**`tests/Feature/StorefrontProductShareBarTest.php`**
- [x] bar renders with canonical slug URL in the Facebook/X hrefs, and URL-encoded
- [x] Messenger link uses `fb-messenger://share/?link=`
- [x] hidden when flag off
- [x] public count hidden below `public_min`, shown at/above it

**`tests/Feature/HubProductShareStatsTest.php`**
- [x] list column and breakdown values correct; 30-day window excludes older rows
- [x] staff of the shop can see; staff/owner of another shop cannot

**`tests/Browser/Feature/ProductShareBrowserTest.php`** (Pest browser, follows `SlugsBrowserTest`)
- [x] click Copy link → "Link copied" appears and `share_count` increments (grant clipboard permission if the plugin supports it; the `execCommand` fallback covers headless)
- [x] Facebook/X links have `target=_blank` and `rel` containing `noopener`
- [x] with `navigator.share` stubbed via script, the Instagram button appears and calls it; with it removed, the button is absent

Run: `composer ci:check`.

---

## T7. Manual QA and release (0.25 d)

- [ ] **VERIFY** previews: paste a published product URL into Facebook Sharing Debugger and the X card preview; confirm title, image and description. Image URLs must be reachable without login.
- [ ] **VERIFY** on a real iPhone and an Android phone: native share sheet lists Instagram; Messenger deep link opens the app (if the app is missing it does nothing; accepted).
- [ ] **VERIFY** desktop Chrome/Safari/Firefox: Messenger and Instagram buttons are hidden; Copy link works over HTTPS.
- [ ] Check RTL (`ar`) layout and keyboard/screen-reader use (tab order, `aria-label`s, `aria-live` confirmation).
- [ ] Add a short `.ai/rules` note: "share tracking is `ProductDetail::trackShare` → `RecordProductShareAction`; counts are clicks, not completions; Instagram is only reachable via native share".
- [ ] Rollout: deploy with the flag **off** → run migrations → enable `MARKETPLACE_PRODUCT_SHARE_ENABLED` in staging → QA → enable in production. Rollback: flip the flag off; tables remain.

---

## Task summary

| Task | Est. | Depends on |
|---|---|---|
| T1 Config and flag | 0.25 d | n/a |
| T2 Data model | 0.5 d | T1 |
| T3 Tracking action | 0.5 d | T2 |
| T4 Share bar component | 1 d | T1, T3 |
| T5 Seller stats | 0.75 d | T2 |
| T6 Tests | 0.75 d | T3–T5 |
| T7 QA and release | 0.25 d | all |
| **Total** | **≈ 4 d** | T4 and T5 can run in parallel after T3 |

## Risks

| Risk | Mitigation |
|---|---|
| Counts are clicks, not completed shares, and can be inflated | De-dupe window, per-visitor rate limit, wording "shares" in UI but documented in S3 |
| Instagram can't be counted or deep-linked on desktop | Native share sheet on mobile only; stats group it as "Mobile share sheet" (S2/S4) |
| Messenger deep link silently fails without the app, and is absent on desktop | Mobile-only button; Copy link remains the universal fallback |
| `#[Renderless]` Livewire call fires a request per click | Calls are fire-and-forget, rate limited, and the page already runs Livewire |
| Clipboard API needs HTTPS and may be blocked | `execCommand` fallback; confirmation only on success |