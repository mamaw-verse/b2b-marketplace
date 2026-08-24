<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Payout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PayoutCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Payout $payout,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $account = $this->payout->shop->payoutAccount;
        $last4 = $account ? mb_substr($account->account_number, -4) : '****';
        $bankName = $account?->bank_name ?? 'your bank account';
        $amount = number_format((float) $this->payout->net_amount, 2);

        return (new MailMessage)
            ->subject("Payout Completed — ₱{$amount} Disbursed")
            ->line("Your payout of ₱{$amount} has been successfully disbursed to your bank account ({$bankName} ending in ****{$last4}).")
            ->action('View Payout Details', url("/hub/payouts/{$this->payout->id}"));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $amount = number_format((float) $this->payout->net_amount, 2);

        return [
            'type' => 'payout_completed',
            'payout_id' => $this->payout->id,
            'net_amount' => $this->payout->net_amount,
            'currency' => $this->payout->currency,
            'message' => "Your payout of ₱{$amount} has been successfully disbursed.",
            'url' => url("/hub/payouts/{$this->payout->id}"),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
