# Phase 5 QA Fixes & Enhancements

> **Phase Context:** This document contains bug fixes and enhancements identified during QA testing after Phase 5 completion.  
> **Codebase root:** `/Users/john/Develop/b2b-marketplace`  
> All paths below are relative to the project root.  
> `[NEW FILE]` = does not exist yet; `[MODIFY]` = exists, targeted changes listed.

---

## Section 1 — Critical Bug Fixes 🐛

---

### 1.1 Authentication — "Show Password" Toggle Bug

**Root cause:** `flux:input viewable` references `fluxInputViewable` and calls `window.open()` — both fail on first paint because Flux's Alpine.js plugins haven't hydrated yet (Livewire navigate re-renders break the event bridge).

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `resources/views/pages/auth/*.blade.php` (login + register) | Replace `<flux:input type="password" viewable />` with a self-contained Alpine.js toggle: `<div x-data="{ show: false }"><input :type="show ? 'text' : 'password'" ...><button @click="show = !show">...</button></div>`. Removes dependency on `fluxInputViewable`. |

---

### 1.2 Quotation Builder — Undefined Variable

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `resources/views/livewire/company-hub/quotation-builder.blade.php` | Fix `Undefined variable $quotation` on line 7: change bare `$quotation` variable references to `$this->quotation` (the `#[Computed]` property accessor). |

---

### 1.3 Product Variants — Unit Field Not Populating

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `app/Livewire/Forms/VariantForm.php` | In `setForEdit()`: explicitly assign `$this->unit = $variant['unit']`. Verify all fields are mapped from the `$variant` array. |
| 2 | [MODIFY] `resources/views/livewire/company-hub/variant-manager.blade.php` | In Edit Variant modal: move unit `<flux:select>` field to appear immediately beside/after the price field. |

---

### 1.4 Onboarding Step 3 — Certificate Filename Truncation

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `resources/views/livewire/company-hub/steps/step-3-halal-certification.blade.php` | Filename display: `{{ Str::limit(pathinfo($cert['filename'], PATHINFO_FILENAME), 20, '…') . '.' . pathinfo($cert['filename'], PATHINFO_EXTENSION) }}` |

---

### 1.5 Onboarding Step 5 — Document Scanning Stuck State

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `resources/views/livewire/company-hub/steps/step-5-documents.blade.php` | For documents with `scan_status === 'pending'`: add `wire:poll.5s="refreshDocumentStatuses"` scoped element + "Re-upload" button calling `wire:click="resetDocument('{{ $type }}')"`. |
| 2 | [MODIFY] `app/Livewire/CompanyHub/OnboardingWizard.php` | Add `resetDocument(string $documentType)`: unset `$this->uploadedDocuments[$documentType]`, detach media from onboarding documents pivot. |
| 3 | [MODIFY] `resources/views/livewire/company-hub/steps/step-5-documents.blade.php` | For `requires_revision` status: remove any `@disabled` / `@readonly` guards on document file inputs to allow re-upload. |

---

### 1.6 Orders — Unit/Price Not Seeding from Quotation

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `app/Actions/CreateOrderAction.php` | When seeding from quotation items, map `quotation_item->unit` → `order_item->unit` and `quotation_item->unit_price` → `order_item->unit_price`. |

---

### 1.7 Mobile Navigation — Portal Detection

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `resources/views/layouts/portal.blade.php` | Explicitly: `$portal = request()->routeIs('account.*') ? 'account' : ($user->hasRole('admin') ? 'admin' : 'hub')`. Currently all non-admin traffic defaults to `'hub'` which may cause mobile drawer to render incorrect items. |

---

### 1.8 Notifications — 404 on Click

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `app/Notifications/OnboardingApprovedNotification.php` | Change `url('/hub/shop')` → `route('hub.hub-dashboard')` in both `toMail()` action URL and `toArray()` `url` key. |
| 2 | [MODIFY] `app/Notifications/OnboardingRejectedNotification.php` | Change `url` → `route('hub.onboarding')`. |
| 3 | [MODIFY] `app/Notifications/OnboardingRevisionRequestedNotification.php` | Change `url` → `route('hub.onboarding')`. |

---

### 1.9 Product Documents — 403 Error for Public Access

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `routes/web.php` (media.view route, line 33) | Extend auth guard: also allow if the media is attached to a published product's documents: `|| $media->products()->where('status', 'published')->exists()`. This unblocks buyer access to seller-uploaded product docs. |

---

### 1.10 Storefront — Archived Variants Showing

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `app/Livewire/Storefront/ProductDetail.php` | In the variants query/load: add `->where('status', 'active')` filter. |
| 2 | [MODIFY] `resources/views/livewire/storefront/product-detail.blade.php` | Confirm variant loop uses the filtered collection, not raw `$product->variants` relation (which bypasses the scope). |

---

### 1.11 Admin — Value Chain Display

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `resources/views/livewire/admin/onboarding-review.blade.php` | "Value Chain Positions": change from link-list to `@foreach ($onboarding->value_chain_positions as $pos) <x-hs-badge>{{ $pos }}</x-hs-badge> @endforeach` (matching Capabilities display pattern). |

---

## Section 2 — Validations & Data Integrity

---

### 2.1 Onboarding Step 3 — Certificate Validations

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `app/Livewire/Forms/OnboardingForm.php` | In `rulesForStep(3)`: add `"certificates.*.certification_body" => ["required", "string"]` with custom message `"Certification body is required."` |
| 2 | [MODIFY] `app/Livewire/Forms/OnboardingForm.php` | Add rule: `"certificateFiles.*" => ["required_if:certificates.*.media_id,null"]` for new certificates without an uploaded file. |
| 3 | [MODIFY] `app/Livewire/CompanyHub/OnboardingWizard.php` | In `saveCertificates()` before DB write: if `$certData['certification_body']` is null, `$this->addError(...)` and `continue` instead of letting DB throw. |

---

### 2.2 Admin Approval — Certificate Validation

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `app/Actions/ApproveOnboardingAction.php` | Before shop creation: check `$onboarding->certificates->every(fn($c) => $c->media_id !== null)`. If any is null, throw `ValidationException` with message "All certificates must have an uploaded file before approval." |
| 2 | [MODIFY] `resources/views/livewire/admin/onboarding-review.blade.php` | Display `$errors->first('certificates')` inline alert on the approve section. |

---

### 2.3 Onboarding — General Form UX

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `resources/views/livewire/company-hub/onboarding-wizard.blade.php` | Replace raw `$errors->first('form.field_key')` displays with a `$fieldLabels` map rendering human-readable names (e.g., `"form.company_name"` → `"Company Name"`). |
| 2 | [MODIFY] `resources/views/livewire/company-hub/onboarding-wizard.blade.php` | Admin notes: display as a prominent yellow alert banner at the top of the page when `$onboardingStatus === 'requires_revision'`, not buried in-form. |
| 3 | [MODIFY] `app/Livewire/CompanyHub/OnboardingWizard.php` `mount()` | If `$onboarding->status === 'approved'` (shop active): `$this->redirectRoute('hub.hub-dashboard')` with flash error "Your profile is approved and cannot be resubmitted." |
| 4 | [MODIFY] `app/Livewire/CompanyHub/OnboardingWizard.php` `submit()` | Guard: only allow if `$onboarding->status` is in `[null, 'draft', 'requires_revision', 'rejected']`. |
| 5 | [MODIFY] `resources/views/livewire/company-hub/onboarding-wizard.blade.php` | For `rejected` status: show prominent "Re-apply" CTA so seller can proceed through the wizard and resubmit. |

---

### 2.4 Quotations — Allow Zero Unit Price (Free Samples)

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `app/Livewire/CompanyHub/QuotationBuilder.php` | Change `unit_price` validation rule from `min:0.01` (or `gt:0`) to `min:0`. |

---

### 2.5 RFQ — File Upload Validation

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `app/Livewire/Account/RfqCreateForm.php` | Validation: explicit MIME allow-list; reject `text/plain`. Error message: "Only PDF, Word, and image files are accepted." |
| 2 | [MODIFY] `resources/views/livewire/account/rfq-create-form.blade.php` | File input: single file only (remove `multiple`). New selection replaces previous. |
| 3 | [MODIFY] `app/Livewire/Account/RfqCreateForm.php` | Change `$attachments` array → `public $attachment = null` (single `TemporaryUploadedFile`). |

---

### 2.6 Order Builder — Number Formatting & Validation

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `resources/views/livewire/company-hub/orders/order-builder.blade.php` | Subtotal: `{{ number_format($item['subtotal'], 2) }}` everywhere. |
| 2 | [MODIFY] `resources/views/livewire/company-hub/orders/order-builder.blade.php` | Add read-only section: fetch and display commission rate and transaction rate from `PayoutSetting::first()`. |
| 3 | [MODIFY] `resources/views/livewire/company-hub/orders/order-builder.blade.php` | Quantity inputs: `type="number"`, `step="1"`, `min="1"` + Alpine `x-on:change="$el.value = Math.floor(Math.max(1, $el.value))"`. |

---

## Section 3 — Branding & UI Improvements

---

### 3.1 Branding Rebrand — "One Halal Global" → "1Halal Store"

**Affected files (all occurrences confirmed by grep):**

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `resources/views/layouts/storefront.blade.php:8` | `$title ?? '1Halal Store'` |
| 2 | [MODIFY] `resources/views/layouts/storefront.blade.php:32` | Replace `<img src="/images/logo.png" alt="One Halal Global" …>` with new logo asset; alt → `"1Halal Store"` |
| 3 | [MODIFY] `resources/views/layouts/storefront.blade.php:71` | `© … 1Halal Store.` |
| 4 | [MODIFY] `resources/views/layouts/portal.blade.php:8` | `$title ?? '1Halal Store'` |
| 5 | [MODIFY] `resources/views/layouts/portal.blade.php:56` | `© … 1Halal Store.` |
| 6 | [MODIFY] `resources/views/partials/portal-header.blade.php:30` | Update `<img>` src → `/images/brand-logo.png` + alt → `1Halal Store` |
| 7 | [MODIFY] `app/Livewire/Storefront/HeroSearch.php:18` | Update `#[Title('1Halal Store — Discover Halal-Certified Companies & Products')]` |
| 8 | [MODIFY] All 31 notification classes in `app/Notifications/*.php` | Update subject/body copy; add logo to mail templates via vendor publish |

---

### 3.2 Logo Asset Deployment

| # | File | Change |
|---|------|--------|
| 1 | Copy `tmp/new-logo/brand-logo-rectangle.png` → `public/images/brand-logo.png` | |
| 2 | Copy `tmp/new-logo/halal-stamp-circle.png` → `public/images/halal-stamp.png` | |
| 3 | [MODIFY] `resources/views/layouts/storefront.blade.php` | Inside Brand `<div>` (lines 31–36): add `<img src="/images/halal-stamp.png" alt="Halal Certified" class="h-12 w-auto mt-2">` beside the main logo |

---

### 3.3 Email Template Logo

| # | File | Change |
|---|------|--------|
| 1 | Run `vendor/bin/sail artisan vendor:publish --tag=laravel-mail` | Then modify `resources/views/vendor/mail/html/header.blade.php` to render `<img src="{{ asset('images/brand-logo.png') }}" alt="1Halal Store">` |

---

### 3.4 Buyer/Seller Dashboard Separation

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `resources/views/partials/portal-header.blade.php` | Beside the logo `<a>` tag, render a small pill badge: if `request()->routeIs('hub.*')` → `"Seller Hub"` (green pill); if `request()->routeIs('account.*')` → `"Buyer Hub"` (blue pill). |
| 2 | [MODIFY] `resources/views/partials/portal-nav.blade.php` | For `$isOnboarded` block: wrap Seller section in `<div class="bg-[var(--hs-green-50)] rounded-xl p-3 mb-3">` and Buyer section in a `<div class="bg-[var(--hs-neutral-50)] rounded-xl p-3">` to visually distinguish. |

---

### 3.5 Notifications — Remove Truncation + Full Page

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `resources/views/livewire/shared/notification-bell.blade.php` | Remove all `truncate` / `line-clamp` CSS classes from notification list item text nodes. |
| 2 | [MODIFY] `app/Livewire/Shared/NotificationBell.php` | Add computed "all notifications" link rendering at dropdown bottom → `route('account.notifications.index')` |
| 3 | [NEW FILE] `app/Livewire/Shared/NotificationList.php` | Full-page paginated notification list; `Auth::user()->notifications()->latest()->paginate(25)`. Includes mark-as-read action. |
| 4 | [NEW FILE] `resources/views/livewire/shared/notification-list.blade.php` | Full-page view using `layouts.portal`. |
| 5 | [MODIFY] `routes/web.php` | Inside `account.` group: `Route::livewire('notifications', 'shared.notification-list')->name('notifications.index');` |

---

### 3.6 Account Settings — Disable Account Deletion

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `resources/views/pages/settings/⚡profile.blade.php` | Remove/comment lines 124–126: `@if ($this->showDeleteUser) <livewire:pages::settings.delete-user-form /> @endif` |

---

## Section 4 — Seller Portal Enhancements

---

### 4.1 Onboarding Step 1 — Hide Country Field

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `resources/views/livewire/company-hub/steps/step-1-company-profile.blade.php` | Remove / comment out country `<flux:select>` input. |
| 2 | [MODIFY] `app/Livewire/Forms/OnboardingForm.php` | In `rulesForStep(1)`: remove `country` validation rule. In `dataForStep(1)`: hard-code `'country' => 'Philippines'`. |

---

### 4.2 Onboarding Step 2 — Product Category Checkbox Group

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `resources/views/livewire/company-hub/steps/step-2-operations.blade.php` | Replace text input with `@foreach ($this->categories as $cat) <label><input type="checkbox" wire:model="form.product_categories" value="{{ $cat->id }}"> {{ $cat->name }}</label> @endforeach` |
| 2 | [MODIFY] `app/Livewire/Forms/OnboardingForm.php` | Change `product_categories` from `string` to `array`. Validation: `'array'`. `dataForStep(2)`: comma-join IDs for storage in existing column. |
| 3 | [MODIFY] `app/Actions/ApproveOnboardingAction.php` | After shop creation: parse `onboarding->product_categories` (CSV or JSON), resolve `Category` IDs, call `$shop->categories()->sync($categoryIds)` via `shop_categories` pivot. |

---

### 4.3 Seller Profile Management (Edit Shop Info)

| # | File | Change |
|---|------|--------|
| 1 | [NEW FILE] `app/Livewire/CompanyHub/ShopProfileForm.php` | Livewire component loading `Auth::user()->shop` and its onboarding. Exposes all editable company info fields from steps 1–4. Excludes document deletion. `save()` calls `$shop->update([...])`. |
| 2 | [NEW FILE] `resources/views/livewire/company-hub/shop-profile-form.blade.php` | Form view. Reuse step-partial structure. `#[Layout('layouts.portal', ['portal' => 'hub'])]`. |
| 3 | [MODIFY] `routes/web.php` | Inside `hub.` group: `Route::livewire('shop/profile', 'company-hub.shop-profile-form')->name('shop.profile');` |
| 4 | [MODIFY] `resources/views/partials/portal-nav.blade.php` | Add "Shop Profile" nav link in Seller section for `$isOnboarded` users. |

---

### 4.4 Seller Dashboard — "View My Shop" Link

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `resources/views/livewire/company-hub/hub/dashboard.blade.php` | Add `<a href="{{ route('storefront.company', $shop) }}" wire:navigate>View My Shop →</a>` button/link. |
| 2 | [MODIFY] `app/Livewire/CompanyHub/Hub/Dashboard.php` | Expose `#[Computed] shop(): Shop` returning `Auth::user()->shop`. |

---

### 4.5 Product Management Improvements

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `resources/views/livewire/company-hub/product-list.blade.php` | Per product row: add `<a href="{{ route('storefront.product', $product) }}" target="_blank">View Live ↗</a>` |
| 2 | [MODIFY] `resources/views/livewire/storefront/product-detail.blade.php` | Add `@auth @if(auth()->user()->shop?->id === $product->shop_id) <a href="{{ route('hub.products.edit', $product) }}">Edit ✏️</a> @endif @endauth` |
| 3 | [MODIFY] `resources/views/livewire/company-hub/product-form.blade.php` | Remove the `<livewire:company-hub.profit-calculator />` embed. |
| 4 | [MODIFY] `resources/views/livewire/company-hub/product-list.blade.php` | Replace icon-only buttons with icon + text: `✏️ Edit`, `📦 Variants`, `🖼️ Gallery`. |
| 5 | [MODIFY] `resources/views/livewire/company-hub/product-form.blade.php` | On edit mode: add `<button wire:click="publish">Publish</button>` / `<button wire:click="archive">Archive</button>` action buttons. |
| 6 | [MODIFY] `app/Livewire/CompanyHub/ProductForm.php` | Add `publish()` and `archive()` methods updating `$this->product->status`; dispatch `IndexProductJob` / de-index job after commit. |

---

### 4.6 Product Variants

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `resources/views/livewire/company-hub/variant-manager.blade.php` | Remove "Delete Variant" button entirely from variant list and modal. |
| 2 | [MODIFY] `app/Livewire/CompanyHub/VariantManager.php` | Remove or make private: `confirmDelete`, `deleteVariant` public methods. |

---

### 4.7 Product Gallery

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `resources/views/livewire/company-hub/gallery-manager.blade.php` | Replace delete button with: `<div class="relative group"> <img ...> <button class="absolute top-1 right-1 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition" wire:click="confirmRemoveImage({{ $item->id }})">✕</button> </div>` |

---

### 4.8 RFQ & Quotations — Seller Portal

**4.8.1 Auto-close child RFQs when parent closes:**

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `app/Livewire/Account/RfqDetail.php` | In `closeRfq()` (buyer closes parent RFQ): add `$rfq->recipients()->whereNotIn('status', ['no_bid', 'awarded'])->update(['status' => 'closed'])` after updating the parent. |

**4.8.2 Rename "Decline" → "Close" in seller RFQ detail:**

| # | File | Change |
|---|------|--------|
| 2 | [MODIFY] `resources/views/livewire/company-hub/rfq-detail.blade.php` | Rename all "Decline" button labels and modal title to "Close". |
| 3 | [MODIFY] `app/Livewire/CompanyHub/RfqDetail.php` | Rename `$declineReason` → `$closeReason`, `decline()` → `closeRfq()`. In `closeRfq()`: also `$this->rfqRecipient->quotations()->where('status', 'submitted')->update(['status' => 'withdrawn'])` then dispatch `QuotationWithdrawnNotification`. |

**4.8.3 View full quotation details + withdraw from RFQ detail:**

| # | File | Change |
|---|------|--------|
| 4 | [MODIFY] `resources/views/livewire/company-hub/rfq-detail.blade.php` | Add expandable line items section per quotation (description, qty, unit, unit_price, subtotal). Add "Withdraw" button → `wire:click="withdrawQuotation({{ $quotation->id }})"`. |
| 5 | [MODIFY] `app/Livewire/CompanyHub/RfqDetail.php` | Add `withdrawQuotation(int $quotationId)`: Gate ownership check; update to `status = 'withdrawn'`; dispatch `QuotationWithdrawnNotification`. |

**4.8.4 Remove "–free form item–" option:**

| # | File | Change |
|---|------|--------|
| 6 | [MODIFY] `resources/views/livewire/company-hub/quotation-builder.blade.php` | Remove the `null` / "–free form item–" option from variant `<select>` dropdown. |

**4.8.5 Add Product ID column to quotation items:**

| # | File | Change |
|---|------|--------|
| 7 | [NEW FILE] `database/migrations/xxxx_add_product_id_to_quotation_items.php` | `$table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();` |
| 8 | [MODIFY] `app/Models/QuotationItem.php` | Add `product_id` to `$fillable`. Add `belongsTo(Product::class)`. |
| 9 | [MODIFY] `app/Livewire/CompanyHub/QuotationBuilder.php` | When `variant_id` is set, auto-resolve `product_id = ProductVariant::find($variant_id)->product_id` and store in item data. |

---

### 4.9 Orders — Seller Portal

**4.9.1 Fee auto-recalculation:**

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `resources/views/livewire/company-hub/orders/order-builder.blade.php` | Alpine `x-data` block: add `$watch('fees', () => recalcTotal())` and `$watch('items', () => recalcTotal())` to recompute displayed total in real-time. |

**4.9.2 Order form – billing, unit dropdown, totals, product picker:**

| # | File | Change |
|---|------|--------|
| 2 | [MODIFY] `app/Livewire/CompanyHub/Orders/OrderBuilder.php` | Add private `fillBillingFromBuyerProfile()`: load `$order->buyer` → `BuyerProfile` → populate `$this->customerContactName`, `$this->customerContactEmail`, `$this->shippingAddress1`, etc. Call in `mount()` after `fillFromOrder()`. |
| 3 | [MODIFY] `resources/views/livewire/company-hub/orders/order-builder.blade.php` | Replace free-text unit `<input>` with `<x-hs-unit-select wire:model="items.{{ $i }}.unit" />` (component exists at `resources/views/components/hs-unit-select.blade.php`). |
| 4 | [MODIFY] `resources/views/livewire/company-hub/orders/order-builder.blade.php` | Add Alpine `x-effect` recomputing `subtotal` per item row and grand total on qty/price change. |
| 5 | [MODIFY] `resources/views/livewire/company-hub/orders/order-builder.blade.php` | Add product/variant search: `<input wire:model.live="productSearch">` with results dropdown; selecting a variant populates a new item row. |
| 6 | [MODIFY] `app/Livewire/CompanyHub/Orders/OrderBuilder.php` | Add `public string $productSearch = ''` + `#[Computed] searchResults()` → `Product::published()->where('name', 'like', "%{$this->productSearch}%")->with('variants')->limit(10)->get()`. |

**4.9.3 Add Product ID to order items:**

| # | File | Change |
|---|------|--------|
| 7 | [NEW FILE] `database/migrations/xxxx_add_product_id_to_order_items.php` | `$table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();` |
| 8 | [MODIFY] `app/Models/OrderItem.php` | Add `product_id` to `$fillable`. Add `belongsTo(Product::class)`. |

**4.9.4 Dispute detail in order page:**

| # | File | Change |
|---|------|--------|
| 9 | [MODIFY] `app/Livewire/CompanyHub/Orders/OrderDetail.php` | Eager-load `$order->load(['disputes'])`. |
| 10 | [MODIFY] `resources/views/livewire/company-hub/orders/order-detail.blade.php` | Add "Disputes" section: iterate `$order->disputes` showing status, reason, resolution, created_at. |

**4.9.5 Partial-refund amount on dispute resolve:**

| # | File | Change |
|---|------|--------|
| 11 | [MODIFY] `resources/views/livewire/company-hub/orders/order-detail.blade.php` | After "Resolve" click, conditionally show `<input type="number" wire:model="refundAmount" min="0">` when `$selectedResolution === 'partial_refund'`. |
| 12 | [MODIFY] Relevant dispute resolution component | Add `public float $refundAmount = 0.0` property; pass to resolution action. |

**4.9.6 Fulfillment items table:**

| # | File | Change |
|---|------|--------|
| 13 | [NEW FILE] `database/migrations/xxxx_create_fulfilment_items_table.php` | Columns: `id`, `fulfilment_id` FK, `order_item_id` FK, `quantity NUMERIC(12,2)`, `timestamps` |
| 14 | [NEW FILE] `app/Models/FulfilmentItem.php` | `belongsTo(Fulfilment::class)`, `belongsTo(OrderItem::class)`. |
| 15 | [MODIFY] `app/Models/Fulfilment.php` | Add `hasMany(FulfilmentItem::class)`. |
| 16 | [MODIFY] Seller fulfillment creation Livewire component (in `app/Livewire/CompanyHub/Fulfilment/`) | On fulfilment creation, create `FulfilmentItem` rows for each order item at full quantity. |

---

## Section 5 — Buyer Portal Enhancements

---

### 5.1 Buyer Dashboard Home Page

| # | File | Change |
|---|------|--------|
| 1 | [NEW FILE] `app/Livewire/Account/Dashboard.php` | Load order status counts, recent orders per status. `#[Layout('layouts.portal', ['portal' => 'account'])]`. |
| 2 | [NEW FILE] `resources/views/livewire/account/dashboard.blade.php` | Shopee-style cards: "To Pay", "To Ship", "To Receive" with counts + order lists. "Start Selling" CTA shown when `!$user->shop`. |
| 3 | [MODIFY] `routes/web.php` | Inside `account.` group: `Route::livewire('dashboard', 'account.dashboard')->name('dashboard');` |
| 4 | [MODIFY] `resources/views/partials/portal-nav.blade.php` | Add "Dashboard" nav link in Buyer section. |

---

### 5.2 RFQ & Quotations — Buyer Portal

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `resources/views/livewire/account/rfq-detail.blade.php` | Wrap "Award" button in `<x-hs-confirm>` component with message "Award this quotation?" |
| 2 | [MODIFY] `app/Livewire/Account/RfqDetail.php` | Add `confirmAward(int $quotationId)` → open confirm modal; `awardQuotation(int $quotationId)` → perform action. |
| 3 | [MODIFY] `resources/views/livewire/account/rfq-detail.blade.php` | Expandable quotation details section: line items (description, qty, unit, unit_price, subtotal), notes, validity. |

---

### 5.3 Orders — Buyer Portal

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `app/Livewire/Account/Orders/OrderList.php` | Eager-load items; expose `tabCounts` computed property from single `groupBy` query. |
| 2 | [MODIFY] `resources/views/livewire/account/orders/order-list.blade.php` | Show order items inline per order row. |
| 3 | [MODIFY] `resources/views/livewire/account/orders/order-list.blade.php` | Add "Cancelled" status tab. |
| 4 | [MODIFY] `resources/views/livewire/account/orders/order-list.blade.php` | Add count badges to all tabs; hide badge when count = 0. Disputes badge only shown when open disputes exist. |
| 5 | [MODIFY] `resources/views/livewire/account/orders/order-list.blade.php` | "To Receive" tab: per item "Track Order" (shipped) and "Confirm Receive" (delivered) action buttons. |
| 6 | [MODIFY] `app/Livewire/Account/Orders/OrderList.php` | Add `confirmReceived(int $orderId)`: update order to `completed`; dispatch `OrderCompletedNotification`. |
| 7 | [MODIFY] `resources/views/livewire/account/orders/order-detail.blade.php` | Add `← Back to Orders` navigation link. |
| 8 | [MODIFY] `resources/views/livewire/account/orders/order-detail.blade.php` | Add advisory text: "We recommend chatting with the seller before initiating a dispute." |
| 9 | [MODIFY] `resources/views/livewire/account/orders/order-detail.blade.php` | Dispute form: add `<input type="file" wire:model="disputePhotos" multiple>` for evidence photos. |
| 10 | [MODIFY] Buyer dispute form component (`app/Livewire/Account/Disputes/`) | Process uploaded photos via `MediaUploadService`, attach to dispute. |
| 11 | [NEW FILE] `database/migrations/xxxx_create_dispute_items_table.php` | Columns: `id`, `dispute_id` FK, `order_item_id` FK, `quantity NUMERIC(12,2)`, `reason TEXT nullable`, `timestamps` |

---

## Section 6 — Admin Portal Enhancements

---

### 6.1 Onboarding Queue — "All" Tab + Count Badges

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `app/Livewire/Admin/OnboardingQueue.php` | Add `'all'` filter: when selected, use `->whereIn('status', ['submitted', 'pending', 'requires_revision'])`. Add `#[Computed] tabCounts()` returning per-status counts from a single `groupBy` query. |
| 2 | [MODIFY] `resources/views/livewire/admin/onboarding-queue.blade.php` | Add "All" tab button. Add count badge `<span>{{ $this->tabCounts[$tab] }}</span>` to each tab, hidden (`x-show`) when count = 0. |

---

## Section 7 — Storefront Enhancements

---

### 7.1 Navigation — "Buyer Hub" / "Seller Hub" Dropdown Links

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `resources/views/partials/portal-header.blade.php` | Auth dropdown (lines 98–113): replace single "Company Hub" link with: always-show `"Buyer Hub" → route('account.profile')`; show only when `$isSeller`: `"Seller Hub" → route('hub.hub-dashboard')`. Remove old "Company Hub" entry. |

---

### 7.2 RFQ — Move to Storefront Layout

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `app/Livewire/Account/RfqCreateForm.php` | Change `#[Layout]` attribute → `#[Layout('layouts.storefront')]`. |
| 2 | [MODIFY] `resources/views/livewire/account/rfq-create-form.blade.php` | After successful submission, display: "RFQ submitted! Find it at [My RFQs](route('account.rfqs.index'))." |
| 3 | [MODIFY] `resources/views/livewire/storefront/product-detail.blade.php` | "Request a Quote" CTA: always route to `route('account.rfqs.create', ['shop_id' => …, 'title' => …])` without checking buyer profile existence first. |
| 4 | [MODIFY] `app/Livewire/Account/RfqCreateForm.php` `mount()` | Remove any redirect/early-return that requires buyer profile to exist before rendering. |

---

### 7.3 Country/Regional Portals Section

| # | File | Change |
|---|------|--------|
| 1 | [MODIFY] `resources/views/livewire/storefront/hero-search.blade.php` | Find section with heading `"Explore Halal business portals around the world"`. Update to: `"More Halal business portals are coming soon."` |
| 2 | [MODIFY] `resources/views/livewire/storefront/hero-search.blade.php` | Update description to: "The 1HALALSTORE network is expanding. New country portals are coming soon, connecting local businesses with buyers and partners across established and emerging Halal markets." |
| 3 | [MODIFY] `resources/views/livewire/storefront/hero-search.blade.php` | Replace "Visit Portal" links with `<span class="text-[var(--hs-neutral-400)] text-sm">Coming Soon</span>`. |
| 4 | [MODIFY] `resources/views/livewire/storefront/hero-search.blade.php` | Add/update four country cards for: **India** ("Connect with Indian Halal producers, manufacturers, exporters and buyers"), **Vietnam** ("Discover Halal products and business opportunities from Vietnam"), **Thailand** ("Explore Thailand's growing Halal food, manufacturing and trade ecosystem"), **Nigeria** ("Find businesses and products from one of Africa's largest Halal markets"). Each card: flag image/emoji, country name, tagline, cover image. |
| 5 | Add country flag images to `public/images/countries/` (india, vietnam, thailand, nigeria). |

---

## Open Questions (All Resolved)

> **Q1 — Post-approval notification URL:** ✅ Resolved — Use `route('hub.hub-dashboard')`

> **Q2 — Product document 403:** ✅ Resolved — Allow all even unauthenticated users

> **Q3 — "Show Password" scope:** ✅ Resolved — Only login/register pages

> **Q4 — Category column format:** ✅ Confirmed — `product_categories` string/CSV column exists, `shop_categories` pivot exists

> **Q5 — ProductVariant status values:** ✅ Resolved — Stick to current status values

---

## Implementation Notes

- All database migrations should use timestamp prefix format: `YYYY_MM_DD_HHMMSS_*`
- Test all onboarding validation changes with empty/null values
- Verify media policy changes don't expose private documents
- Run `vendor/bin/sail bin pint --dirty --format agent` after PHP changes
- Test mobile navigation on both buyer and seller portals
- Verify all notification URLs work correctly after changes
- Test order builder calculations with various fee scenarios
- Ensure all new migrations are reversible with proper `down()` methods
