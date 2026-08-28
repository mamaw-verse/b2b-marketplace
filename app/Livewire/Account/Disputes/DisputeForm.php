<?php

declare(strict_types=1);

namespace App\Livewire\Account\Disputes;

use App\Events\DisputeOpened;
use App\Models\Dispute;
use App\Models\Order;
use App\Services\MediaUploadService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.portal', ['portal' => 'account'])]
#[Title('Open Dispute')]
final class DisputeForm extends Component
{
    use WithFileUploads;

    public Order $order;

    public string $type = 'refund_only';

    public string $reason = '';

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $disputePhotos = [];

    public function mount(Order $order): void
    {
        Gate::authorize('view', $order);
        $this->order = $order;
    }

    public function submit(): void
    {
        $this->validate([
            'type' => 'required|in:return_refund,refund_only',
            'reason' => 'required|string|max:2000',
            'disputePhotos' => ['nullable', 'array', 'max:5'],
            'disputePhotos.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,pdf'],
        ]);

        $status = $this->type === 'return_refund' ? 'awaiting_return' : 'open';

        $dispute = Dispute::create([
            'order_id' => $this->order->id,
            'opened_by_user_id' => Auth::id(),
            'type' => $this->type,
            'status' => $status,
            'reason' => $this->reason,
        ]);

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

        session()->flash('success', 'Dispute opened.');
        $this->redirectRoute('account.orders.show', ['order' => $this->order->id]);
    }

    public function render()
    {
        return view('livewire.account.disputes.dispute-form');
    }
}
