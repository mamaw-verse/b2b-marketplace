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

/*
| Media viewer.
| Documents attached to a PUBLISHED product are public (Q2: accessible to all,
| even unauthenticated users). Any other media requires an authenticated admin
| or the original uploader.
*/
Route::get('media/{media}/view', function (App\Models\Media $media) {
    $isPublicProductDocument = $media->products()
        ->where('status', 'published')
        ->exists();

    if (! $isPublicProductDocument) {
        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        if (! $user->hasRole('admin') && $media->uploaded_by_id !== $user->id) {
            abort(403);
        }
    }

    return Illuminate\Support\Facades\Storage::disk('s3')->response(
        $media->storage_path,
        $media->original_filename
    );
})->name('media.view');

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
});

/*
|--------------------------------------------------------------------------
| Buyer Account Routes (auth + verified required)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->prefix('account')->name('account.')->group(function () {
    Route::livewire('dashboard', 'account.dashboard')->name('dashboard');
    Route::livewire('profile', 'account.buyer-profile-form')->name('profile');
    Route::livewire('rfqs', 'account.rfq-list')->name('rfqs.index');
    Route::livewire('rfqs/create', 'account.rfq-create-form')->name('rfqs.create');
    Route::livewire('rfqs/{rfq}', 'account.rfq-detail')->name('rfqs.show');
    Route::livewire('messages', 'account.chat-inbox')->name('messages');

    // Orders (buyer)
    Route::livewire('orders', 'account.orders.order-list')->name('orders.index');
    Route::livewire('orders/{order}', 'account.orders.order-detail')->name('orders.show');
    Route::livewire('orders/{order}/pay', 'account.orders.payment-page')->name('orders.pay');

    // Payment History (buyer)
    Route::livewire('payments', 'account.payment-history')->name('payments');

    // Notifications (full-page list)
    Route::livewire('notifications', 'shared.notification-list')->name('notifications.index');

    // Disputes (buyer)
    Route::livewire('orders/{order}/dispute/create', 'account.disputes.dispute-form')->name('orders.dispute.create');
    Route::livewire('orders/{order}/dispute/{dispute}', 'account.disputes.dispute-detail')->name('orders.dispute.show');
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
    Route::livewire('shop/profile', 'company-hub.shop-profile-form')->name('shop.profile');

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

    // Orders (seller)
    Route::livewire('orders', 'company-hub.orders.order-queue')->name('orders.index');
    Route::livewire('orders/create', 'company-hub.orders.order-builder')->name('orders.create');
    Route::livewire('orders/{order}/edit', 'company-hub.orders.order-builder')->name('orders.edit');
    Route::livewire('orders/{order}', 'company-hub.orders.order-detail')->name('orders.show');

    // Payout Account
    Route::livewire('payout-account', 'company-hub.payout-account-form')->name('payout-account');

    // Payouts (seller)
    Route::livewire('payouts', 'company-hub.payouts.payout-history')->name('payouts.index');
    Route::livewire('payouts/{payout}', 'company-hub.payouts.payout-detail')->name('payouts.show');

    // Seller Hub pages
    Route::livewire('dashboard', 'company-hub.hub.dashboard')->name('hub-dashboard');
    Route::livewire('customers', 'company-hub.hub.customers-list')->name('customers.index');
    Route::livewire('reports', 'company-hub.hub.reports')->name('reports');

    // Earnings (seller)
    Route::livewire('earnings', 'company-hub.earnings.earnings-overview')->name('earnings');

    // Reviews (seller)
    Route::livewire('reviews', 'company-hub.reviews.review-list')->name('reviews.index');
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

    // Disputes (admin)
    Route::livewire('disputes', 'admin.disputes.dispute-queue')->name('disputes.index');
    Route::livewire('disputes/{dispute}', 'admin.disputes.dispute-arbitration')->name('disputes.show');

    // Payout Settings
    Route::livewire('payout-settings', 'admin.payout-settings')->name('payout-settings');

    // Financial Ledger (admin)
    Route::livewire('ledger', 'admin.finance.financial-ledger')->name('ledger');

    // Payout Accounts
    Route::livewire('payout-accounts', 'admin.payout-accounts.payout-account-queue')->name('payout-accounts.index');

    // Payouts
    Route::livewire('payouts', 'admin.payouts.payout-queue')->name('payouts.index');
    Route::livewire('payouts/eligible', 'admin.payouts.eligible-payouts')->name('payouts.eligible');
    Route::livewire('payouts/{payout}', 'admin.payouts.payout-detail')->name('payouts.show');

    // Analytics Dashboard
    Route::livewire('analytics', 'admin.finance.analytics-dashboard')->name('analytics');

    // Review Reports
    Route::livewire('review-reports', 'admin.reviews.review-report-queue')->name('review-reports.index');
});

/*
|--------------------------------------------------------------------------
| Payment Callback Routes (DLT redirects here after checkout)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/orders/{order}/payment/success', [App\Http\Controllers\OrderPaymentCallbackController::class, 'success'])
        ->name('orders.payment.success');
    Route::get('/orders/{order}/payment/failure', [App\Http\Controllers\OrderPaymentCallbackController::class, 'failure'])
        ->name('orders.payment.failure');
});

require __DIR__.'/settings.php';
