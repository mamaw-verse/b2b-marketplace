<?php

declare(strict_types=1);

use App\Livewire\Admin\SourcingQueue;
use App\Models\SourcingRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('s3');
});

test('admin can view sourcing requests', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $request = SourcingRequest::factory()->create();

    $this->actingAs($admin);

    Livewire::test(SourcingQueue::class)
        ->assertSee('Product Sourcing Requests')
        ->assertSee($request->name)
        ->assertSee($request->product_name);
});

test('admin can filter sourcing requests by status', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $pending = SourcingRequest::factory()->create(['status' => 'pending']);
    $inProgress = SourcingRequest::factory()->inProgress()->create();

    $this->actingAs($admin);

    Livewire::test(SourcingQueue::class)
        ->assertSee($pending->name)
        ->assertSee($inProgress->name)
        ->set('statusFilter', 'in_progress')
        ->assertSee($inProgress->name)
        ->assertDontSee($pending->name);
});

test('admin can search sourcing requests', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $request = SourcingRequest::factory()->create(['name' => 'Unique Buyer Search']);
    $other = SourcingRequest::factory()->create(['name' => 'Other Person']);

    $this->actingAs($admin);

    Livewire::test(SourcingQueue::class)
        ->set('search', 'Unique Buyer')
        ->assertSee($request->name)
        ->assertDontSee($other->name);
});

test('previewable reference files show a preview button', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    SourcingRequest::factory()->create([
        'reference_file_path' => 'sourcing-requests/2026/09/09/reference.pdf',
    ]);

    $this->actingAs($admin);

    Livewire::test(SourcingQueue::class)
        ->assertSee('Preview')
        ->assertDontSee('>Download<');
});

test('non-previewable reference files show a download button', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $request = SourcingRequest::factory()->create([
        'reference_file_path' => 'sourcing-requests/2026/09/09/reference.docx',
    ]);
    Storage::disk('s3')->put($request->reference_file_path, 'docx contents');

    $this->actingAs($admin);

    Livewire::test(SourcingQueue::class)
        ->assertSee('Download')
        ->assertDontSee('>Preview<');
});

test('queue links to detail page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $request = SourcingRequest::factory()->create();

    $this->actingAs($admin);

    Livewire::test(SourcingQueue::class)
        ->assertSee(route('admin.sourcing-requests.show', $request));
});
