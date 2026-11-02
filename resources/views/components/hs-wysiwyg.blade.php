@props([
    'wireModel',           // Livewire property name, e.g. "description"
    'label'       => null, // Optional label text
    'description' => null, // Optional helper text below the label
    'placeholder' => null, // Placeholder shown in the editor
    'inputId'     => null, // Optional id override; auto-generated if omitted
])

@php
    $editorId = $inputId ?? 'hs-wysiwyg-' . md5($wireModel);
    $inputName = 'hs-wysiwyg-input-' . md5($wireModel);
@endphp

<div
    x-data="{
        value: @entangle($wireModel),

        init() {
            const editor = this.$refs.editor;

            // Set initial content once Trix is ready
            editor.addEventListener('trix-initialize', () => {
                if (this.value) {
                    editor.editor.loadHTML(this.value);
                }
            });

            // Sync to Livewire on every content change
            editor.addEventListener('trix-change', () => {
                this.value = editor.innerHTML;
            });
        }
    }"
    {{ $attributes->only('class') }}
>
    @if ($label)
        <label
            for="{{ $editorId }}"
            class="block text-sm font-semibold text-[var(--hs-neutral-700)] mb-1.5"
        >{{ $label }}</label>
    @endif

    @if ($description)
        <p class="text-xs text-[var(--hs-neutral-500)] mb-2">{{ $description }}</p>
    @endif

    {{-- Hidden input required by Trix --}}
    <input id="{{ $inputName }}" type="hidden" />

    {{-- Unified border wrapper — Trix injects <trix-toolbar> then <trix-editor> inside this --}}
    <div class="hs-wysiwyg-wrap">
        <trix-editor
            id="{{ $editorId }}"
            x-ref="editor"
            input="{{ $inputName }}"
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            class="hs-wysiwyg-editor"
        ></trix-editor>
    </div>

    {{ $slot }}
</div>
