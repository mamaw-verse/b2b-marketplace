# Google Sign-In, Bot Checking, and Analytics

How the three integrations are implemented in this codebase.

Project path: `/Users/john/Develop/b2b-marketplace`

---

## 1. Google Sign-In (Laravel Socialite)

### Dependencies & config

- Package: `laravel/socialite` (`composer.json`).
- Credentials live in `config/services.php` under `services.google`, populated from env:
  - `GOOGLE_CLIENT_ID`
  - `GOOGLE_CLIENT_SECRET`
  - `GOOGLE_REDIRECT_URI` (default `${APP_URL}/auth/google/callback`)

### Routes — `routes/web.php`

```php
// Guest-only, throttled 6/min
Route::middleware(['guest', 'throttle:6,1'])->group(function () {
    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

// Authenticated, throttled 6/min — "confirm identity with Google" flow
Route::middleware(['auth', 'throttle:6,1'])->group(function () {
    Route::get('/auth/google/confirm', [GoogleAuthController::class, 'confirmRedirect'])->name('auth.google.confirm');
    Route::get('/auth/google/confirm/callback', [GoogleAuthController::class, 'confirmCallback'])->name('auth.google.confirm.callback');
});
```

### Controller — `app/Http/Controllers/Auth/GoogleAuthController.php`

- `redirect()` — plain `Socialite::driver('google')->redirect()`.
- `callback()` — the login/signup flow:
  1. `Socialite::driver('google')->user()` exchanges the code server-side.
  2. Rejects unless `googleUser->getId()` is filled and `hasVerifiedEmail()` passes (requires a non-empty email **and** raw `verified_email === true`).
  3. `resolveUser()` runs inside `DB::transaction`:
     - Looks up `users.google_id` first (`lockForUpdate`).
     - Falls back to `users.email`; if found, links `google_id` onto the existing account — but throws `UnexpectedValueException` if that email is already bound to a *different* Google ID.
     - Otherwise creates a new `User` with `email_verified_at = now()`, `password = null`, and name split from Google's `given_name`/`family_name` (falling back to `name`).
  4. `Auth::login($user, true)` (remember-me), `session()->regenerate()`, `redirect()->intended(route('dashboard'))`.
  - Errors redirect to `route('login')` with an `errors.google` message.
- `confirmRedirect()` / `confirmCallback()` — a second flow for password-confirmation: re-runs Google OAuth with `login_hint` = current user's email and `prompt=login`, using a dedicated `redirectUrl` (`auth.google.confirm.callback`). On callback it requires the returned `google_id` to `hash_equals` the signed-in user's stored `google_id`, then calls `$request->session()->passwordConfirmed()` — letting Google act as an identity-confirmation factor alongside password/passkeys.

### Schema — `database/migrations/2026_10_18_212549_add_google_id_and_make_password_nullable_on_users_table.php`

- Adds `users.google_id` (`string`, nullable, unique).
- Makes `users.password` nullable so Google-only accounts can exist.

### UI

- `resources/views/components/google-auth-button.blade.php` — `<x-google-auth-button>` renders a "Continue with Google" link to `auth.google.redirect`; used on the login and register pages.
- `resources/js/auth.js` — `loginForm`/`registrationForm` remember the last auth method in `localStorage` (`auth.lastMethod`); the login page swaps the Google/passkey buttons to the top under a "Last used" label.
- `app/Providers/FortifyServiceProvider.php` passes `hasGoogleIdentity` to the confirm-password view.
- `resources/views/pages/auth/confirm-password.blade.php` shows a "Confirm with Google" button (`route('auth.google.confirm')`) when the user has a linked `google_id`.

### Tests

- `tests/Feature/GoogleAuthenticationTest.php`

---

## 2. Bot Checking — Cloudflare Turnstile + Human Pass + Honeypot

Three layered defenses: an invisible Turnstile widget, a signed "human pass" cookie for repeat submissions, and a honeypot field.

### Frontend — `resources/views/components/turnstile-gate.blade.php`

Included once per layout (`layouts/auth`, `layouts/auth/split`, `layouts/auth/simple`, `layouts/auth/card`, `layouts/portal`, `layouts/storefront`). Renders a single **invisible** Turnstile widget (`size: 'invisible'`, `appearance: 'interaction-only'`, `execution: 'execute'`) in a hidden fixed container and exposes `window.HumanCheck`:

- `ensure()` — executes the widget, POSTs the token to `route('human-check')`, stores `expires_at`. Deduplicated via `_ensuringPromise`.
- `fresh()` — executes the widget and returns a raw token (no cookie) for one-shot form fields.
- `isReady()` / `invalidate()`.

Behavior hooks in the same component:

- Pre-warms on the first `focusin` inside any `<form>`.
- Intercepts plain form submits on `form[data-human]`: if the form has a hidden `input[name="cf-turnstile-response"]` that is empty, it prevents submission, obtains a fresh token via `HumanCheck.fresh()`, fills the input, and resubmits. Otherwise it relies on the `human_pass` cookie.
- Intercepts Livewire requests returning `403` with `code === 'HUMAN_CHECK_REQUIRED'`, runs `ensure()`, and retries once (`options._humanRetried`).
- Listens for the `human-check-required` browser event (dispatched by `RequiresHumanPass`).

There is also `resources/views/components/turnstile.blade.php` — an Alpine/Livewire variant rendering a `flexible` widget bound to a Livewire property (`model`), with `turnstile-reset` window-event support. (Currently no views consume it; auth forms use the gate + hidden input pattern instead.)

### Token verification — `app/Services/TurnstileVerifier.php`

- Singleton bound in `AppServiceProvider::register()` with `services.turnstile.secret`, `humancheck.expected_hostname`, `humancheck.expected_action`.
- `verify($token, $remoteIp, $action)`:
  - Empty token → failure `['missing-input-response']`.
  - `humancheck.enabled` false → success (kill switch).
  - In `testing` env without `Http::fake()`, accepts the literal `valid-token` and rejects everything else (lets tests run without stubbing HTTP).
  - POSTs `secret`, `response`, optional `remoteip` to `https://challenges.cloudflare.com/turnstile/v0/siteverify` (5s timeout, form-encoded).
  - Non-2xx → failure `['http-error']`; `success: false` → propagates Cloudflare `error-codes`.
  - Optionally enforces `hostname` and `action` match (`TURNSTILE_EXPECTED_HOSTNAME` / `TURNSTILE_EXPECTED_ACTION`).
  - Any exception/timeout → failure `['timeout-or-connection-error']` (logged as warning).
- Returns `App\Services\TurnstileResult` (`success` + `errorCodes`).

### Human-check endpoint — `app/Http/Controllers/HumanCheckController.php`

- `POST /human-check` (`routes/web.php`), throttled `20/min`, named `human-check`.
- Validates `token`, calls `TurnstileVerifier::verify`, returns `422` with the `{ error: { code, message, error_codes } }` envelope on failure.
- On success issues the `human_pass` cookie via `HumanPass::issue()` and returns `{ ok: true, expires_at }`.

### Human Pass cookie — `app/Support/HumanPass.php`

- Cookie name `human_pass`; payload `{ iat, exp, ua_hash, ip_hash? }` encrypted with `Crypt::encryptString` (not Laravel's cookie encryption — the cookie is excluded via `encryptCookies(except:)` in `bootstrap/app.php`).
- `valid()` checks: config enabled → decrypt → `exp` not passed → `hash_equals` on `sha256(user-agent)` → optional `sha256(ip)` when `humancheck.bind_ip` is on.
- Cookie flags: `HttpOnly`, `SameSite=Lax`, `Secure` in production, TTL from `humancheck.ttl` (default 1200s / 20 min).

### Enforcement — two mechanisms

1. **Fortify auth forms** — `cf-turnstile-response` is a *required* field validated by `App\Rules\TurnstileRule` (which calls `TurnstileVerifier::verify` with `request()->ip()`):
   - `app/Http/Requests/Auth/LoginRequest.php` — bound over Fortify's login request in `FortifyServiceProvider::register()`.
   - `app/Http/Requests/Auth/PasswordResetLinkRequest.php` — bound over `SendPasswordResetLinkRequest`.
   - `app/Actions/Fortify/CreateNewUser.php` — registration validation.
   - Views keep a hidden `<input name="cf-turnstile-response" value="">` that the gate's `fresh()` fills on submit (`login.blade.php`, `register.blade.php`, `forgot-password.blade.php`, all with `data-human` on the form).

2. **Livewire public forms** — `App\Concerns\RequiresHumanPass::guardHuman()`:
   - Used by `app/Livewire/Storefront/ProductSourcingForm.php` and `app/Livewire/Storefront/ApplyForCertificate.php`.
   - If no valid `human_pass` cookie: dispatches the `human-check-required` browser event (gate runs `ensure()`) and throws a `ValidationException` on the `human_check` field (rendered via `<flux:error name="human_check" />`).
   - `App\Http\Middleware\EnsureHumanPass` (alias `human` in `bootstrap/app.php`) also exists — returns `403 { code: 'HUMAN_CHECK_REQUIRED' }` for JSON/AJAX/Livewire requests and redirects back with errors otherwise — but is currently only exercised in tests, not attached to routes.

### Honeypot — `ProductSourcingForm::$honeypot`

A public Livewire property rendered hidden from humans; if non-empty on submit the form silently resets and shows the success state without persisting (`ProductSourcingForm::submit()`).

### Config & env

- `config/humancheck.php`: `enabled` (`HUMANCHECK_ENABLED`), `ttl` (`HUMANCHECK_TTL`), `bind_ip` (`HUMANCHECK_BIND_IP`), `low_risk_fail_open` (`HUMANCHECK_LOW_RISK_FAIL_OPEN`), `expected_hostname`/`expected_action`.
- `config/services.php` → `services.turnstile.key` / `.secret` (`TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY`; defaults are Cloudflare's public dummy test keys `1x0000...`).
- Preconnect/dns-prefetch hints for `challenges.cloudflare.com` in `layouts/storefront.blade.php`.

### Tests

- `tests/Feature/HumanCheckTest.php` — endpoint success/422/timeout/rate-limit.
- `tests/Feature/AuthTurnstileTest.php` — login/register/reset Turnstile enforcement.
- `tests/Feature/HumanPassMiddlewareTest.php`, `HumanPass` cookie cases in `ProductSourcingFormTest`, `CertificationApplicationTest`, `ApplyForCertificateTest`.

---

## 3. Analytics

Two unrelated things share the name — keep them separate.

### A. Client-side Google Analytics 4 (storefront only)

- `resources/views/layouts/storefront.blade.php` (lines ~24–32): when `services.google_analytics.measurement_id` is set, injects the standard `gtag.js` snippet:

  ```blade
  @if(config('services.google_analytics.measurement_id'))
      <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.google_analytics.measurement_id') }}"></script>
      <script>
          window.dataLayer = window.dataLayer || [];
          function gtag(){dataLayer.push(arguments);}
          gtag('js', new Date());
          gtag('config', '{{ config('services.google_analytics.measurement_id') }}');
      </script>
  @endif
  ```

- Config: `services.google_analytics.measurement_id` ← `GOOGLE_ANALYTICS_ID` (`config/services.php`). No other layout (auth, portal) includes it.
- Covered by `tests/Feature/StorefrontHomeTest.php` (snippet present/absent depending on config).

### B. Server-side GA4 Data API — admin dashboard

- Package: `google/analytics-data` (`composer.json`).
- `app/Services/GoogleAnalyticsService.php`:
  - Reads `services.google_analytics.property_id` (`GA4_PROPERTY_ID`) and `services.google_analytics.credentials` (`GOOGLE_ANALYTICS_CREDENTIALS` — path to a service-account JSON key file).
  - Builds a `BetaAnalyticsDataClient` only when both property ID and a readable credentials file exist; otherwise `isConfigured()` is false and `getOverview()` returns `null`.
  - `getOverview($from, $to)` runs a `RunReportRequest` for `activeUsers`, `sessions`, `screenPageViews`, `bounceRate`, `averageSessionDuration`, returning a normalized array (or `null` on API exception, which is `report()`ed).
- Consumer: `app/Livewire/Admin/Finance/AnalyticsDashboard.php` (`Route::livewire('dashboard', ...)` → `admin.finance.analytics-dashboard`) — merges the GA overview into `metrics['ga']` alongside internal metrics (GMV, orders, payouts, RFQs, certifications) from `App\Services\ShopAnalyticsService`. `$gaConfigured` drives whether the GA card renders.
- Tests: `tests/Feature/AdminAnalyticsDashboardTest.php` binds a `GoogleAnalyticsFake` into the container.

### Not GA — internal analytics

`App\Services\ShopAnalyticsService` and `Api\V1\AnalyticsController` (`GET /api/v1/.../analytics`) compute marketplace-internal stats (seller dashboard counts, reports, leaderboards) from the database — unrelated to Google Analytics.

### Env summary

| Var | Purpose |
|---|---|
| `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` / `GOOGLE_REDIRECT_URI` | Socialite Google OAuth |
| `TURNSTILE_SITE_KEY` / `TURNSTILE_SECRET_KEY` | Turnstile widget + siteverify |
| `HUMANCHECK_ENABLED` / `HUMANCHECK_TTL` / `HUMANCHECK_BIND_IP` / `HUMANCHECK_LOW_RISK_FAIL_OPEN` / `TURNSTILE_EXPECTED_HOSTNAME` / `TURNSTILE_EXPECTED_ACTION` | Human-check behavior |
| `GOOGLE_ANALYTICS_ID` | GA4 measurement ID (gtag snippet) |
| `GA4_PROPERTY_ID` / `GOOGLE_ANALYTICS_CREDENTIALS` | GA4 Data API property + service-account key path |
