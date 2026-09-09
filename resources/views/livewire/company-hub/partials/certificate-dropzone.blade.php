@php $certFileError = $errors->first('certificateFile'); @endphp
<div x-data="{ dragging: false }" class="mt-1">
    <label
        for="new-certificate-input"
        x-on:dragover.prevent="dragging = true"
        x-on:dragleave.prevent="dragging = false"
        x-on:drop.prevent="
            dragging = false;
            if ($event.dataTransfer?.files?.length) {
                const input = document.getElementById('new-certificate-input');
                input.files = $event.dataTransfer.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        "
        x-bind:class="dragging
            ? 'border-[var(--hs-green-600)] bg-[var(--hs-green-50)]'
            : '{{ $certFileError ? 'border-[var(--hs-danger-500)] bg-[var(--hs-danger-50)]' : 'border-[var(--hs-neutral-200)] hover:border-[var(--hs-green-600)] hover:bg-[var(--hs-green-50)]' }}'"
        class="flex h-full min-h-40 flex-col items-center justify-center gap-2 rounded-[var(--hs-radius-card)] border-2 border-dashed p-6 text-center cursor-pointer transition"
    >
        <flux:icon name="cloud-arrow-up" class="size-7 {{ $certFileError ? 'text-[var(--hs-danger-500)]' : 'text-[var(--hs-neutral-500)]' }}" />
        <p class="text-sm font-medium {{ $certFileError ? 'text-[var(--hs-danger-700)]' : 'text-[var(--hs-neutral-900)]' }}">
            {{ __('Drag & drop, or click to browse') }}
        </p>
        <p class="text-xs {{ $certFileError ? 'text-[var(--hs-danger-500)]' : 'text-[var(--hs-neutral-500)]' }}">{{ __('PDF, DOC, DOCX, JPG, PNG, WebP — max 20MB') }}</p>
        <input
            id="new-certificate-input"
            type="file"
            wire:model="certificateFile"
            class="hidden"
            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp"
        />
    </label>

    @if ($certFileError)
        <p class="mt-2 flex items-center gap-1.5 text-xs text-[var(--hs-danger-700)]">
            <flux:icon name="exclamation-circle" class="size-4 shrink-0" />
            {{ $certFileError }}
        </p>
    @endif

    <div
        wire:loading
        wire:target="certificateFile"
        class="mt-2 flex items-center gap-2 text-sm text-[var(--hs-neutral-500)]"
    >
        <flux:icon name="arrow-path" class="size-4 animate-spin" />
        {{ __('Uploading file…') }}
    </div>
</div>
