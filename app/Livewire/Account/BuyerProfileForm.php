<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'account'])]
#[Title('Buyer Profile')]
final class BuyerProfileForm extends Component
{
    public string $business_name = '';

    public string $billing_address_1 = '';

    public string $billing_address_2 = '';

    public string $billing_city = '';

    public string $billing_province = '';

    public string $billing_zip_code = '';

    public string $billing_country_code = 'PH';

    public string $shipping_address_1 = '';

    public string $shipping_address_2 = '';

    public string $shipping_city = '';

    public string $shipping_province = '';

    public string $shipping_zip_code = '';

    public string $shipping_country_code = 'PH';

    public string $contact_phone = '';

    public string $contact_email = '';

    public bool $useShippingSameAsBilling = true;

    public function mount(): void
    {
        $profile = Auth::user()->buyerProfile;

        if ($profile) {
            $this->business_name = $profile->business_name;
            $this->billing_address_1 = $profile->billing_address_1 ?? '';
            $this->billing_address_2 = $profile->billing_address_2 ?? '';
            $this->billing_city = $profile->billing_city ?? '';
            $this->billing_province = $profile->billing_province ?? '';
            $this->billing_zip_code = $profile->billing_zip_code ?? '';
            $this->billing_country_code = $profile->billing_country_code ?? 'PH';
            $this->shipping_address_1 = $profile->shipping_address_1 ?? '';
            $this->shipping_address_2 = $profile->shipping_address_2 ?? '';
            $this->shipping_city = $profile->shipping_city ?? '';
            $this->shipping_province = $profile->shipping_province ?? '';
            $this->shipping_zip_code = $profile->shipping_zip_code ?? '';
            $this->shipping_country_code = $profile->shipping_country_code ?? 'PH';
            $this->contact_phone = $profile->contact_phone ?? '';
            $this->contact_email = $profile->contact_email ?? '';

            $this->useShippingSameAsBilling = $this->isShippingSameAsBilling();
        }
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'business_name' => $this->business_name,
            'billing_address_1' => $this->billing_address_1 ?: null,
            'billing_address_2' => $this->billing_address_2 ?: null,
            'billing_city' => $this->billing_city ?: null,
            'billing_province' => $this->billing_province ?: null,
            'billing_zip_code' => $this->billing_zip_code ?: null,
            'billing_country_code' => $this->billing_country_code ?: 'PH',
            'shipping_address_1' => $this->useShippingSameAsBilling ? ($this->billing_address_1 ?: null) : ($this->shipping_address_1 ?: null),
            'shipping_address_2' => $this->useShippingSameAsBilling ? ($this->billing_address_2 ?: null) : ($this->shipping_address_2 ?: null),
            'shipping_city' => $this->useShippingSameAsBilling ? ($this->billing_city ?: null) : ($this->shipping_city ?: null),
            'shipping_province' => $this->useShippingSameAsBilling ? ($this->billing_province ?: null) : ($this->shipping_province ?: null),
            'shipping_zip_code' => $this->useShippingSameAsBilling ? ($this->billing_zip_code ?: null) : ($this->shipping_zip_code ?: null),
            'shipping_country_code' => $this->useShippingSameAsBilling ? ($this->billing_country_code ?: 'PH') : ($this->shipping_country_code ?: 'PH'),
            'contact_phone' => $this->contact_phone ?: null,
            'contact_email' => $this->contact_email ?: null,
        ];

        Auth::user()->buyerProfile()->updateOrCreate(
            ['user_id' => Auth::id()],
            $data,
        );

        Flux::toast('Buyer profile saved.', variant: 'success');
    }

    public function render()
    {
        return view('livewire.account.buyer-profile-form');
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:255'],
            'billing_address_1' => ['nullable', 'string', 'max:255'],
            'billing_address_2' => ['nullable', 'string', 'max:255'],
            'billing_city' => ['nullable', 'string', 'max:120'],
            'billing_province' => ['nullable', 'string', 'max:120'],
            'billing_zip_code' => ['nullable', 'string', 'max:20'],
            'billing_country_code' => ['nullable', 'string', 'size:2'],
            'shipping_address_1' => ['nullable', 'string', 'max:255'],
            'shipping_address_2' => ['nullable', 'string', 'max:255'],
            'shipping_city' => ['nullable', 'string', 'max:120'],
            'shipping_province' => ['nullable', 'string', 'max:120'],
            'shipping_zip_code' => ['nullable', 'string', 'max:20'],
            'shipping_country_code' => ['nullable', 'string', 'size:2'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'contact_email' => ['nullable', 'email', 'max:255'],
        ];
    }

    private function isShippingSameAsBilling(): bool
    {
        return $this->shipping_address_1 === $this->billing_address_1
            && $this->shipping_city === $this->billing_city
            && $this->shipping_province === $this->billing_province
            && $this->shipping_zip_code === $this->billing_zip_code;
    }
}
