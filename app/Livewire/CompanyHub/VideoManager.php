<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub;

use App\Models\Product;
use App\Services\MediaUploadService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Product Video')]
final class VideoManager extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $productId;

    public ?TemporaryUploadedFile $newVideo = null;

    public function mount(Product $product): void
    {
        $this->authorize('update', $product);
        $this->productId = $product->id;
    }

    public function uploadVideo(MediaUploadService $uploader): void
    {
        $maxKb = (int) config('marketplace.video.max_kb', 51200);

        $this->validate([
            'newVideo' => ['required', 'file', 'mimes:mp4,webm', 'mimetypes:video/mp4,video/webm', 'max:'.$maxKb],
        ]);

        $product = $this->product;
        $this->authorize('update', $product);

        $media = $uploader->storeVideo($this->newVideo, Auth::id(), 'Product video');

        DB::transaction(function () use ($product, $media): void {
            $product->update(['video_media_id' => $media->id, 'updated_by_id' => Auth::id()]);
        });

        $this->reset('newVideo');
        unset($this->product, $this->hasPendingScan);
        Flux::toast(__('Video uploaded successfully.'), variant: 'success');
    }

    public function removeVideo(): void
    {
        $product = $this->product;
        $this->authorize('update', $product);
        $product->update(['video_media_id' => null, 'updated_by_id' => Auth::id()]);

        unset($this->product, $this->hasPendingScan);
        Flux::toast(__('Video removed.'), variant: 'success');
    }

    #[Computed]
    public function product(): Product
    {
        return Product::with('video')->findOrFail($this->productId);
    }

    #[Computed]
    public function hasPendingScan(): bool
    {
        return (bool) config('services.clamav.enabled', false)
            && $this->product->video?->scan_status === 'pending';
    }

    public function refreshScanStatus(): void
    {
        unset($this->product, $this->hasPendingScan);
    }

    public function render(): View
    {
        return view('livewire.company-hub.video-manager');
    }
}
