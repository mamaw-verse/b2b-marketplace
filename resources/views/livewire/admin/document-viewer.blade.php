{{-- Document Viewer Modal --}}
<flux:modal wire:model="showDocumentViewer" class="md:w-[48rem]">
    <div class="space-y-4">
        <flux:heading size="lg">Document Preview</flux:heading>

        @if ($documentPreviewUrl)
            @php
                $media = $viewingMediaId ? \App\Models\Media::find($viewingMediaId) : null;
                $isImage = $media && in_array($media->extension, ['jpg', 'jpeg', 'png', 'webp']);
            @endphp

            @if ($isImage)
                <div class="flex justify-center">
                    <img src="{{ $documentPreviewUrl }}" alt="{{ $media->original_filename }}" class="max-h-[60vh] rounded">
                </div>
            @else
                <iframe src="{{ $documentPreviewUrl }}" class="h-[60vh] w-full rounded border" title="Document Preview"></iframe>
            @endif

            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-[var(--hs-neutral-500)]">{{ $media?->original_filename }}</flux:text>
                <flux:button variant="ghost" wire:click="closeDocumentViewer">Close</flux:button>
            </div>
        @else
            <flux:text>Loading document...</flux:text>
        @endif
    </div>
</flux:modal>
