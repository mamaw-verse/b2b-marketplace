<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub;

use App\Models\Product;
use App\Models\ProductVariant;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Manage Variants')]
final class VariantManager extends Component
{
    public Product $product;

    /** @var array<int, array<string, mixed>> */
    public array $variants = [];

    /** @var array<int, array{min_qty: int|string, price: float|string}> */
    public array $quantityBreaks = [];

    public bool $showQuantityBreaksModal = false;

    public ?int $editingBreaksIndex = null;

    public function mount(Product $product): void
    {
        $this->authorize('update', $product);
        $this->product = $product;
        $this->loadVariants();
    }

    public function addVariant(): void
    {
        $this->variants[] = [
            'id' => null,
            'sku' => '',
            'price' => '',
            'minimum_order_quantity' => '1',
            'unit' => '',
            'lead_time_days' => '',
            'pack_size' => '',
            'status' => 'archived',
            'quantity_breaks' => [],
        ];
    }

    public function removeVariant(int $index): void
    {
        $variant = $this->variants[$index] ?? null;

        if ($variant && $variant['id']) {
            ProductVariant::where('id', $variant['id'])
                ->where('product_id', $this->product->id)
                ->delete();
        }

        unset($this->variants[$index]);
        $this->variants = array_values($this->variants);

        \Flux\Flux::toast('Variant removed.', variant: 'success');
    }

    public function toggleVariantStatus(int $index): void
    {
        if (isset($this->variants[$index])) {
            $this->variants[$index]['status'] = $this->variants[$index]['status'] === 'published'
                ? 'archived'
                : 'published';
        }
    }

    public function openQuantityBreaks(int $index): void
    {
        $this->editingBreaksIndex = $index;
        $this->quantityBreaks = $this->variants[$index]['quantity_breaks'] ?? [];

        if (empty($this->quantityBreaks)) {
            $this->quantityBreaks = [['min_qty' => '', 'price' => '']];
        }

        $this->showQuantityBreaksModal = true;
    }

    public function addQuantityBreak(): void
    {
        $this->quantityBreaks[] = ['min_qty' => '', 'price' => ''];
    }

    public function removeQuantityBreak(int $index): void
    {
        unset($this->quantityBreaks[$index]);
        $this->quantityBreaks = array_values($this->quantityBreaks);
    }

    public function saveQuantityBreaks(): void
    {
        if ($this->editingBreaksIndex !== null && isset($this->variants[$this->editingBreaksIndex])) {
            $breaks = collect($this->quantityBreaks)
                ->filter(fn (array $b) => $b['min_qty'] !== '' && $b['price'] !== '')
                ->map(fn (array $b) => ['min_qty' => (int) $b['min_qty'], 'price' => (float) $b['price']])
                ->values()
                ->all();

            $this->variants[$this->editingBreaksIndex]['quantity_breaks'] = $breaks;
        }

        $this->showQuantityBreaksModal = false;
        $this->editingBreaksIndex = null;
        $this->quantityBreaks = [];
    }

    public function saveAll(): void
    {
        $this->validate();

        foreach ($this->variants as $variantData) {
            $attributes = [
                'product_id' => $this->product->id,
                'sku' => $variantData['sku'],
                'price' => $variantData['price'],
                'minimum_order_quantity' => (int) $variantData['minimum_order_quantity'],
                'unit' => $variantData['unit'],
                'lead_time_days' => $variantData['lead_time_days'] !== '' ? (int) $variantData['lead_time_days'] : null,
                'pack_size' => $variantData['pack_size'] ?: null,
                'status' => $variantData['status'],
                'quantity_breaks' => $variantData['quantity_breaks'] ?? [],
            ];

            if ($variantData['id']) {
                ProductVariant::where('id', $variantData['id'])
                    ->where('product_id', $this->product->id)
                    ->update($attributes);
            } else {
                ProductVariant::create($attributes);
            }
        }

        $this->loadVariants();

        \Flux\Flux::toast('Variants saved successfully.', variant: 'success');
    }

    public function render()
    {
        return view('livewire.company-hub.variant-manager');
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'variants' => ['array'],
            'variants.*.sku' => ['required', 'string', 'max:100'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.minimum_order_quantity' => ['required', 'integer', 'min:1'],
            'variants.*.unit' => ['required', 'string', 'max:30'],
            'variants.*.lead_time_days' => ['nullable', 'integer', 'min:0'],
            'variants.*.pack_size' => ['nullable', 'string', 'max:100'],
            'variants.*.status' => ['required', 'in:published,archived'],
        ];
    }

    private function loadVariants(): void
    {
        $this->variants = $this->product->variants()
            ->orderBy('created_at')
            ->get()
            ->map(fn (ProductVariant $v) => [
                'id' => $v->id,
                'sku' => $v->sku,
                'price' => $v->price,
                'minimum_order_quantity' => (string) $v->minimum_order_quantity,
                'unit' => $v->unit,
                'lead_time_days' => $v->lead_time_days !== null ? (string) $v->lead_time_days : '',
                'pack_size' => $v->pack_size ?? '',
                'status' => $v->status,
                'quantity_breaks' => $v->quantity_breaks ?? [],
            ])
            ->all();
    }
}
