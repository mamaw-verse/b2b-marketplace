<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">

        {{-- Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">
                {{ $contentId ? __('Edit Sponsored Content') : __('New Sponsored Content') }}
            </h1>
        </div>

        {{-- Form --}}
        <form wire:submit="save" class="px-6 py-6 space-y-6">

            {{-- Title --}}
            <flux:field>
                <flux:label>{{ __('Title') }}</flux:label>
                <flux:input wire:model="form.title" placeholder="{{ __('e.g. Ramadan Super Sale — Al Barakah Foods') }}" />
                <flux:error name="form.title" />
            </flux:field>

            {{-- Destination URL --}}
            <flux:field>
                <flux:label>{{ __('Destination URL') }}</flux:label>
                <flux:input wire:model="form.destinationUrl" type="url" placeholder="https://" />
                <flux:error name="form.destinationUrl" />
            </flux:field>

            {{-- Tier --}}
            <flux:field>
                <flux:label>{{ __('Tier') }}</flux:label>
                <flux:select wire:model.live="form.tierId" placeholder="{{ __('Select a tier...') }}">
                    @foreach ($this->tiers as $tier)
                        <flux:select.option value="{{ $tier->id }}">
                            {{ __(':tier (weight :priority_weight)', ['tier' => $tier->name, 'priority_weight' => $tier->priority_weight]) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="form.tierId" />
            </flux:field>

            {{-- Placements --}}
            <flux:field>
                <flux:label>{{ __('Placements') }}</flux:label>
                <div class="mt-2 space-y-2">
                    @foreach (\App\Livewire\Admin\SponsoredContent\SponsoredContentComposer::PLACEMENT_KEYS as $key)
                        <label class="flex items-center gap-3 cursor-pointer">
                            <flux:checkbox wire:model="form.placements" value="{{ $key }}" />
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
                    <flux:label>{{ __('Starts At') }}</flux:label>
                    <flux:input wire:model="form.startsAt" type="datetime-local" />
                    <flux:error name="form.startsAt" />
                </flux:field>
                <flux:field>
                    <flux:label>{{ __('Ends At') }}</flux:label>
                    <flux:input wire:model="form.endsAt" type="datetime-local" />
                    <flux:error name="form.endsAt" />
                </flux:field>
            </div>

            {{-- ══════════════════════════════════════════════
                 DUAL CREATIVE UPLOAD
            ══════════════════════════════════════════════ --}}
            <div>
                <p class="text-sm font-medium text-[var(--hs-neutral-900)] mb-1">
                    {{ __('Creative Images') }}
                    <span class="ml-1 text-xs font-normal text-[var(--hs-neutral-500)]">{{ __('(at least one required)') }}</span>
                </p>
                <p class="text-xs text-[var(--hs-neutral-500)] mb-4">
                    {{ __('Upload a wide banner for homepage/search placements and/or a tall poster for the hero carousel. JPG, PNG or WebP · max 20 MB.') }}
                </p>

                <div class="space-y-6">

                {{-- ── Row 1: Wide (full width) ────────────────────────────── --}}
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wide text-[var(--hs-neutral-600)]">
                            {{ __('Wide (Landscape)') }}
                        </span>
                        <span class="text-[10px] font-mono text-[var(--hs-neutral-400)] bg-[var(--hs-neutral-100)] rounded px-1.5 py-0.5">
                            {{ \App\Livewire\Admin\SponsoredContent\SponsoredContentComposer::CREATIVE_DIMENSIONS['wide'] }}
                        </span>
                    </div>

                    {{-- Alpine holds the local preview URL; updated via FileReader on file input change --}}
                    <div
                        x-data="{
                            dragging: false,
                            previewUrl: null,
                            setPreview(files) {
                                if (!files || !files[0]) return;
                                const reader = new FileReader();
                                reader.onload = e => this.previewUrl = e.target.result;
                                reader.readAsDataURL(files[0]);
                            },
                            clear() {
                                this.previewUrl = null;
                                $wire.set('wideCreative', null);
                            }
                        }"
                        x-on:dragover.prevent="dragging = true"
                        x-on:dragleave.prevent="dragging = false"
                        x-on:drop.prevent="dragging = false; setPreview($event.dataTransfer.files); $wire.upload('wideCreative', $event.dataTransfer.files[0])"
                        x-bind:class="dragging
                            ? 'border-[var(--hs-green-600)] bg-[var(--hs-green-50)]'
                            : 'border-[var(--hs-neutral-300)] hover:border-[var(--hs-green-500)] hover:bg-[var(--hs-green-50)]'"
                        class="relative w-full rounded-[var(--hs-radius-card)] border-2 border-dashed transition overflow-hidden"
                        style="aspect-ratio: 4/1;"
                    >
                        {{-- Local preview after picking a new file --}}
                        <div x-show="previewUrl" x-cloak class="absolute inset-0">
                            <img x-bind:src="previewUrl" class="w-full h-full object-cover" alt="" />
                            <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 hover:opacity-100 transition">
                                <button type="button" x-on:click="clear()"
                                    class="flex items-center gap-1.5 rounded-full bg-white/90 px-3 py-1.5 text-xs font-semibold text-red-600">
                                    <flux:icon name="trash" class="size-3.5" />{{ __('Remove') }}
                                </button>
                            </div>
                        </div>

                        {{-- Existing saved image (edit mode, no new upload yet) --}}
                        @if ($contentId && $form->wideMediaId && ! $wideCreative)
                            @php $existingWide = \App\Models\Media::find($form->wideMediaId); @endphp
                            @if ($existingWide)
                                <div class="absolute inset-0" x-show="!previewUrl">
                                    <img src="{{ route('media.view', $existingWide) }}" class="w-full h-full object-cover" alt="{{ __('Current wide creative') }}" />
                                    <div class="absolute top-2 left-2">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-black/50 px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wider text-white backdrop-blur-sm">{{ __('Current') }}</span>
                                    </div>
                                </div>
                            @endif
                        @endif

                        {{-- Upload prompt (hidden once a preview or existing image is shown) --}}
                        <label for="wide-creative-input"
                            class="absolute inset-0 flex flex-col items-center justify-center gap-1.5 cursor-pointer"
                            x-show="!previewUrl @if($contentId && $form->wideMediaId) && false @endif">
                            <flux:icon name="photo" class="size-8 text-[var(--hs-neutral-400)]" />
                            <p class="text-xs font-medium text-[var(--hs-neutral-600)]">{{ __('Drop or click to upload') }}</p>
                            <input id="wide-creative-input" type="file"
                                wire:model="wideCreative"
                                accept="image/jpeg,image/png,image/webp"
                                class="hidden"
                                x-on:change="setPreview($event.target.files)" />
                        </label>

                        <div wire:loading wire:target="wideCreative" class="absolute inset-0 flex items-center justify-center bg-white/80">
                            <flux:icon name="arrow-path" class="size-5 animate-spin text-[var(--hs-green-600)]" />
                        </div>
                    </div>

                    <flux:error name="wideCreative" />

                    @if ($contentId && $form->wideMediaId)
                        <label for="wide-creative-input" class="inline-flex items-center gap-1 text-xs text-[var(--hs-green-700)] cursor-pointer hover:underline">
                            <flux:icon name="arrow-path" class="size-3.5" />{{ __('Replace wide creative') }}
                            <input id="wide-creative-input-replace" type="file" wire:model="wideCreative" accept="image/jpeg,image/png,image/webp" class="hidden" />
                        </label>
                    @endif
                </div>

                {{-- ── Row 2: Tall (portrait, wider card) ──────────────────── --}}
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wide text-[var(--hs-neutral-600)]">
                            {{ __('Tall (Portrait)') }}
                        </span>
                        <span class="text-[10px] font-mono text-[var(--hs-neutral-400)] bg-[var(--hs-neutral-100)] rounded px-1.5 py-0.5">
                            {{ \App\Livewire\Admin\SponsoredContent\SponsoredContentComposer::CREATIVE_DIMENSIONS['tall'] }}
                        </span>
                    </div>

                    <div class="w-48">
                        <div
                            x-data="{
                                dragging: false,
                                previewUrl: null,
                                setPreview(files) {
                                    if (!files || !files[0]) return;
                                    const reader = new FileReader();
                                    reader.onload = e => this.previewUrl = e.target.result;
                                    reader.readAsDataURL(files[0]);
                                },
                                clear() {
                                    this.previewUrl = null;
                                    $wire.set('tallCreative', null);
                                }
                            }"
                            x-on:dragover.prevent="dragging = true"
                            x-on:dragleave.prevent="dragging = false"
                            x-on:drop.prevent="dragging = false; setPreview($event.dataTransfer.files); $wire.upload('tallCreative', $event.dataTransfer.files[0])"
                            x-bind:class="dragging
                                ? 'border-[var(--hs-green-600)] bg-[var(--hs-green-50)]'
                                : 'border-[var(--hs-neutral-300)] hover:border-[var(--hs-green-500)] hover:bg-[var(--hs-green-50)]'"
                            class="relative rounded-[var(--hs-radius-card)] border-2 border-dashed transition overflow-hidden"
                            style="aspect-ratio: 2/3;"
                        >
                            {{-- Local preview after picking a new file --}}
                            <div x-show="previewUrl" x-cloak class="absolute inset-0">
                                <img x-bind:src="previewUrl" class="w-full h-full object-cover" alt="" />
                                <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 hover:opacity-100 transition">
                                    <button type="button" x-on:click="clear()"
                                        class="flex items-center gap-1 rounded-full bg-white/90 px-2 py-1 text-[10px] font-semibold text-red-600">
                                        <flux:icon name="trash" class="size-3" />{{ __('Remove') }}
                                    </button>
                                </div>
                            </div>

                            {{-- Existing saved image (edit mode, no new upload yet) --}}
                            @if ($contentId && $form->tallMediaId && ! $tallCreative)
                                @php $existingTall = \App\Models\Media::find($form->tallMediaId); @endphp
                                @if ($existingTall)
                                    <div class="absolute inset-0" x-show="!previewUrl">
                                        <img src="{{ route('media.view', $existingTall) }}" class="w-full h-full object-cover" alt="{{ __('Current tall creative') }}" />
                                        <div class="absolute top-2 left-2">
                                            <span class="inline-flex items-center gap-1 rounded-full bg-black/50 px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wider text-white backdrop-blur-sm">{{ __('Current') }}</span>
                                        </div>
                                    </div>
                                @endif
                            @endif

                            {{-- Upload prompt --}}
                            <label for="tall-creative-input"
                                class="absolute inset-0 flex flex-col items-center justify-center gap-1.5 cursor-pointer"
                                x-show="!previewUrl @if($contentId && $form->tallMediaId) && false @endif">
                                <flux:icon name="photo" class="size-6 text-[var(--hs-neutral-400)]" />
                                <p class="text-[10px] font-medium text-[var(--hs-neutral-600)] text-center leading-tight px-2">{{ __('Drop or click') }}</p>
                                <input id="tall-creative-input" type="file"
                                    wire:model="tallCreative"
                                    accept="image/jpeg,image/png,image/webp"
                                    class="hidden"
                                    x-on:change="setPreview($event.target.files)" />
                            </label>

                            <div wire:loading wire:target="tallCreative" class="absolute inset-0 flex items-center justify-center bg-white/80">
                                <flux:icon name="arrow-path" class="size-4 animate-spin text-[var(--hs-green-600)]" />
                            </div>
                        </div>
                    </div>

                    <flux:error name="tallCreative" />

                    @if ($contentId && $form->tallMediaId)
                        <label for="tall-creative-input" class="inline-flex items-center gap-1 text-xs text-[var(--hs-green-700)] cursor-pointer hover:underline">
                            <flux:icon name="arrow-path" class="size-3.5" />{{ __('Replace tall creative') }}
                            <input id="tall-creative-input-replace" type="file" wire:model="tallCreative" accept="image/jpeg,image/png,image/webp" class="hidden" />
                        </label>
                    @endif
                </div>

                </div>{{-- /space-y-6 --}}

                {{-- At-least-one-creative validation error --}}
                <flux:error name="wideCreative" />
            </div>

            {{-- Active toggle --}}
            <div class="flex items-center gap-3">
                <flux:switch wire:model="form.isActive" />
                <span class="text-sm text-[var(--hs-neutral-700)]">{{ __('Active') }}</span>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-3 pt-2 border-t border-[var(--hs-neutral-100)]">
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    {{ $contentId ? __('Save Changes') : __('Create') }}
                </flux:button>
                <flux:button href="{{ route('admin.sponsored-content.index') }}" wire:navigate variant="ghost">
                    {{ __('Cancel') }}
                </flux:button>
            </div>

        </form>
    </div>
</div>
