<div @if ($this->hasPendingScan) wire:poll.5s="refreshScanStatus" @endif>
    @include('livewire.company-hub.partials.product-tabs', ['product' => $this->product])

    <div class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] shadow-[var(--hs-shadow-sm)]">
        <div class="flex items-center justify-between border-b border-[var(--hs-neutral-100)] px-6 py-5">
            <div>
                <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Product video') }}</h1>
                <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ $this->product->name }}</p>
            </div>
        </div>

        <div class="space-y-6 p-6">
            @if ($this->hasPendingScan)
                <div class="flex items-center gap-2 rounded-[var(--hs-radius-card)] border border-[var(--hs-warning-200)] bg-[var(--hs-warning-50)] px-4 py-2.5 text-sm text-[var(--hs-warning-700)]" aria-live="polite">
                    <flux:icon name="arrow-path" class="size-4 shrink-0 animate-spin" />
                    {{ __('Checking scan status automatically…') }}
                </div>
            @endif

            @if ($this->product->video)
                <div class="max-w-3xl space-y-3">
                    @if ($this->product->video->scan_status === 'clean')
                        <video controls preload="metadata" playsinline class="w-full rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-black" @if ($this->product->featured_image_url) poster="{{ $this->product->featured_image_url }}" @endif>
                            <source src="{{ route('media.view', $this->product->video) }}" type="{{ $this->product->video->mime_type }}">
                            {{ __('Your browser does not support video playback.') }}
                        </video>
                    @elseif ($this->product->video->scan_status === 'infected')
                        <p class="rounded-[var(--hs-radius-card)] border border-[var(--hs-danger-200)] bg-[var(--hs-danger-50)] p-4 text-sm text-[var(--hs-danger-700)]">{{ __('This video did not pass the security scan and is not available for playback.') }}</p>
                    @else
                        <p class="rounded-[var(--hs-radius-card)] border border-[var(--hs-warning-200)] bg-[var(--hs-warning-50)] p-4 text-sm text-[var(--hs-warning-700)]">{{ __('This video will be available after its security scan is complete.') }}</p>
                    @endif

                    <flux:modal.trigger name="remove-video-confirm">
                        <flux:button variant="danger" icon="trash">{{ __('Remove video') }}</flux:button>
                    </flux:modal.trigger>
                </div>
            @else
                <x-hs-empty-state icon="film" title="{{ __('No video yet') }}" subtitle="{{ __('Upload one MP4 or WebM video for this product.') }}" />
            @endif

            <p class="text-xs text-[var(--hs-neutral-500)]">{{ __('Square video (1:1) works best — e.g. 1080 × 1080 px.') }}</p>

            <x-hs-dropzone
                input-id="product-video-input"
                wire-model="newVideo"
                accept="video/mp4,video/webm,.mp4,.webm"
                :multiple="false"
                hint="{{ __('MP4 or WebM — up to 50 MB') }}"
                on-finish="$wire.uploadVideo()"
                loading-target="newVideo,uploadVideo"
            >
                @error('newVideo') <p class="mt-2 text-sm text-[var(--hs-danger-700)]">{{ $message }}</p> @enderror
            </x-hs-dropzone>
        </div>
    </div>

    <x-hs-confirm
        name="remove-video-confirm"
        title="{{ __('Remove video') }}"
        message="{{ __('Remove this product video?') }}"
        variant="danger"
        confirmText="{{ __('Remove') }}"
    >
        <x-slot name="action">
            <flux:button variant="danger" wire:click="removeVideo" wire:loading.attr="disabled">{{ __('Remove') }}</flux:button>
        </x-slot>
    </x-hs-confirm>
</div>
