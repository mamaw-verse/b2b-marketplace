<?php

declare(strict_types=1);

namespace App\Livewire\Account\Orders;

use App\Actions\ProcessOrderPaymentAction;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Services\DltPaymentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;

#[Layout('layouts.portal', ['portal' => 'account'])]
#[Title('Pay Order')]
final class PaymentPage extends Component
{
    #[Locked]
    public int $orderId;

    // State
    public string $step = 'brands'; // brands | card_form | ewallet_form | processing | success | failed

    public bool $loadingBrands = true;

    public ?string $errorMessage = null;

    // Brands data
    public array $brands = [];

    public array $cardBrands = [];

    public array $ewalletBrands = [];

    // Selected brand
    public ?array $selectedBrand = null;

    // Card form fields
    public string $cardNumber = '';

    public string $cardholderName = '';

    public string $expiryMonth = '';

    public string $expiryYear = '';

    public string $ccv = '';

    // Payor fields (shared by card + e-wallet)
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

    // Response
    public ?string $paymentStatus = null;

    #[Computed]
    public function order(): Order
    {
        return Order::with(['items', 'fees', 'shop'])->findOrFail($this->orderId);
    }

    public function mount(Order $order): void
    {
        Gate::authorize('pay', $order);

        $this->orderId = $order->id;

        // Pre-fill payor from order snapshot
        $this->firstName = explode(' ', $order->customer_contact_name ?? '')[0] ?? '';
        $this->lastName = explode(' ', $order->customer_contact_name ?? '', 2)[1] ?? '';
        $this->email = $order->customer_contact_email ?? Auth::user()->email;
        $this->phone = $order->customer_contact_phone ?? '';
        $this->addressLine1 = $order->billing_address_1 ?? '';
        $this->addressLine2 = $order->billing_address_2 ?? '';
        $this->city = $order->billing_city ?? '';
        $this->province = $order->billing_province ?? '';
        $this->postalCode = $order->billing_zip_code ?? '';

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
        $this->selectedBrand = $isCard ? ($this->cardBrands[$index] ?? null) : ($this->ewalletBrands[$index] ?? null);
        $this->errorMessage = null;

        if ($this->selectedBrand) {
            $this->step = $isCard ? 'card_form' : 'ewallet_form';
        }
    }

    public function goBack(): void
    {
        $this->step = 'brands';
        $this->selectedBrand = null;
        $this->errorMessage = null;
    }

    public function submitCardPayment(): void
    {
        $this->validate([
            'cardNumber' => 'required|string|min:15|max:19',
            'cardholderName' => 'required|string|min:2|max:50',
            'expiryMonth' => 'required|string|size:2',
            'expiryYear' => 'required|string|size:4',
            'ccv' => 'required|string|min:3|max:4',
            'firstName' => 'required|string|min:2|max:50',
            'lastName' => 'required|string|min:2|max:50',
            'email' => 'required|email',
            'addressLine1' => 'required|string|max:40',
            'addressLine2' => 'required|string|max:40',
            'city' => 'required|string|max:50',
            'province' => 'required|string|max:50',
            'postalCode' => 'required|string|max:10',
        ]);

        // Guard: re-check order has not been recalled/cancelled
        $order = $this->order();
        $order->refresh();
        if ($order->status !== 'pending_payment') {
            $this->step = 'failed';
            $this->errorMessage = 'This order is no longer awaiting payment. It may have been recalled or cancelled.';

            return;
        }

        $this->step = 'processing';
        $this->errorMessage = null;

        $paymentTerm = $order->paymentTerms()->first();
        $merchantTxnId = Str::limit('order'.$order->id.Str::random(30), 50, '');

        $attempt = PaymentAttempt::create([
            'order_payment_term_id' => $paymentTerm->id,
            'status' => 'pending',
            'dlt_merchant_transaction_id' => $merchantTxnId,
        ]);

        try {
            $dlt = app(DltPaymentService::class);
            $result = $dlt->submitPayment([
                'merchant_transaction_id' => $merchantTxnId,
                'is_card_payment' => true,
                'time_offset' => '+08:00',
                'channel' => 1,
                'amount' => number_format((float) $order->order_total, 2, '.', ''),
                'currency' => 'PHP',
                'payment_brand' => $this->selectedBrand['value'],
                'payment_brand_code' => $this->selectedBrand['code'],
                'product_name' => Str::limit($order->items()->first()?->description ?? 'Order #'.$order->id, 100, ''),
                'product_description' => 'Order #'.$order->id.' payment',
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
                // Card fields
                'card_holder_name' => $this->cardholderName,
                'card_number' => str_replace(' ', '', $this->cardNumber),
                'expiration_month' => $this->expiryMonth,
                'expiration_year' => mb_substr($this->expiryYear, -2),
                'ccv' => $this->ccv,
                // 3DS device fields (collected from browser via JS — use defaults for server-side)
                'device' => [
                    'timezone' => '+08:00',
                    'browser_color_depth' => '24',
                    'browser_language' => 'en-US',
                    'browser_screen_height' => '1080',
                    'browser_screen_width' => '1920',
                    'os' => 'Windows',
                    'browser_accept_header' => 'text/html',
                    'user_agent' => request()->userAgent() ?? 'Mozilla/5.0',
                    'browser_javascript_enabled' => true,
                    'browser_java_enabled' => false,
                    'accept_content' => 'text/html',
                ],
                'success_url' => route('orders.payment.success', ['order' => $order->id, 'attempt' => $attempt->id]),
                'failure_url' => route('orders.payment.failure', ['order' => $order->id, 'attempt' => $attempt->id]),
            ]);

            $paymentData = $result['data'] ?? [];
            $paymentStatus = $paymentData['payment_status'] ?? 'REJECTED';

            $attempt->update([
                'dlt_payment_status' => $paymentStatus,
                'dlt_amount' => $paymentData['amount'] ?? null,
                'gateway_response' => $paymentData,
            ]);

            if ($paymentStatus === 'PAID') {
                (new ProcessOrderPaymentAction)->execute($attempt, $order);
                $this->step = 'success';
            } elseif ($paymentStatus === 'PENDING') {
                // Card payment pending — rare, show pending state
                $this->step = 'processing';
                $this->paymentStatus = 'PENDING';
            } else {
                $attempt->update(['status' => 'failed', 'failed_at' => now()]);
                $this->step = 'failed';
                $this->errorMessage = 'Payment was declined. Please try again with a different card.';
            }
        } catch (RuntimeException $e) {
            $attempt->update([
                'status' => 'failed',
                'failed_at' => now(),
                'gateway_response' => ['error' => $e->getMessage()],
            ]);
            $this->step = 'failed';
            $this->errorMessage = 'Payment failed. Please try again.';
            report($e);
        }
    }

    public function submitEwalletPayment(): void
    {
        $this->validate([
            'firstName' => 'required|string|min:2|max:50',
            'lastName' => 'required|string|min:2|max:50',
            'email' => 'required|email',
            'addressLine1' => 'required|string|max:40',
            'addressLine2' => 'required|string|max:40',
            'city' => 'required|string|max:50',
            'province' => 'required|string|max:50',
            'postalCode' => 'required|string|max:10',
        ]);

        $this->step = 'processing';
        $this->errorMessage = null;

        // Guard: re-check order has not been recalled/cancelled
        $order = $this->order();
        $order->refresh();
        if ($order->status !== 'pending_payment') {
            $this->step = 'failed';
            $this->errorMessage = 'This order is no longer awaiting payment. It may have been recalled or cancelled.';

            return;
        }

        $paymentTerm = $order->paymentTerms()->first();
        $merchantTxnId = Str::limit('order'.$order->id.Str::random(30), 50, '');

        $attempt = PaymentAttempt::create([
            'order_payment_term_id' => $paymentTerm->id,
            'status' => 'pending',
            'dlt_merchant_transaction_id' => $merchantTxnId,
        ]);

        try {
            $dlt = app(DltPaymentService::class);
            $result = $dlt->submitPayment([
                'merchant_transaction_id' => $merchantTxnId,
                'is_card_payment' => false,
                'time_offset' => '+08:00',
                'channel' => 1,
                'amount' => number_format((float) $order->order_total, 2, '.', ''),
                'currency' => 'PHP',
                'payment_brand' => $this->selectedBrand['value'],
                'payment_brand_code' => $this->selectedBrand['code'],
                'product_name' => Str::limit($order->items()->first()?->description ?? 'Order #'.$order->id, 100, ''),
                'product_description' => 'Order #'.$order->id.' payment',
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
                'success_url' => route('orders.payment.success', ['order' => $order->id, 'attempt' => $attempt->id]),
                'failure_url' => route('orders.payment.failure', ['order' => $order->id, 'attempt' => $attempt->id]),
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
                // Redirect buyer to e-wallet page (GCash, etc.)
                $this->dispatch('redirect-to-payment', url: $paymentUrl);
            } else {
                // No redirect URL — unexpected for e-wallet
                $attempt->update(['status' => 'failed', 'failed_at' => now()]);
                $this->step = 'failed';
                $this->errorMessage = 'E-wallet payment could not be initiated. Please try again.';
            }
        } catch (RuntimeException $e) {
            $attempt->update([
                'status' => 'failed',
                'failed_at' => now(),
                'gateway_response' => ['error' => $e->getMessage()],
            ]);
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
    }

    public function render()
    {
        return view('livewire.account.orders.payment-page', [
            'order' => $this->order,
        ]);
    }
}
