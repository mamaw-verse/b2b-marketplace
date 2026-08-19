<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ShopOnboarding;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class OnboardingStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ShopOnboarding $onboarding,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->onboarding->user_id),
        ];
    }

    /**
     * The event's broadcast data.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'onboarding_id' => $this->onboarding->id,
            'status' => $this->onboarding->status,
            'admin_notes' => $this->onboarding->admin_notes,
            'reviewed_at' => $this->onboarding->reviewed_at?->toIso8601String(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'onboarding.status.changed';
    }
}
