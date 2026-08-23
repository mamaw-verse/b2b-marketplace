<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Storefront Routes (home page + discovery)
|--------------------------------------------------------------------------
*/
Route::livewire('/', 'storefront.hero-search')->name('home');
Route::livewire('/companies', 'storefront.company-directory')->name('storefront.companies');
Route::livewire('/companies/{shop}', 'storefront.company-profile')->name('storefront.company');
Route::livewire('/products', 'storefront.product-directory')->name('storefront.products');
Route::livewire('/products/{product}', 'storefront.product-detail')->name('storefront.product');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('redirect', function () {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            return redirect()->route('admin.onboardings.index');
        }

        if ($user->shop && $user->shop->status === 'active') {
            return redirect()->route('hub.products.index');
        }

        return redirect()->route('hub.onboarding');
    })->name('dashboard');

    Route::get('media/{media}/view', function (App\Models\Media $media) {
        $user = auth()->user();
        if (! $user->hasRole('admin') && $media->uploaded_by_id !== $user->id) {
            abort(403);
        }

        return Illuminate\Support\Facades\Storage::disk('s3')->response(
            $media->storage_path,
            $media->original_filename
        );
    })->name('media.view');
});

/*
|--------------------------------------------------------------------------
| Buyer Account Routes (auth + verified required)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->prefix('account')->name('account.')->group(function () {
    Route::livewire('profile', 'account.buyer-profile-form')->name('profile');
    Route::livewire('rfqs', 'account.rfq-list')->name('rfqs.index');
    Route::livewire('rfqs/create', 'account.rfq-create-form')->name('rfqs.create');
    Route::livewire('rfqs/{rfq}', 'account.rfq-detail')->name('rfqs.show');
    Route::livewire('messages', 'account.chat-inbox')->name('messages');
});

/*
|--------------------------------------------------------------------------
| Company Hub Routes (auth + verified required)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->prefix('hub')->name('hub.')->group(function () {
    Route::get('/', function () {
        $user = auth()->user();
        if ($user->shop && $user->shop->status === 'active') {
            return redirect()->route('hub.products.index');
        }

        return redirect()->route('hub.onboarding');
    })->name('dashboard');
    Route::livewire('onboarding', 'company-hub.onboarding-wizard')->name('onboarding');

    // Product management
    Route::livewire('products', 'company-hub.product-list')->name('products.index');
    Route::livewire('products/create', 'company-hub.product-form')->name('products.create');
    Route::livewire('products/{product}/edit', 'company-hub.product-form')->name('products.edit');
    Route::livewire('products/{product}/variants', 'company-hub.variant-manager')->name('products.variants');
    Route::livewire('products/{product}/gallery', 'company-hub.gallery-manager')->name('products.gallery');
    Route::livewire('products/{product}/documents', 'company-hub.document-manager')->name('products.documents');

    // RFQ management (seller)
    Route::livewire('rfqs', 'company-hub.rfq-queue')->name('rfqs.index');
    Route::livewire('rfqs/{rfqRecipient}', 'company-hub.rfq-detail')->name('rfqs.show');
    Route::livewire('rfqs/{rfqRecipient}/quotations/create', 'company-hub.quotation-builder')->name('quotations.create');
    Route::livewire('rfqs/{rfqRecipient}/quotations/{quotation}/edit', 'company-hub.quotation-builder')->name('quotations.edit');
    Route::livewire('messages', 'company-hub.chat-inbox')->name('messages');
});

/*
|--------------------------------------------------------------------------
| Admin Routes (auth + verified + role:admin required)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/onboardings')->name('dashboard');
    Route::livewire('onboardings', 'admin.onboarding-queue')->name('onboardings.index');
    Route::livewire('onboardings/{onboarding}', 'admin.onboarding-review')->name('onboardings.show');
    Route::livewire('categories', 'admin.category-manager')->name('categories.index');
    Route::livewire('categories/create', 'admin.category-create')->name('categories.create');
    Route::livewire('categories/{category}/edit', 'admin.category-edit')->name('categories.edit');
});

require __DIR__.'/settings.php';
