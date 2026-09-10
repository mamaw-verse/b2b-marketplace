<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Livewire\Concerns\HasDocumentViewer;
use App\Models\SourcingRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Sourcing Requests')]
final class SourcingQueue extends Component
{
    use HasDocumentViewer;
    use WithPagination;

    public string $search = '';

    #[Url(as: 'status', except: '')]
    public string $statusFilter = '';

    public function updated($property): void
    {
        if (in_array($property, ['search', 'statusFilter'])) {
            $this->resetPage();
        }
    }

    /**
     * Product sourcing requests, newest first.
     */
    #[Computed]
    public function requests(): LengthAwarePaginator
    {
        return SourcingRequest::query()
            ->when($this->search !== '', fn ($query) => $query->where(function ($q) {
                $q->where('name', 'ilike', '%'.$this->search.'%')
                    ->orWhere('email', 'ilike', '%'.$this->search.'%')
                    ->orWhere('product_name', 'ilike', '%'.$this->search.'%');
            }))
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->latest()
            ->paginate(15);
    }

    /**
     * Open a reference file in the document viewer modal.
     */
    public function viewReferenceDocument(int $requestId): void
    {
        $request = SourcingRequest::findOrFail($requestId);

        if (! $this->canPreviewDocument($request->reference_file_path)) {
            return;
        }

        $this->viewingMediaId = null;
        $this->documentPreviewUrl = $this->referenceFileUrl($request->id);
        $this->showDocumentViewer = true;
    }

    public function canPreviewDocument(?string $filePath): bool
    {
        if ($filePath === null) {
            return false;
        }

        return in_array(
            str(pathinfo($filePath, PATHINFO_EXTENSION))->lower()->toString(),
            ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
            true
        );
    }

    public function referenceFileUrl(int $requestId, bool $download = false): ?string
    {
        $request = SourcingRequest::find($requestId);

        if ($request === null || $request->reference_file_path === null) {
            return null;
        }

        $parameters = ['sourcingRequest' => $request->id];

        if ($download) {
            $parameters['download'] = '1';
        }

        return route('admin.sourcing-requests.document', $parameters);
    }

    public function render()
    {
        return view('livewire.admin.sourcing-queue');
    }
}
