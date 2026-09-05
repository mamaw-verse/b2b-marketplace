<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] max-w-2xl">

        {{-- Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <div class="flex items-center gap-3 mb-1">
                <a href="{{ route('admin.sponsored-content.index') }}" wire:navigate
                   class="text-sm text-[var(--hs-neutral-500)] hover:text-[var(--hs-neutral-700)] flex items-center gap-1">
                    <flux:icon name="arrow-left" class="size-4" />
                    Back to Sponsored Content
                </a>
            </div>
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">
                {{ $contentId ? 'Edit Sponsored Content' : 'New Sponsored Content' }}
            </h1>
        </div>

        {{-- Form --}}
        <form wire:submit="save" class="px-6 py-6 space-y-6">

            {{-- Title --}}
            <flux:field>
                <flux:label>Title</flux:label>
                <flux:input wire:model="form.title" placeholder="e.g. Ramadan Super Sale — Al Barakah Foods" />
                <flux:error name="form.title" />
            </flux:field>

            {{-- Destination URL --}}
            <flux:field>
                <flux:label>Destination URL</flux:label>
                <flux:input wire:model="form.destinationUrl" type="url" placeholder="https://" />
                <flux:error name="form.destinationUrl" />
            </flux:field>

            {{-- Tier --}}
            <flux:field>
                <flux:label>Tier</flux:label>
                <flux:select wire:model="form.tierId" placeholder="Select a tier...">
                    @foreach ($this->tiers as $tier)
                        <flux:select.option value="{{ $tier->id }}">
                            {{ $tier->name }} (weight {{ $tier->priority_weight }})
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="form.tierId" />
            </flux:field>

            {{-- Placements --}}
            <flux:field>
                <flux:label>Placements</flux:label>
                <div class="mt-2 space-y-2">
                    @foreach (\App\Livewire\Admin\SponsoredContent\SponsoredContentComposer::PLACEMENT_KEYS as $key)
                        <label class="flex items-center gap-3 cursor-pointer">
                            <flux:checkbox
                                wire:model="form.placements"
                                value="{{ $key }}"
                            />
                            <span class="text-sm text-[var(--hs-neutral-700)]">
                                {{ str_replace('_', ' ', ucfirst($key)) }}
                            </span>
                        </label>
                    @endforeach
                </div>
                <flux:error name="form.placements" />
            </flux:field>

            {{-- Schedule --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Starts At</flux:label>
                    <flux:input wire:model="form.startsAt" type="datetime-local" />
                    <flux:error name="form.startsAt" />
                </flux:field>
                <flux:field>
                    <flux:label>Ends At</flux:label>
                    <flux:input wire:model="form.endsAt" type="datetime-local" />
                    <flux:error name="form.endsAt" />
                </flux:field>
            </div>

            {{-- Creative upload --}}
            <flux:field>
                <flux:label>
                    Creative Image
                    @if ($contentId && $form->mediaId)
                        <span class="text-xs font-normal text-[var(--hs-neutral-500)] ml-1">(leave blank to keep existing)</span>
                    @endif
                </flux:label>
                <flux:input wire:model="creative" type="file" accept="image/jpeg,image/png,image/webp" />
                <flux:description>JPG, PNG or WebP · max 20 MB</flux:description>
                <flux:error name="creative" />
            </flux:field>

            {{-- Active toggle --}}
            <div class="flex items-center gap-3">
                <flux:switch wire:model="form.isActive" />
                <span class="text-sm text-[var(--hs-neutral-700)]">Active</span>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-3 pt-2 border-t border-[var(--hs-neutral-100)]">
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    {{ $contentId ? 'Save Changes' : 'Create' }}
                </flux:button>
                <flux:button
                    href="{{ route('admin.sponsored-content.index') }}"
                    wire:navigate
                    variant="ghost"
                >
                    Cancel
                </flux:button>
            </div>
        </form>
    </div>
</div>
