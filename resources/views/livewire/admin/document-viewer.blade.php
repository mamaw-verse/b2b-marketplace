{{-- Document Viewer Modal --}}
<flux:modal wire:model="showDocumentViewer" class="w-full md:w-[64rem] lg:w-[76rem] max-w-[calc(100vw-2rem)]">
    <div class="space-y-4">
        @if ($documentPreviewUrl)
            @php
                $media = $viewingMediaId ? \App\Models\Media::find($viewingMediaId) : null;
                $isImage = $media && in_array($media->extension, ['jpg', 'jpeg', 'png', 'webp']);
            @endphp

            <div class="flex items-start justify-between gap-4 pe-10">
                <div class="min-w-0">
                    <flux:heading size="lg">{{ __('Document Preview') }}</flux:heading>
                    <p class="mt-1 truncate text-sm text-[var(--hs-neutral-500)]">{{ $media?->original_filename }}</p>
                </div>
                <flux:button
                    variant="ghost"
                    size="sm"
                    icon="arrow-top-right-on-square"
                    href="{{ $documentPreviewUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="shrink-0"
                >
                    {{ __('Open in new tab') }}
                </flux:button>
            </div>

            @if ($isImage)
                <div class="flex justify-center rounded border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] p-2">
                    <img src="{{ $documentPreviewUrl }}" alt="{{ $media->original_filename }}" class="max-h-[75vh] rounded">
                </div>
            @else
                <iframe src="{{ $documentPreviewUrl }}" class="h-[75vh] w-full rounded border border-[var(--hs-neutral-200)]" title="{{ __('Document Preview') }}"></iframe>
            @endif

            <div class="flex justify-end">
                <flux:button variant="ghost" wire:click="closeDocumentViewer">{{ __('Close') }}</flux:button>
            </div>
        @else
            <flux:heading size="lg">{{ __('Document Preview') }}</flux:heading>
            <flux:text>{{ __('Loading document...') }}</flux:text>
        @endif
    </div>
</flux:modal>
