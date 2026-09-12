<?php

declare(strict_types=1);

namespace App\Livewire\Account\Disputes;

use App\Events\DisputeEscalated;
use App\Models\Dispute;
use App\Models\Order;
use App\Services\MediaUploadService;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

final class DisputeDetail extends Component
{
    public Order $order;

    public ?Dispute $dispute = null;

    public bool $show = false;

    public function mount(Order $order): void
    {
        Gate::authorize('view', $order);
        $this->order = $order;
    }

    #[On('open-dispute-detail-modal')]
    public function openModal(int $disputeId): void
    {
        Gate::authorize('view', $this->order);

        $dispute = $this->order->disputes()
            ->with(['items.orderItem', 'photos'])
            ->findOrFail($disputeId);

        $this->dispute = $dispute;
        $this->show = true;
    }

    /**
     * Signed, time-limited preview URLs for the dispute's supporting photos,
     * keyed by media id. Private media is never served via a public path.
     *
     * @return array<int, array{id: int, filename: string, is_image: bool, url: string}>
     */
    #[Computed]
    public function photos(): array
    {
        if (! $this->dispute) {
            return [];
        }

        $service = app(MediaUploadService::class);

        return $this->dispute->photos->map(fn ($media) => [
            'id' => $media->id,
            'filename' => $media->original_filename,
            'is_image' => in_array($media->extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true),
            'url' => $service->signedUrl($media),
        ])->all();
    }

    public function escalate(): void
    {
        if (! $this->dispute || in_array($this->dispute->status, ['escalated', 'resolved_refunded', 'resolved_denied', 'withdrawn'])) {
            return;
        }

        $this->dispute->update(['status' => 'escalated', 'escalated_at' => now()]);
        DisputeEscalated::dispatch($this->dispute->fresh());
        $this->dispute->refresh();

        Flux::toast('Dispute escalated to admin.', variant: 'success');
        $this->dispatch('dispute-updated');
    }

    public function withdraw(): void
    {
        if (! $this->dispute || in_array($this->dispute->status, ['resolved_refunded', 'resolved_denied', 'withdrawn'])) {
            return;
        }

        $this->dispute->update(['status' => 'withdrawn']);
        $this->dispute->refresh();
        $this->show = false;

        Flux::toast('Dispute withdrawn.', variant: 'success');
        $this->dispatch('dispute-updated');
    }

    public function render()
    {
        return view('livewire.account.disputes.dispute-detail');
    }
}
