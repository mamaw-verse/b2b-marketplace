<div>
    @include('livewire.company-hub.partials.product-tabs')

    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        {{-- Card Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">
                {{ $product ? 'Edit Product' : 'Create Product' }}
            </h1>
        </div>

        <div class="p-6">

            <form wire:submit="save" class="space-y-6">
                <flux:input
                    wire:model="name"
                    label="Product Name"
                    placeholder="Enter product name"
                    required
                />
                @error('name') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror

                <flux:textarea
                    wire:model="description"
                    label="Description"
                    placeholder="Describe your product..."
                    rows="4"
                />
                @error('description') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror

                <div>
                    <label class="block text-sm font-semibold text-[var(--hs-neutral-700)] mb-1.5">Categories</label>
                    <div class="flex flex-wrap gap-2" x-data="{ selected: @entangle('category_ids') }">
                        @foreach ($this->categories as $id => $name)
                            <label 
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-[var(--hs-radius-pill)] text-sm font-medium border cursor-pointer transition"
                                x-bind:class="selected.includes({{ $id }}) || selected.includes('{{ $id }}') ? 'bg-[var(--hs-green-600)] border-[var(--hs-green-600)] text-white' : 'bg-[var(--hs-neutral-0)] border-[var(--hs-neutral-200)] text-[var(--hs-neutral-600)] hover:border-[var(--hs-green-300)]'"
                            >
                                <input type="checkbox" wire:model="category_ids" value="{{ $id }}" class="hidden">
                                {{ $name }}
                            </label>
                        @endforeach
                    </div>
                </div>
                @error('category_ids') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror

                <div>
                    <label class="block text-sm font-medium text-[var(--hs-neutral-700)]">Featured Image</label>
                    @if ($product?->featured_image_path && ! $featured_image)
                        <p class="mt-2 mb-2 text-sm text-[var(--hs-neutral-500)]">Current image set. Upload a new one to replace it.</p>
                    @endif
                    <input type="file" wire:model="featured_image" accept="image/*" class="mt-1 block w-full text-sm text-[var(--hs-neutral-500)] file:mr-4 file:rounded-[var(--hs-radius-pill)] file:border-0 file:bg-[var(--hs-green-50)] file:px-4 file:py-2 file:text-sm file:font-medium file:text-[var(--hs-green-700)] hover:file:bg-[var(--hs-green-100)]" />
                    @error('featured_image') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror

                    @if ($featured_image && method_exists($featured_image, 'temporaryUrl'))
                        <div class="mt-3">
                            <img src="{{ $featured_image->temporaryUrl() }}" alt="Preview" class="h-32 w-32 rounded-[var(--hs-radius-card)] object-cover border border-[var(--hs-neutral-200)]" />
                        </div>
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    <flux:button type="submit" variant="primary">
                        {{ $product ? 'Update Product' : 'Create Product' }}
                    </flux:button>
                    <flux:button variant="ghost" href="{{ route('hub.products.index') }}" wire:navigate>
                        Cancel
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div>
