<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub;

use App\Events\QuotationSubmitted;
use App\Models\Quotation;
use App\Models\RfqRecipient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Quotation Builder')]
final class QuotationBuilder extends Component
{
    public ?Quotation $quotation = null;

    public RfqRecipient $rfqRecipient;

    public string $notes = '';

    /** @var array<int, array{description: string, quantity: string, unit: string, unit_price: string}> */
    public array $items = [];

    public bool $showSubmitConfirmation = false;

    public function mount(RfqRecipient $rfqRecipient, ?Quotation $quotation = null): void
    {
        Gate::authorize('view', $rfqRecipient);

        $this->rfqRecipient = $rfqRecipient;

        if ($quotation?->exists) {
            Gate::authorize('update', $quotation);
            $this->quotation = $quotation;
            $this->notes = $quotation->notes ?? '';
            $this->items = $quotation->items->map(fn ($item) => [
                'description' => $item->description,
                'quantity' => (string) $item->quantity,
                'unit' => $item->unit,
                'unit_price' => (string) $item->unit_price,
            ])->toArray();
        }

        if (empty($this->items)) {
            $this->addItem();
        }
    }

    public function addItem(): void
    {
        $this->items[] = ['description' => '', 'quantity' => '', 'unit' => '', 'unit_price' => ''];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);

        if (empty($this->items)) {
            $this->addItem();
        }
    }

    public function saveDraft(): void
    {
        $this->validate();

        DB::transaction(function () {
            if ($this->quotation) {
                $this->quotation->update(['notes' => $this->notes ?: null]);
                $this->quotation->items()->delete();
            } else {
                $this->quotation = Quotation::create([
                    'rfq_id' => $this->rfqRecipient->rfq_id,
                    'rfq_recipient_id' => $this->rfqRecipient->id,
                    'status' => 'draft',
                    'notes' => $this->notes ?: null,
                    'currency' => 'PHP',
                ]);
            }

            $totalAmount = 0;
            foreach ($this->items as $index => $item) {
                $subtotal = round((float) $item['quantity'] * (float) $item['unit_price'], 2);
                $totalAmount += $subtotal;
                $this->quotation->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                    'sort_order' => $index,
                ]);
            }

            $this->quotation->update(['total_amount' => $totalAmount]);
        });

        \Flux\Flux::toast('Draft saved.', variant: 'success');
    }

    public function confirmSubmit(): void
    {
        $this->validate();
        $this->showSubmitConfirmation = true;
    }

    public function submitQuotation(): void
    {
        $this->saveDraft();

        if (! $this->quotation || $this->quotation->items()->count() === 0) {
            $this->addError('items', 'At least one line item is required.');

            return;
        }

        $buyerProfile = $this->quotation->rfq->user->buyerProfile;
        $buyerSnapshot = $buyerProfile ? $buyerProfile->toSnapshot() : $this->quotation->rfq->buyer_snapshot;

        $this->quotation->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'valid_until' => now()->addDays(14),
            'buyer_snapshot' => $buyerSnapshot,
        ]);

        $this->quotation->recalculateTotal();
        $this->quotation->rfqRecipient->touchActivity();
        $this->quotation->rfq->touchActivity();

        QuotationSubmitted::dispatch($this->quotation->fresh());

        $this->showSubmitConfirmation = false;

        \Flux\Flux::toast('Quotation submitted successfully.', variant: 'success');

        $this->redirect(route('hub.rfqs.show', $this->rfqRecipient), navigate: true);
    }

    public function withdrawQuotation(): void
    {
        if (! $this->quotation || ! $this->quotation->isSubmitted()) {
            return;
        }

        Gate::authorize('withdraw', $this->quotation);

        $this->quotation->update(['status' => 'withdrawn']);
        $this->quotation->rfq->touchActivity();

        \Flux\Flux::toast('Quotation withdrawn.', variant: 'warning');

        $this->redirect(route('hub.rfqs.show', $this->rfqRecipient), navigate: true);
    }

    public function render()
    {
        return view('livewire.company-hub.quotation-builder');
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit' => ['required', 'string', 'max:30'],
            'items.*.unit_price' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
