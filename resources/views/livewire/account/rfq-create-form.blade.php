<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        {{-- Card Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">
                {{ __('Request for Quotation') }}
            </h1>
            @if ($shop)
                <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">
                    {{ __('Sending to') }} <span class="font-medium text-[var(--hs-neutral-700)]">{{ $shop->storefront_name }}</span>
                </p>
            @endif
        </div>

        <div class="p-6">
            <form wire:submit="submit" class="space-y-6">
                @if ($shop)
                    <input type="hidden" wire:model="shop_id" />
                @else
                    <flux:input
                        wire:model="shop_id"
                        label="Shop ID"
                        placeholder="Select a seller"
                        required
                    />
                @endif
                @error('shop_id') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror
                @error('buyer_profile') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror

                <flux:input
                    wire:model="title"
                    label="Title"
                    placeholder="e.g., 500kg Halal Coconut Flour — bulk pricing inquiry"
                    description="Describe what you're looking for in a few words."
                    required
                />
                @error('title') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror

                <flux:textarea
                    wire:model="description"
                    label="Requirements / Description"
                    placeholder="Describe your requirements in detail — quantity, specifications, packaging preferences, delivery timeline, certifications needed, etc."
                    rows="6"
                />
                @error('description') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror

                {{-- Attachments --}}
                <div>
                    <label class="block text-sm font-medium text-[var(--hs-neutral-700)] mb-1.5">{{ __('Attachments (optional)') }}</label>
                    <p class="text-xs text-[var(--hs-neutral-500)] mb-2">{{ __('Attach spec sheets, artwork, or reference documents. Max 5 files, 10MB each.') }}</p>
                    <input
                        type="file"
                        wire:model="attachments"
                        multiple
                        class="block w-full text-sm text-[var(--hs-neutral-500)] file:mr-4 file:rounded-[var(--hs-radius-pill)] file:border-0 file:bg-[var(--hs-green-50)] file:px-4 file:py-2 file:text-sm file:font-medium file:text-[var(--hs-green-700)] hover:file:bg-[var(--hs-green-100)]"
                    />
                    @error('attachments.*') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror
                </div>

                {{-- Info --}}
                <div class="rounded-[var(--hs-radius-card)] bg-[var(--hs-neutral-50)] border border-[var(--hs-neutral-200)] p-4">
                    <p class="text-xs text-[var(--hs-neutral-500)]">
                        <strong>{{ __('Note:') }}</strong> {{ __('Once sent, this RFQ cannot be edited. Your business name and address will be shared with the seller as part of this inquiry.') }}
                    </p>
                </div>

                {{-- Submit --}}
                <div class="flex items-center gap-3 pt-4 border-t border-[var(--hs-neutral-100)]">
                    <flux:button type="submit" variant="primary">
                        {{ __('Send RFQ') }}
                    </flux:button>
                    <flux:button variant="ghost" href="{{ route('account.rfqs.index') }}" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div>
