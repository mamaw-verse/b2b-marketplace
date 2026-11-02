{{--
    Poll every 5s while any document is pending scan. Calls refreshDocuments
    to bust the computed cache so the table re-renders with fresh statuses.
    Polling stops automatically once hasPendingDocuments is false.
--}}
<div @if ($this->hasPendingDocuments) wire:poll.5s="refreshDocuments" @endif>
    @include('livewire.company-hub.partials.product-tabs')

    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        {{-- Card Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)] flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Documents') }}</h1>
                <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ $product->name }}</p>
            </div>
        </div>

        <div class="p-6">

            {{-- Upload dropzone --}}
            <x-hs-dropzone
                input-id="doc-manager-input"
                wire-model="newDocuments"
                accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp"
                hint="{{ __('PDF, DOC, XLS, JPG, PNG, WebP — up to 20MB each') }}"
                on-finish="$wire.uploadDocuments()"
                loading-target="newDocuments,uploadDocuments"
                class="mb-6"
            >
                @error('newDocuments.*') <p class="mt-2 text-sm text-[var(--hs-danger-700)]">{{ $message }}</p> @enderror
            </x-hs-dropzone>

            {{-- Scanning notice --}}
            @if ($this->hasPendingDocuments)
                <div class="mb-4 flex items-center gap-2 rounded-[var(--hs-radius-card)] border border-[var(--hs-warning-200)] bg-[var(--hs-warning-50)] px-4 py-2.5 text-sm text-[var(--hs-warning-700)]" aria-live="polite">
                    <flux:icon name="arrow-path" class="size-4 shrink-0 animate-spin" />
                    {{ __('Checking scan status automatically…') }}
                </div>
            @endif

            {{-- Documents: card list on mobile, table on sm+ --}}
            <div class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] overflow-hidden">

                {{-- Mobile card list (hidden on sm+) --}}
                <div class="sm:hidden divide-y divide-[var(--hs-neutral-100)]">
                    @forelse ($this->documents as $document)
                        <div class="flex items-start gap-3 px-4 py-3.5" wire:key="doc-card-{{ $document->id }}">
                            <div class="shrink-0 mt-0.5">
                                @if ($document->scan_status === 'infected')
                                    <flux:icon name="exclamation-circle" class="size-5 text-[var(--hs-danger-500)]" />
                                @elseif ($document->scan_status === 'clean')
                                    <flux:icon name="document-check" class="size-5 text-[var(--hs-green-600)]" />
                                @else
                                    <flux:icon name="document" class="size-5 text-[var(--hs-neutral-400)]" />
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-[var(--hs-neutral-900)]">{{ $document->original_filename }}</p>
                                <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <x-hs-badge variant="tag" :label="strtoupper($document->extension)" />
                                    @switch($document->scan_status)
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
                                    <span class="text-xs text-[var(--hs-neutral-400)]">{{ $document->created_at->format('M d, Y') }}</span>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-1">
                                <flux:button variant="ghost" size="sm" icon="eye" wire:click="viewDocument({{ $document->id }})" />
                                <flux:button variant="ghost" size="sm" icon="trash" wire:click="confirmRemoveDocument({{ $document->id }})" />
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-4">
                            <x-hs-empty-state icon="document" title="{{ __('No documents attached') }}" subtitle="{{ __('Upload documents for your product.') }}" />
                        </div>
                    @endforelse
                </div>

                {{-- Desktop table (hidden on mobile) --}}
                <div class="hidden sm:block overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>{{ __('Filename') }}</flux:table.column>
                            <flux:table.column>{{ __('Type') }}</flux:table.column>
                            <flux:table.column>{{ __('Uploaded') }}</flux:table.column>
                            <flux:table.column>{{ __('Scan Status') }}</flux:table.column>
                            <flux:table.column>{{ __('Actions') }}</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @forelse ($this->documents as $document)
                                <flux:table.row :key="$document->id" wire:key="doc-row-{{ $document->id }}">
                                    <flux:table.cell variant="strong">
                                        <div class="flex items-center gap-2">
                                            @if ($document->scan_status === 'infected')
                                                <flux:icon name="exclamation-circle" class="size-4 shrink-0 text-[var(--hs-danger-500)]" />
                                            @elseif ($document->scan_status === 'clean')
                                                <flux:icon name="document-check" class="size-4 shrink-0 text-[var(--hs-green-600)]" />
                                            @else
                                                <flux:icon name="document" class="size-4 shrink-0 text-[var(--hs-neutral-400)]" />
                                            @endif
                                            <span class="truncate max-w-[200px]">{{ $document->original_filename }}</span>
                                        </div>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <x-hs-badge variant="tag" :label="strtoupper($document->extension)" />
                                    </flux:table.cell>
                                    <flux:table.cell>{{ $document->created_at->format('M d, Y') }}</flux:table.cell>
                                    <flux:table.cell class="py-0">
                                        @switch($document->scan_status)
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
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <div class="flex items-center gap-1">
                                            <flux:button variant="ghost" size="sm" icon="eye" wire:click="viewDocument({{ $document->id }})" />
                                            <flux:button variant="ghost" size="sm" icon="trash" wire:click="confirmRemoveDocument({{ $document->id }})" />
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @empty
                                <flux:table.row>
                                    <flux:table.cell colspan="5" class="text-center">
                                        <x-hs-empty-state icon="document" title="{{ __('No documents attached') }}" subtitle="{{ __('Upload documents for your product.') }}" />
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforelse
                        </flux:table.rows>
                    </flux:table>
                </div>
            </div>
        </div>
    </div>

    {{-- Remove Document Confirmation --}}
    <x-hs-confirm
        name="remove-document-confirm"
        title="{{ __('Remove Document') }}"
        message="{{ __('Remove this document?') }}"
        variant="danger"
        confirmText="{{ __('Remove') }}"
    >
        <x-slot name="action">
            <flux:button variant="danger" wire:click="removeDocument" wire:loading.attr="disabled">
                {{ __('Remove') }}
            </flux:button>
        </x-slot>
    </x-hs-confirm>

    {{-- Document Preview Modal --}}
    @include('livewire.admin.document-viewer')
</div>
