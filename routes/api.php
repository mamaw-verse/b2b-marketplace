<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AdminCategoryController;
use App\Http\Controllers\Api\V1\AdminOnboardingController;
use App\Http\Controllers\Api\V1\HubProductController;
use App\Http\Controllers\Api\V1\OnboardingController;
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
