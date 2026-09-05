<?php

declare(strict_types=1);

use App\Livewire\Shared\NotificationBell;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);
});

it('renders the notification bell for authenticated users', function () {
    Livewire::test(NotificationBell::class)
        ->assertOk()
        ->assertSee(__('Notifications'));
});

it('shows zero unread count when there are no notifications', function () {
    $component = Livewire::test(NotificationBell::class);

    expect($component->get('unreadCount'))->toBe(0);
    $component->assertDontSee('bg-[var(--hs-danger-600)]');
});

it('shows the unread badge when notifications exist', function () {
    $this->user->notifications()->create([
        'id' => Str::uuid(),
        'type' => 'App\Notifications\NewMessageNotification',
        'data' => ['type' => 'new_message', 'message' => 'Hello!'],
        'read_at' => null,
    ]);

    $component = Livewire::test(NotificationBell::class);

    expect($component->get('unreadCount'))->toBe(1);
});

it('marks a single notification as read', function () {
    $notifId = (string) Str::uuid();

    $this->user->notifications()->create([
        'id' => $notifId,
        'type' => 'App\Notifications\NewMessageNotification',
        'data' => ['type' => 'new_message', 'message' => 'Hello!'],
        'read_at' => null,
    ]);

    Livewire::test(NotificationBell::class)
        ->call('markRead', $notifId)
        ->assertHasNoErrors();

    expect($this->user->unreadNotifications()->count())->toBe(0);
});

it('marks all notifications as read', function () {
    foreach (range(1, 3) as $i) {
        $this->user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\NewMessageNotification',
            'data' => ['type' => 'new_message', 'message' => "Message {$i}"],
            'read_at' => null,
        ]);
    }

    Livewire::test(NotificationBell::class)
        ->assertSet('unreadCount', 3)
        ->call('markAllRead')
        ->assertHasNoErrors();

    expect($this->user->unreadNotifications()->count())->toBe(0);
});

it('refreshes counts when notification-received event is dispatched', function () {
    Livewire::test(NotificationBell::class)
        ->dispatch('notification-received', notification: ['type' => 'new_message'])
        ->assertHasNoErrors();
});
