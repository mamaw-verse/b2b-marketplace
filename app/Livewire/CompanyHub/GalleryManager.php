<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub;

use App\Models\Product;
use App\Models\ProductGallery;
use App\Services\MediaUploadService;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Gallery Manager')]
final class GalleryManager extends Component
{
    use WithFileUploads;

    public Product $product;

    /** @var array<int, TemporaryUploadedFile> */
    public array $newPhotos = [];

    public ?int $confirmingImageId = null;

    public function mount(Product $product): void
    {
        $this->authorize('update', $product);
        $this->product = $product;
    }

    public function uploadPhotos(MediaUploadService $uploader): void
    {
        $this->validate([
            'newPhotos' => ['required', 'array', 'min:1'],
            'newPhotos.*' => ['image', 'max:5120'],
        ]);

        $maxSort = $this->product->gallery()->max('sort_order') ?? 0;

        foreach ($this->newPhotos as $photo) {
            $media = $uploader->store($photo, Auth::id(), 'Product gallery image');

            ProductGallery::create([
                'product_id' => $this->product->id,
                'media_id' => $media->id,
                'sort_order' => ++$maxSort,
            ]);
        }

        $this->reset('newPhotos');
        unset($this->galleryItems, $this->hasPendingPhotos);
        Flux::toast('Photos uploaded successfully.', variant: 'success');
    }

    public function updateGalleryOrder(int|string $id, int $position): void
    {
        ProductGallery::where('id', $id)
            ->where('product_id', $this->product->id)
            ->update(['sort_order' => $position]);

        // Re-sequence all items to avoid gaps
        $this->product->gallery()
            ->orderBy('sort_order')
            ->get()
            ->each(function (ProductGallery $item, int $index): void {
                $item->update(['sort_order' => $index]);
            });
    }

    public function confirmRemoveImage(int $galleryId): void
    {
        $this->confirmingImageId = $galleryId;
        Flux::modal('remove-image-confirm')->show();
    }

    public function removeImage(): void
    {
        if (! $this->confirmingImageId) {
            return;
        }

        ProductGallery::where('id', $this->confirmingImageId)
            ->where('product_id', $this->product->id)
            ->delete();

        $this->confirmingImageId = null;
        unset($this->galleryItems, $this->hasPendingPhotos);
        Flux::toast('Image removed.', variant: 'success');
    }

    /**
     * @return Collection<int, ProductGallery>
     */
    #[Computed]
    public function galleryItems(): Collection
    {
        return $this->product->gallery()->with('media')->orderBy('sort_order')->get();
    }

    /**
     * Whether any gallery image is still awaiting its antivirus scan.
     */
    #[Computed]
    public function hasPendingPhotos(): bool
    {
        if (! (bool) config('services.clamav.enabled', false)) {
            return false;
        }

        return $this->galleryItems->contains(
            fn (ProductGallery $item): bool => $item->media?->scan_status === 'pending'
        );
    }

    public function refreshPhotos(): void
    {
        unset($this->galleryItems, $this->hasPendingPhotos);
    }

    public function render()
    {
        return view('livewire.company-hub.gallery-manager');
    }
}
