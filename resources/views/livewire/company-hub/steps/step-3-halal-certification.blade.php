<div class="space-y-6" @if ($this->hasPendingCertificates) wire:poll.5s="refreshCertificateStatuses" @endif>
    <div class="flex items-start justify-between gap-4">
        <div>
            <flux:heading size="lg">{{ __('Halal Certification') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Add your halal certification details if available. This step is optional — you may add or update certificates later.') }}</flux:text>
        </div>
        <flux:button
            wire:click="refreshCertificateStatuses"
            wire:loading.attr="disabled"
            wire:target="refreshCertificateStatuses"
            variant="ghost"
            size="sm"
            icon="arrow-path"
            class="shrink-0"
        >
            {{ __('Refresh Status') }}
        </flux:button>
    </div>

    @if ($this->hasPendingCertificates)
        <div class="flex items-center gap-2 rounded-[var(--hs-radius-card)] border border-[var(--hs-warning-200)] bg-[var(--hs-warning-50)] px-4 py-2.5 text-sm text-[var(--hs-warning-700)]" aria-live="polite">
            <flux:icon name="arrow-path" class="size-4 shrink-0 animate-spin" />
            {{ __('Checking scan status automatically…') }}
        </div>
    @endif

    @forelse ($certificates as $index => $certificate)
        <flux:card wire:key="certificate-{{ $certificate['id'] ?? 'new-' . $index }}" class="relative space-y-4 p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 flex-wrap">
                    <flux:heading size="sm">{{ __('Certificate') }} #{{ $index + 1 }}</flux:heading>

                    @if (!empty($certificate['media_id']))
                        @switch($certificate['scan_status'])
                            @case('clean')
                                <flux:badge color="green" size="sm" icon="check-circle">{{ __('Verified') }}</flux:badge>
                                @break
                            @case('pending')
                                <flux:badge color="yellow" size="sm">{{ __('Scanning…') }}</flux:badge>
                                @break
                            @case('infected')
                                <flux:badge color="red" size="sm" icon="x-circle">{{ __('Failed scan') }}</flux:badge>
                                @break
                            @default
                                <flux:badge size="sm">{{ __('Uploaded') }}</flux:badge>
                        @endswitch
                    @endif
                </div>

                <flux:button wire:click="confirmRemoveCertificate({{ $index }})" variant="ghost" size="sm" icon="trash" class="text-red-500 hover:text-red-700">
                    {{ __('Remove') }}
                </flux:button>
            </div>

            <div class="space-y-4">
                <flux:input
                    wire:model.blur="certificates.{{ $index }}.expiry_date"
                    :label="__('Expiry Date')"
                    type="date"
                />

                {{-- Certificate File --}}
                <div>
                    <flux:label>{{ __('Certificate File') }}</flux:label>

                    @php
                        $certFilename = $certificate['filename'] ?? '';
                        $certExt = $certFilename ? pathinfo($certFilename, PATHINFO_EXTENSION) : '';
                        $certBase = $certFilename ? pathinfo($certFilename, PATHINFO_FILENAME) : '';
                        $certDisplay = $certFilename
                            ? \Illuminate\Support\Str::limit($certBase, 20, '…') . ($certExt ? '.' . $certExt : '')
                            : __('File already uploaded');
                        $certScanStatus = $certificate['scan_status'] ?? null;
                    @endphp

                    {{-- Clean: show filename + Replace --}}
                    @if (!empty($certificate['media_id']) && $certScanStatus === 'clean')
                        <div class="mt-1 flex items-center gap-3 rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-100)] bg-[var(--hs-neutral-0)] px-4 py-3">
                            <flux:icon name="document-check" class="size-5 shrink-0 text-[var(--hs-green-600)]" />
                            <p class="min-w-0 flex-1 truncate text-sm font-medium text-[var(--hs-neutral-900)]" title="{{ $certFilename }}">{{ $certDisplay }}</p>
                            <flux:icon name="check-circle" class="size-5 shrink-0 text-[var(--hs-green-600)]" />
                            <flux:button
                                wire:click="replaceCertificateFile({{ $index }})"
                                wire:loading.attr="disabled"
                                wire:target="replaceCertificateFile({{ $index }})"
                                variant="ghost"
                                size="sm"
                                icon="arrow-path"
                                class="shrink-0"
                            >
                                {{ __('Replace') }}
                            </flux:button>
                        </div>

                    {{-- Pending scan --}}
                    @elseif (!empty($certificate['media_id']) && $certScanStatus === 'pending')
                        <div class="mt-1 flex items-center gap-3 rounded-[var(--hs-radius-card)] border border-[var(--hs-warning-200)] bg-[var(--hs-warning-50)] px-4 py-3">
                            <flux:icon name="clock" class="size-5 shrink-0 text-[var(--hs-warning-700)]" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-[var(--hs-neutral-900)]">{{ $certDisplay }}</p>
                                <p class="text-xs text-[var(--hs-warning-700)]">{{ __('Security scan in progress. Re-upload if it stays here for a while.') }}</p>
                            </div>
                            <flux:button
                                wire:click="resetCertificateFile({{ $index }})"
                                variant="ghost"
                                size="sm"
                                icon="arrow-path"
                                class="shrink-0"
                            >
                                {{ __('Re-upload') }}
                            </flux:button>
                        </div>

                    {{-- Infected or no file: show dropzone (with infected warning above it) --}}
                    @else
                        @if (!empty($certificate['media_id']) && $certScanStatus === 'infected')
                            <p class="mt-2 flex items-center gap-1.5 text-xs text-[var(--hs-danger-700)]">
                                <flux:icon name="exclamation-circle" class="size-4 shrink-0" />
                                {{ __('":file" failed the security scan. Please upload a different file.', ['file' => $certDisplay]) }}
                            </p>
                        @endif

                        @php $certFileError = $errors->first("certificateFiles.{$index}"); @endphp
                        <div x-data="{ dragging: false }" class="mt-1">
                            <label
                                for="cert-input-{{ $index }}"
                                x-on:dragover.prevent="dragging = true"
                                x-on:dragleave.prevent="dragging = false"
                                x-on:drop.prevent="
                                    dragging = false;
                                    if ($event.dataTransfer?.files?.length) {
                                        const input = document.getElementById('cert-input-{{ $index }}');
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
                                <p class="text-xs {{ $certFileError ? 'text-[var(--hs-danger-500)]' : 'text-[var(--hs-neutral-500)]' }}">{{ __('PDF, JPG, PNG — max 20MB') }}</p>
                                <input
                                    id="cert-input-{{ $index }}"
                                    type="file"
                                    wire:model="certificateFiles.{{ $index }}"
                                    class="hidden"
                                    accept=".pdf,.jpg,.jpeg,.png"
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
                                wire:target="certificateFiles.{{ $index }}"
                                class="mt-2 flex items-center gap-2 text-sm text-[var(--hs-neutral-500)]"
                            >
                                <flux:icon name="arrow-path" class="size-4 animate-spin" />
                                {{ __('Uploading file…') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </flux:card>
    @empty
        <div class="rounded-lg border border-dashed border-[var(--hs-neutral-300)] p-8 text-center">
            <flux:icon name="document-text" class="mx-auto size-10 text-[var(--hs-neutral-400)]" />
            <flux:text class="mt-2">{{ __('No certificates added yet.') }}</flux:text>
        </div>
    @endforelse

    <flux:button wire:click="addCertificate" variant="subtle" icon="plus">
        {{ __('Add Certificate') }}
    </flux:button>

    <x-hs-confirm
        name="confirm-remove-certificate"
        title="{{ __('Remove Certificate') }}"
        message="{{ __('Remove this certificate?') }}"
        variant="danger"
        confirmText="{{ __('Remove') }}"
    >
        <x-slot name="action">
            <flux:button variant="danger" wire:click="removeCertificate" wire:loading.attr="disabled">
                {{ __('Remove') }}
            </flux:button>
        </x-slot>
    </x-hs-confirm>
</div>
