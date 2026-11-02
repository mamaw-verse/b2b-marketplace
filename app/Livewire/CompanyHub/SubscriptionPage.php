<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub;

use App\Events\SubscriptionStatusChanged;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Subscription')]
final class SubscriptionPage extends Component
{
    public function mount(): void
    {
        $this->ensureActiveShop();
    }

    public function subscribe(int $planId): void
    {
        $shop = $this->ensureActiveShop();
        $plan = SubscriptionPlan::query()
            ->where('is_active', true)
            ->findOrFail($planId);

        if ($shop->subscriptions()->where('status', 'pending_payment')->exists()) {
            Flux::toast(__('You already have a subscription payment in progress.'), variant: 'warning');

            return;
        }

        if ($this->shouldStartTrial($shop, $plan)) {
            $trialDays = (int) $plan->trial_days;

            $subscription = Subscription::create([
                'shop_id' => $shop->id,
                'subscription_plan_id' => $plan->id,
                'status' => 'trialing',
                'trial_ends_at' => now()->addDays($trialDays),
                'current_period_start' => now(),
                'current_period_end' => now()->addDays($trialDays),
            ]);

            event(new SubscriptionStatusChanged($subscription));

            Flux::toast(__('Your free trial is now active.'), variant: 'success');

            return;
        }

        // Free plan — activate directly without a payment gateway
        if ((float) $plan->price <= 0) {
            $subscription = Subscription::create([
                'shop_id' => $shop->id,
                'subscription_plan_id' => $plan->id,
                'status' => 'active',
                'current_period_start' => now(),
                'current_period_end' => now()->addYear(),
            ]);

            event(new SubscriptionStatusChanged($subscription));

            Flux::toast(__('Your subscription is now active.'), variant: 'success');

            return;
        }

        $subscription = DB::transaction(function () use ($shop, $plan): Subscription {
            return Subscription::create([
                'shop_id' => $shop->id,
                'subscription_plan_id' => $plan->id,
                'status' => 'pending_payment',
            ]);
        });

        $paymentRoute = config('marketplace.payment_method') === 'manual'
            ? route('hub.subscription.pay.manual', ['subscription' => $subscription->id])
            : route('hub.subscription.pay', ['subscription' => $subscription->id]);

        $this->redirect($paymentRoute, navigate: true);
    }

    public function dismissPendingPayment(): void
    {
        $shop = $this->ensureActiveShop();

        $shop->subscriptions()
            ->where('status', 'pending_payment')
            ->get()
            ->each(function (Subscription $subscription): void {
                $subscription->update(['status' => 'cancelled']);
                event(new SubscriptionStatusChanged($subscription->fresh()));
            });
    }

    public function resumePayment(): void
    {
        $shop = $this->ensureActiveShop();

        $pendingSubscription = $shop->subscriptions()
            ->where('status', 'pending_payment')
            ->latest('created_at')
            ->first();

        if (! $pendingSubscription) {
            $this->message = null;

            return;
        }

        $paymentRoute = config('marketplace.payment_method') === 'manual'
            ? route('hub.subscription.pay.manual', ['subscription' => $pendingSubscription->id])
            : route('hub.subscription.pay', ['subscription' => $pendingSubscription->id]);

        $this->redirect($paymentRoute, navigate: true);
    }

    #[Computed]
    public function plans(): Collection
    {
        return SubscriptionPlan::query()
            ->where('is_active', true)
            ->orderBy('price')
            ->get();
    }

    #[Computed]
    public function currentSubscription(): ?Subscription
    {
        return $this->ensureActiveShop()
            ->subscriptions()
            ->with('plan')
            ->latest('created_at')
            ->first();
    }

    #[Computed]
    public function pendingSubscription(): ?Subscription
    {
        return $this->ensureActiveShop()
            ->subscriptions()
            ->where('status', 'pending_payment')
            ->latest('created_at')
            ->first();
    }

    /**
     * Days remaining in the current trial, or null if not trialing.
     */
    #[Computed]
    public function trialDaysRemaining(): ?int
    {
        $sub = $this->currentSubscription;

        if (! $sub || $sub->status !== 'trialing' || ! $sub->trial_ends_at) {
            return null;
        }

        return (int) max(0, now()->diffInDays($sub->trial_ends_at, false));
    }

    #[Computed]
    public function shop(): Shop
    {
        return $this->ensureActiveShop();
    }

    public function render()
    {
        return view('livewire.company-hub.subscription-page');
    }

    public function canStartTrial(int $planId): bool
    {
        $plan = $this->plans->firstWhere('id', $planId);

        return $plan !== null && $this->shouldStartTrial($this->ensureActiveShop(), $plan);
    }

    private function ensureActiveShop(): Shop
    {
        $shop = Auth::user()?->shop;

        abort_unless($shop && $shop->status === 'active', 403);

        return $shop;
    }

    private function shouldStartTrial(Shop $shop, SubscriptionPlan $plan): bool
    {
        // A cancelled subscription means the shop never actually activated — they
        // abandoned or cancelled before any payment was processed. Don't count
        // those against trial eligibility; only non-cancelled records disqualify.
        $hasPreviousNonCancelledSubscription = $shop->subscriptions()
            ->whereNotIn('status', ['cancelled'])
            ->exists();

        // The plan's trial_days field is the sole source of truth.
        // No country or payment-method restrictions — if the admin configures a
        // trial on the plan, every newly-approved seller gets it.
        return (int) $plan->trial_days > 0
            && ! $hasPreviousNonCancelledSubscription;
    }
}
