<div>
    @include('livewire.company-hub.partials.product-tabs')

    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        {{-- Card Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)] flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">Gallery</h1>
                <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ $product->name }}</p>
            </div>
        </div>

        <div class="p-6">

            {{-- Upload section --}}
            <div class="mb-6 rounded-[var(--hs-radius-card)] border border-dashed border-[var(--hs-neutral-300)] bg-[var(--hs-neutral-50)] p-6">
                <div class="text-center">
                    <svg class="mx-auto size-10 text-[var(--hs-neutral-400)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                    <p class="mt-2 text-sm text-[var(--hs-neutral-600)]">Upload product images</p>
                    <p class="text-xs text-[var(--hs-neutral-400)]">JPEG, PNG, WebP up to 5MB each</p>
                </div>
                <div class="mt-4">
                    <input type="file" wire:model="newPhotos" accept="image/jpeg,image/png,image/webp" multiple class="block w-full text-sm text-[var(--hs-neutral-500)] file:mr-4 file:rounded-[var(--hs-radius-pill)] file:border-0 file:bg-[var(--hs-green-50)] file:px-4 file:py-2 file:text-sm file:font-medium file:text-[var(--hs-green-700)] hover:file:bg-[var(--hs-green-100)]" />
                    @error('newPhotos.*') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror
                </div>

                @if ($newPhotos)
                    <div class="mt-4 flex justify-center">
                        <flux:button variant="primary" size="sm" wire:click="uploadPhotos">
                            Upload {{ count($newPhotos) }} {{ count($newPhotos) === 1 ? 'Photo' : 'Photos' }}
                        </flux:button>
                    </div>
                @endif
            </div>

            {{-- Sortable gallery grid --}}
            @if ($this->galleryItems->isNotEmpty())
                <div wire:sort="updateGalleryOrder" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                    @foreach ($this->galleryItems as $item)
                        <div wire:key="gallery-{{ $item->id }}" wire:sort:item="{{ $item->id }}" class="group relative cursor-grab overflow-hidden rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] shadow-[var(--hs-shadow-sm)]">
                            <div wire:sort:handle class="aspect-square">
                                <img
                                    src="{{ $item->image_url }}"
                                    alt="Gallery image"
                                    class="h-full w-full object-cover"
                                />
                            </div>
                            <div class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 transition group-hover:opacity-100 rounded-[var(--hs-radius-card)]">
                                <flux:button variant="ghost" size="sm" icon="trash" class="text-white" wire:click="confirmRemoveImage({{ $item->id }})" />
                            </div>
                            <div class="absolute top-2 left-2">
                                <span class="inline-flex items-center justify-center size-6 rounded-full bg-[var(--hs-neutral-0)]/90 text-xs font-bold text-[var(--hs-neutral-700)] shadow-sm">
                                    {{ $loop->iteration }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <x-hs-empty-state icon="image" title="No images yet" subtitle="Upload photos to build your product gallery." />
            @endif
        </div>
    </div>

    {{-- Remove Image Confirmation --}}
    <x-hs-confirm
        name="remove-image-confirm"
        title="Remove Image"
        message="Remove this image?"
        variant="danger"
        confirmText="Remove"
    >
        <x-slot name="action">
            <flux:button variant="danger" wire:click="removeImage" wire:loading.attr="disabled">
                {{ __('Remove') }}
            </flux:button>
        </x-slot>
    </x-hs-confirm>
</div>
