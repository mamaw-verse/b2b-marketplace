<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SellerPayoutAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PayoutAccountRejectedNotification extends Notification implements ShouldQueue
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
        return (new MailMessage)
            ->subject(__('Payout Account Verification Failed'))
            ->line(__('Your bank account could not be verified. Reason: :reason. Please update your details and resubmit.', ['reason' => $this->account->rejection_reason]))
            ->action(__('Update Payout Account'), url('/hub/payout-account'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payout_account_rejected',
            'payout_account_id' => $this->account->id,
            'rejection_reason' => $this->account->rejection_reason,
            'message' => "Your bank account could not be verified. Reason: {$this->account->rejection_reason}.",
            'url' => url('/hub/payout-account'),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
