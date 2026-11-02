---
paths:
  - '{app/Http/Controllers/Auth/**,resources/views/pages/settings/**,resources/views/pages/auth/confirm-password.blade.php}'
---

# Auth

## Sensitive actions support passwordless confirmation
Sensitive settings actions require a recent `auth.password_confirmed_at` session. Password-backed users confirm with their current password; passwordless users may satisfy the same Fortify confirmation session through an existing passkey or a fresh Google reauthentication that must match their linked `google_id`.
