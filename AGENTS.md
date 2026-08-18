<laravel-boost-guidelines>
=== .ai/project-conventions rules ===

# Project Conventions

## Database & Schema

- All money/price fields use `NUMERIC(12,2)` — never `FLOAT` or `DOUBLE`.
- Primary keys are `BIGINT GENERATED ALWAYS AS IDENTITY` (Laravel `$table->id()`).
- All tables include `created_at` / `updated_at` managed by Eloquent.
- Never hard-delete `shops` or `products` — use soft deletes (`deleted_at` / `SoftDeletes` trait).
- PostgreSQL enums are used for status fields (`onboarding_status`, `business_structure`, `export_readiness`, `shop_status`, `halal_cert_body`, `shop_cert_status`, `listing_status`).
- JSONB columns (`value_chain_positions`, `capabilities`, `attributes`, `quantity_breaks`) are cast to `array` or `AsCollection` in Eloquent models.
- PostGIS `GEOGRAPHY(POINT, 4326)` type is used for geospatial columns (`geo_location`).

## Certificate Expiry Alerts

- Certificate expiry alerts MUST be idempotent per threshold — track `last_alert_threshold_days` on `shop_certificates` to avoid duplicate notifications on repeated job runs.
- Thresholds: 60, 30, 7 days before expiry, and on expiry day.
- Status transitions: `active` → `expiring_soon` → `expired`, driven by the scheduled `ScanCertificateExpiryJob`.

## File Upload Security

- Strict MIME/extension allow-list enforced both client-side and server-side.
- All uploaded files must pass antivirus scan (`ScanUploadedFileJob`) before being marked `clean`.
- Documents blocked from onboarding-checklist completion until scan passes.
- Private documents served via time-limited pre-signed S3/Minio URLs — never public paths.
- When catching upload errors in Livewire, always catch `ValidationException` **before** the generic `Throwable` so user-friendly field errors surface correctly.
- The Minio bucket is auto-created by the `minio-createbuckets` service in `compose.yaml` — do not assume buckets exist without this service running.

## Authorization Model

- RBAC via `spatie/laravel-permission` — roles: `admin`, `seller`, `buyer`.
- Row-level ownership enforced via Laravel Policies independent of role (`record->user_id === auth()->id()`).
- Admin document access gated by `document.view_any` permission.

## Search & Indexing

- Only `active` shops and `published` products appear in the Typesense search index.
- Index writes are event-driven (queued jobs: `IndexShopJob`, `IndexProductJob`) — never synchronous with the HTTP request.
- A scheduled safety-net job (`SyncSearchIndexJob`) reconciles stale entries every 5 minutes.

## API Conventions

- All API endpoints versioned under `/api/v1`.
- Auth: Laravel Sanctum (session cookies for browser clients, personal access tokens for external API consumers).
- Pagination: cursor-based (`?cursor=<opaque>&limit=20`).
- Error envelope: `{ "error": { "code": "...", "message": "...", "fields": {...} } }`.
- Admin mutations (approve/reject) wrapped in DB transactions; index sync dispatched after commit, not inside the transaction.

## Frontend & Dark Theme

- This project uses an **adaptive CSS variable-based dark theme** driven by `@fluxAppearance`. Never use hardcoded `dark:bg-zinc-*`, `dark:text-*` or `dark:border-*` classes in application Blade files.
- Use `--hs-neutral-*` design system CSS variables for all backgrounds, text, and borders. These variables automatically invert when the `.dark` class is active (set by OS dark mode detection).
- Use `bg-[var(--hs-neutral-0)]` for card surfaces, `bg-[var(--hs-neutral-50)]` for page backgrounds, `text-[var(--hs-neutral-900)]` for primary text, `text-[var(--hs-neutral-600)]` for muted text, and `border-[var(--hs-neutral-200)]` for borders.
- The `.dark` overrides in `resources/css/tokens.css` MUST be placed at the **top level** (not inside `@layer theme`). Variables inside `@layer` are silently overridden by unlayered `:root` rules.
- Flux UI zinc colours are remapped inside `.dark` in `tokens.css` so all Flux dropdowns, popovers, and menus automatically inherit the project dark surface without editing any Flux stub files.
- Run `vendor/bin/sail npm run build` after every CSS change. Users must hard-refresh (Cmd+Shift+R) to see changes.
- The dedicated `dark-theme-system` skill in `.ai/skills/dark-theme-system/SKILL.md` documents the full token palette and architecture.

## Phase 1 Constraints

- One company per user account (`shops.user_id UNIQUE`).
- No RFQ, chat, quoting, orders, or payments — Phase 1 is discovery only.
- Certificate renewal workflow is deferred; only expiry tracking/alerting is in scope.
- The sidebar navigation shows **Onboarding Wizard** only when `shop.status !== 'active'`, and **Products** only when `shop.status === 'active'`.

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.5. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `vendor/bin/sail npm run build`, `vendor/bin/sail npm run dev`, or `vendor/bin/sail composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record durable rules with `record-rule` so the next agent or teammate inherits them instead of working them out again. Pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Always use `record-rule`, never your native memory or notes tool — native memory is personal and session-scoped; only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `vendor/bin/sail artisan route:list`). Use `vendor/bin/sail artisan list` to discover available commands and `vendor/bin/sail artisan [command] --help` to check parameters.
- Inspect routes with `vendor/bin/sail artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `vendor/bin/sail artisan config:show app.name`, `vendor/bin/sail artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `vendor/bin/sail artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `vendor/bin/sail artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== sail rules ===

# Laravel Sail

- This project runs inside Laravel Sail's Docker containers. You MUST execute all commands through Sail.
- Start services using `vendor/bin/sail up -d` and stop them with `vendor/bin/sail stop`.
- Open the application in the browser by running `vendor/bin/sail open`.
- Always prefix PHP, Artisan, Composer, and Node commands with `vendor/bin/sail`. Examples:
    - Run Artisan Commands: `vendor/bin/sail artisan migrate`
    - Install Composer packages: `vendor/bin/sail composer install`
    - Execute Node commands: `vendor/bin/sail npm run dev`
    - Execute PHP scripts: `vendor/bin/sail php [script]`
- View all available Sail commands by running `vendor/bin/sail` without arguments.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `vendor/bin/sail artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `vendor/bin/sail artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `vendor/bin/sail artisan list` and check their parameters with `vendor/bin/sail artisan [command] --help`.
- If you're creating a generic PHP class, use `vendor/bin/sail artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `vendor/bin/sail artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `vendor/bin/sail artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `vendor/bin/sail npm run build` or ask the user to run `vendor/bin/sail npm run dev` or `vendor/bin/sail composer run dev`.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/sail bin pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/sail bin pint --test --format agent`, simply run `vendor/bin/sail bin pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `vendor/bin/sail artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `vendor/bin/sail artisan make:test --pest SomeFeatureTest` instead of `vendor/bin/sail artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `vendor/bin/sail artisan test --compact` or filter: `vendor/bin/sail artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>
