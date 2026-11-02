<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\HumanCheckController;
use App\Http\Controllers\OrderPaymentCallbackController;
use App\Http\Controllers\SponsoredContentClickController;
use App\Http\Controllers\SubscriptionPaymentCallbackController;
use App\Models\CertificationApplication;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductGallery;
use App\Models\SourcingRequest;
use App\Models\SponsoredContent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Public Storefront Routes (home page + discovery)
|--------------------------------------------------------------------------
*/
Route::post('/locale', function (Request $request) {
    $validLocales = collect(config('marketplace.portals', []))->pluck('locale')->unique()->implode(',');
    $request->validate(['locale' => 'required|string|in:'.$validLocales]);
    session(['locale' => $request->locale]);

    return back();
})->name('locale.switch');

Route::post('/human-check', HumanCheckController::class)
    ->middleware('throttle:20,1')
    ->name('human-check');

Route::livewire('/', 'storefront.hero-search')->name('home');
Route::livewire('/companies', 'storefront.company-directory')->name('storefront.companies');
Route::livewire('/companies/{shop}', 'storefront.company-profile')->name('storefront.company');
Route::livewire('/products', 'storefront.product-directory')->name('storefront.products');
Route::livewire('/products/{product}', 'storefront.product-detail')->name('storefront.product');
Route::livewire('/certification/apply', 'storefront.apply-for-certificate')->name('apply.certificate');
Route::livewire('/request-a-product', 'storefront.product-sourcing-form')->name('sourcing-request.create');
Route::livewire('/about', 'storefront.about-page')->name('about');
Route::livewire('/terms', 'legal.terms-page')->name('legal.terms');
Route::livewire('/privacy', 'legal.privacy-page')->name('legal.privacy');
Route::livewire('/faq', 'legal.faq-page')->name('help.faq');

Route::middleware(['guest', 'throttle:6,1'])->group(function () {
    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware(['auth', 'throttle:6,1'])->group(function () {
    Route::get('/auth/google/confirm', [GoogleAuthController::class, 'confirmRedirect'])->name('auth.google.confirm');
    Route::get('/auth/google/confirm/callback', [GoogleAuthController::class, 'confirmCallback'])->name('auth.google.confirm.callback');
});

/*
| Media viewer.
| Documents attached to a marketplace-visible published product are public
| (accessible to all, even unauthenticated users). Gallery images of
| marketplace-visible published products are public too. Any other media
| requires an authenticated admin, the original uploader, or the relevant
| buyer/seller.
|
| All responses stream through the app (not direct S3 URLs) so a private
| bucket keeps working — direct Storage::url() calls 403 on private objects.
*/
Route::get('media/{media}/view', function (Media $media) {
    $subscriptionsEnabled = (bool) config('marketplace.features.subscriptions_enabled', true);

    $isPublicProductDocument = $media->products()
        ->where('status', 'published')
        ->whereHas('shop', fn ($query) => $query
            ->where('status', 'active')
            ->when($subscriptionsEnabled, fn ($q) => $q->whereHas('subscriptions', fn ($subscriptionQuery) => $subscriptionQuery
                ->whereIn('status', ['active', 'trialing']))))
        ->exists();

    $isPublicGalleryImage = ProductGallery::where('media_id', $media->id)
        ->whereHas('product', fn ($query) => $query
            ->where('status', 'published')
            ->whereHas('shop', fn ($shopQuery) => $shopQuery
                ->where('status', 'active')
                ->when($subscriptionsEnabled, fn ($q) => $q->whereHas('subscriptions', fn ($subscriptionQuery) => $subscriptionQuery
                    ->whereIn('status', ['active', 'trialing'])))))
        ->exists();

    // Sponsored content creatives are public — they appear on the storefront
    // for unauthenticated visitors so their images must be accessible to all.
    $isSponsoredContentCreative = SponsoredContent::where('wide_media_id', $media->id)
        ->orWhere('tall_media_id', $media->id)
        ->orWhere('media_id', $media->id)
        ->exists();

    $isPublic = $isPublicProductDocument || $isPublicGalleryImage || $isSponsoredContentCreative;

    if (! $isPublic) {
        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        $isAdminOrUploader = $user->hasRole('admin') || $media->uploaded_by_id === $user->id;

        // Sellers must see their own draft/archived product images in the hub
        // (gallery manager, product list) even before publishing.
        $isProductOwner = $user->shop && (
            $media->products()->where('shop_id', $user->shop->id)->exists()
            || ProductGallery::where('media_id', $media->id)
                ->whereHas('product', fn ($q) => $q->where('shop_id', $user->shop->id))
                ->exists()
        );

        // Dispute photos must be viewable by BOTH parties of the dispute, not just
        // the uploader: the buyer uploads them, but the seller needs to see them to
        // resolve the case. Allow either the buyer or the shop owner of any order
        // whose dispute references this media.
        $isDisputeParty = $media->disputes()
            ->whereHas('order', fn ($q) => $q
                ->where('buyer_user_id', $user->id)
                ->orWhereHas('shop', fn ($s) => $s->where('user_id', $user->id))
            )
            ->exists();

        // RFQ attachments must be viewable by the buyer who created the RFQ and
        // any seller shop that is a recipient of that RFQ.
        $isRfqRecipient = $user->shop && $media->rfqs()
            ->whereHas('recipients', fn ($q) => $q->where('shop_id', $user->shop->id))
            ->exists();

        if (! $isAdminOrUploader && ! $isProductOwner && ! $isDisputeParty && ! $isRfqRecipient) {
            abort(403);
        }
    }

    $response = Storage::disk('s3')->response(
        $media->storage_path,
        $media->original_filename
    );

    // Public storefront images get long browser caching; private files stay private.
    $response->headers->set(
        'Cache-Control',
        $isPublic ? 'public, max-age=31536000, immutable' : 'private, max-age=3600'
    );

    return $response;
})->name('media.view');

/*
| Product featured image.
| featured_image_path is a raw S3 path (not a Media row), so it needs its own
| proxy. Same visibility rule as gallery: public when the product is
| marketplace-visible, otherwise owner/admin only.
*/
Route::get('products/{product}/featured-image', function (Product $product) {
    if (! $product->featured_image_path || ! Storage::disk('s3')->exists($product->featured_image_path)) {
        abort(404);
    }

    $subscriptionsEnabled = (bool) config('marketplace.features.subscriptions_enabled', true);

    $isPublic = $product->status === 'published'
        && $product->shop?->status === 'active'
        && (! $subscriptionsEnabled || $product->shop->hasActiveSubscription());

    if (! $isPublic) {
        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        $isOwnerOrAdmin = $user->hasRole('admin')
            || ($user->shop && (int) $user->shop->id === (int) $product->shop_id);

        if (! $isOwnerOrAdmin) {
            abort(403);
        }
    }

    $response = Storage::disk('s3')->response(
        $product->featured_image_path,
        basename($product->featured_image_path)
    );

    $response->headers->set(
        'Cache-Control',
        $isPublic ? 'public, max-age=31536000, immutable' : 'private, max-age=3600'
    );

    return $response;
})->name('products.featured-image');

/*
| User profile photo (private — owner or admin only).
*/
Route::get('users/{user}/photo', function (User $user) {
    if (! $user->photo_path || ! Storage::disk('s3')->exists($user->photo_path)) {
        abort(404);
    }

    $viewer = auth()->user();

    if (! $viewer || (! $viewer->hasRole('admin') && (int) $viewer->id !== (int) $user->id)) {
        abort(403);
    }

    $response = Storage::disk('s3')->response(
        $user->photo_path,
        basename($user->photo_path)
    );

    $response->headers->set('Cache-Control', 'private, max-age=3600');

    return $response;
})->name('users.photo')->middleware(['auth']);

Route::middleware(['auth', 'verified'])->group(function () {
    // Phase 6: canonical unified dashboard for all non-admin users.
    Route::livewire('portal/dashboard', 'portal.dashboard')->name('portal.dashboard');

    // Legacy redirect: /redirect (used by Fortify post-login) routes to the
    // appropriate destination. Admin → admin console; all others → unified portal.
    Route::get('redirect', function () {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard.index');
        }

        return redirect()->route('portal.dashboard');
    })->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| Buyer Account Routes (auth + verified required)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->prefix('account')->name('account.')->group(function () {
    // Phase 6: /account/dashboard is kept for backward-compat but redirects to the
    // unified portal dashboard. Existing links (emails, bookmarks) continue to work.
    Route::redirect('dashboard', '/portal/dashboard')->name('dashboard');
    Route::livewire('profile', 'account.buyer-profile-form')->name('profile');
    // Seller onboarding application (buyer applies to become a seller)
    Route::livewire('onboarding', 'company-hub.onboarding-wizard')->name('onboarding');

    Route::livewire('rfqs', 'account.rfq-list')->name('rfqs.index');
    Route::livewire('rfqs/create', 'account.rfq-create-form')->name('rfqs.create');
    Route::livewire('rfqs/{rfq}', 'account.rfq-detail')->name('rfqs.show');
    Route::middleware('feature:chat_enabled')->group(function () {
        Route::livewire('messages', 'account.chat-inbox')->name('messages');
    });

    // Orders (buyer)
    Route::middleware('feature:orders_enabled')->group(function () {
        Route::livewire('orders', 'account.orders.order-list')->name('orders.index');
        Route::livewire('orders/{order}', 'account.orders.order-detail')->name('orders.show');
        Route::livewire('orders/{order}/pay', 'account.orders.payment-page')->name('orders.pay');
    });

    // Payment History (buyer)
    Route::livewire('payments', 'account.payment-history')->name('payments');

    // Notifications (full-page list)
    Route::livewire('notifications', 'shared.notification-list')->name('notifications.index');

    // Disputes (buyer) — creation and viewing are modals on the order detail page
    // (DisputeForm / DisputeDetail components), not standalone pages.
});

/*
|--------------------------------------------------------------------------
| Company Hub Routes (auth + verified required)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->prefix('hub')->name('hub.')->group(function () {
    // Phase 6: /hub redirects to the unified portal dashboard.
    Route::get('/', function () {
        return redirect()->route('portal.dashboard');
    })->name('dashboard');
    // The old hub-dashboard Livewire route is kept for backward-compat
    // (existing links, emails, etc.) and redirects to the unified dashboard.
    Route::redirect('dashboard', '/portal/dashboard')->name('hub-dashboard');
    Route::livewire('shop/profile', 'company-hub.shop-profile-form')->name('shop.profile');
    Route::middleware('feature:subscriptions_enabled')->group(function () {
        Route::livewire('subscription', 'company-hub.subscription-page')->name('subscription');
        Route::livewire('subscription/{subscription}/pay', 'company-hub.subscription-payment-page')->name('subscription.pay');
        Route::livewire('subscription/{subscription}/pay/manual', 'company-hub.manual-subscription-payment-page')->name('subscription.pay.manual');
    });

    // Product management
    Route::livewire('products', 'company-hub.product-list')->name('products.index');
    Route::livewire('products/create', 'company-hub.product-form')->name('products.create');
    Route::livewire('products/{product}/edit', 'company-hub.product-form')->name('products.edit');
    Route::livewire('products/{product}/variants', 'company-hub.variant-manager')->name('products.variants');
    Route::livewire('products/{product}/gallery', 'company-hub.gallery-manager')->name('products.gallery');
    Route::livewire('products/{product}/documents', 'company-hub.document-manager')->name('products.documents');

    // RFQ management (seller)
    Route::middleware('feature:seller_rfq_queue_enabled')->group(function () {
        Route::livewire('rfqs', 'company-hub.rfq-queue')->name('rfqs.index');
        Route::livewire('rfqs/{rfqRecipient}', 'company-hub.rfq-detail')->name('rfqs.show');
    });

    // Quotations (seller)
    Route::middleware('feature:quotations_enabled')->group(function () {
        Route::livewire('rfqs/{rfqRecipient}/quotations/create', 'company-hub.quotation-builder')->name('quotations.create');
        Route::livewire('rfqs/{rfqRecipient}/quotations/{quotation}/edit', 'company-hub.quotation-builder')->name('quotations.edit');
    });
    Route::middleware('feature:chat_enabled')->group(function () {
        Route::livewire('messages', 'company-hub.chat-inbox')->name('messages');
    });

    // Orders (seller)
    Route::middleware('feature:orders_enabled')->group(function () {
        Route::livewire('orders', 'company-hub.orders.order-queue')->name('orders.index');
        Route::livewire('orders/create', 'company-hub.orders.order-builder')->name('orders.create');
        Route::livewire('orders/{order}/edit', 'company-hub.orders.order-builder')->name('orders.edit');
        Route::livewire('orders/{order}', 'company-hub.orders.order-detail')->name('orders.show');
    });

    // Payout Account & Payouts (seller)
    Route::middleware('feature:payouts_enabled')->group(function () {
        Route::livewire('payout-account', 'company-hub.payout-account-form')->name('payout-account');
        Route::livewire('payouts', 'company-hub.payouts.payout-history')->name('payouts.index');
        Route::livewire('payouts/{payout}', 'company-hub.payouts.payout-detail')->name('payouts.show');
    });

    // Seller Hub pages
    Route::livewire('customers', 'company-hub.hub.customers-list')->name('customers.index');
    Route::livewire('reports', 'company-hub.hub.reports')->name('reports');

    // Certificates (seller)
    Route::livewire('certifications', 'company-hub.certificate-list')->name('certifications.index');

    // Earnings (seller)
    Route::livewire('earnings', 'company-hub.earnings.earnings-overview')->name('earnings');

    // Reviews (seller)
    Route::middleware('feature:reviews_enabled')->group(function () {
        Route::livewire('reviews', 'company-hub.reviews.review-list')->name('reviews.index');
    });
});

/*
|--------------------------------------------------------------------------
| Admin Routes (auth + verified + role:admin required)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/dashboard')->name('dashboard');
    Route::livewire('onboardings', 'admin.onboarding-queue')->name('onboardings.index');
    Route::livewire('onboardings/{onboarding}', 'admin.onboarding-review')->name('onboardings.show');
    Route::livewire('certifications', 'admin.certification-queue')->name('certifications.index');
    // Streams a certification application's uploaded document through the app.
    // Streaming through the app (not S3 presigned URLs) avoids MinIO signature
    // host mismatches between the internal endpoint and the public URL.
    Route::get('certifications/applications/{application}/document', function (CertificationApplication $application) {
        if ($application->document_path === null || ! Storage::disk('s3')->exists($application->document_path)) {
            abort(404);
        }

        $filename = basename($application->document_path);
        $disposition = request()->boolean('download') ? 'attachment' : 'inline';

        return Storage::disk('s3')->response(
            $application->document_path,
            $filename,
            ['Content-Disposition' => "{$disposition}; filename=\"{$filename}\""],
        );
    })->name('certifications.applications.document');

    Route::livewire('sourcing-requests', 'admin.sourcing-queue')->name('sourcing-requests.index');
    Route::livewire('sourcing-requests/{sourcingRequest}', 'admin.sourcing-request-detail')->name('sourcing-requests.show');
    // Streams a sourcing request's reference file through the app.
    // Streaming through the app (not S3 presigned URLs) avoids MinIO signature
    // host mismatches between the internal endpoint and the public URL.
    Route::get('sourcing-requests/{sourcingRequest}/document', function (SourcingRequest $sourcingRequest) {
        if ($sourcingRequest->reference_file_path === null || ! Storage::disk('s3')->exists($sourcingRequest->reference_file_path)) {
            abort(404);
        }

        $filename = basename($sourcingRequest->reference_file_path);
        $disposition = request()->boolean('download') ? 'attachment' : 'inline';

        return Storage::disk('s3')->response(
            $sourcingRequest->reference_file_path,
            $filename,
            ['Content-Disposition' => "{$disposition}; filename=\"{$filename}\""],
        );
    })->name('sourcing-requests.document');

    Route::livewire('subscriptions', 'admin.subscription-queue')->name('subscriptions.index');
    Route::livewire('categories', 'admin.category-manager')->name('categories.index');
    Route::livewire('categories/create', 'admin.category-create')->name('categories.create');
    Route::livewire('categories/{category}/edit', 'admin.category-edit')->name('categories.edit');

    // RFQ Management (admin)
    Route::middleware('feature:admin_rfq_management_enabled')->group(function () {
        Route::livewire('rfqs', 'admin.rfq.rfq-list')->name('rfqs.index');
        Route::livewire('rfqs/{rfq}', 'admin.rfq.rfq-detail')->name('rfqs.show');
    });

    // Disputes (admin)
    Route::middleware('feature:disputes_enabled')->group(function () {
        Route::livewire('disputes', 'admin.disputes.dispute-queue')->name('disputes.index');
        Route::livewire('disputes/{dispute}', 'admin.disputes.dispute-arbitration')->name('disputes.show');
    });

    // Payouts (admin)
    Route::middleware('feature:payouts_enabled')->group(function () {
        Route::livewire('payout-settings', 'admin.payout-settings')->name('payout-settings');
        Route::livewire('payout-accounts', 'admin.payout-accounts.payout-account-queue')->name('payout-accounts.index');
        Route::livewire('payouts', 'admin.payouts.payout-queue')->name('payouts.index');
        Route::livewire('payouts/eligible', 'admin.payouts.eligible-payouts')->name('payouts.eligible');
        Route::livewire('payouts/{payout}', 'admin.payouts.payout-detail')->name('payouts.show');
    });

    // Financial Ledger (admin) — disabled pending Phase 7
    // Route::livewire('ledger', 'admin.finance.financial-ledger')->name('ledger');

    // Analytics Dashboard
    Route::livewire('dashboard', 'admin.finance.analytics-dashboard')->name('dashboard.index');

    // Review Reports
    Route::middleware('feature:reviews_enabled')->group(function () {
        Route::livewire('review-reports', 'admin.reviews.review-report-queue')->name('review-reports.index');
    });

    // Admin Merchant & Product Management
    Route::livewire('merchants', 'admin.merchants.merchant-list')->name('merchants.index');
    Route::livewire('merchants/{shop}', 'admin.merchants.merchant-show')->name('merchants.show');
    Route::livewire('products/moderation', 'admin.products.product-moderation-list')->name('products.moderation');

    // Sponsored Content (M7)
    Route::middleware('feature:sponsored_content_enabled')->group(function () {
        Route::livewire('sponsored-content', 'admin.sponsored-content.sponsored-content-list')->name('sponsored-content.index');
        Route::livewire('sponsored-content/create', 'admin.sponsored-content.sponsored-content-composer')->name('sponsored-content.create');
        Route::livewire('sponsored-content/{sponsoredContent}/edit', 'admin.sponsored-content.sponsored-content-composer')->name('sponsored-content.edit');
    });
});

// Sponsored content click-tracking (public — no auth required)
Route::get('/ads/{sponsoredContent}/click', SponsoredContentClickController::class)
    ->name('sponsored-content.click');

/*
|--------------------------------------------------------------------------
| Payment Callback Routes (DLT redirects here after checkout)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'feature:orders_enabled'])->group(function () {
    Route::get('/orders/{order}/payment/success', [OrderPaymentCallbackController::class, 'success'])
        ->name('orders.payment.success');
    Route::get('/orders/{order}/payment/failure', [OrderPaymentCallbackController::class, 'failure'])
        ->name('orders.payment.failure');
});

Route::middleware(['auth', 'feature:subscriptions_enabled'])->group(function () {
    Route::get('/hub/subscription/{subscription}/payment/success', [SubscriptionPaymentCallbackController::class, 'success'])
        ->name('hub.subscriptions.payment.success');
    Route::get('/hub/subscription/{subscription}/payment/failure', [SubscriptionPaymentCallbackController::class, 'failure'])
        ->name('hub.subscriptions.payment.failure');
});

require __DIR__.'/settings.php';
