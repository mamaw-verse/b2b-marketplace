<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub;

use App\Models\Product;
use App\Services\MediaUploadService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Document Manager')]
final class DocumentManager extends Component
{
    use WithFileUploads;

    public Product $product;

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $newDocuments = [];

    public function mount(Product $product): void
    {
        $this->authorize('update', $product);
        $this->product = $product;
    }

    public function uploadDocuments(MediaUploadService $uploader): void
    {
        $this->validate([
            'newDocuments' => ['required', 'array', 'min:1'],
            'newDocuments.*' => ['file', 'max:20480', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,webp'],
        ]);

        foreach ($this->newDocuments as $doc) {
            $media = $uploader->store($doc, Auth::id(), 'Product document');
            $this->product->documents()->attach($media->id);
        }

        $this->reset('newDocuments');
        \Flux\Flux::toast('Documents uploaded successfully.', variant: 'success');
    }

    public function removeDocument(int $mediaId): void
    {
        $this->product->documents()->detach($mediaId);

        \Flux\Flux::toast('Document removed.', variant: 'success');
    }

    /**
     * @return Collection<int, \App\Models\Media>
     */
    #[Computed]
    public function documents(): Collection
    {
        return $this->product->documents()->orderByDesc('created_at')->get();
    }

    public function render()
    {
        return view('livewire.company-hub.document-manager');
    }
}
