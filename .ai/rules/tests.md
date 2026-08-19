---
paths:
  - 'tests/**'
---

# Tests

## Use custom error envelope assertions for API validation tests
The project uses a custom validation error envelope `{ "error": { "code": "VALIDATION_ERROR", "message": "...", "fields": {...} } }` (defined in bootstrap/app.php). Do NOT use Laravel's `assertJsonValidationErrors()` helper for API tests — it expects the standard `{ "errors": {...} }` format. Instead use `assertJsonPath('error.code', 'VALIDATION_ERROR')` and `assertJsonPath('error.fields.field_name.0', '...')` or `assertJson(fn ($json) => $json->has('error.fields.field_name'))`.
