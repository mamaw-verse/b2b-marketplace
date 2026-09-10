{{-- Document Viewer Modal --}}
<flux:modal wire:model="showDocumentViewer" class="w-full md:w-[64rem] lg:w-[76rem] max-w-[calc(100vw-2rem)] h-[85vh] max-h-[85vh] overflow-hidden flex flex-col">
    <div class="flex h-full min-h-0 flex-col gap-4 overflow-hidden">
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

            <div class="min-h-0 flex-1 overflow-auto rounded border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)]">
                @if ($isImage)
                    <div class="flex justify-center p-2">
                        <img src="{{ $documentPreviewUrl }}" alt="{{ $media->original_filename }}" class="max-h-full rounded">
                    </div>
                @else
                    <iframe src="{{ $documentPreviewUrl }}" class="h-full w-full" title="{{ __('Document Preview') }}"></iframe>
                @endif
            </div>

            <div class="flex justify-end shrink-0">
                <flux:button variant="ghost" wire:click="closeDocumentViewer">{{ __('Close') }}</flux:button>
            </div>
        @else
            <flux:heading size="lg">{{ __('Document Preview') }}</flux:heading>
            <flux:text>{{ __('Loading document...') }}</flux:text>
        @endif
    </div>
</flux:modal>
