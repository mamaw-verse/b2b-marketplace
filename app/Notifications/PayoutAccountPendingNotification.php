<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SellerPayoutAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PayoutAccountPendingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SellerPayoutAccount $account,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $storefrontName = $this->account->shop->storefront_name;

        return (new MailMessage)
            ->subject(__('New Payout Account Pending Verification'))
            ->line(__("Seller ':storefront' has submitted a payout account for verification.", ['storefront' => $storefrontName]))
            ->action(__('Review Payout Accounts'), url('/admin/payout-accounts'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $storefrontName = $this->account->shop->storefront_name;

        return [
            'type' => 'payout_account_pending',
            'payout_account_id' => $this->account->id,
            'shop_id' => $this->account->shop_id,
            'storefront_name' => $storefrontName,
            'message' => "Seller '{$storefrontName}' has submitted a payout account for verification.",
            'url' => url('/admin/payout-accounts'),
        ];
    }
}
