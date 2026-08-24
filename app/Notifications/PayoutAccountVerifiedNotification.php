<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SellerPayoutAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PayoutAccountVerifiedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SellerPayoutAccount $account,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $last4 = mb_substr($this->account->account_number, -4);

        return (new MailMessage)
            ->subject('Your Payout Account Has Been Verified')
            ->line("Your bank account ({$this->account->bank_name} ending in ****{$last4}) has been verified. You're now eligible to receive payouts.")
            ->action('View Payout Account', url('/hub/payout-account'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $last4 = mb_substr($this->account->account_number, -4);

        return [
            'type' => 'payout_account_verified',
            'payout_account_id' => $this->account->id,
            'bank_name' => $this->account->bank_name,
            'message' => "Your bank account ({$this->account->bank_name} ending in ****{$last4}) has been verified.",
            'url' => url('/hub/payout-account'),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
