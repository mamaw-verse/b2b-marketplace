@props([
    'inputId',                              // id on the hidden <input> — must be unique per page
    'wireModel',                            // wire:model property name, e.g. "newPhotos"
    'accept'      => '*',                   // input accept attribute
    'multiple'    => true,                  // allow multiple file selection
    'hint'        => null,                  // small text under the main label, e.g. "JPEG, PNG — up to 5MB"
    'onFinish'    => null,                  // JS expression to call after Livewire finishes temp upload
                                            //   e.g. "$wire.uploadPhotos()"
    'loadingTarget' => null,                // wire:target for the uploading spinner, e.g. "newPhotos,uploadPhotos"
])

<div
    x-data="{ dragging: false }"
    @if ($onFinish) x-on:livewire-upload-finish="{{ $onFinish }}" @endif
    {{ $attributes->only('class') }}
>
    <label
        for="{{ $inputId }}"
        x-on:dragover.prevent="dragging = true"
        x-on:dragleave.prevent="dragging = false"
        x-on:drop.prevent="
            dragging = false;
            if ($event.dataTransfer?.files?.length) {
                const input = document.getElementById('{{ $inputId }}');
                input.files = $event.dataTransfer.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        "
        x-bind:class="dragging
            ? 'border-[var(--hs-green-600)] bg-[var(--hs-green-50)]'
            : 'border-[var(--hs-neutral-300)] hover:border-[var(--hs-green-600)] hover:bg-[var(--hs-green-50)]'"
        class="flex flex-col items-center justify-center gap-2 rounded-[var(--hs-radius-card)] border-2 border-dashed p-8 text-center cursor-pointer transition"
    >
        <flux:icon name="cloud-arrow-up" class="size-10 text-[var(--hs-neutral-400)]" />
        <p class="text-sm font-medium text-[var(--hs-neutral-900)]">{{ __('Drag & drop, or click to browse') }}</p>
        @if ($hint)
            <p class="text-xs text-[var(--hs-neutral-500)]">{{ $hint }}</p>
        @endif
        <input
            id="{{ $inputId }}"
            type="file"
            wire:model="{{ $wireModel }}"
            accept="{{ $accept }}"
            @if ($multiple) multiple @endif
            class="hidden"
        />
    </label>

    {{ $slot }}

    @if ($loadingTarget)
        <div wire:loading wire:target="{{ $loadingTarget }}" class="mt-2 flex items-center gap-2 text-sm text-[var(--hs-neutral-500)]">
            <flux:icon name="arrow-path" class="size-4 animate-spin" />
            {{ __('Uploading…') }}
        </div>
    @endif
</div>
