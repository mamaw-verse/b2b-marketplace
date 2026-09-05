# Codebase Audit Report & Refactoring Roadmap

This document outlines the findings from a comprehensive audit of the B2B Marketplace codebase, focusing on Backend Architecture, Livewire & Flux UI Standardization, and Security & Data Protection.

## 1. Backend Architecture Audit

### 1.1. Technical Debt: `Schema::hasTable` in Production Logic (High Priority)
**Finding:** Widespread use of `Schema::hasTable('payouts')` and `Schema::hasTable('payout_items')` in controllers and Livewire components (e.g., `SellerHubController`, `HubEarningsController`, `EarningsOverview`, `Reports`). 
**Impact:** This is a severe performance anti-pattern. It forces a query to the database's information schema on *every single request* to these endpoints, bypassing caching and adding significant overhead.
**Recommendation:** Replace these checks with a proper feature flag (e.g., `config('features.payouts_enabled')`) or ensure migrations are finalized so conditional schema checks are no longer needed.

### 1.2. Fat Controllers & Procedural Logic
**Finding:** Several controllers contain excessive business logic and inline validation that should be extracted:
*   `SellerHubController` (324 lines): Contains complex raw DB aggregations, joins, and subqueries for the dashboard, reports, and leaderboards.
*   `HubProductController` (259 lines): Uses large inline `$request->validate()` arrays (e.g., `storeVariant` has 14+ inline rules) instead of dedicated `FormRequest` classes.
**Recommendation:** 
*   Extract the reporting/analytics queries in `SellerHubController` into dedicated Service classes (e.g., `ShopAnalyticsService`).
*   Standardize validation by moving all inline `$request->validate()` calls to `FormRequest` classes (e.g., `StoreProductVariantRequest`).

### 1.3. Database Performance & Raw Queries
**Finding:** Heavy reliance on `DB::raw()` in analytics and reporting (`SellerHubController`, `TransactionController`, `RecalculateRatingsJob`). While these are mostly static aggregations (`SUM`, `COUNT`), they couple the controllers tightly to the database schema.
**Finding:** Missing `createMany` or bulk inserts. For instance, `HubOrderController::update` deletes and iteratively recreates order items one by one in a loop.
**Recommendation:** Wrap complex reporting queries in dedicated Repository or Action classes. Optimize iterative inserts where feasible.

---

## 2. Livewire & Flux UI Standardisation Audit

### 2.1. Fat Livewire Components
**Finding:** Several Livewire components have grown into monolithic classes handling too many responsibilities (state, DB mutations, file uploads, complex UI logic):
*   `OnboardingWizard` (471 lines)
*   `QuotationBuilder` (361 lines)
*   `PaymentPage` (355 lines)
*   `VariantManager` (333 lines)
**Recommendation:** Break these down using Livewire Form Objects (`Livewire\Form`) to encapsulate validation and state, and extract the DB mutations into Action classes (e.g., `SubmitOnboardingAction`).

### 2.2. Performance Anti-patterns: Public Model Properties
**Finding:** Components like `PaymentPage`, `OrderBuilder`, `QuotationBuilder`, and `VariantManager` expose full Eloquent models as public properties (e.g., `public Order $order`).
**Impact:** Livewire serializes public properties and sends them to the frontend. Exposing full models (especially with loaded relationships like `fees`, `items`, `shop`) bloats the network payload and risks leaking sensitive data to browser DevTools.
**Recommendation:** 
*   Use `#[Computed]` properties for read-only models.
*   Extract only the specific scalar properties needed for mutation into the component or a Livewire Form Object.
*   If models must be public, use Livewire's `#[Locked]` attribute on sensitive ID properties to prevent client-side tampering.

### 2.3. UX Consistency
**Finding:** Good usage of `wire:loading.attr="disabled"` across forms (e.g., `PaymentPage`, `DisputeDetail`, `OrderBuilder`).
**Recommendation:** Ensure all destructive actions (e.g., `removeVariant`, `removeCertificate`) use Flux UI confirmation modals consistently. Most do, but standardizing the event dispatching will improve maintainability.

---

## 3. Security & Data Protection Audit

### 3.1. Authorization Inconsistencies
**Finding:** The application uses a mix of Laravel Policies (`Gate::authorize`) and manual closure-based aborts. 
*   `PaymentPage::mount()` uses `abort_unless($order->buyer_user_id === Auth::id(), 403)`.
*   `OrderBuilder::mount()` uses `abort_unless($order->shop_id === $shop->id, 403)`.
**Recommendation:** Move all manual `abort_unless` checks into their respective Policies (e.g., `OrderPolicy@pay`, `OrderPolicy@editDraft`) to centralize authorization rules.

### 3.2. File Upload Validation Bypass
**Finding:** `MediaUploadService` enforces strict MIME and extension validation. However, `HubProductController::storeDocument` validates the upload using only `['required', 'file', 'max:20480']` before passing it to the service.
**Impact:** While the Service acts as a safety net and throws a `ValidationException` if the MIME is invalid, this is better handled at the Request layer so standard validation error bags are returned gracefully to the frontend.
**Recommendation:** Apply the `mimes:` rule directly in the controller or FormRequest for all file uploads (as is correctly done in `storeGallery`).

### 3.3. IDOR Protections
**Finding:** Generally well-protected. Livewire components mostly mutate models passed via mount (which are authorized). However, array-index deletions (like `removeCertificate` in `OnboardingWizard`) rely on backend session state, which is safe from IDOR but can suffer from state desync if multiple tabs are open.

---

## 4. Proposed Changes & Refactoring Roadmap

To address these findings systematically without disrupting active development, I propose a three-phase refactoring roadmap:

### Phase 1: Security & Performance Quick Wins
1.  **Remove `Schema::hasTable`:** Replace all dynamic schema checks with a static configuration flag (`config('marketplace.features.payouts')`) to instantly improve database and application performance.
2.  **Fix Authorization Leaks:** Refactor manual `abort_unless` calls in `PaymentPage` and `OrderBuilder` into `OrderPolicy`.
3.  **Patch File Upload Validation:** Add explicit `mimes` and `mimetypes` rules to `HubProductController::storeDocument` and other similar endpoints.

### Phase 2: Livewire State & Payload Optimization
1.  **Refactor Public Models:** Audit all Livewire components exposing `public Order $order`, `public Product $product`, etc. Convert them to `#[Computed]` properties where possible, or use `#[Locked]` for IDs.
2.  **Extract Form Objects:** Introduce Livewire `Form` classes for `OnboardingWizard`, `QuotationBuilder`, and `VariantManager` to slim down the component classes.

### Phase 3: Backend Architecture & Controller Slimming
1.  **Extract Service Classes:** Create `ShopAnalyticsService` to house the complex raw SQL queries currently residing in `SellerHubController`.
2.  **Adopt FormRequests:** Migrate all inline `$request->validate()` calls in `HubProductController` and `OnboardingWizard` to dedicated FormRequest classes.
3.  **Optimize DB Writes:** Refactor loop-based `create()` calls in `HubOrderController` and `OrderBuilder` to use `createMany()` or bulk `insert()` for better database write performance.
