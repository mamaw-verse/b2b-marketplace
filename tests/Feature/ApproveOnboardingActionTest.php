<?php

declare(strict_types=1);

use App\Actions\ApproveOnboardingAction;
use App\Models\Category;
use App\Models\Media;
use App\Models\ShopOnboarding;
use App\Models\ShopOnboardingCertificate;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function makeOnboardingFor(User $seller, array $overrides = []): ShopOnboarding
{
    return ShopOnboarding::create(array_merge([
        'user_id' => $seller->id,
        'registered_business_name' => 'Approve Co',
        'storefront_name' => 'Approve Shop',
        'business_structure' => 'corporation',
        'dti_sec_number' => 'SEC-AP',
        'tax_identification_number' => 'TIN-AP',
        'country_code' => 'PH',
        'export_readiness_status' => 'never_exported',
        'status' => 'submitted',
        'submitted_at' => now(),
    ], $overrides));
}

test('approval fails when a certificate is missing its uploaded file', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $onboarding = makeOnboardingFor($seller);

    ShopOnboardingCertificate::create([
        'onboarding_id' => $onboarding->id,
        'certification_body' => 'IDCP',
        'registration_number' => 'REG-1',
        'expiry_date' => now()->addYear()->format('Y-m-d'),
        'media_id' => null,
    ]);

    expect(fn () => app(ApproveOnboardingAction::class)->execute($onboarding->fresh(), $admin))
        ->toThrow(ValidationException::class);

    // Onboarding stays un-approved.
    expect($onboarding->fresh()->status)->toBe('submitted');
    $this->assertDatabaseCount('shops', 0);
});

test('approval syncs product categories onto the new shop via the pivot', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $catA = Category::factory()->create();
    $catB = Category::factory()->create();

    $onboarding = makeOnboardingFor($seller, [
        'product_categories' => "{$catA->id},{$catB->id}",
    ]);

    $media = Media::factory()->create(['uploaded_by_id' => $seller->id]);

    ShopOnboardingCertificate::create([
        'onboarding_id' => $onboarding->id,
        'certification_body' => 'IDCP',
        'registration_number' => 'REG-1',
        'expiry_date' => now()->addYear()->format('Y-m-d'),
        'media_id' => $media->id,
    ]);

    $shop = app(ApproveOnboardingAction::class)->execute($onboarding->fresh(), $admin);

    expect($shop->categories()->pluck('categories.id')->sort()->values()->all())
        ->toBe([$catA->id, $catB->id]);

    $this->assertDatabaseHas('shop_categories', ['shop_id' => $shop->id, 'category_id' => $catA->id]);
    $this->assertDatabaseHas('shop_categories', ['shop_id' => $shop->id, 'category_id' => $catB->id]);
});

test('approval succeeds and creates an active shop when certificates are complete', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $onboarding = makeOnboardingFor($seller);

    $media = Media::factory()->create(['uploaded_by_id' => $seller->id]);

    ShopOnboardingCertificate::create([
        'onboarding_id' => $onboarding->id,
        'certification_body' => 'IDCP',
        'registration_number' => 'REG-1',
        'expiry_date' => now()->addYear()->format('Y-m-d'),
        'media_id' => $media->id,
    ]);

    $shop = app(ApproveOnboardingAction::class)->execute($onboarding->fresh(), $admin);

    expect($shop->status)->toBe('active');
    expect($onboarding->fresh()->status)->toBe('approved');
});
