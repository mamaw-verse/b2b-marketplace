<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Single settings page: profile + appearance + security. No
    // password.confirm gate so the page loads directly; sensitive actions
    // stay protected individually (password change requires the current
    // password; Fortify 2FA/passkey flows require recent confirmation).
    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');

    // Backward-compat: former standalone pages now live as sections on
    // the single profile page. Route names are kept so existing links
    // (sidebar bookmarks, passkey flows) keep working.
    Route::redirect('settings/appearance', 'settings/profile')->name('appearance.edit');
    Route::redirect('settings/security', 'settings/profile')->name('security.edit');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('profile.edit'),
        'manage' => route('profile.edit'),
    ]);
})->name('well-known.passkeys');
