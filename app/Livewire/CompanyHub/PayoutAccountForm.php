<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub;

use App\Http\Requests\UpsertPayoutAccountRequest;
use App\Models\User;
use App\Notifications\PayoutAccountPendingNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Payout Account')]
final class PayoutAccountForm extends Component
{
    public string $bank_name = '';

    public string $bank_code = '';

    public string $account_holder_name = '';

    public string $account_number = '';

    public string $branch = '';

    public ?string $verification_status = null;

    public ?string $rejection_reason = null;

    public ?string $verified_at = null;

    public bool $hasExistingAccount = false;

    public function mount(): void
    {
        $shop = Auth::user()->shop;

        if (! $shop || $shop->status !== 'active') {
            return;
        }

        $account = $shop->payoutAccount;

        if ($account) {
            $this->hasExistingAccount = true;
            $this->bank_name = $account->bank_name;
            $this->bank_code = $account->bank_code;
            $this->account_holder_name = $account->account_holder_name;
            $this->account_number = $account->account_number;
            $this->branch = $account->branch ?? '';
            $this->verification_status = $account->verification_status;
            $this->rejection_reason = $account->rejection_reason;
            $this->verified_at = $account->verified_at?->format('M d, Y');
        }
    }

    public function save(): void
    {
        $this->validate([
            'bank_name' => ['required', 'string', 'max:150', 'in:'.implode(',', UpsertPayoutAccountRequest::philippineBanks())],
            'bank_code' => ['required', 'string', 'max:30'],
            'account_holder_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:50'],
            'branch' => ['nullable', 'string', 'max:150'],
        ]);

        $shop = Auth::user()->shop;

        $account = $shop->payoutAccount()->updateOrCreate(
            ['shop_id' => $shop->id],
            [
                'bank_name' => $this->bank_name,
                'bank_code' => $this->bank_code,
                'account_holder_name' => $this->account_holder_name,
                'account_number' => $this->account_number,
                'branch' => $this->branch ?: null,
                'verification_status' => 'pending',
                'verified_at' => null,
                'verified_by_id' => null,
                'rejection_reason' => null,
            ],
        );

        $this->hasExistingAccount = true;
        $this->verification_status = 'pending';
        $this->rejection_reason = null;
        $this->verified_at = null;

        // Notify all admins about the pending payout account
        $admins = User::role('admin')->get();
        Notification::send($admins, new PayoutAccountPendingNotification($account));

        session()->flash('success', 'Payout account saved successfully. It is now pending verification.');
    }

    /**
     * @return array<int, string>
     */
    public function getBankListProperty(): array
    {
        return UpsertPayoutAccountRequest::philippineBanks();
    }

    public function render()
    {
        return view('livewire.company-hub.payout-account-form');
    }
}
