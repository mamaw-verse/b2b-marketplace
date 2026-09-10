<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Models\ShopOnboarding;
use Livewire\Form;

final class OnboardingForm extends Form
{
    // ── Step 1: Business Identity ─────────────────────────────────────────────

    public string $registered_business_name = '';

    public string $company_description = '';

    public string $products_description = '';

    public string $business_structure = '';

    public string $dti_sec_number = '';

    public string $tax_identification_number = '';

    public string $phone = '';

    public string $email = '';

    public string $address_1 = '';

    public string $address_2 = '';

    public string $city = '';

    public string $province = '';

    public string $zip_code = '';

    public string $country_code = '';

    // ── Step 2: Product & Compliance ──────────────────────────────────────────

    /** @var array<int, int|string> */
    public array $product_categories = [];

    public string $fda_lto_number = '';

    public string $average_production_lead_time = '';

    public string $export_readiness_status = '';

    // ── Step 4: Value Chain & Capabilities ────────────────────────────────────

    /** @var array<int, string> */
    public array $value_chain_positions = [];

    /** @var array<int, string> */
    public array $capabilities = [];

    /**
     * Human-readable attribute names used in validation error messages.
     * Prevents Laravel from generating messages like "The form.registered business name field is required."
     *
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'registered_business_name' => 'Registered Business Name',
            'company_description' => 'Company Description',
            'products_description' => 'Products Description',
            'business_structure' => 'Business Structure',
            'dti_sec_number' => 'DTI/SEC Number',
            'tax_identification_number' => 'TIN',
            'phone' => 'Phone',
            'email' => 'Email',
            'address_1' => 'Address Line 1',
            'address_2' => 'Address Line 2',
            'city' => 'City',
            'province' => 'Province',
            'zip_code' => 'Zip Code',
            'product_categories' => 'Product Categories',
            'fda_lto_number' => 'FDA LTO Number',
            'average_production_lead_time' => 'Average Production Lead Time',
            'export_readiness_status' => 'Export Readiness',
            'value_chain_positions' => 'Value Chain Positions',
            'capabilities' => 'Capabilities',
        ];
    }

    /**
     * Validation rules for a given wizard step.
     *
     * @return array<string, array<int, string>>
     */
    public function rulesForStep(int $step): array
    {
        return match ($step) {
            1 => [
                'registered_business_name' => ['required', 'string', 'max:255'],
                'business_structure' => ['required', 'string'],
                'company_description' => ['required', 'string', 'max:2000'],
                'products_description' => ['required', 'string', 'max:2000'],
                'dti_sec_number' => ['nullable', 'string', 'max:100'],
                'tax_identification_number' => ['nullable', 'string', 'max:50'],
                'phone' => ['required', 'string', 'max:30'],
                'email' => ['required', 'email', 'max:255'],
                'address_1' => ['required', 'string', 'max:255'],
                'address_2' => ['nullable', 'string', 'max:255'],
                'city' => ['required', 'string', 'max:120'],
                'province' => ['required', 'string', 'max:120'],
                'zip_code' => ['required', 'string', 'max:20'],
            ],
            2 => [
                'product_categories' => ['nullable', 'array'],
                'fda_lto_number' => ['nullable', 'string', 'max:100'],
                'average_production_lead_time' => ['nullable', 'integer'],
                'export_readiness_status' => ['required', 'string'],
            ],
            3 => [
                'value_chain_positions' => ['array'],
                'capabilities' => ['array'],
            ],
            default => [],
        };
    }

    /**
     * Persistable attributes for a given wizard step.
     *
     * @return array<string, mixed>
     */
    public function dataForStep(int $step): array
    {
        return match ($step) {
            1 => [
                'registered_business_name' => $this->registered_business_name,
                'business_structure' => $this->business_structure ?: null,
                'company_description' => $this->company_description ?: null,
                'products_description' => $this->products_description ?: null,
                'dti_sec_number' => $this->dti_sec_number,
                'tax_identification_number' => $this->tax_identification_number,
                'phone' => $this->phone,
                'email' => $this->email,
                'address_1' => $this->address_1,
                'address_2' => $this->address_2,
                'city' => $this->city,
                'province' => $this->province,
                'zip_code' => $this->zip_code,
                'country_code' => config('marketplace.instance_country', 'PH'),
            ],
            2 => [
                'product_categories' => $this->product_categories !== []
                    ? implode(',', $this->product_categories)
                    : null,
                'fda_lto_number' => $this->fda_lto_number,
                'average_production_lead_time' => $this->average_production_lead_time ?: null,
                'export_readiness_status' => $this->export_readiness_status ?: null,
            ],
            3 => [
                'value_chain_positions' => $this->value_chain_positions,
                'capabilities' => $this->capabilities,
            ],
            default => [],
        };
    }

    /**
     * Fill the form from an existing onboarding record.
     */
    public function setOnboarding(ShopOnboarding $onboarding): void
    {
        $this->registered_business_name = $onboarding->registered_business_name ?? '';
        $this->business_structure = $onboarding->business_structure ?? '';
        $this->company_description = $onboarding->company_description ?? '';
        $this->products_description = $onboarding->products_description ?? '';
        $this->dti_sec_number = $onboarding->dti_sec_number ?? '';
        $this->tax_identification_number = $onboarding->tax_identification_number ?? '';
        $this->phone = $onboarding->phone ?? '';
        $this->email = $onboarding->email ?? '';
        $this->address_1 = $onboarding->address_1 ?? '';
        $this->address_2 = $onboarding->address_2 ?? '';
        $this->city = $onboarding->city ?? '';
        $this->province = $onboarding->province ?? '';
        $this->zip_code = $onboarding->zip_code ?? '';
        $this->country_code = $onboarding->country_code ?? config('marketplace.instance_country', 'PH');
        $this->product_categories = $this->parseProductCategories($onboarding->product_categories);
        $this->fda_lto_number = $onboarding->fda_lto_number ?? '';
        $this->average_production_lead_time = (string) ($onboarding->average_production_lead_time ?? '');
        $this->export_readiness_status = $onboarding->export_readiness_status ?? '';
        $this->value_chain_positions = $onboarding->value_chain_positions ?? [];
        $this->capabilities = $onboarding->capabilities ?? [];
    }

    /**
     * Parse the stored comma-separated product_categories string into an array
     * of category IDs for the checkbox group. Handles legacy free-text values
     * gracefully by returning an empty array when no numeric IDs are present.
     *
     * @return array<int, int>
     */
    private function parseProductCategories(?string $raw): array
    {
        if ($raw === null || mb_trim($raw) === '') {
            return [];
        }

        return collect(explode(',', $raw))
            ->map(fn (string $value): string => mb_trim($value))
            ->filter(fn (string $value): bool => $value !== '' && ctype_digit($value))
            ->map(fn (string $value): int => (int) $value)
            ->values()
            ->all();
    }
}
