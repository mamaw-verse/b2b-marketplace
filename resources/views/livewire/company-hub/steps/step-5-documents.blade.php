{{--
    While any document is still scanning, poll the whole checklist every 5s so
    statuses update on their own; polling stops automatically once no document
    is pending (hasPendingDocuments() is false), so a settled checklist is idle.
--}}
<div class="space-y-6" @if ($this->hasPendingDocuments) wire:poll.5s="refreshDocumentStatuses" @endif>
    <div class="flex items-start justify-between gap-4">
        <div>
            <flux:heading size="lg">{{ __('Document Upload Checklist') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Upload all required documents below. Accepted formats: PDF, JPG, PNG (max 20MB each).') }}</flux:text>
        </div>
        {{-- Manual refresh as a fallback; auto-refresh handles the common case --}}
        <flux:button
            wire:click="refreshDocumentStatuses"
            wire:loading.attr="disabled"
            wire:target="refreshDocumentStatuses"
            variant="ghost"
            size="sm"
            icon="arrow-path"
            class="shrink-0"
        >
            {{ __('Refresh Status') }}
        </flux:button>
    </div>

    {{-- Live status line so the user knows the page is checking on its own --}}
    @if ($this->hasPendingDocuments)
        <div class="flex items-center gap-2 rounded-[var(--hs-radius-card)] border border-[var(--hs-warning-200)] bg-[var(--hs-warning-50)] px-4 py-2.5 text-sm text-[var(--hs-warning-700)]" aria-live="polite">
            <flux:icon name="arrow-path" class="size-4 shrink-0 animate-spin" />
            {{ __('Checking scan status automatically…') }}
        </div>
    @endif

    @foreach (self::REQUIRED_DOCUMENTS as $type => $label)
        @php $doc = $uploadedDocuments[$type] ?? null; @endphp
        <flux:card class="p-4" wire:key="doc-{{ $type }}">
            <div class="flex items-center gap-2 flex-wrap">
                <flux:heading size="sm">{{ __($label) }}</flux:heading>

                @if ($doc)
                    @switch($doc['scan_status'])
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
                @else
                    <flux:badge color="zinc" size="sm">{{ __('Required') }}</flux:badge>
                @endif
            </div>

            {{-- Uploaded / verified state: show the file. A "Replace" action
                 detaches it and re-opens the dropzone, so documents can be
                 swapped out — e.g. after a rejection or a revision request. --}}
            @if ($doc && $doc['scan_status'] === 'clean')
                <div class="mt-3 flex items-center gap-3 rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-100)] bg-[var(--hs-neutral-0)] px-4 py-3">
                    <flux:icon name="document-check" class="size-5 shrink-0 text-[var(--hs-green-600)]" />
                    <p class="min-w-0 flex-1 truncate text-sm font-medium text-[var(--hs-neutral-900)]">{{ $doc['filename'] }}</p>
                    <flux:icon name="check-circle" class="size-5 shrink-0 text-[var(--hs-green-600)]" />
                    <flux:button
                        wire:click="resetDocument('{{ $type }}')"
                        wire:loading.attr="disabled"
                        wire:target="resetDocument('{{ $type }}')"
                        variant="ghost"
                        size="sm"
                        icon="arrow-path"
                        class="shrink-0"
                    >
                        {{ __('Replace') }}
                    </flux:button>
                </div>

            {{-- Pending scan: show the file, allow re-upload. Polling is handled
                 by the checklist container above while any doc is pending. --}}
            @elseif ($doc && $doc['scan_status'] === 'pending')
                <div class="mt-3 space-y-2">
                    <div class="flex items-center gap-3 rounded-[var(--hs-radius-card)] border border-[var(--hs-warning-200)] bg-[var(--hs-warning-50)] px-4 py-3">
                        <flux:icon name="clock" class="size-5 shrink-0 text-[var(--hs-warning-700)]" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-[var(--hs-neutral-900)]">{{ $doc['filename'] }}</p>
                            <p class="text-xs text-[var(--hs-warning-700)]">
                                {{ __('Security scan in progress. Re-upload if it stays here for a while.') }}
                            </p>
                        </div>
                        <flux:button
                            wire:click="resetDocument('{{ $type }}')"
                            variant="ghost"
                            size="sm"
                            icon="arrow-path"
                            class="shrink-0"
                        >
                            {{ __('Re-upload') }}
                        </flux:button>
                    </div>
                </div>

            {{-- No file yet, or a rejected file: show the dropzone --}}
            @else
                @if ($doc && $doc['scan_status'] === 'infected')
                    <p class="mt-2 flex items-center gap-1.5 text-xs text-[var(--hs-danger-700)]">
                        <flux:icon name="exclamation-circle" class="size-4 shrink-0" />
                        {{ __('“:file” failed the security scan. Please upload a different file.', ['file' => $doc['filename']]) }}
                    </p>
                @endif

                <div x-data="{ dragging: false }" class="mt-3">
                    <label
                        for="doc-input-{{ $type }}"
                        x-on:dragover.prevent="dragging = true"
                        x-on:dragleave.prevent="dragging = false"
                        x-on:drop.prevent="dragging = false"
                        x-bind:class="dragging
                            ? 'border-[var(--hs-green-600)] bg-[var(--hs-green-50)]'
                            : 'border-[var(--hs-neutral-200)] hover:border-[var(--hs-green-600)] hover:bg-[var(--hs-green-50)]'"
                        class="flex flex-col items-center justify-center gap-2 rounded-[var(--hs-radius-card)] border-2 border-dashed p-6 text-center cursor-pointer transition"
                    >
                        <flux:icon name="cloud-arrow-up" class="size-7 text-[var(--hs-neutral-500)]" />
                        <p class="text-sm font-medium text-[var(--hs-neutral-900)]">
                            {{ __('Drag & drop, or click to browse') }}
                        </p>
                        <p class="text-xs text-[var(--hs-neutral-500)]">{{ __('PDF, JPG, PNG — max 20MB') }}</p>
                        <input
                            id="doc-input-{{ $type }}"
                            type="file"
                            wire:model="documentUploads.{{ $type }}"
                            class="hidden"
                            accept=".pdf,.jpg,.jpeg,.png"
                        />
                    </label>

                    {{-- Selected-but-not-yet-uploaded file + upload action --}}
                    @if (isset($documentUploads[$type]) && $documentUploads[$type])
                        <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <p class="flex min-w-0 items-center gap-2 text-sm text-[var(--hs-neutral-700)]">
                                <flux:icon name="document" class="size-4 shrink-0 text-[var(--hs-neutral-500)]" />
                                <span class="truncate">{{ $documentUploads[$type]->getClientOriginalName() }}</span>
                            </p>
                            <flux:button
                                wire:click="uploadDocument('{{ $type }}')"
                                wire:loading.attr="disabled"
                                wire:target="uploadDocument('{{ $type }}'), documentUploads.{{ $type }}"
                                variant="primary"
                                size="sm"
                                class="w-full sm:w-auto"
                            >
                                <span wire:loading.remove wire:target="uploadDocument('{{ $type }}')">{{ __('Upload') }}</span>
                                <span wire:loading wire:target="uploadDocument('{{ $type }}')">{{ __('Uploading…') }}</span>
                            </flux:button>
                        </div>
                    @endif

                    {{-- Upload/transfer progress for this specific field --}}
                    <div
                        wire:loading
                        wire:target="documentUploads.{{ $type }}"
                        class="mt-2 flex items-center gap-2 text-sm text-[var(--hs-neutral-500)]"
                    >
                        <flux:icon name="arrow-path" class="size-4 animate-spin" />
                        {{ __('Uploading file…') }}
                    </div>
                </div>
            @endif

            {{-- Inline error, directly under the control it belongs to --}}
            @error("documentUploads.{$type}")
                <p class="mt-2 flex items-center gap-1.5 text-xs text-[var(--hs-danger-700)]">
                    <flux:icon name="exclamation-circle" class="size-4 shrink-0" />
                    {{ $message }}
                </p>
            @enderror
        </flux:card>
    @endforeach
</div>
