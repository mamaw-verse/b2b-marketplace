<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Payout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PayoutFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Payout $payout,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $storefrontName = $this->payout->shop->storefront_name;

        return (new MailMessage)
            ->subject(__('Payout Failed — Requires Attention'))
            ->line(__("Payout #:id for shop ':storefront' failed. Reason: :reason. Please investigate and retry.", ['id' => $this->payout->id, 'storefront' => $storefrontName, 'reason' => $this->payout->failure_reason]))
            ->action(__('Review Payout'), url("/admin/payouts/{$this->payout->id}"));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $storefrontName = $this->payout->shop->storefront_name;

        return [
            'type' => 'payout_failed',
            'payout_id' => $this->payout->id,
            'shop_id' => $this->payout->shop_id,
            'storefront_name' => $storefrontName,
            'failure_reason' => $this->payout->failure_reason,
            'message' => "Payout #{$this->payout->id} for shop '{$storefrontName}' failed. Reason: {$this->payout->failure_reason}.",
            'url' => url("/admin/payouts/{$this->payout->id}"),
        ];
    }
}
