<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub;

use App\Models\Category;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Business Profile')]
final class ShopProfileForm extends Component
{
    public string $registered_business_name = '';

    public string $storefront_name = '';

    public string $business_structure = '';

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

    /** @var array<int, string> */
    public array $value_chain_positions = [];

    /** @var array<int, string> */
    public array $capabilities = [];

    /** @var array<int, int> */
    public array $product_categories = [];

    public function mount(): void
    {
        $shop = Auth::user()->shop;

        abort_unless($shop !== null, 404);

        $this->registered_business_name = $shop->registered_business_name ?? '';
        $this->storefront_name = $shop->storefront_name ?? '';
        $this->business_structure = $shop->business_structure ?? '';
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
        $this->value_chain_positions = $shop->value_chain_positions ?? [];
        $this->capabilities = $shop->capabilities ?? [];
        $this->product_categories = $shop->categories->pluck('id')->all();
    }

    public function save(): void
    {
        $shop = Auth::user()->shop;

        abort_unless($shop !== null, 404);

        $validated = $this->validate();

        $categoryIds = $validated['product_categories'] ?? [];
        unset($validated['product_categories']);

        $shop->update($validated);
        $shop->categories()->sync($categoryIds);

        Flux::toast(__('Shop profile updated.'), variant: 'success');
    }

    /**
     * Categories available for the product-category checkbox group.
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::orderBy('name')->get();
    }

    /**
     * Read-only compliance data verified during onboarding: legal identifiers,
     * halal certificates, and uploaded documents. These are shown for reference
     * but not editable here, since changing them would invalidate the review.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function verifiedDetails(): array
    {
        $shop = Auth::user()->shop;

        return [
            'dti_sec_number' => $shop->dti_sec_number,
            'tax_identification_number' => $shop->tax_identification_number,
            'country_code' => $shop->country_code,
            'certificates' => $shop->certificates()->with('media')->get(),
            'documents' => $shop->documents()->get(),
        ];
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
            'registered_business_name' => ['required', 'string', 'max:255'],
            'storefront_name' => ['required', 'string', 'max:255'],
            'business_structure' => ['required', Rule::in(['sole_proprietorship', 'partnership', 'corporation'])],
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
            'value_chain_positions' => ['array'],
            'value_chain_positions.*' => ['string', Rule::in(['farm', 'processing', 'manufacturing', 'distribution', 'retail', 'food_service'])],
            'capabilities' => ['array'],
            'capabilities.*' => ['string', Rule::in(['processing', 'contract_processing', 'packaging', 'contract_manufacturing', 'oem_odm', 'private_label', 'cold_storage', 'warehousing', 'distribution', 'wholesale', 'importing', 'exporting'])],
            'product_categories' => ['array'],
            'product_categories.*' => ['integer', Rule::exists('categories', 'id')],
        ];
    }
}
