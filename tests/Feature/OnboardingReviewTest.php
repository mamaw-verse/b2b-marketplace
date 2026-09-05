<?php

declare(strict_types=1);

use App\Livewire\Admin\OnboardingReview;
use App\Models\Category;
use App\Models\Media;
use App\Models\ShopOnboarding;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $this->onboarding = ShopOnboarding::create([
        'user_id' => $seller->id,
        'registered_business_name' => 'Review Co',
        'storefront_name' => 'Review Shop',
        'business_structure' => 'corporation',
        'dti_sec_number' => 'SEC-R',
        'tax_identification_number' => 'TIN-R',
        'country_code' => 'PH',
        'export_readiness_status' => 'never_exported',
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $this->actingAs($this->admin);
});

test('the review page renders the business details and document sections', function () {
    Livewire::test(OnboardingReview::class, ['onboarding' => $this->onboarding])
        ->assertOk()
        ->assertSee('Business Information')
        ->assertSee('Halal Certificates')
        ->assertSee('Uploaded Documents')
        ->assertSee('Review Co');
});

test('the document viewer exposes an open in new tab link to the signed url', function () {
    $document = Media::factory()->create([
        'original_filename' => 'business-permit.pdf',
        'extension' => 'pdf',
        'mime_type' => 'application/pdf',
    ]);

    $this->onboarding->documents()->attach($document->id);

    Livewire::test(OnboardingReview::class, ['onboarding' => $this->onboarding->fresh()])
        ->assertDontSee('Open in new tab')
        ->call('viewDocument', $document->id)
        ->assertSet('showDocumentViewer', true)
        ->assertSee('Open in new tab')
        ->assertSee('business-permit.pdf')
        ->assertSeeHtml(route('media.view', $document))
        ->assertSeeHtml('target="_blank"');
});

test('closing the document viewer clears the preview state', function () {
    $document = Media::factory()->create();

    $this->onboarding->documents()->attach($document->id);

    Livewire::test(OnboardingReview::class, ['onboarding' => $this->onboarding->fresh()])
        ->call('viewDocument', $document->id)
        ->call('closeDocumentViewer')
        ->assertSet('showDocumentViewer', false)
        ->assertSet('documentPreviewUrl', null)
        ->assertSet('viewingMediaId', null)
        ->assertDontSee('Open in new tab');
});

test('product category ids are resolved to their category names', function () {
    $bakery = Category::factory()->create(['name' => 'Bakery']);
    $beverages = Category::factory()->create(['name' => 'Beverages']);

    $this->onboarding->update(['product_categories' => "{$bakery->id},{$beverages->id}"]);

    Livewire::test(OnboardingReview::class, ['onboarding' => $this->onboarding->fresh()])
        ->assertSee('Bakery')
        ->assertSee('Beverages')
        ->assertDontSee("{$bakery->id},{$beverages->id}");
});

test('product categories section shows a placeholder when none are set', function () {
    $this->onboarding->update(['product_categories' => null]);

    Livewire::test(OnboardingReview::class, ['onboarding' => $this->onboarding->fresh()])
        ->assertSee('—');
});
