<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use Livewire\Form;

final class VariantForm extends Form
{
    /** Null means creating a new variant; int is the DB id being edited. */
    public ?int $editingVariantId = null;

    public string $sku = '';

    public string $price = '';

    public string $moq = '1';

    public string $unit = '';

    public string $leadTime = '';

    public string $packSize = '';

    public string $status = 'archived';

    /** @var array<string, mixed> */
    public array $attributes = [];

    /**
     * Validation rules for the variant draft.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'moq' => ['required', 'integer', 'min:1'],
            'unit' => ['required', 'string', 'max:30'],
            'leadTime' => ['nullable', 'integer', 'min:0'],
            'packSize' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:published,archived'],
            'attributes' => ['array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'sku' => 'SKU',
            'price' => 'Price',
            'moq' => 'Minimum order quantity',
            'unit' => 'Unit',
            'leadTime' => 'Lead time',
            'packSize' => 'Pack size',
            'status' => 'Status',
        ];
    }

    /**
     * Reset the form to blank state for creating a new variant.
     *
     * @param  array<string, mixed>  $blankAttributes
     */
    public function setForCreate(array $blankAttributes): void
    {
        $this->editingVariantId = null;
        $this->sku = '';
        $this->price = '';
        $this->moq = '1';
        $this->unit = '';
        $this->leadTime = '';
        $this->packSize = '';
        $this->status = 'archived';
        $this->attributes = $blankAttributes;
    }

    /**
     * Fill the form from an existing variant array.
     *
     * @param  array<string, mixed>  $variant
     * @param  array<string, mixed>  $blankAttributes
     */
    public function setForEdit(array $variant, array $blankAttributes): void
    {
        $this->editingVariantId = $variant['id'];
        $this->sku = $variant['sku'];
        $this->price = (string) $variant['price'];
        $this->moq = (string) $variant['minimum_order_quantity'];
        $this->unit = $variant['unit'];
        $this->leadTime = (string) ($variant['lead_time_days'] ?? '');
        $this->packSize = $variant['pack_size'] ?? '';
        $this->status = $variant['status'];
        $this->attributes = array_merge($blankAttributes, $variant['attributes'] ?? []);
    }

    /**
     * Persistable attributes for saving a variant.
     *
     * @return array<string, mixed>
     */
    public function toVariantData(int $productId): array
    {
        return [
            'product_id' => $productId,
            'sku' => $this->sku,
            'price' => $this->price,
            'minimum_order_quantity' => (int) $this->moq,
            'unit' => $this->unit,
            'lead_time_days' => $this->leadTime !== '' ? (int) $this->leadTime : null,
            'pack_size' => $this->packSize ?: null,
            'status' => $this->status,
            'attributes' => $this->attributes,
        ];
    }
}
