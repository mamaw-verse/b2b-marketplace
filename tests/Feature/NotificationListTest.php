<?php

declare(strict_types=1);

use App\Livewire\Shared\NotificationList;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create(['email_verified_at' => now()]);
});

function makeNotification(User $user, string $message, ?string $readAt = null): void
{
    $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\GenericNotification',
        'data' => ['type' => 'info', 'message' => $message],
        'read_at' => $readAt,
    ]);
}

test('the notifications index route renders the full list', function () {
    makeNotification($this->user, 'A long notification message that must not be truncated at all on the full page.');

    $this->actingAs($this->user)
        ->get(route('account.notifications.index'))
        ->assertOk()
        ->assertSee('A long notification message that must not be truncated at all on the full page.');
});

test('the notification list paginates at 25 per page', function () {
    foreach (range(1, 30) as $i) {
        makeNotification($this->user, "Notification number {$i}");
    }

    $component = Livewire::actingAs($this->user)
        ->test(NotificationList::class)
        ->assertOk();

    // 30 total, 25 per page → page 1 has exactly 25 items and pagination links show.
    expect($component->viewData('notifications')->count())->toBe(25)
        ->and($component->viewData('notifications')->total())->toBe(30)
        ->and($component->viewData('notifications')->perPage())->toBe(25);
});

test('markAllRead clears unread notifications', function () {
    makeNotification($this->user, 'Unread one');
    makeNotification($this->user, 'Unread two');

    expect($this->user->unreadNotifications()->count())->toBe(2);

    Livewire::actingAs($this->user)
        ->test(NotificationList::class)
        ->call('markAllRead');

    expect($this->user->fresh()->unreadNotifications()->count())->toBe(0);
});

test('the notification bell links to the full notifications page', function () {
    $this->actingAs($this->user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee(route('account.notifications.index'), false)
        ->assertSee('View all notifications');
});
