<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub\Disputes;

use App\Actions\ResolveDisputeAction;
use App\Models\Dispute;
use App\Models\Order;
use App\Services\MediaUploadService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class DisputePanel extends Component
{
    public Order $order;

    public ?Dispute $dispute = null;

    public string $resolutionType = 'full_refund';

    public ?string $resolutionAmount = null;

    public string $resolutionNotes = '';

    public function mount(Order $order): void
    {
        $this->order = $order;
        $this->dispute = $order->disputes()
            ->whereNotIn('status', ['resolved_refunded', 'resolved_denied', 'withdrawn'])
            ->with(['items.orderItem', 'photos', 'openedBy'])
            ->latest()
            ->first();

        // A return_refund dispute whose return hasn't been confirmed can only be
        // denied — force the resolution type so the deny-only UI can't submit a refund.
        if ($this->dispute
            && $this->dispute->type === 'return_refund'
            && in_array($this->dispute->status, ['open', 'awaiting_return'], true)
        ) {
            $this->resolutionType = 'denied';
        }
    }

    /**
     * Keep the partial-refund amount in sync with the chosen resolution type:
     * clear it whenever the type is not partial_refund so a stale amount can
     * never linger in component state.
     */
    public function updatedResolutionType(string $value): void
    {
        if ($value !== 'partial_refund') {
            $this->resolutionAmount = null;
            $this->resetErrorBag('resolutionAmount');
        }
    }

    /**
     * Signed, time-limited preview URLs for the buyer's supporting photos,
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

    public function confirmReturn(): void
    {
        Gate::authorize('manageDispute', $this->order);

        if (! $this->dispute || ! in_array($this->dispute->status, ['open', 'awaiting_return'])) {
            return;
        }

        $this->dispute->update(['status' => 'return_received']);

        \Flux\Flux::toast('Return confirmed as received.', variant: 'success');

        // Reload so the panel re-renders with refund options unlocked.
        $this->redirectRoute('hub.orders.show', ['order' => $this->order->id], navigate: true);
    }

    public function resolve(): void
    {
        Gate::authorize('manageDispute', $this->order);

        if (! $this->dispute || in_array($this->dispute->status, ['resolved_refunded', 'resolved_denied', 'withdrawn'])) {
            return;
        }

        // Inline validation for the partial-refund amount so the seller gets
        // immediate feedback rather than a post-submit flash. Full refund and
        // denial ignore the amount entirely.
        if ($this->resolutionType === 'partial_refund') {
            $this->validate([
                'resolutionAmount' => ['required', 'numeric', 'gt:0', 'lte:'.(float) $this->order->order_total],
            ], [
                'resolutionAmount.required' => 'Enter the amount to refund.',
                'resolutionAmount.gt' => 'The refund amount must be greater than zero.',
                'resolutionAmount.lte' => 'The refund amount cannot exceed the order total.',
            ]);
        }

        $result = (new ResolveDisputeAction)->execute($this->dispute, Auth::user(), [
            'resolution_type' => $this->resolutionType,
            'resolution_amount' => $this->resolutionAmount ? (float) $this->resolutionAmount : null,
            'resolution_notes' => $this->resolutionNotes,
        ]);

        if ($result['success']) {
            \Flux\Flux::toast('Dispute resolved.', variant: 'success');

            // Full page reload so the order status, fulfilment section, and this
            // panel all reflect the resolved state.
            $this->redirectRoute('hub.orders.show', ['order' => $this->order->id], navigate: true);

            return;
        }

        \Flux\Flux::toast($result['error'], variant: 'danger');
    }

    public function escalate(): void
    {
        Gate::authorize('manageDispute', $this->order);

        if (! $this->dispute || in_array($this->dispute->status, ['escalated', 'resolved_refunded', 'resolved_denied', 'withdrawn'])) {
            return;
        }

        $this->dispute->update(['status' => 'escalated', 'escalated_at' => now()]);
        \App\Events\DisputeEscalated::dispatch($this->dispute->fresh());

        \Flux\Flux::toast('Dispute escalated to admin.', variant: 'success');

        // Reload so the escalated state is reflected across the page.
        $this->redirectRoute('hub.orders.show', ['order' => $this->order->id], navigate: true);
    }

    public function render()
    {
        return view('livewire.company-hub.disputes.dispute-panel');
    }
}
