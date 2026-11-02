<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    // Single settings page: profile + appearance + security. Unverified users
    // may update a mistyped email; sensitive actions remain protected individually.
    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {
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
