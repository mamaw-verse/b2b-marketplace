<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AdminCategoryController;
use App\Http\Controllers\Api\V1\AdminOnboardingController;
use App\Http\Controllers\Api\V1\BuyerProfileController;
use App\Http\Controllers\Api\V1\ConversationController;
use App\Http\Controllers\Api\V1\Hub\QuotationController;
use App\Http\Controllers\Api\V1\Hub\RfqRecipientController;
use App\Http\Controllers\Api\V1\HubProductController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\OnboardingController;
use App\Http\Controllers\Api\V1\QuotationActionController;
use App\Http\Controllers\Api\V1\RfqController;
use App\Http\Controllers\Api\V1\SearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — v1
|--------------------------------------------------------------------------
|
| All API endpoints versioned under /api/v1 per design.md §3.1.
| Auth: Laravel Sanctum (session cookies for browser, PATs for external).
|
*/

Route::prefix('v1')->group(function () {

    // Company Onboarding (requires auth + verified email)
    Route::middleware(['auth:sanctum', 'verified'])->group(function () {
        // Buyer Profile
        Route::get('/buyer-profile', [BuyerProfileController::class, 'show']);
        Route::put('/buyer-profile', [BuyerProfileController::class, 'update']);

        // RFQs (Buyer)
        Route::post('/rfqs', [RfqController::class, 'store'])
            ->middleware('throttle:10,60');
        Route::get('/rfqs', [RfqController::class, 'index']);
        Route::get('/rfqs/{rfq}', [RfqController::class, 'show']);
        Route::post('/rfqs/{rfq}/close', [RfqController::class, 'close']);

        // Quotation Actions (Buyer)
        Route::post('/quotations/{quotation}/award', [QuotationActionController::class, 'award']);
        Route::post('/quotations/{quotation}/reject', [QuotationActionController::class, 'reject']);

        // Chat / Messaging
        Route::get('/conversations', [ConversationController::class, 'index']);
        Route::get('/conversations/{conversation}/threads', [ConversationController::class, 'threads']);
        Route::post('/conversations/{conversation}/threads', [ConversationController::class, 'storeThread']);
        Route::get('/threads/{thread}/messages', [MessageController::class, 'index'])
            ->where('thread', '[0-9]+');
        Route::post('/threads/{thread}/messages', [MessageController::class, 'store'])
            ->middleware('throttle:60,1')
            ->where('thread', '[0-9]+');

        Route::get('/onboarding', [OnboardingController::class, 'show']);
        Route::put('/onboarding/step/{step}', [OnboardingController::class, 'updateStep'])
            ->whereIn('step', ['1', '2', '3', '4', '5']);
        Route::post('/onboarding/certificates', [OnboardingController::class, 'storeCertificate']);
        Route::delete('/onboarding/certificates/{certificate}', [OnboardingController::class, 'destroyCertificate']);
        Route::post('/onboarding/documents', [OnboardingController::class, 'storeDocument']);
        Route::post('/onboarding/submit', [OnboardingController::class, 'submit']);
    });

    // Company Hub — Product Catalog (requires auth + verified email)
    Route::middleware(['auth:sanctum', 'verified'])->prefix('hub')->group(function () {
        // Seller RFQ Queue
        Route::get('/rfqs', [RfqRecipientController::class, 'index']);
        Route::get('/rfqs/{rfqRecipient}', [RfqRecipientController::class, 'show']);
        Route::post('/rfqs/{rfqRecipient}/review', [RfqRecipientController::class, 'review']);
        Route::post('/rfqs/{rfqRecipient}/in-progress', [RfqRecipientController::class, 'inProgress']);
        Route::post('/rfqs/{rfqRecipient}/decline', [RfqRecipientController::class, 'decline']);

        // Quotations (Seller)
        Route::post('/rfqs/{rfqRecipient}/quotations', [QuotationController::class, 'store'])
            ->middleware('throttle:20,60');
        Route::put('/quotations/{quotation}', [QuotationController::class, 'update']);
        Route::delete('/quotations/{quotation}', [QuotationController::class, 'destroy']);
        Route::post('/quotations/{quotation}/submit', [QuotationController::class, 'submit']);
        Route::post('/quotations/{quotation}/withdraw', [QuotationController::class, 'withdraw']);

        Route::get('/products', [HubProductController::class, 'index']);
        Route::post('/products', [HubProductController::class, 'store']);
        Route::put('/products/{product}', [HubProductController::class, 'update']);
        Route::post('/products/{product}/publish', [HubProductController::class, 'publish']);
        Route::post('/products/{product}/variants', [HubProductController::class, 'storeVariant']);
        Route::put('/products/{product}/variants/{variant}', [HubProductController::class, 'updateVariant']);
        Route::post('/products/{product}/gallery', [HubProductController::class, 'storeGallery']);
        Route::post('/products/{product}/documents', [HubProductController::class, 'storeDocument']);
    });

    // Admin Review Console (requires auth + onboarding.review permission)
    Route::middleware(['auth:sanctum', 'can:onboarding.review'])->prefix('admin')->group(function () {
        Route::get('/onboardings', [AdminOnboardingController::class, 'index']);
        Route::get('/onboardings/{onboarding}', [AdminOnboardingController::class, 'show']);
        Route::post('/onboardings/{onboarding}/approve', [AdminOnboardingController::class, 'approve']);
        Route::post('/onboardings/{onboarding}/request-revision', [AdminOnboardingController::class, 'requestRevision']);
        Route::post('/onboardings/{onboarding}/reject', [AdminOnboardingController::class, 'reject']);
    });

    // Admin Category Management (requires auth + category.manage permission)
    Route::middleware(['auth:sanctum', 'can:category.manage'])->prefix('admin')->group(function () {
        Route::get('/categories', [AdminCategoryController::class, 'index']);
        Route::post('/categories', [AdminCategoryController::class, 'store']);
        Route::put('/categories/{category}', [AdminCategoryController::class, 'update']);
        Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy']);
    });

    // Public Search & Discovery (no auth required)
    Route::middleware(['throttle:search-limiter'])->group(function () {
        Route::get('/search/companies', [SearchController::class, 'companies']);
        Route::get('/search/products', [SearchController::class, 'products']);
        Route::get('/companies/{shop}', [SearchController::class, 'showCompany']);
        Route::get('/products/{product}', [SearchController::class, 'showProduct']);
        Route::get('/categories', [SearchController::class, 'categories']);
    });

});
