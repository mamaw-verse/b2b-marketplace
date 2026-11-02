# Storefront Mobile UI/UX Audit

Audit scope: all storefront views, mobile-first (320px–640px breakpoint).  
Each item identifies the problem, affected file(s), and the fix applied or still pending.

---

## Issues & Status

### Layout & Navigation

- [x] **#1 — Header: logo hidden on mobile storefront**  
  _File:_ `resources/views/partials/portal-header.blade.php`  
  _Problem:_ Logo was `hidden sm:flex` — on mobile only the hamburger icon showed, no brand identity.  
  _Fix:_ Changed to always `flex`. Logo scaled down to `h-7 sm:h-9` so it doesn't crowd the hamburger.

- [x] **#10 — Footer: wall of links on mobile**  
  _File:_ `resources/views/layouts/storefront.blade.php`  
  _Problem:_ Footer grid was `grid-cols-1` on mobile → brand column + 3 link columns each full-width, creating a very long wall of links.  
  _Fix:_ Changed to `grid-cols-2` at mobile. Brand column gets `col-span-2` (full width), and the 3 link columns sit 2-per-row then 1 orphan — much more compact.

---

### Hero / Home Page

- [x] **#2 — Hero section: carousel card hidden on mobile (`hidden lg:block`)**  
  _File:_ `resources/views/livewire/storefront/hero-search.blade.php`  
  _Problem:_ The right-column carousel was `hidden lg:block` — on mobile the copy column had no visual counterpart, making the hero feel incomplete.  
  _Fix:_ Added a `lg:hidden` compact trust-pill strip (MHCB-Verified, Global B2B Network, Halal Certified) as a mobile substitute. The full carousel still loads in a `hidden lg:block` wrapper on desktop only.

- [x] **#11 — Hero search: category pills scrollable but no affordance**  
  _File:_ `resources/views/livewire/storefront/hero-search.blade.php`  
  _Problem:_ Pills row uses `scrollbar-hide overflow-x-auto` but there's no visual cue that it scrolls — users can miss off-screen categories.  
  _Fix:_ Wrapped the pills row in a `relative` container and added a right-edge gradient fade (`sm:hidden`) to indicate horizontal scroll.

- [x] **#12 — Supply chain flow: 7th item orphaned in 2-col mobile grid**  
  _File:_ `resources/views/livewire/storefront/hero-search.blade.php`  
  _Problem:_ 7 items in a `grid-cols-2` = 3 full rows + 1 item alone at bottom-left. Looks unbalanced.  
  _Fix:_ The last item (index 6) gets `col-span-2 sm:col-span-1` so it spans the full row and centres itself on mobile. Reverted to normal on sm+.

- [x] **#13 — Country portals grid: cards too tall on mobile (min-height 220px)**  
  _File:_ `resources/views/livewire/storefront/hero-search.blade.php`  
  _Problem:_ 2-col grid of 7+ cards each 220px tall = very long scroll distance on mobile.  
  _Fix:_ Changed inline `style="min-height: 220px"` to Tailwind `min-h-[160px] sm:min-h-[220px]` on both the "you are here" and the other portal cards.

---

### Product Cards & Directory

- [x] **#3 — Product card: 4/3 image too tall in 2-col mobile grid**  
  _File:_ `resources/views/components/hs-product-card.blade.php`  
  _Problem:_ `aspect-[4/3]` in a narrow 2-col grid makes images very short and squat, cutting off product images.  
  _Fix:_ Changed to `aspect-square sm:aspect-[4/3]` — square on mobile, original ratio on sm+.

- [x] **#4 — Product directory: heading too large/spaced on mobile**  
  _File:_ `resources/views/livewire/storefront/product-directory.blade.php`  
  _Problem:_ Full `pt-12 pb-12` padding, large `text-4xl sm:text-[46px]` heading, and the eyebrow label all consumed significant vertical space before users could reach the search bar.  
  _Fix:_ Reduced to `pt-6 pb-6 sm:pt-12 sm:pb-12`. Heading is `text-2xl sm:text-4xl`. Eyebrow label is `hidden sm:flex`. Stats gap tightened.

- [x] **#5 — Company directory: category sidebar above search on mobile**  
  _File:_ `resources/views/livewire/storefront/company-directory.blade.php`  
  _Problem:_ Sidebar rendered first in DOM → on mobile the collapsed category toggle appeared above the search bar, consuming space and confusing the flow.  
  _Fix:_ Restructured layout — main content div renders first with `lg:order-last`, desktop sidebar is `hidden lg:block`. A separate mobile-only inline filter toggle (with active-state indicator) sits just below the search bar.

---

### Product Detail

- [x] **#6 — Product detail: breadcrumb overflows on narrow screens**  
  _File:_ `resources/views/livewire/storefront/product-detail.blade.php`  
  _Problem:_ Product name in breadcrumb had no overflow constraint — long names broke layout.  
  _Fix:_ Added `truncate min-w-0` to the name span and `shrink-0` to "Products" link and chevron.

- [x] **#7 — Product detail: volume pricing table cramped on mobile**  
  _File:_ `resources/views/livewire/storefront/product-detail.blade.php`  
  _Problem:_ Fixed `px-5` side padding and small `text-lg` price were cramped on 360px screens.  
  _Fix:_ Padding changed to `px-3 sm:px-5`. Row negative margin adjusted to match. Price is `text-base sm:text-lg`. Discount badge uses `px-1.5` on mobile.

- [x] **#8 — Product detail: gallery+info gap too large on mobile (gap-10)**  
  _File:_ `resources/views/livewire/storefront/product-detail.blade.php`  
  _Problem:_ `gap-10` between stacked gallery and info column on mobile = 40px of dead space.  
  _Fix:_ Changed to `gap-6 lg:gap-10`.

---

### Company Profile

- [x] **#9 — Company profile: sidebar shown after content on mobile**  
  _File:_ `resources/views/livewire/storefront/company-profile.blade.php`  
  _Problem:_ In a `grid-cols-1 lg:grid-cols-3` layout, the sidebar (categories, certifications, export readiness) rendered after the products and reviews — users had to scroll past all products to find trust signals.  
  _Fix:_ Sidebar gets `order-first lg:order-last` so it appears first on mobile (trust signals → products → reviews) but remains on the right column on desktop.

---

### Reviews

- [x] **#15 — Review list: report button too small to tap (size-4 icon, no label)**  
  _File:_ `resources/views/livewire/storefront/review-list.blade.php`  
  _Problem:_ Report button is a bare `size-4` SVG icon with no label and no padding — well below the 44×44px minimum tap target.  
  _Fix:_ Added `inline-flex items-center gap-1.5 -m-2 p-2` (expands tap target without shifting layout) and a visible "Report" text label next to the flag icon.

---

### Certification Apply Page

- [x] **#14 — Apply-for-certificate: notes sidebar appears below form on mobile**  
  _File:_ `resources/views/livewire/storefront/apply-for-certificate.blade.php`  
  _Problem:_ `grid-cols-1 lg:grid-cols-[1.7fr_1fr]` collapses to single column. The "Important Notes" aside renders after the form — users fill in the form without seeing the instructions.  
  _Fix:_ Added `order-first lg:order-last` to the aside so notes appear before the form on mobile, back on the right on desktop.

---

## Remaining Work

All audit items complete. 🎉

---

## Files Modified This Session

| File | Changes |
|------|---------|
| `resources/views/partials/portal-header.blade.php` | Logo always visible; `h-7 sm:h-9` |
| `resources/views/components/hs-product-card.blade.php` | `aspect-square sm:aspect-[4/3]` |
| `resources/views/livewire/storefront/product-detail.blade.php` | Breadcrumb truncation, volume pricing padding, gallery gap |
| `resources/views/livewire/storefront/company-profile.blade.php` | Sidebar `order-first lg:order-last` |
| `resources/views/livewire/storefront/hero-search.blade.php` | Mobile trust strip, scroll fade, supply-chain orphan fix, portal card height |
| `resources/views/livewire/storefront/product-directory.blade.php` | Tightened heading spacing on mobile |
| `resources/views/livewire/storefront/company-directory.blade.php` | Search-first layout, inline mobile filter below search |
| `resources/views/layouts/storefront.blade.php` | Footer 2-col on mobile, brand col-span-2 |
| `resources/views/livewire/storefront/apply-for-certificate.blade.php` | Notes aside `order-first lg:order-last` |
| `resources/views/livewire/storefront/review-list.blade.php` | Report button tap target + visible label |


---

## Auth Pages Audit

Scope: login, register, forgot password, reset password, verify email, two-factor challenge.  
Layout used: `x-layouts::auth` (the custom split-panel layout in `resources/views/layouts/auth.blade.php`).

---

### Layout — `layouts/auth.blade.php`

- [x] **A1 — Left promo column is `hidden lg:block` — no brand on mobile**  
  _Problem:_ The entire left column (logo + benefits list) is desktop-only. On mobile the form renders directly with no logo, no brand mark, and no context about what the platform is. Users land on a blank white card with no visual identity.  
  _Fix:_ Added a `lg:hidden` mobile header row inside the right column — brand logo (`h-8`, links home) on the left, back link on the right — above the tabs. Form padding also reduced to `p-6` on mobile (`sm:p-10` preserved).

- [x] **A2 — Outer padding too generous on small screens (`py-16`)**  
  _Problem:_ The centring wrapper uses `px-6 py-16` — 64px top/bottom on a 667px screen (iPhone SE) leaves very little room for the form card, causing the register form to require scrolling before any field is reached.  
  _Fix:_ Changed to `px-4 py-8 sm:px-6 sm:py-10` — the mobile value was previously *larger* than the sm value.

- [x] **A3 — Auth card has no back-to-home link on mobile**  
  _Problem:_ On desktop the logo in the left panel links home. On mobile there is no escape hatch — a user who arrives from a shared link has no visible way back to the storefront without the browser back button.  
  _Fix:_ Covered by the A1 mobile header — it contains a "← Back to site" link plus the logo which links home.

---

### Login — `pages/auth/login.blade.php`

- [x] **A4 — `Remember me` checkbox and `Forgot password?` link on same line but both full-width on narrow screens**  
  _Problem:_ The flex row `flex items-center justify-between` works fine at 375px+ but at 320px the "Keep me signed in" label wraps and the row breaks. No min-width protection.  
  _Fix:_ Changed to `flex flex-wrap items-center justify-between gap-x-3 gap-y-2` — items stack cleanly at 320px.

- [x] **A5 — Passkey verify component renders above the login form with no spacing context**  
  _Problem:_ `<x-passkey-verify />` drops in before the form with no label or separator — on mobile it appears as a blank gap if passkeys aren't enrolled, or an unstyled button with no explanation.  
  _Fix:_ Verified the component already handles this — content is wrapped in `<template x-if="supported">` (renders nothing when passkeys unavailable) and includes an "Or continue with email" divider. Only change needed: its error paragraph used raw `text-red-600`, normalized to `text-[var(--hs-danger-700)]`.

---

### Register — `pages/auth/register.blade.php`

- [x] **A6 — First name / Last name two-column grid collapses to 1-col but fields are very short**  
  _Problem:_ `grid-cols-1 sm:grid-cols-2` means on mobile both name fields are full-width and stacked — fine in principle, but with `space-y-4` they're well spaced. No real issue here for spacing, but the Flux `<flux:input>` for first/last name has no `autocomplete="given-name"` / `autocomplete="family-name"` on mobile — iOS/Android won't autofill names correctly without it.  
  _Note:_ Verified `autocomplete` attributes are already set correctly in the Blade. No action taken.

- [x] **A7 — Password strength checklist is very long on mobile (5 items)**  
  _Problem:_ When the user types, a 5-item requirements list appears below the password field. In the 2-col auth card layout on mobile (single column), this pushes the Confirm password field far down — users may not see it and scroll past the CTA button.  
  _Fix:_ Refactored the requirements array into a `requirements` Alpine getter (shared by both renderings). On mobile (`sm:hidden`) only a "N/5 requirements met" line plus unmet items shows; the full 5-item checklist is `hidden sm:block` for larger screens.

- [x] **A8 — Terms modal is `max-h-[85vh]` with full Terms + Privacy inline — very long scroll on mobile**  
  _Problem:_ The modal renders both full legal documents. On a 375px screen `max-h-[85vh]` ≈ 570px of scroll area, but the combined documents are thousands of words. The "Agree" button is gated behind reading 100% — users on mobile are likely to either give up or force-scroll without reading. The progress bar is helpful but the UX is very friction-heavy.  
  _Fix:_ Applied the minimum recommendation — `max-h-[92vh] sm:max-h-[85vh]`. The deeper tabbed-modal restructure remains a candidate if legal UX needs another pass.

- [x] **A9 — `Create account` submit button not sticky/visible when soft keyboard is open**  
  _Problem:_ On mobile, when the soft keyboard opens for any field, it pushes the viewport up. The submit button is at the bottom of the form and is entirely off-screen. There is no sticky CTA.  
  _Fix:_ Wrapped the submit in a `sticky bottom-0` bar with card-matching background and slight bleed (`-mx-2 -mb-2 px-2 py-3`) so the CTA stays pinned and opaque while the keyboard is open.

---

### Forgot Password — `pages/auth/forgot-password.blade.php`

- [x] **A10 — Uses `x-layouts::auth` but renders without the Login/Create account tabs**  
  _Problem:_ `forgot-password` uses `<x-layouts::auth>` (which includes the Login / Create Account tab row) but doesn't pass an `activeTab` — the tabs render with neither tab highlighted. This is confusing: the user is on a page that isn't "Log in" or "Create account" but the tabs suggest it is one of those.  
  _Fix:_ Switched to `<x-layouts::auth.card>` — the standalone card layout with a centred logo and no tabs. Also applied to reset-password, verify-email, and two-factor-challenge which had the same problem (the default `activeTab="login"` wrongly highlighted the Login tab). Added `@props(['title'])` and `@livewireScripts` to the card layout.

- [x] **A11 — Error message colour uses raw `text-red-600` instead of design token**  
  _Problem:_ `@error('email') <p class="mt-1 text-sm text-red-600">` — inconsistent with the rest of the system which uses `var(--hs-danger-500)` / `var(--hs-danger-700)`.  
  _Fix:_ Changed to `text-[var(--hs-danger-700)]` (`--hs-danger-600` does not exist in tokens.css — danger shades are 50/100/200/500/700/800).

---

### Reset Password — `pages/auth/reset-password.blade.php`

- [x] **A12 — Password fields have no show/hide toggle**  
  _Problem:_ Both "New password" and "Confirm password" fields are plain `type="password"` inputs with no visibility toggle — unlike the login and register pages which have eye-icon toggles. On mobile where typing is error-prone, this makes password confirmation harder.  
  _Fix:_ Added the same Alpine `x-data="{ show: false }"` eye/eye-slash toggle used on the login page to both fields (`pr-10` added to inputs for the icon).

- [x] **A13 — Error messages use `text-red-600` (same token inconsistency as A11)**  
  _Fix:_ Changed to `text-[var(--hs-danger-700)]` on both email and password errors.

---

### Verify Email — `pages/auth/verify-email.blade.php`

- [x] **A14 — "Resend" and "Log out" buttons are the same visual weight on mobile**  
  _Problem:_ Both CTAs are full-width buttons stacked vertically. On a 375px screen they're the same size. The destructive action (log out) is directly below the primary action (resend) with no visual hierarchy — users may accidentally tap "Log out" instead of "Resend".  
  _Fix:_ "Log out" is now a centred text-style button (`text-sm font-medium text-[var(--hs-neutral-500)]` with underline hover) instead of a full-width bordered button. "Resend" remains the sole full-width primary CTA.

---

### Two-Factor Challenge — `pages/auth/two-factor-challenge.blade.php`

- [x] **A15 — OTP input field has `text-center tracking-widest` but is not `inputmode="numeric"` constrained**  
  _Problem:_ `inputmode="numeric"` is already set (good). However, the field width is full (`w-full`) — a 6-digit code on a `w-full` input looks odd with wide letter-spacing on large phones.  
  _Fix:_ Capped at `max-w-[220px]` and centred with `block mx-auto` — the 6-digit code now reads as intentionally compact.

- [x] **A16 — "Use a recovery code" toggle is `text-xs` — very small tap target**  
  _Problem:_ The toggle button is `text-xs font-medium` with no padding — approximately 12px tall, well below the 44px minimum tap target.  
  _Fix:_ Bumped to `text-sm` and added `inline-block py-2.5` for a ~44px tap height.

---

### Summary Table — Auth Pages

| # | Page | Issue | Priority |
|---|------|-------|----------|
| A1 | Layout | No logo/brand on mobile (left panel hidden) | High |
| A2 | Layout | Excess `py-16` outer padding on short screens | Medium |
| A3 | Layout | No back-to-home link on mobile | Low |
| A4 | Login | Remember/Forgot row breaks at 320px | Low |
| A5 | Login | Passkey component unstyled/no divider on mobile | Medium |
| A6 | Register | *(autocomplete already correct — no action needed)* | — |
| A7 | Register | Password checklist too long on mobile, pushes CTA off-screen | Medium |
| A8 | Register | Terms modal scroll extremely heavy on mobile | High |
| A9 | Register | Submit button not visible when soft keyboard open | Medium |
| A10 | Forgot PW | Wrong layout used — Login/Create tabs show without active state | Medium |
| A11 | Forgot PW | Error uses `text-red-600` not design token | Low |
| A12 | Reset PW | No show/hide toggle on password fields | Medium |
| A13 | Reset PW | Error uses `text-red-600` not design token | Low |
| A14 | Verify Email | "Log out" same visual weight as "Resend" — risk of accidental tap | High |
| A15 | 2FA | OTP input too wide on mobile | Low |
| A16 | 2FA | "Use a recovery code" toggle — sub-44px tap target | Medium |
