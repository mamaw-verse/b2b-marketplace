<?php

declare(strict_types=1);

use App\Livewire\Admin\SourcingRequestDetail;
use App\Models\SourcingRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('s3');
});

test('admin can view sourcing request detail', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $request = SourcingRequest::factory()->create([
        'product_name' => 'Organic turmeric',
        'product_description' => 'High curcumin, 500kg needed.',
    ]);

    $this->actingAs($admin);

    Livewire::test(SourcingRequestDetail::class, ['sourcingRequest' => $request])
        ->assertSee('Sourcing Request')
        ->assertSee('Organic turmeric')
        ->assertSee('High curcumin, 500kg needed.')
        ->assertSee($request->name)
        ->assertSee($request->email);
});

test('admin can update status on detail page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $request = SourcingRequest::factory()->create(['status' => 'pending']);

    $this->actingAs($admin);

    Livewire::test(SourcingRequestDetail::class, ['sourcingRequest' => $request])
        ->set('status', 'in_progress')
        ->call('save')
        ->assertHasNoErrors();

    expect($request->fresh()->status)->toBe('in_progress');
});

test('admin can add notes on detail page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $request = SourcingRequest::factory()->create();

    $this->actingAs($admin);

    Livewire::test(SourcingRequestDetail::class, ['sourcingRequest' => $request])
        ->set('notes', 'Follow up with supplier')
        ->call('save')
        ->assertHasNoErrors();

    expect($request->fresh()->notes)->toBe('Follow up with supplier');
});

test('status validation rejects invalid values', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $request = SourcingRequest::factory()->create();

    $this->actingAs($admin);

    Livewire::test(SourcingRequestDetail::class, ['sourcingRequest' => $request])
        ->set('status', 'closed')
        ->call('save')
        ->assertHasErrors(['status']);
});

test('detail page shows reference file for image', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $request = SourcingRequest::factory()->create([
        'reference_file_path' => 'sourcing-requests/2026/09/09/ref.jpg',
    ]);

    $this->actingAs($admin);

    // Detail page shows file info with filename, type, and a Download button for all file types.
    Livewire::test(SourcingRequestDetail::class, ['sourcingRequest' => $request])
        ->assertSee('Reference File')
        ->assertSee('ref.jpg')
        ->assertSee('Download');
});

test('detail page shows file info for non-image', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $request = SourcingRequest::factory()->create([
        'reference_file_path' => 'sourcing-requests/2026/09/09/spec.pdf',
    ]);

    $this->actingAs($admin);

    Livewire::test(SourcingRequestDetail::class, ['sourcingRequest' => $request])
        ->assertSee('Reference File')
        ->assertSee('spec.pdf')
        ->assertSee('Download');
});

test('detail page hides reference file section when none uploaded', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $request = SourcingRequest::factory()->create([
        'reference_file_path' => null,
    ]);

    $this->actingAs($admin);

    Livewire::test(SourcingRequestDetail::class, ['sourcingRequest' => $request])
        ->assertDontSee('Reference File');
});
