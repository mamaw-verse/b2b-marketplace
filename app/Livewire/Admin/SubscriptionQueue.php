<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Subscriptions')]
final class SubscriptionQueue extends Component
{
    use WithPagination;

    public string $statusFilter = 'all';

    public string $planFilter = 'all';

    // Settings modal state
    public bool $showSettingsModal = false;

    /** @var array<int, array{id: int, name: string, currency: string, billing_period: string, price: string, trial_days: string, is_active: bool}> */
    public array $settingsPlans = [];

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPlanFilter(): void
    {
        $this->resetPage();
    }

    public function openSettings(): void
    {
        $this->loadSettingsPlans();
        $this->showSettingsModal = true;
    }

    public function saveSettings(): void
    {
        $rules = [];

        foreach ($this->settingsPlans as $index => $plan) {
            $rules["settingsPlans.{$index}.price"] = 'required|numeric|min:0|max:999999999999';
            $rules["settingsPlans.{$index}.trial_days"] = 'nullable|integer|min:0|max:9999';
            $rules["settingsPlans.{$index}.is_active"] = 'boolean';
        }

        $this->validate($rules);

        foreach ($this->settingsPlans as $planData) {
            SubscriptionPlan::where('id', $planData['id'])->update([
                'price' => $planData['price'],
                'trial_days' => $planData['trial_days'] !== '' ? (int) $planData['trial_days'] : null,
                'is_active' => (bool) $planData['is_active'],
            ]);
        }

        $this->showSettingsModal = false;
        $this->loadSettingsPlans();

        Flux::toast('Subscription settings saved.');
    }

    /**
     * @return LengthAwarePaginator<Subscription>
     */
    #[Computed]
    public function subscriptions(): LengthAwarePaginator
    {
        return Subscription::query()
            ->with(['shop.user', 'plan'])
            ->whereHas('shop', fn ($shopQuery) => $shopQuery->where('country_code', config('marketplace.instance_country')))
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->planFilter !== 'all', fn ($query) => $query->where('subscription_plan_id', (int) $this->planFilter))
            ->latest('created_at')
            ->paginate(15);
    }

    /**
     * @return \Illuminate\Support\Collection<int, SubscriptionPlan>
     */
    #[Computed]
    public function plans(): \Illuminate\Support\Collection
    {
        return SubscriptionPlan::query()
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.subscription-queue');
    }

    private function loadSettingsPlans(): void
    {
        $this->settingsPlans = SubscriptionPlan::orderBy('id')
            ->get()
            ->map(fn (SubscriptionPlan $plan): array => [
                'id' => $plan->id,
                'name' => $plan->name,
                'currency' => $plan->currency,
                'billing_period' => $plan->billing_period,
                'price' => (string) $plan->price,
                'trial_days' => $plan->trial_days !== null ? (string) $plan->trial_days : '',
                'is_active' => $plan->is_active,
            ])
            ->all();
    }
}
