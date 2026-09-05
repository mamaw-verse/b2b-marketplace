# tasks.md — Phase 6 Implementation Checklist

Companion to `requirements.md` and `design.md`. Grouped by milestone (see `requirements.md` §10).

---

## M1 — Feature flag registry
- [x] Add `features` array to `config/marketplace.php` with all flags in `design.md` §2.1 (chat intentionally excluded — stays enabled)
- [x] Flip `payouts_enabled` default from `true` (Phase 5) to `false`
- [x] Build `EnsureFeatureEnabled` middleware (parameterized), apply to order/dispute/payout/review/quotation/seller-RFQ route groups — chat routes explicitly left ungated
- [x] Add early-return flag guards to `OrderPolicy`, `DisputePolicy`, `PayoutPolicy`, `QuotationPolicy`, `ReviewPolicy`, seller-facing `RfqPolicy` methods (not `ConversationPolicy`/chat policies — unaffected)
- [x] Wrap nav entries (`company-hub-nav`, `account-nav`, `admin-nav`) in `@if(config(...))` for each disabled feature; confirm chat inbox nav entries remain visible/unconditional
- [x] Update `RunPayoutReleaseJob`, `SellerNoBidTimeoutJob`, `QuotationExpiryJob` to no-op when their flag is off
- [x] Regression test: confirm all 8 disabled routes 404, chat routes remain reachable, all existing Phase 2–4 tables still exist and are queryable by admin

## M2 — Onboarding changes
- [x] Migration: `shop_onboardings` + `company_description`, `+ products_description`
- [x] Migration: `shops` + `company_description`, `+ products_description`
- [x] Update `OnboardingForm` (Phase 5 Form object) — remove Step 3 (certification) fields/validation, add two new textareas, reduce Step 5 to `business_permit` + `other_supporting_document`
- [x] Update wizard Blade views — renumber to 4 visible steps, new fields
- [x] Update `OnboardingWizardTest` feature tests for new field set and removed step
- [x] Post-approval confirmation screen: add "Apply for Certification" + "Choose a Subscription Plan" CTAs

## M3 — Certification decoupling
- [ ] Migration: `certification_applications` table
- [ ] Merchant-facing form (2 fields: expiry date, file) + submission handler
- [ ] Admin certification queue (`/admin/certifications`) — list, approve, reject with notes
- [ ] On approve: set `shops.is_halal_certified = true`, `shops.certified_expiry_date`
- [ ] Reuse Phase 1 expiry-alert job pattern for certification expiry (new job or extend existing)
- [ ] Storefront badge rendering sourced from `shops.is_halal_certified`
- [ ] Feature tests: submit, approve, reject, reapply-after-reject

## M4 — Subscriptions
- [ ] Migrations: `subscription_plans`, `subscriptions`
- [ ] Seed initial `subscription_plans` rows (PH + default/international — confirm pricing per `requirements.md` §11 item 6 before seeding real prices)
- [ ] Subscription selection UI in Company Hub
- [ ] PH free-trial logic: detect PH shop, create `trialing` subscription with `trial_ends_at = +180d`, skip payment
- [ ] Non-PH / post-trial payment path: extend `payments` table with payable-type discriminator or `subscription_id` FK (confirm actual Phase 3 schema first), wire DLT charge call
- [ ] `SubscriptionStatusChanged` event + listener to re-trigger search indexing (design.md §7)
- [ ] Scheduled job: renewal-approaching notification (reuse Phase 1 expiry-alert pattern)
- [ ] `ProductPolicy::publish()` (or equivalent) updated to require `approved` shop + `active`/`trialing` subscription
- [ ] Admin subscription tracking screen (`/admin/subscriptions`) — filter by status/plan/country
- [ ] Feature tests: PH trial creation, non-PH payment success/failure, publish blocked without subscription, publish allowed while drafting

## M5 — RFQ ownership transfer
- [ ] Migration: `rfq_recipients` + `admin_status`, `+ admin_notes`, `+ managed_by`
- [ ] Hide Company Hub "RFQs Received" (behind `seller_rfq_queue_enabled`, default off)
- [ ] Build admin RFQ management screen (`/admin/rfqs`) — list all RFQs, detail view with buyer snapshot + attachments, status update, internal notes
- [ ] Update `RfqAutoCloseJob` to key off `admin_status` + buyer activity when `admin_rfq_management_enabled` is on
- [ ] Retire (no-op, don't delete) `SellerNoBidTimeoutJob`
- [ ] Remove the RFQ-submission call to `ConversationService::openThreadForRfq()` (chat auto-threading) from the RFQ creation path — chat itself stays fully enabled
- [ ] Add a "Message this company" entry point on the storefront company page as chat's new (RFQ-independent) starting point
- [ ] Confirm quotations flag independently gates its own UI (no residual seller quotation entry points reachable from the new admin RFQ screen); confirm chat inbox is unaffected for both buyer and seller
- [ ] Feature tests: RFQ visible to admin not seller, admin status transitions, auto-close timing, RFQ submission no longer creates a conversation thread, direct "Message this company" still creates one

## M6 — Admin merchant & product management
- [x] Merchant list screen (`/admin/merchants`) — search/filter by status, country, subscription state, certification state
- [x] Merchant detail view — onboarding/subscription/certification history, product count
- [x] Suspend / reactivate actions, wired to search-index exclusion (design.md §7)
- [x] Migration: `products` + `moderation_status`
- [x] Product moderation queue (`/admin/products/moderation`) — flag / unpublish / restore actions
- [x] Confirm three independent visibility levers (merchant suspend, product moderation, merchant's own archive) compose correctly on the search-index check
- [x] Feature tests for suspend/reactivate and flag/unpublish/restore

## M7 — Sponsored content
- [x] Migrations for `sponsored_content_tiers`, `sponsored_content`, `sponsored_content_placements`, `sponsored_content_events`
- [x] Pre-seed tiers (Gold, Silver, Bronze)
- [x] Create models with scopes and relations
- [x] Admin UI: List view with pause/resume and analytics summary
- [x] Admin UI: Create/Edit form with placement selection and file upload
- [x] Storefront: Embed `SponsoredContentBanner` component in layout
- [x] Click tracking redirect controller
- [x] Impression/click event capture wired into placement rendering
- [x] Feature tests: scheduling window enforcement, tier priority ordering, event capture

## M8 — Analytics rework
- [x] Extend `ShopAnalyticsService` (Phase 5 §3.1 pattern) with subscription/RFQ/sponsored-content aggregation methods
- [x] Admin dashboard: subscription counts by status/plan/country, trial-to-paid conversion, certification volume, RFQ volume/resolution time, sponsored content performance by tier
- [x] Merchant dashboard: own subscription status/renewal date, certification status, listing counts
- [x] Confirm dormant order/payout/review aggregations remain callable (not deleted) but excluded from default dashboard views while their flags are off

## M9 — Storefront/homepage
- [x] Country flag component on storefront/company header, keyed off `shops.country_code`
- [x] Migration + seed: `country_portals` (6 rows: PH + Malaysia, KSA, Brunei, Indonesia, Turkey)
- [x] Homepage country-portal section updated to render all active `country_portals`, linking to country-filtered storefront views
- [x] Localization infrastructure: locale switcher, `lang/` scaffolding, locale-aware date/currency formatting
- [x] Translate in-scope surfaces: storefront, onboarding, subscription, homepage (per `requirements.md` §1.4)
- [x] Feature tests: portal link filtering by country, locale switch persistence, fallback-to-default-locale for missing keys

---

## Cross-cutting / final checks
- [ ] Full regression suite green with all Phase 6 flags at their new defaults (§2.1)
- [ ] Full regression suite green with all Phase 6 flags flipped back to their Phase 5 defaults (rollback smoke test, `design.md` §11)
- [ ] No destructive migration present in the Phase 6 migration set (manual diff review against `design.md` §1)
- [ ] Update `.ai/rules/app.md` with the Phase 6 feature-flag pattern, consistent with the Phase 5 documentation habit
