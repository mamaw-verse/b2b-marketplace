<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub;

use App\Livewire\Forms\VariantForm;
use App\Models\Product;
use App\Models\ProductVariant;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Manage Variants')]
final class VariantManager extends Component
{
    #[Locked]
    public int $productId;

    /** @var array<int, array<string, mixed>> */
    public array $variants = [];

    // ── Add / Edit modal ─────────────────────────────────────────────────────

    public bool $showVariantModal = false;

    public VariantForm $form;

    // ── Quantity breaks modal ─────────────────────────────────────────────────

    public bool $showQuantityBreaksModal = false;

    public ?int $editingBreaksVariantId = null;

    /** @var array<int, array{min_qty: int|string, price: float|string}> */
    public array $quantityBreaks = [];

    // ── Confirm dialogs ────────────────────────────────────────────────────────

    public ?int $confirmingBreakIndex = null;

    // ─────────────────────────────────────────────────────────────────────────

    #[Computed]
    public function product(): Product
    {
        return Product::findOrFail($this->productId);
    }

    public function mount(Product $product): void
    {
        $this->authorize('update', $product);
        $this->productId = $product->id;
        $this->loadVariants();
    }

    // ── Variant modal ─────────────────────────────────────────────────────────

    public function openAddVariant(): void
    {
        $this->form->setForCreate($this->blankAttributes());
        $this->resetValidation();
        $this->showVariantModal = true;
    }

    public function openEditVariant(int $variantId): void
    {
        $variant = collect($this->variants)->firstWhere('id', $variantId);

        if (! $variant) {
            return;
        }

        $this->form->setForEdit($variant, $this->blankAttributes());
        $this->resetValidation();
        $this->showVariantModal = true;
    }

    public function saveVariant(): void
    {
        $this->form->validate();

        $data = $this->form->toVariantData($this->product()->id);

        if ($this->form->editingVariantId) {
            ProductVariant::where('id', $this->form->editingVariantId)
                ->where('product_id', $this->product()->id)
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

    public function toggleStatus(int $variantId): void
    {
        $variant = ProductVariant::where('id', $variantId)
            ->where('product_id', $this->product()->id)
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

    public function removeQuantityBreak(): void
    {
        if ($this->confirmingBreakIndex === null) {
            return;
        }

        unset($this->quantityBreaks[$this->confirmingBreakIndex]);
        $this->quantityBreaks = array_values($this->quantityBreaks);
        $this->confirmingBreakIndex = null;
    }

    public function confirmRemoveQuantityBreak(int $index): void
    {
        $this->confirmingBreakIndex = $index;
        \Flux\Flux::modal('confirm-remove-quantity-break')->show();
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
                ->where('product_id', $this->product()->id)
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
        $schema = $this->product()->variant_attributes_schema ?? [];

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
        $this->variants = $this->product()->variants()
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
