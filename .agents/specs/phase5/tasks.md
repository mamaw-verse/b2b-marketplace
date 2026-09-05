# Codebase Audit Refactoring Tasks

## Phase 1: Security & Performance Quick Wins ✅ COMPLETE
- [x] **Task 1.1:** Remove `Schema::hasTable` checks from `SellerHubController`, `HubEarningsController`, `EarningsOverview`, and `Reports`, replacing them with a feature flag or finalized migration assumption.
- [x] **Task 1.2:** Refactor manual `abort_unless` authorization checks in `PaymentPage` and `OrderBuilder` into centralized Laravel Policy methods (e.g., `OrderPolicy`).
- [x] **Task 1.3:** Add explicit `mimes` and `mimetypes` validation rules to `HubProductController::storeDocument` (and any other missing endpoints) to handle file upload errors at the Request layer.

## Phase 2: Livewire State & Payload Optimization ✅ COMPLETE
- [x] **Task 2.1:** Audit and refactor `PaymentPage` to replace the `public Order $order` property with a `#[Computed]` property and scalar variables for form state.
- [x] **Task 2.2:** Audit and refactor `OrderBuilder` to prevent exposing the full `Order` model in the public payload.
- [x] **Task 2.3:** Audit and refactor `QuotationBuilder` to prevent exposing the full `Quotation` model in the public payload.
- [x] **Task 2.4:** Audit and refactor `VariantManager` to prevent exposing the full `Product` model in the public payload.
- [x] **Task 2.5:** Extract Livewire Form Objects (`Livewire\Form`) for `OnboardingWizard` to encapsulate its massive form state and validation logic.
- [x] **Task 2.6:** Extract Livewire Form Objects (`Livewire\Form`) for `VariantManager` to clean up component state.

## Phase 3: Backend Architecture & Controller Slimming ✅ COMPLETE
- [x] **Task 3.1:** Create a `ShopAnalyticsService` and move the complex raw DB aggregations (dashboard, reports, leaderboards) out of `SellerHubController`.
- [x] **Task 3.2:** Create a `StoreProductVariantRequest` (and `UpdateProductVariantRequest`) and migrate the inline validation from `HubProductController`.
- [x] **Task 3.3:** Migrate any remaining large inline `$request->validate()` calls in `HubProductController` to dedicated FormRequest classes.
- [x] **Task 3.4:** Optimize the sequential item creation loop in `HubOrderController::update` and `OrderBuilder::save` to use `createMany()` or bulk `insert()` where feasible.
