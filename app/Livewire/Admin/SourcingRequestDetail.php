<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Livewire\Concerns\HasDocumentViewer;
use App\Models\SourcingRequest;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Sourcing Request')]
final class SourcingRequestDetail extends Component
{
    use HasDocumentViewer;

    public SourcingRequest $sourcingRequest;

    public string $status = '';

    public string $notes = '';

    public function mount(SourcingRequest $sourcingRequest): void
    {
        $this->sourcingRequest = $sourcingRequest;
        $this->status = $sourcingRequest->status;
        $this->notes = $sourcingRequest->notes ?? '';
    }

    public function save(): void
    {
        $this->validate([
            'status' => ['required', 'string', 'in:pending,in_progress,completed'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->sourcingRequest->update([
            'status' => $this->status,
            'notes' => $this->notes,
        ]);

        $this->dispatch('toast', message: 'Sourcing request updated.');
    }

    public function viewReferenceDocument(): void
    {
        if (! $this->sourcingRequest->reference_file_path) {
            return;
        }

        $this->viewingMediaId = null;
        $this->documentPreviewUrl = $this->referenceFileUrl();
        $this->showDocumentViewer = true;
    }

    public function canPreviewDocument(): bool
    {
        $filePath = $this->sourcingRequest->reference_file_path;

        if ($filePath === null) {
            return false;
        }

        return in_array(
            str(pathinfo($filePath, PATHINFO_EXTENSION))->lower()->toString(),
            ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
            true
        );
    }

    public function referenceFileUrl(bool $download = false): ?string
    {
        $request = $this->sourcingRequest;

        if ($request->reference_file_path === null) {
            return null;
        }

        $parameters = ['sourcingRequest' => $request->id];

        if ($download) {
            $parameters['download'] = '1';
        }

        return route('admin.sourcing-requests.document', $parameters);
    }

    public function isImage(): bool
    {
        $filePath = $this->sourcingRequest->reference_file_path;

        if ($filePath === null) {
            return false;
        }

        return in_array(
            str(pathinfo($filePath, PATHINFO_EXTENSION))->lower()->toString(),
            ['jpg', 'jpeg', 'png', 'webp'],
            true
        );
    }

    public function render()
    {
        return view('livewire.admin.sourcing-request-detail');
    }
}
