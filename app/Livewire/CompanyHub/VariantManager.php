<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub;

use App\Models\Product;
use App\Models\ProductVariant;
use Livewire\Attributes\Computed;
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

    // ── Add / Edit modal ─────────────────────────────────────────────────────

    public bool $showVariantModal = false;

    /** Null means creating a new variant; int is the DB id being edited. */
    public ?int $editingVariantId = null;

    public string $draftSku = '';

    public string $draftPrice = '';

    public string $draftMoq = '1';

    public string $draftUnit = '';

    public string $draftLeadTime = '';

    public string $draftPackSize = '';

    public string $draftStatus = 'archived';

    /** @var array<string, mixed> */
    public array $draftAttributes = [];

    // ── Quantity breaks modal ─────────────────────────────────────────────────

    public bool $showQuantityBreaksModal = false;

    public ?int $editingBreaksVariantId = null;

    /** @var array<int, array{min_qty: int|string, price: float|string}> */
    public array $quantityBreaks = [];

    // ─────────────────────────────────────────────────────────────────────────

    public function mount(Product $product): void
    {
        $this->authorize('update', $product);
        $this->product = $product;
        $this->loadVariants();
    }

    // ── Variant modal ─────────────────────────────────────────────────────────

    public function openAddVariant(): void
    {
        $this->editingVariantId = null;
        $this->draftSku = '';
        $this->draftPrice = '';
        $this->draftMoq = '1';
        $this->draftUnit = '';
        $this->draftLeadTime = '';
        $this->draftPackSize = '';
        $this->draftStatus = 'archived';
        $this->draftAttributes = $this->blankAttributes();
        $this->resetValidation();
        $this->showVariantModal = true;
    }

    public function openEditVariant(int $variantId): void
    {
        $variant = collect($this->variants)->firstWhere('id', $variantId);

        if (! $variant) {
            return;
        }

        $this->editingVariantId = $variantId;
        $this->draftSku = $variant['sku'];
        $this->draftPrice = (string) $variant['price'];
        $this->draftMoq = (string) $variant['minimum_order_quantity'];
        $this->draftUnit = $variant['unit'];
        $this->draftLeadTime = (string) ($variant['lead_time_days'] ?? '');
        $this->draftPackSize = $variant['pack_size'] ?? '';
        $this->draftStatus = $variant['status'];
        $this->draftAttributes = array_merge($this->blankAttributes(), $variant['attributes'] ?? []);
        $this->resetValidation();
        $this->showVariantModal = true;
    }

    public function saveVariant(): void
    {
        $this->validateVariantDraft();

        $data = [
            'product_id' => $this->product->id,
            'sku' => $this->draftSku,
            'price' => $this->draftPrice,
            'minimum_order_quantity' => (int) $this->draftMoq,
            'unit' => $this->draftUnit,
            'lead_time_days' => $this->draftLeadTime !== '' ? (int) $this->draftLeadTime : null,
            'pack_size' => $this->draftPackSize ?: null,
            'status' => $this->draftStatus,
            'attributes' => $this->draftAttributes,
        ];

        if ($this->editingVariantId) {
            ProductVariant::where('id', $this->editingVariantId)
                ->where('product_id', $this->product->id)
                ->update($data);
            $message = 'Variant updated.';
        } else {
            $data['quantity_breaks'] = [];
            ProductVariant::create($data);
            $message = 'Variant added.';
        }

        $this->showVariantModal = false;
        $this->loadVariants();
        \Flux\Flux::toast($message, variant: 'success');
    }

    public function removeVariant(int $variantId): void
    {
        ProductVariant::where('id', $variantId)
            ->where('product_id', $this->product->id)
            ->delete();

        $this->loadVariants();
        \Flux\Flux::toast('Variant removed.', variant: 'warning');
    }

    public function toggleStatus(int $variantId): void
    {
        $variant = ProductVariant::where('id', $variantId)
            ->where('product_id', $this->product->id)
            ->firstOrFail();

        $variant->update([
            'status' => $variant->status === 'published' ? 'archived' : 'published',
        ]);

        $this->loadVariants();
    }

    // ── Quantity breaks modal ─────────────────────────────────────────────────

    public function openQuantityBreaks(int $variantId): void
    {
        $this->editingBreaksVariantId = $variantId;
        $variant = collect($this->variants)->firstWhere('id', $variantId);
        $this->quantityBreaks = $variant['quantity_breaks'] ?? [];

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
        $breaks = collect($this->quantityBreaks)
            ->filter(fn (array $b) => $b['min_qty'] !== '' && $b['price'] !== '')
            ->map(fn (array $b) => ['min_qty' => (int) $b['min_qty'], 'price' => (float) $b['price']])
            ->values()
            ->all();

        if ($this->editingBreaksVariantId) {
            ProductVariant::where('id', $this->editingBreaksVariantId)
                ->where('product_id', $this->product->id)
                ->update(['quantity_breaks' => $breaks]);

            $this->loadVariants();
        }

        $this->showQuantityBreaksModal = false;
        $this->editingBreaksVariantId = null;
        $this->quantityBreaks = [];

        \Flux\Flux::toast('Quantity breaks saved.', variant: 'success');
    }

    // ── Computed ──────────────────────────────────────────────────────────────

    /**
     * @return array<string, array<string, mixed>>
     */
    #[Computed]
    public function mergedAttributeSchema(): array
    {
        $schema = $this->product->variant_attributes_schema ?? [];

        // Normalize schema: if it's a numeric array OR uses attr_* keys from uniqid,
        // convert to associative using label as key
        if ($schema && (array_is_list($schema) || $this->looksLikeUniqidKeys($schema))) {
            return collect($schema)
                ->filter(fn ($attr) => isset($attr['label']) && $attr['label'] !== '')
                ->mapWithKeys(fn ($attr) => [
                    mb_strtolower(str_replace(' ', '_', $attr['label'])) => $attr,
                ])
                ->toArray();
        }

        return $schema;
    }

    public function render()
    {
        return view('livewire.company-hub.variant-manager');
    }

    /**
     * Check if schema uses auto-generated uniqid keys (attr_xxx).
     */
    private function looksLikeUniqidKeys(array $schema): bool
    {
        foreach (array_keys($schema) as $key) {
            if (! is_string($key) || ! str_starts_with($key, 'attr_')) {
                return false;
            }
        }

        return true;
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    private function validateVariantDraft(): void
    {
        $prefix = 'draft';

        $this->validate([
            'draftSku' => ['required', 'string', 'max:100'],
            'draftPrice' => ['required', 'numeric', 'min:0'],
            'draftMoq' => ['required', 'integer', 'min:1'],
            'draftUnit' => ['required', 'string', 'max:30'],
            'draftLeadTime' => ['nullable', 'integer', 'min:0'],
            'draftPackSize' => ['nullable', 'string', 'max:100'],
            'draftStatus' => ['required', 'in:published,archived'],
            'draftAttributes' => ['array'],
        ], [], [
            'draftSku' => 'SKU',
            'draftPrice' => 'Price',
            'draftMoq' => 'Minimum order quantity',
            'draftUnit' => 'Unit',
            'draftLeadTime' => 'Lead time',
            'draftPackSize' => 'Pack size',
            'draftStatus' => 'Status',
        ]);
    }

    /**
     * Build a blank attributes map keyed by schema key.
     *
     * @return array<string, string>
     */
    private function blankAttributes(): array
    {
        return array_fill_keys(array_keys($this->mergedAttributeSchema()), '');
    }

    private function loadVariants(): void
    {
        $this->variants = $this->product->variants()
            ->orderBy('created_at')
            ->get()
            ->map(fn (ProductVariant $v) => [
                'id' => $v->id,
                'sku' => $v->sku,
                'price' => (string) $v->price,
                'minimum_order_quantity' => (string) $v->minimum_order_quantity,
                'unit' => $v->unit,
                'lead_time_days' => $v->lead_time_days !== null ? (string) $v->lead_time_days : '',
                'pack_size' => $v->pack_size ?? '',
                'status' => $v->status,
                'quantity_breaks' => $v->quantity_breaks ?? [],
                'attributes' => $v->attributes ?? [],
            ])
            ->all();
    }
}
