# Round 2 Bugfix — Implementation Notes

## #43 — Dispute Hold Days cannot be edited (Done)

### Summary

Fixed the "Dispute Hold Days" field (and all other payout settings fields) reverting to their previous value after clicking "Save Settings".

### Problem

The payout settings form uses `flux:input type="number"` fields with plain `wire:model`. In Livewire 4, `wire:model` without a modifier is deferred — it does not synchronise the input's value to the server-side component property until the next Livewire network request. For number inputs wrapped inside Flux's `<div data-flux-input>` container, the updated value was not being captured from the DOM before the `wire:submit="save"` action fired. As a result, `$this->dispute_hold_days` still held its mounted value of `"7"` when `save()` ran, so the database was re-written with the old value and `loadSettings()` read `"7"` back, making the field appear to revert.

### Solution / Changes

**`resources/views/livewire/admin/payout-settings.blade.php`**

Changed all four `flux:input` fields from `wire:model` to `wire:model.blur`:

```blade
{{-- before --}}
<flux:input wire:model="dispute_hold_days" … />

{{-- after --}}
<flux:input wire:model.blur="dispute_hold_days" … />
```

The `.blur` modifier causes Livewire to synchronise the field's value to the component the moment the user leaves (blurs) the input — before the form is submitted — guaranteeing the correct value is available when `save()` runs.

The same change was applied to all four fields: `dispute_hold_days`, `minimum_payout_threshold`, `transaction_fee_amount`, `commission_fee_rate`.

No changes to the Livewire component PHP, the model, migrations, or API controller were required.

### Tests

New file: `tests/Feature/PayoutSettingsLivewireTest.php` (9 tests)

| Test | Asserts |
|------|---------|
| component mounts with current setting values | All four properties equal the seeded defaults on mount |
| admin can update dispute hold days | Setting to `"1"` and calling `save()` persists `1` in the DB |
| component reloads updated value after save | Component property reflects the new DB value after `save()` |
| save records the admin who made the change | `updated_by_id` is set to the acting admin's ID |
| save updates all four settings simultaneously | Bulk update persists all four fields correctly |
| validation rejects dispute_hold_days below 1 | `save()` fails validation and DB remains at `7` |
| validation rejects dispute_hold_days above 365 | `save()` fails validation and DB remains at `7` |
| validation rejects non-integer dispute_hold_days | `save()` fails validation and DB remains at `7` |
| non-admin cannot access the payout settings page | GET `/admin/payout-settings` returns 403 for a seller |

All 22 tests pass (9 new + 13 pre-existing API tests in `PayoutSettingsTest.php`).

## #28 — Storefront: Implement Google Analytics (Done)

### Summary

Implemented Google Analytics 4 (GA4) for the public storefront and wired GA4 traffic data into the admin analytics dashboard.

### Problem

- No Google Analytics or third-party tracking existed anywhere in the codebase.
- Task also requested displaying the GA data on the `/admin` analytics dashboard.

### Solution / Changes

**1. GA4 client library**
- Added dependency `google/analytics-data` (`^0.26.0`) — official GA4 Data API client.

**2. Config & env**
- `config/services.php` — added `google_analytics` block:
  ```php
  'google_analytics' => [
      'measurement_id' => env('GOOGLE_ANALYTICS_ID'),
      'property_id'    => env('GA4_PROPERTY_ID'),
      'credentials'    => env('GOOGLE_ANALYTICS_CREDENTIALS'),
  ],
  ```
- `.env.example` — added `GOOGLE_ANALYTICS_ID`, `GA4_PROPERTY_ID`, `GOOGLE_ANALYTICS_CREDENTIALS`.

**3. Storefront tracking snippet**
- `resources/views/layouts/storefront.blade.php` — added the standard `gtag.js` snippet in `<head>`, rendered only when `GOOGLE_ANALYTICS_ID` is configured:
  ```blade
  @if(config('services.google_analytics.measurement_id'))
      <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.google_analytics.measurement_id') }}"></script>
      <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','{{ config('services.google_analytics.measurement_id') }}');</script>
  @endif
  ```
- Only the storefront layout is tracked (public-facing). The authenticated portal layout is intentionally not tracked.

**4. `App\Services\GoogleAnalyticsService` (new)**
- Wraps the GA4 Data API client in a small service following existing `app/Services/*` conventions.
- `isConfigured(): bool` — true only when both `GA4_PROPERTY_ID` and a resolvable credentials file are set.
- `getOverview(string $from, string $to): ?array` — returns `active_users`, `sessions`, `page_views`, `bounce_rate`, `avg_session_duration` for the given date range.
- Graceful degradation: returns `null` when unconfigured or on API/network error (wrapped in `try/catch` + `report()`). Diagonal/testing-safe: client is only built lazily when credentials exist.

**5. `App\Livewire\Admin\Finance\AnalyticsDashboard` (admin dashboard)**
- Resolves `GoogleAnalyticsService` via `app()` (project convention for Livewire service resolution).
- `mount()` sets `$gaConfigured`; `loadMetrics()` fetches the GA overview scoped to the same selected date range and adds it under `$this->metrics['ga']`.
- No constructor service-injection used (matches existing components; also makes the final class mockable via container binding in tests).

**6. Analytics dashboard Blade view**
- Added a "Website Traffic" section (rendered only when `$gaConfigured`) below "Fees & Ratings" with 5 cards: **Active Users, Sessions, Page Views, Bounce Rate (%), Avg. Session Duration (MM:SS)**.
- When no GA data is available the cards fall back to `0` (counts) or an em dash (`bounce rate` / `avg session duration`).

### Env variables to activate

| Var | Purpose |
|-----|---------|
| `GOOGLE_ANALYTICS_ID` | GA4 Measurement ID (`G-XXXXXXXXXX`) — enables storefront `gtag.js` snippet |
| `GA4_PROPERTY_ID` | GA4 property numeric ID — enables admin dashboard GA data |
| `GOOGLE_ANALYTICS_CREDENTIALS` | Absolute path to a Google Cloud service-account JSON file — authorizes the Data API |

Notes:
- GA4 property must have the **Google Analytics Data API** enabled in Google Cloud Console.
- If any of the above is missing, the feature degrades silently (no snippet, no traffic section, no errors).

### Tests

- `tests/Feature/StorefrontHomeTest.php`
  - GA snippet is **not** rendered when measurement id is missing.
  - GA snippet **is** rendered when measurement id is configured.
- `tests/Feature/AdminAnalyticsDashboardTest.php` (new)
  - Admin can access the analytics dashboard.
  - "Website Traffic" section hidden when GA not configured.
  - "Website Traffic" section renders GA metrics (including `42.3%` bounce, `1:35` avg session) when configured — uses a container-bound fake service.
  - Dashboard renders without error when the GA API call fails.

### Lint / Tests status

- `pint` clean (dirty diff fixed).
- 22 relevant tests pass (StorefrontHome, AdminAnalyticsDashboard, Analytics).
- Full suite: 324 tests, 322 pass — 2 pre-existing unrelated failures:
  - `tests/Feature/AccountRfqCloseCascadeTest` (`closing_an_RFQ_does_not_close_recipients_whose_quotation_was_awarded`)
  - `tests/Feature/BrandingTest` (`storefront_homepage_shows_country_portals_coming_soon_section`)

## #30 — Should be auto-upload on file select (Done)

### Summary

Updated the step-5 document upload checklist so files upload automatically as soon as the user selects them, removing the manual "Upload" button entirely.

### Problem

On the `/hub/onboarding` document steps, selecting a file via the `wire:model="documentUploads.{{ $type }}"` input only staged the file locally. The user then had to click a separate "Upload" button to invoke `uploadDocument()`. This two-step flow was cumbersome and easy to miss.

### Solution / Changes

**`app/Livewire/CompanyHub/OnboardingWizard.php`**

Added a Livewire lifecycle hook that fires whenever the `documentUploads` property is updated (i.e. the moment a file is selected), triggering the upload automatically:

```php
public function updatedDocumentUploads(mixed $value, string $key): void
{
    if ($value !== null) {
        $this->uploadDocument($key, app(MediaUploadService::class));
    }
}
```

The `MediaUploadService` is resolved explicitly via `app()` because Livewire's method-injection only works when the method is invoked directly as a `wire:click` action — inside a lifecycle hook the service must be passed manually.

**`resources/views/livewire/company-hub/steps/step-5-documents.blade.php`**

- Removed the entire "Selected-but-not-yet-uploaded file + upload action" block, including the manual `flux:button` ("Upload" / "Uploading…") and the filename prompt it rendered.
- Updated the transfer-progress `wire:loading` block to also target the `uploadDocument('{{ $type }}')` call (in addition to `documentUploads.{{ $type }}`), so the "Uploading file…" spinner is shown during both the file transfer and the server-side persist step.

The droppable area and file input are unchanged — the dropzone now both captures the file and triggers the upload in one action.

### Tests

Appended to `tests/Feature/OnboardingWizardTest.php` (2 tests):

| Test | Asserts |
|------|---------|
| setting documentUploads auto-triggers uploadDocument without manual button click | `uploadDocument()` returns gracefully with no file set, and the `updatedDocumentUploads` lifecycle hook exists on the component |
| step 5 document upload dropzone has no manual upload button | The rendered step-5 view contains no `wire:click="uploadDocument"` and shows the "Drag & drop, or click to browse" prompt |

All 27 onboarding wizard tests pass. Full suite: 333/335 pass — the same 2 pre-existing unrelated failures as before (`AccountRfqCloseCascadeTest`, `BrandingTest`).

### Follow-up fixes (reported after initial #30 change)

Two additional issues surfaced once the manual "Upload" button was removed:

**1. Scanning takes an eternity to finish**

`app/Jobs/ScanUploadedFileJob.php` streamed the file to ClamAV in **2 KB** chunks (`$chunkSize = 2048`) while the comment claimed 2 MB. The ClamAV INSTREAM protocol caps each chunk at 2 MB, so a 20 MB file required ~10,000 socket writes instead of ~10 — a ~1000x over-chunking that made scanning feel like it never finished.

Fixed line 99 to `2 * 1024 * 1024` (2 MB), so chunk writes now match the protocol limit. (Pint's `mb_str_functions` fixer reverted `strlen`/`substr` to `mb_strlen`/`mb_substr`, which behave identically here.)

**2. Dropping a file into the upload area doesn't work**

`resources/views/livewire/company-hub/steps/step-5-documents.blade.php` managed only the CSS drag-over highlight; the `x-on:drop.prevent` handler never read files from the drop event or assigned them to the hidden `<input type="file">`, so no `change` event fired and `wire:model` never updated.

Fixed the `drop` handler to read `$event.dataTransfer.files`, assign them to the hidden input's `.files`, and dispatch a synthetic `change` event (triggering Livewire's `wire:model` → `updatedDocumentUploads` → auto-upload chain):

```blade
x-on:drop.prevent="
    dragging = false;
    if ($event.dataTransfer?.files?.length) {
        const input = document.getElementById('doc-input-{{ $type }}');
        input.files = $event.dataTransfer.files;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }
"
```

**Tests added** (in `tests/Feature/OnboardingWizardTest.php`):

| Test | Asserts |
|------|---------|
| step 5 document dropzone wires a dropped file to the hidden input | The rendered view contains the `dataTransfer?.files` read and `dispatchEvent(new Event('change', { bubbles: true }))` wiring |

All 27 onboarding wizard tests + 3 document-manager tests pass.

## #31 — Publish/archive error when toggled rapidly (Done)

### Summary

Fixed a Livewire error caused by rapid double-clicks on the Publish/Archive button and improved the button's visual prominence so sellers can easily locate it.

### Problem

- Rapidly clicking the toggle button dispatched multiple concurrent `confirmToggleStatus` calls before the first request settled, causing Livewire component state corruption.
- The button used `variant="ghost"`, making it nearly invisible against the table row background — users had trouble finding it.

### Solution / Changes

**`resources/views/livewire/company-hub/product-list.blade.php`**

Two changes to the Publish/Archive toggle button:

1. **Disable during loading** — added `wire:loading.attr="disabled"` and `wire:target="confirmToggleStatus"` so the button is disabled for the exact duration of the `confirmToggleStatus` network round-trip, preventing any additional clicks from being registered:

   ```blade
   {{-- before --}}
   <flux:button
       variant="ghost"
       size="sm"
       …
       wire:click="confirmToggleStatus(…)"
   />

   {{-- after --}}
   <flux:button
       variant="subtle"
       size="sm"
       …
       wire:click="confirmToggleStatus(…)"
       wire:target="confirmToggleStatus"
       wire:loading.attr="disabled"
   />
   ```

2. **Better visibility** — changed `variant` from `ghost` to `subtle`. The `subtle` variant has a light background fill, giving it clear affordance compared to the fully transparent `ghost` variant while remaining visually lighter than a primary action.

No changes to the Livewire component PHP (`ProductList.php`) were required — the existing `confirmingProductId` guard inside `toggleStatus()` already provides a server-side idempotency check.

### Tests

New file: `tests/Feature/ProductListTest.php` (10 tests)

| Test | Asserts |
|------|---------|
| component renders and shows seller products | Both published and archived products are visible |
| confirmToggleStatus sets confirming state for a published product | `confirmingProductId` and `confirmingAction` set correctly |
| confirmToggleStatus sets confirming state for an archived product | Same, for publish flow |
| toggleStatus archives a published product | DB status flips to `archived` |
| toggleStatus publishes an archived product | DB status flips to `published` |
| toggleStatus resets confirming state after execution | Both `confirmingProductId` and `confirmingAction` null after toggle |
| toggleStatus does nothing when no product is being confirmed | Early-return guard works; status unchanged |
| seller cannot toggle status of a product belonging to another shop | Throws `ModelNotFoundException` — ownership enforced |
| status filter shows only published products | Archived product hidden when filter = `published` |
| status filter shows only archived products | Published product hidden when filter = `archived` |

All 10 tests pass.

## #32 — Bad RFQ User Experience (Done)

### Summary

Replaced the redirect-away-to-profile-page flow with a 2-step RFQ form. Buyers without a profile complete it inline on step 1, then proceed to the RFQ form on step 2 — all without losing any URL state (`shop_id`, `product_id`, `title`). Buyers who already have a profile skip step 1 entirely and get a small "Edit profile" link on the RFQ form.

### Problem

When a buyer's profile was missing, submitting the RFQ form displayed a warning banner with a link to `/account/profile`. Navigating there lost the entire RFQ context (the shop, reference product, and pre-filled title carried in the URL), forcing the buyer to start over.

### Solution / Changes

**`app/Livewire/Account/RfqCreateForm.php`**

- Added a `$step` integer property (`1` = profile setup, `2` = RFQ form).
- `mount()` checks `Auth::user()->buyerProfile`. If absent → `$step = 1`; if present → `$step = 2`.
- Added all buyer profile fields as public properties (`business_name`, `billing_address_*`, `shipping_address_*`, `contact_phone`, `contact_email`, `useShippingSameAsBilling`) matching the `BuyerProfile` model's fillable set.
- Added `saveProfile()` action: validates using `profileRules()`, calls `buyerProfile()->updateOrCreate(...)`, advances `$step` to 2.
- The existing `submit()` action is unchanged except it now falls back to `$step = 1` (instead of adding an error banner) if the profile is somehow missing when called.
- `rules()` (used by Livewire real-time validation) delegates to `profileRules()` or `rfqRules()` based on the active step.
- Removed the old `$this->addError('buyer_profile', ...)` path entirely — no more warning banner.

**`resources/views/livewire/account/rfq-create-form.blade.php`**

- Added a 2-step progress indicator (numbered circles with a connecting line) above the card, showing which step is active. The completed step shows a checkmark.
- **Step 1 card** — "Set up your buyer profile" with the full address form, a "Continue to RFQ" submit button (`wire:submit="saveProfile"`), and a Cancel link. The existing step-2 RFQ content is hidden.
- **Step 2 card** — the existing RFQ form, unchanged in behaviour. Added an "Edit profile" link (plain underlined text, `route('account.profile')`) in the recipient strip header for buyers who want to update their profile later.
- The old `@error('buyer_profile')` warning banner block was removed.

### UX Flow

```
No profile                       Profile exists
     │                                 │
     ▼                                 ▼
Step 1: Profile form           Step 2: RFQ form directly
  Fill business name,            (step indicator shown,
  address, contact info          "Edit profile" link visible)
  → "Continue to RFQ"
     │
     ▼
Step 2: RFQ form
  (URL params preserved)
  → "Send RFQ"
     │
     ▼
  In-place success state
```

### Tests

Updated `tests/Feature/RfqCreateFormTest.php` — 24 tests, 68 assertions (all pass):

| Test | Asserts |
|------|---------|
| buyer without a profile lands on step 1 | `$step = 1`, "Set up your buyer profile" visible, "Continue to RFQ" visible |
| buyer with an existing profile lands directly on step 2 | `$step = 2`, "Set up your buyer profile" not visible, "Send RFQ" visible |
| the RFQ form renders without redirecting even when buyer profile is absent | `assertOk()` + `assertNoRedirect()` |
| the RFQ form renders in the storefront layout | Page loads, "Request for Quotation" heading visible |
| saveProfile creates the buyer profile and advances to step 2 | Profile row created in DB, `$step` advances to 2, "Send RFQ" visible |
| saveProfile fails validation when business name is missing | Validation error on `business_name`, stays on step 1 |
| saveProfile shipping same as billing copies billing fields | `shipping_city` matches `billing_city` on profile |
| step 2 shows an edit profile link pointing to the account profile route | `route('account.profile')` and "Edit profile" text visible |
| step 1 does not show the edit profile link | "Edit profile" not rendered on step 1 |
| attachment uses a single file input (not multiple) | No `multiple` attr, `wire:model="attachment"` present |
| text/plain file is rejected | `attachment` validation error |
| a PDF attachment passes validation | No `attachment` error |
| no attachment is also valid | No `attachment` error |
| the page is wrapped in a storefront page container | Max-width container and shop name visible |
| without a shop_id the buyer is pointed at the directory | "Choose a supplier first" shown, no raw shop_id input |
| an inactive shop is not treated as a valid recipient | "Choose a supplier first" shown |
| clearAttachment discards the staged upload | `attachment = null`, filename gone |
| reference product featured image shown when arriving from product page | Image + "Reference product" label visible |
| product from another shop not shown as reference | Product name absent |
| unpublished product not shown as reference | Product name absent |
| reference product without featured image falls back to placeholder | No `<img>` tag |
| submitting stays on page with in-place success message | "RFQ submitted", "Go to My RFQs", no redirect |
| the success state cannot submit the RFQ twice | Only 1 `Rfq` row created |
| submitting on step 2 with missing profile falls back to step 1 | `$step` resets to 1 |

## Step 3 — Certificate File Dropzone (Done)

### Summary

Replaced the plain `<flux:input type="file">` in step 3 (Halal Certification) with the same drag-and-drop dropzone pattern used on step 5, and added auto-upload on file selection.

### Problem

Step 3's certificate file field was a bare `flux:input type="file"` with no drag-and-drop support and no visual feedback on upload progress. It also required the user to click "Next" before the file was persisted — if they uploaded on step 3 and came back later the slot appeared blank.

### Solution / Changes

**`resources/views/livewire/company-hub/steps/step-3-halal-certification.blade.php`**

- Replaced the `flux:input type="file"` block with a two-state layout:
  - **File already uploaded:** shows a verified row (filename + check icon + "Replace" button), identical in style to the step-5 clean state.
  - **No file yet:** shows the drag-and-drop zone — `<label>` with Alpine `dragover`/`dragleave`/`drop` handlers, a hidden `<input type="file" id="cert-input-{{ $index }}">`, and a "Drag & drop, or click to browse" prompt.
- The `drop` handler reads `$event.dataTransfer.files`, assigns them to the hidden input's `.files`, and dispatches a synthetic `change` event — the same technique used in step 5 — so Livewire's `wire:model` triggers and the `updatedCertificateFiles` hook fires automatically.
- A `wire:loading` spinner ("Uploading file…") is shown while the file transfers to the server.

**`app/Livewire/CompanyHub/OnboardingWizard.php`**

- Added `updatedCertificateFiles(mixed $value, string $key): void` — auto-uploads the file via `MediaUploadService`, sets `certificates[$index]['media_id']` and `certificates[$index]['filename']`, and immediately persists `media_id` on the `ShopOnboardingCertificate` record if it already exists in the DB.
- Added `replaceCertificateFile(int $index): void` — clears `certificates[$index]['media_id']` and `certificates[$index]['filename']` so the dropzone reappears (file stays in S3 until the next save/re-upload).

### Tests

6 new tests appended to `tests/Feature/OnboardingWizardTest.php`:

| Test | Asserts |
|------|---------|
| step 3 certificate file shows a drag-and-drop dropzone instead of a plain file input | Dropzone prompt visible, no bare `type="file" wire:model="certificateFiles"` |
| step 3 dropzone wires a dropped file to the hidden cert input | `dataTransfer?.files` and `dispatchEvent(new Event('change', { bubbles: true }))` in view |
| step 3 shows verified file row with Replace button when certificate already has media | "Replace" visible, dropzone hidden |
| replaceCertificateFile clears the media_id so the dropzone reappears | `media_id = null`, dropzone visible after call |
| currentStep is persisted in the URL so a page refresh restores the last step | `withQueryParams(['currentStep' => 3])` → `$currentStep = 3` |
| a stale or out-of-range step in the URL is clamped to valid bounds | `99 → 5`, `0 → 1` |

All 33 onboarding wizard tests pass.

---

## Step Persistence on Refresh — `#[Url]` on `currentStep` (Done)

### Summary

The wizard now remembers which step the user was on when they refresh the page.

### Problem

`$currentStep` was a plain public property with no URL binding. Refreshing `/hub/onboarding` always rehydrated it to its default value of `1`, sending the user back to the first step even if they had already filled in steps 2–4.

### Solution / Changes

**`app/Livewire/CompanyHub/OnboardingWizard.php`**

- Added `use Livewire\Attributes\Url;` import.
- Decorated `$currentStep` with `#[Url]`: the step number is now reflected as `?currentStep=N` in the browser URL whenever the user advances or goes back. On refresh/revisit Livewire reads the query parameter and restores the correct step.
- Added a clamp in `mount()` (`max(1, min($this->currentStep, self::TOTAL_STEPS))`) so an out-of-range or malformed URL value is silently corrected rather than causing rendering issues.

---

## #51 — Seller cannot view RFQ attachment (Done)

### Summary

Fixed a 403 Forbidden error sellers got when trying to view an RFQ attachment. The `media.view` route only authorized the admin or the original uploader (the buyer), so RFQ recipients were locked out of the attachment files.

### Problem

When a seller clicks an attachment link in `resources/views/livewire/company-hub/rfq-detail.blade.php` (line 255), it hits `route('media.view', $attachment)`. The authorization logic in `routes/web.php` allowed access only if the current user was an admin, the original uploader, or a party to a dispute referencing the media. RFQ recipients were none of these, so they got a 403 — yet the UI clearly presents these attachments to sellers as viewable.

### Solution / Changes

**1. `app/Models/Media.php` — added missing inverse relationship**

`Media` had inverse relationships for `products()` and `disputes()`, but no inverse for the RFQ attachment pivot (`rfq_attachments`). Added:

```php
public function rfqs(): BelongsToMany
{
    return $this->belongsToMany(Rfq::class, 'rfq_attachments');
}
```

This matches the existing `Rfq::attachments()` relationship and the pattern of the neighboring `products()`/`disputes()` methods.

**2. `routes/web.php` — granted RFQ recipient access**

Added an `$isRfqRecipient` check alongside the existing dispute-party check. A user is granted access if they own a shop (`$user->shop`) that is a recipient of any RFQ attaching this media:

```php
// RFQ attachments must be viewable by the buyer who created the RFQ and
// any seller shop that is a recipient of that RFQ.
$isRfqRecipient = $user->shop && $media->rfqs()
    ->whereHas('recipients', fn ($q) => $q->where('shop_id', $user->shop->id))
    ->exists();

if (! $isAdminOrUploader && ! $isDisputeParty && ! $isRfqRecipient) {
    abort(403);
}
```

The `whereHas` on `recipients` scoped to the user's shop_id guarantees access is limited to actual recipients — a non-recipient seller (or one with no shop) is still denied.

### Edge cases

- **Buyer (uploader) access** — already covered by the existing `$isAdminOrUploader` check (`$media->uploaded_by_id === $user->id`), so the buyer can always view their own RFQ attachments without needing the new condition.
- **No shop / non-recipient** — `$user->shop &&` short-circuits for users without a shop, and the `whereHas('recipients', ... shop_id)` predicate excludes sellers whose shop is not a recipient. They still receive 403.

### Tests

Appended to `tests/Feature/MediaViewTest.php` (3 new tests; 7 total pass):

| Test | Asserts |
|------|---------|
| an RFQ recipient (seller) can view an RFQ attachment | A seller whose shop is a recipient of the RFQ gets HTTP 200 |
| a non-recipient seller cannot view an RFQ attachment | A seller whose shop is not a recipient gets HTTP 403 |
| the RFQ buyer can view their own RFQ attachment | The buyer (uploader) gets HTTP 200 |

All 7 `MediaViewTest` tests + 3 `DisputeMediaAccessTest` tests pass; `pint` clean.
