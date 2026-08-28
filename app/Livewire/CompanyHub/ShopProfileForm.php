<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Shop Profile')]
final class ShopProfileForm extends Component
{
    public string $storefront_name = '';

    public string $phone = '';

    public string $email = '';

    public string $address_1 = '';

    public string $address_2 = '';

    public string $city = '';

    public string $province = '';

    public string $zip_code = '';

    public ?string $fda_lto_number = null;

    public ?int $average_production_lead_time = null;

    public string $export_readiness_status = '';

    public function mount(): void
    {
        $shop = Auth::user()->shop;

        abort_unless($shop !== null, 404);

        $this->storefront_name = $shop->storefront_name ?? '';
        $this->phone = $shop->phone ?? '';
        $this->email = $shop->email ?? '';
        $this->address_1 = $shop->address_1 ?? '';
        $this->address_2 = $shop->address_2 ?? '';
        $this->city = $shop->city ?? '';
        $this->province = $shop->province ?? '';
        $this->zip_code = $shop->zip_code ?? '';
        $this->fda_lto_number = $shop->fda_lto_number;
        $this->average_production_lead_time = $shop->average_production_lead_time;
        $this->export_readiness_status = $shop->export_readiness_status ?? '';
    }

    public function save(): void
    {
        $shop = Auth::user()->shop;

        abort_unless($shop !== null, 404);

        $validated = $this->validate();

        $shop->update($validated);

        \Flux\Flux::toast(__('Shop profile updated.'), variant: 'success');
    }

    public function render()
    {
        return view('livewire.company-hub.shop-profile-form');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'storefront_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address_1' => ['required', 'string', 'max:255'],
            'address_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'province' => ['required', 'string', 'max:120'],
            'zip_code' => ['required', 'string', 'max:20'],
            'fda_lto_number' => ['nullable', 'string', 'max:100'],
            'average_production_lead_time' => ['nullable', 'integer', 'min:0'],
            'export_readiness_status' => ['required', Rule::in(['never_exported', 'exporting_regionally', 'exporting_globally'])],
        ];
    }
}
