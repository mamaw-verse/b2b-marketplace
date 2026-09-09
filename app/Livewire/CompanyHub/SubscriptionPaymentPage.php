<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub;

use App\Events\SubscriptionStatusChanged;
use App\Models\PaymentAttempt;
use App\Models\Subscription;
use App\Services\DltPaymentService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Pay Subscription')]
final class SubscriptionPaymentPage extends Component
{
    #[Locked]
    public int $subscriptionId;

    /** @var string brands|payment_form|processing|success|pending|failed */
    public string $step = 'brands';

    public bool $loadingBrands = true;

    public ?string $errorMessage = null;

    /** @var array<int, array{value: string, code: string, image: string|null, is_card: bool}> */
    public array $brands = [];

    /** @var array<int, array{value: string, code: string, image: string|null, is_card: bool}> */
    public array $cardBrands = [];

    /** @var array<int, array{value: string, code: string, image: string|null, is_card: bool}> */
    public array $ewalletBrands = [];

    /** @var array{value: string, code: string, image: string|null, is_card: bool}|null */
    public ?array $selectedBrand = null;

    // Payor / billing fields
    public string $firstName = '';

    public string $lastName = '';

    public string $email = '';

    public string $phone = '';

    public string $addressLine1 = '';

    public string $addressLine2 = '';

    public string $city = '';

    public string $province = '';

    public string $postalCode = '';

    public string $countryCode = 'PH';

    #[Computed]
    public function subscription(): Subscription
    {
        return Subscription::with('plan')->findOrFail($this->subscriptionId);
    }

    public function mount(Subscription $subscription): void
    {
        $shop = Auth::user()?->shop;
        abort_unless($shop && $shop->id === $subscription->shop_id, 403);

        // Must be pending_payment — any return from DLT lands on the callback controller,
        // not back on this page.
        abort_unless($subscription->status === 'pending_payment', 403);

        $this->subscriptionId = $subscription->id;

        $user = Auth::user();
        $this->firstName = $user->first_name ?? '';
        $this->lastName = $user->last_name ?? '';
        $this->email = $user->email ?? '';

        $this->loadBrands();
    }

    public function loadBrands(): void
    {
        $this->loadingBrands = true;
        $this->errorMessage = null;

        try {
            $dlt = app(DltPaymentService::class);
            $this->brands = $dlt->getBrands();
            $this->cardBrands = array_values(array_filter($this->brands, fn ($b) => $b['is_card'] === true));
            $this->ewalletBrands = array_values(array_filter($this->brands, fn ($b) => $b['is_card'] === false));
        } catch (RuntimeException $e) {
            $this->errorMessage = 'Failed to load payment options. Please try again.';
            report($e);
        } finally {
            $this->loadingBrands = false;
        }
    }

    public function selectBrand(int $index, bool $isCard): void
    {
        $this->selectedBrand = $isCard
            ? ($this->cardBrands[$index] ?? null)
            : ($this->ewalletBrands[$index] ?? null);

        $this->errorMessage = null;

        if ($this->selectedBrand) {
            $this->step = 'payment_form';
        }
    }

    public function goBack(): void
    {
        $this->step = 'brands';
        $this->selectedBrand = null;
        $this->errorMessage = null;
    }

    public function submitPayment(): void
    {
        $this->validate([
            'firstName' => 'required|string|min:2|max:50',
            'lastName' => 'required|string|min:2|max:50',
            'email' => 'required|email|max:100',
            'phone' => 'nullable|string|max:16',
            'addressLine1' => 'required|string|max:80',
            'addressLine2' => 'nullable|string|max:80',
            'city' => 'required|string|max:60',
            'province' => 'required|string|max:40',
            'postalCode' => 'required|string|max:10',
        ]);

        $subscription = $this->subscription;

        if ($subscription->status !== 'pending_payment') {
            $this->step = 'failed';
            $this->errorMessage = 'This subscription is no longer awaiting payment.';

            return;
        }

        $this->step = 'processing';
        $this->errorMessage = null;

        $merchantTxnId = mb_substr(
            'sub'.mb_str_pad((string) $subscription->id, 5, '0', STR_PAD_LEFT).mb_strtoupper((string) Str::ulid()),
            0,
            45
        );

        $attempt = PaymentAttempt::create([
            'subscription_id' => $subscription->id,
            'status' => 'pending',
            'dlt_merchant_transaction_id' => $merchantTxnId,
        ]);

        try {
            $dlt = app(DltPaymentService::class);
            $result = $dlt->submitPayment([
                'merchant_transaction_id' => $merchantTxnId,
                'is_card_payment' => $this->selectedBrand['is_card'],
                'time_offset' => '+08:00',
                'channel' => 4,
                'amount' => number_format((float) $subscription->plan->price, 2, '.', ''),
                'currency' => 'PHP',
                'payment_brand' => $this->selectedBrand['value'],
                'payment_brand_code' => $this->selectedBrand['code'],
                'product_name' => Str::limit($subscription->plan->name, 80, ''),
                'product_description' => Str::limit($subscription->plan->name.' annual subscription', 120, ''),
                'first_name' => $this->firstName,
                'last_name' => $this->lastName,
                'email' => $this->email,
                'phone' => $this->phone,
                'address_line_one' => $this->addressLine1,
                'address_line_two' => $this->addressLine2,
                'city_municipality' => $this->city,
                'state_province_region' => $this->province,
                'country_code' => $this->countryCode,
                'postal_code' => $this->postalCode,
                'success_url' => route('hub.subscriptions.payment.success', ['subscription' => $subscription->id, 'attempt' => $attempt->id]),
                'failure_url' => route('hub.subscriptions.payment.failure', ['subscription' => $subscription->id, 'attempt' => $attempt->id]),
            ]);

            $paymentData = $result['data'] ?? [];
            $paymentUrl = $paymentData['payment_url'] ?? null;

            $attempt->update([
                'dlt_payment_status' => $paymentData['payment_status'] ?? 'PENDING',
                'dlt_payment_url' => $paymentUrl,
                'dlt_amount' => $paymentData['amount'] ?? null,
                'gateway_response' => $paymentData,
            ]);

            if ($paymentUrl) {
                $this->dispatch('redirect-to-payment', url: $paymentUrl);
            } else {
                $attempt->update(['status' => 'failed', 'failed_at' => now()]);
                $subscription->update(['status' => 'cancelled']);
                event(new SubscriptionStatusChanged($subscription->fresh()));
                Flux::toast(__('Payment could not be initiated. Please try again.'), variant: 'danger');
                $this->step = 'failed';
                $this->errorMessage = 'Payment could not be initiated. Please try again.';
            }
        } catch (RuntimeException $e) {
            $attempt->update([
                'status' => 'failed',
                'failed_at' => now(),
                'gateway_response' => ['error' => $e->getMessage()],
            ]);
            $subscription->update(['status' => 'cancelled']);
            event(new SubscriptionStatusChanged($subscription->fresh()));
            Flux::toast(__('Payment failed. Please try again.'), variant: 'danger');
            $this->step = 'failed';
            $this->errorMessage = 'Payment failed. Please try again.';
            report($e);
        }
    }

    public function retry(): void
    {
        $this->step = 'brands';
        $this->errorMessage = null;
        $this->selectedBrand = null;
        $this->loadBrands();
    }

    public function render()
    {
        return view('livewire.company-hub.subscription-payment-page', [
            'subscription' => $this->subscription,
        ]);
    }
}
