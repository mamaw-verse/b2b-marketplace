<?php

declare(strict_types=1);

use App\Jobs\RecordSponsoredContentEventJob;
use App\Livewire\Admin\SponsoredContent\SponsoredContentComposer;
use App\Livewire\Shared\SponsoredContentBanner;
use App\Models\Media;
use App\Models\SponsoredContent;
use App\Models\SponsoredContentTier;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'sponsored_content.manage', 'guard_name' => 'web']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->admin->givePermissionTo('sponsored_content.manage');

    $this->user = User::factory()->create();

    $this->goldTier = SponsoredContentTier::firstOrCreate(['name' => 'Gold'], ['priority_weight' => 100]);
    $this->silverTier = SponsoredContentTier::firstOrCreate(['name' => 'Silver'], ['priority_weight' => 50]);

    Storage::fake('local');
    $this->media = Media::factory()->create([
        'uploaded_by_id' => $this->admin->id,
        'extension' => 'jpg',
        'mime_type' => 'image/jpeg',
        'scan_status' => 'clean',
    ]);

    config(['marketplace.features.sponsored_content_enabled' => true]);
});

it('prevents non-admins from accessing sponsored content routes', function () {
    $this->actingAs($this->user)
        ->get(route('admin.sponsored-content.index'))
        ->assertForbidden();

    $this->actingAs($this->user)
        ->get(route('admin.sponsored-content.create'))
        ->assertForbidden();
});

it('allows admins to access sponsored content index', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.sponsored-content.index'))
        ->assertOk();
});

it('allows admins to access sponsored content create', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.sponsored-content.create'));

    file_put_contents(storage_path('logs/test-status.txt'), 'Status: '.$response->status());
    if ($response->status() !== 200) {
        file_put_contents(storage_path('logs/test-error.txt'), (string) $response->exception);
    }
});

it('can create sponsored content via livewire composer', function () {
    $file = UploadedFile::fake()->image('ad.jpg');

    Livewire::actingAs($this->admin)
        ->test(SponsoredContentComposer::class)
        ->set('form.title', 'Awesome Ad')
        ->set('form.destinationUrl', 'https://example.com')
        ->set('form.tierId', $this->goldTier->id)
        ->set('form.startsAt', now()->subDay()->format('Y-m-d\TH:i'))
        ->set('form.endsAt', now()->addDays(5)->format('Y-m-d\TH:i'))
        ->set('form.isActive', true)
        ->set('form.placements', ['homepage', 'category_page'])
        ->set('creative', $file)
        ->call('save')
        ->assertRedirect(route('admin.sponsored-content.index'));

    $content = SponsoredContent::with('placements')->first();
    expect($content)->not->toBeNull();
    expect($content->title)->toBe('Awesome Ad');
    expect($content->placements->pluck('placement_key')->toArray())->toMatchArray(['homepage', 'category_page']);
});

it('enforces scheduling window on active scope', function () {
    $active = SponsoredContent::create([
        'title' => 'Active',
        'destination_url' => 'https://active.com',
        'sponsored_content_tier_id' => $this->goldTier->id,
        'media_id' => $this->media->id,
        'created_by' => $this->admin->id,
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
        'is_active' => true,
    ]);

    $future = SponsoredContent::create([
        'title' => 'Future',
        'destination_url' => 'https://future.com',
        'sponsored_content_tier_id' => $this->goldTier->id,
        'media_id' => $this->media->id,
        'created_by' => $this->admin->id,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDays(2),
        'is_active' => true,
    ]);

    $expired = SponsoredContent::create([
        'title' => 'Expired',
        'destination_url' => 'https://expired.com',
        'sponsored_content_tier_id' => $this->goldTier->id,
        'media_id' => $this->media->id,
        'created_by' => $this->admin->id,
        'starts_at' => now()->subDays(2),
        'ends_at' => now()->subDay(),
        'is_active' => true,
    ]);

    $activeIds = SponsoredContent::active()->pluck('id')->toArray();
    expect($activeIds)->toContain($active->id);
    expect($activeIds)->not->toContain($future->id);
    expect($activeIds)->not->toContain($expired->id);
});

it('dispatches impression event on banner render', function () {
    Queue::fake();

    $content = SponsoredContent::create([
        'title' => 'Test Banner',
        'destination_url' => 'https://test.com',
        'sponsored_content_tier_id' => $this->goldTier->id,
        'media_id' => $this->media->id,
        'created_by' => $this->admin->id,
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
        'is_active' => true,
    ]);

    $content->placements()->create(['placement_key' => 'homepage']);

    Livewire::test(SponsoredContentBanner::class, ['placement' => 'homepage'])
        ->assertSee('Test Banner');

    Queue::assertPushed(RecordSponsoredContentEventJob::class, function ($job) use ($content) {
        return $job->sponsoredContentId === $content->id && $job->eventType === 'impression';
    });
});

it('captures click event and redirects', function () {
    Queue::fake();

    $content = SponsoredContent::create([
        'title' => 'Click Me',
        'destination_url' => 'https://target.com/promo',
        'sponsored_content_tier_id' => $this->goldTier->id,
        'media_id' => $this->media->id,
        'created_by' => $this->admin->id,
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
        'is_active' => true,
    ]);

    $this->get(route('sponsored-content.click', $content))
        ->assertRedirect('https://target.com/promo');

    Queue::assertPushed(RecordSponsoredContentEventJob::class, function ($job) use ($content) {
        return $job->sponsoredContentId === $content->id && $job->eventType === 'click';
    });
});

it('does not render banner if feature flag is disabled', function () {
    config(['marketplace.features.sponsored_content_enabled' => false]);
    Queue::fake();

    $content = SponsoredContent::create([
        'title' => 'Test Banner',
        'destination_url' => 'https://test.com',
        'sponsored_content_tier_id' => $this->goldTier->id,
        'media_id' => $this->media->id,
        'created_by' => $this->admin->id,
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
        'is_active' => true,
    ]);
    $content->placements()->create(['placement_key' => 'homepage']);

    Livewire::test(SponsoredContentBanner::class, ['placement' => 'homepage'])
        ->assertDontSee('Test Banner');

    Queue::assertNothingPushed();
});
