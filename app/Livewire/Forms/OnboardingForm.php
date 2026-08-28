<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Models\ShopOnboarding;
use Livewire\Form;

final class OnboardingForm extends Form
{
    // ── Step 1: Business Identity ─────────────────────────────────────────────

    public string $registered_business_name = '';

    public string $storefront_name = '';

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

    public string $country_code = 'PH';

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
     * Validation rules for a given wizard step.
     *
     * @return array<string, array<int, string>>
     */
    public function rulesForStep(int $step): array
    {
        return match ($step) {
            1 => [
                'registered_business_name' => ['required', 'string', 'max:255'],
                'storefront_name' => ['required', 'string', 'max:255'],
                'business_structure' => ['required', 'string'],
                'dti_sec_number' => ['required', 'string', 'max:100'],
                'tax_identification_number' => ['required', 'string', 'max:50'],
                'phone' => ['nullable', 'string', 'max:30'],
                'email' => ['nullable', 'email', 'max:255'],
                'address_1' => ['nullable', 'string', 'max:255'],
                'address_2' => ['nullable', 'string', 'max:255'],
                'city' => ['nullable', 'string', 'max:120'],
                'province' => ['nullable', 'string', 'max:120'],
                'zip_code' => ['nullable', 'string', 'max:20'],
            ],
            2 => [
                'product_categories' => ['nullable', 'array'],
                'fda_lto_number' => ['nullable', 'string', 'max:100'],
                'average_production_lead_time' => ['nullable', 'integer'],
                'export_readiness_status' => ['required', 'string'],
            ],
            4 => [
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
                'storefront_name' => $this->storefront_name,
                'business_structure' => $this->business_structure ?: null,
                'dti_sec_number' => $this->dti_sec_number,
                'tax_identification_number' => $this->tax_identification_number,
                'phone' => $this->phone,
                'email' => $this->email,
                'address_1' => $this->address_1,
                'address_2' => $this->address_2,
                'city' => $this->city,
                'province' => $this->province,
                'zip_code' => $this->zip_code,
                'country_code' => 'PH',
            ],
            2 => [
                'product_categories' => $this->product_categories !== []
                    ? implode(',', $this->product_categories)
                    : null,
                'fda_lto_number' => $this->fda_lto_number,
                'average_production_lead_time' => $this->average_production_lead_time ?: null,
                'export_readiness_status' => $this->export_readiness_status ?: null,
            ],
            4 => [
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
        $this->storefront_name = $onboarding->storefront_name ?? '';
        $this->business_structure = $onboarding->business_structure ?? '';
        $this->dti_sec_number = $onboarding->dti_sec_number ?? '';
        $this->tax_identification_number = $onboarding->tax_identification_number ?? '';
        $this->phone = $onboarding->phone ?? '';
        $this->email = $onboarding->email ?? '';
        $this->address_1 = $onboarding->address_1 ?? '';
        $this->address_2 = $onboarding->address_2 ?? '';
        $this->city = $onboarding->city ?? '';
        $this->province = $onboarding->province ?? '';
        $this->zip_code = $onboarding->zip_code ?? '';
        $this->country_code = 'PH';
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
