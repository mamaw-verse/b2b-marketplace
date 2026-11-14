{{--
    Poll every 5s while any photo is pending scan. Calls refreshPhotos to bust
    the computed cache. Stops automatically once hasPendingPhotos is false.
--}}
<div @if ($this->hasPendingPhotos) wire:poll.5s="refreshPhotos" @endif>
    @include('livewire.company-hub.partials.product-tabs')

    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        {{-- Card Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)] flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Gallery') }}</h1>
                <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ $product->name }}</p>
            </div>
        </div>

        <div class="p-6">

            <p class="mb-1.5 text-xs text-[var(--hs-neutral-500)]">{{ __('Images must not contain contact information such as phone numbers, email addresses, social media links, or website URLs.') }}</p>
            <p class="mb-3 text-xs text-[var(--hs-neutral-500)]">{{ __('Square images (1:1) work best — e.g. 1000 × 1000 px each.') }}</p>

            {{-- Upload dropzone --}}
            <x-hs-dropzone
                input-id="gallery-photo-input"
                wire-model="newPhotos"
                accept="image/jpeg,image/png,image/webp"
                hint="{{ __('JPEG, PNG, WebP — up to 5MB each') }}"
                on-finish="$wire.uploadPhotos()"
                loading-target="newPhotos,uploadPhotos"
                class="mb-6"
            >
                @error('newPhotos.*') <p class="mt-2 text-sm text-[var(--hs-danger-700)]">{{ $message }}</p> @enderror
            </x-hs-dropzone>

            {{-- Scanning notice --}}
            @if ($this->hasPendingPhotos)
                <div class="mb-4 flex items-center gap-2 rounded-[var(--hs-radius-card)] border border-[var(--hs-warning-200)] bg-[var(--hs-warning-50)] px-4 py-2.5 text-sm text-[var(--hs-warning-700)]" aria-live="polite">
                    <flux:icon name="arrow-path" class="size-4 shrink-0 animate-spin" />
                    {{ __('Checking scan status automatically…') }}
                </div>
            @endif

            {{-- Sortable gallery grid --}}
            @if ($this->galleryItems->isNotEmpty())
                <div wire:sort="updateGalleryOrder" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                    @foreach ($this->galleryItems as $item)
                        @php $scanStatus = $item->media?->scan_status ?? 'pending'; @endphp
                        <div
                            wire:key="gallery-{{ $item->id }}"
                            wire:sort:item="{{ $item->id }}"
                            class="group relative overflow-hidden rounded-[var(--hs-radius-card)] border bg-[var(--hs-neutral-0)] shadow-[var(--hs-shadow-sm)] {{ $scanStatus === 'infected' ? 'border-[var(--hs-danger-300)]' : 'border-[var(--hs-neutral-200)]' }} {{ $scanStatus === 'pending' ? '' : 'cursor-grab' }}"
                        >
                            <div wire:sort:handle class="aspect-square">
                                <img
                                    src="{{ $item->image_url }}"
                                    alt="{{ __('Gallery image') }}"
                                    class="h-full w-full object-cover {{ $scanStatus !== 'clean' ? 'opacity-50' : '' }}"
                                />
                            </div>

                            {{-- Scan status overlay (pending / infected) --}}
                            @if ($scanStatus === 'pending')
                                <div class="absolute inset-0 flex flex-col items-center justify-center gap-1 bg-[var(--hs-neutral-900)]/40">
                                    <flux:icon name="arrow-path" class="size-6 text-white animate-spin" />
                                    <span class="text-xs font-medium text-white">{{ __('Scanning…') }}</span>
                                </div>
                            @elseif ($scanStatus === 'infected')
                                <div class="absolute inset-0 flex flex-col items-center justify-center gap-1 bg-[var(--hs-danger-900)]/50">
                                    <flux:icon name="x-circle" class="size-6 text-[var(--hs-danger-200)]" />
                                    <span class="text-xs font-medium text-[var(--hs-danger-100)]">{{ __('Scan failed') }}</span>
                                </div>
                            @endif

                            {{-- Remove button --}}
                            <button
                                type="button"
                                wire:click="confirmRemoveImage({{ $item->id }})"
                                aria-label="{{ __('Remove image') }}"
                                class="absolute top-1.5 right-1.5 flex size-6 items-center justify-center rounded-full bg-[var(--hs-danger-500)] text-white text-xs transition sm:opacity-0 sm:group-hover:opacity-100 hover:bg-[var(--hs-danger-700)]"
                            >
                                <flux:icon name="x-mark" variant="mini" class="size-3.5" />
                            </button>

                            {{-- Position badge --}}
                            @if ($scanStatus === 'clean')
                                <div class="absolute top-1.5 left-1.5">
                                    <span class="inline-flex items-center justify-center size-6 rounded-full bg-[var(--hs-neutral-0)]/90 text-xs font-bold text-[var(--hs-neutral-700)] shadow-sm">
                                        {{ $loop->iteration }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <x-hs-empty-state icon="image" title="{{ __('No images yet') }}" subtitle="{{ __('Upload photos to build your product gallery.') }}" />
            @endif
        </div>
    </div>

    {{-- Remove Image Confirmation --}}
    <x-hs-confirm
        name="remove-image-confirm"
        title="{{ __('Remove Image') }}"
        message="{{ __('Remove this image?') }}"
        variant="danger"
        confirmText="{{ __('Remove') }}"
    >
        <x-slot name="action">
            <flux:button variant="danger" wire:click="removeImage" wire:loading.attr="disabled">
                {{ __('Remove') }}
            </flux:button>
        </x-slot>
    </x-hs-confirm>
</div>
