<?php

declare(strict_types=1);

namespace App\Livewire\Account\Disputes;

use App\Events\DisputeOpened;
use App\Models\Dispute;
use App\Models\Order;
use App\Services\MediaUploadService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

final class DisputeForm extends Component
{
    use WithFileUploads;

    public Order $order;

    public bool $show = false;

    public string $type = 'refund_only';

    public string $reason = '';

    /**
     * Per-order-item selection state, keyed by order_item_id.
     * Shape: [orderItemId => ['selected' => bool, 'quantity' => int|string]]
     *
     * @var array<int, array{selected: bool, quantity: int|string}>
     */
    public array $selectedItems = [];

    /** @var array<int, TemporaryUploadedFile> */
    public array $disputePhotos = [];

    public function mount(Order $order): void
    {
        Gate::authorize('view', $order);
        $this->order = $order->loadMissing('items');
    }

    #[On('open-dispute-modal')]
    public function openModal(): void
    {
        Gate::authorize('view', $this->order);

        $this->reset(['type', 'reason', 'disputePhotos', 'selectedItems']);
        $this->resetErrorBag();

        // Seed a row per order item, unselected, defaulting to the full ordered quantity.
        foreach ($this->order->items as $item) {
            $this->selectedItems[$item->id] = [
                'selected' => false,
                'quantity' => (int) $item->quantity,
            ];
        }

        $this->show = true;
    }

    public function submit(): void
    {
        Gate::authorize('view', $this->order);

        $this->validate([
            'type' => 'required|in:return_refund,refund_only',
            'reason' => 'required|string|max:2000',
            'disputePhotos' => ['required', 'array', 'min:1', 'max:5'],
            'disputePhotos.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,pdf'],
        ], [
            'disputePhotos.required' => 'Please attach at least one supporting photo.',
            'disputePhotos.min' => 'Please attach at least one supporting photo.',
        ]);

        $chosen = $this->validateSelectedItems();

        $status = $this->type === 'return_refund' ? 'awaiting_return' : 'open';

        $dispute = DB::transaction(function () use ($status, $chosen): Dispute {
            $dispute = Dispute::create([
                'order_id' => $this->order->id,
                'opened_by_user_id' => Auth::id(),
                'type' => $this->type,
                'status' => $status,
                'reason' => $this->reason,
            ]);

            foreach ($chosen as $orderItemId => $quantity) {
                $dispute->items()->create([
                    'order_item_id' => $orderItemId,
                    'quantity' => $quantity,
                ]);
            }

            return $dispute;
        });

        // Upload and attach photos
        if (! empty($this->disputePhotos)) {
            $mediaService = app(MediaUploadService::class);
            $mediaIds = [];
            foreach ($this->disputePhotos as $photo) {
                $media = $mediaService->store($photo, Auth::id(), 'dispute_photo');
                $mediaIds[] = $media->id;
            }
            $dispute->photos()->attach($mediaIds);
        }

        DisputeOpened::dispatch($dispute);

        $this->show = false;

        Flux::toast('Dispute opened.', variant: 'success');

        // Tell the parent order-detail component to refresh so the new dispute shows.
        $this->dispatch('dispute-opened');
    }

    public function render()
    {
        return view('livewire.account.disputes.dispute-form');
    }

    /**
     * Validate the item selection and return the chosen [order_item_id => quantity] map.
     * At least one item must be selected; each selected quantity must be a whole
     * number between 1 and the quantity that was actually ordered for that item.
     *
     * @return array<int, int>
     */
    private function validateSelectedItems(): array
    {
        $orderItems = $this->order->items->keyBy('id');
        $chosen = [];

        /** @var array<string, string> $messages */
        $messages = [];

        foreach ($this->selectedItems as $orderItemId => $row) {
            if (empty($row['selected'])) {
                continue;
            }

            $orderItem = $orderItems->get((int) $orderItemId);

            if (! $orderItem) {
                continue;
            }

            $quantity = (int) ($row['quantity'] ?? 0);
            $maxQuantity = (int) $orderItem->quantity;

            if ($quantity < 1) {
                $messages["selectedItems.{$orderItemId}.quantity"] = 'Quantity must be at least 1.';

                continue;
            }

            if ($quantity > $maxQuantity) {
                $messages["selectedItems.{$orderItemId}.quantity"] = "Quantity cannot exceed {$maxQuantity} (ordered).";

                continue;
            }

            $chosen[(int) $orderItemId] = $quantity;
        }

        if ($chosen === [] && $messages === []) {
            $messages['selectedItems'] = 'Select at least one item to dispute.';
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }

        return $chosen;
    }
}
