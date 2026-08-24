<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\PayoutSetting;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Payout Settings')]
final class PayoutSettings extends Component
{
    #[Validate('required|integer|min:1|max:365')]
    public string $dispute_hold_days = '';

    #[Validate('required|numeric|min:0.01|max:999999999999')]
    public string $minimum_payout_threshold = '';

    #[Validate('required|numeric|min:0|max:999999999999')]
    public string $transaction_fee_amount = '';

    #[Validate('required|numeric|min:0|max:1')]
    public string $commission_fee_rate = '';

    public ?string $lastUpdatedBy = null;

    public ?string $lastUpdatedAt = null;

    public function mount(): void
    {
        $this->loadSettings();
    }

    public function save(): void
    {
        $this->validate();

        $settings = [
            'dispute_hold_days' => $this->dispute_hold_days,
            'minimum_payout_threshold' => $this->minimum_payout_threshold,
            'transaction_fee_amount' => $this->transaction_fee_amount,
            'commission_fee_rate' => $this->commission_fee_rate,
        ];

        foreach ($settings as $key => $value) {
            PayoutSetting::where('key', $key)->update([
                'value' => $value,
                'updated_by_id' => auth()->id(),
            ]);
        }

        $this->loadSettings();

        Flux::toast('Payout settings saved successfully.');
    }

    public function render()
    {
        return view('livewire.admin.payout-settings');
    }

    private function loadSettings(): void
    {
        $settings = PayoutSetting::with('updatedBy')->get()->keyBy('key');

        $this->dispute_hold_days = $settings->get('dispute_hold_days')?->value ?? '7';
        $this->minimum_payout_threshold = $settings->get('minimum_payout_threshold')?->value ?? '500.00';
        $this->transaction_fee_amount = $settings->get('transaction_fee_amount')?->value ?? '15.00';
        $this->commission_fee_rate = $settings->get('commission_fee_rate')?->value ?? '0.03';

        // Find the most recently updated setting for "last updated" display
        $lastUpdated = $settings->sortByDesc('updated_at')->first();

        if ($lastUpdated && $lastUpdated->updatedBy) {
            $this->lastUpdatedBy = $lastUpdated->updatedBy->first_name.' '.$lastUpdated->updatedBy->last_name;
            $this->lastUpdatedAt = $lastUpdated->updated_at->diffForHumans();
        } else {
            $this->lastUpdatedBy = null;
            $this->lastUpdatedAt = null;
        }
    }
}
