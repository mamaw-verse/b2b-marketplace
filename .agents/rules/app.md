---
paths:
  - 'app/**/*.php'
---

# App

## Phase 5 Phase 1 Refactoring Patterns
Performance & Security Quick Wins completed (Aug 2026):

1. **Feature Flags over Schema Checks**: Use `config('marketplace.features.payouts_enabled')` instead of `Schema::hasTable()` in production code. Dynamic schema checks cause DB overhead on every request.

2. **Centralized Authorization**: Use `Gate::authorize('methodName', $model)` in controllers/components instead of manual `abort_unless()` checks. All authorization logic lives in Policy classes.

3. **Request-Layer File Validation**: Always add explicit `mimes:` validation rules at the request/FormRequest layer. Use `MediaUploadService::allowedExtensions()` for consistency. Service-layer validation is a safety net, not the primary check.

Modified files: config/marketplace.php, OrderPolicy, 7 earnings/analytics files, 6 Livewire components, HubProductController.
