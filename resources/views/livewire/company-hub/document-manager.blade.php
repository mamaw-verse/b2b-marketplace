{{--
    While any document is still scanning, poll every 5s so the Scan Status
    column updates on its own. The `documents` computed re-queries on each
    poll, so a bare wire:poll is enough to pick up the job's result. Polling
    stops automatically once hasPendingDocuments is false, so a settled list
    is idle.
--}}
<div @if ($this->hasPendingDocuments) wire:poll.5s @endif>
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

            {{-- Upload section --}}
            <div class="mb-6 rounded-[var(--hs-radius-card)] border border-dashed border-[var(--hs-neutral-300)] bg-[var(--hs-neutral-50)] p-6">
                <h3 class="text-sm font-semibold text-[var(--hs-neutral-900)] mb-3">{{ __('Upload Documents') }}</h3>
                <div>
                    <input type="file" wire:model="newDocuments" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp" multiple class="block w-full text-sm text-[var(--hs-neutral-500)] file:mr-4 file:rounded-[var(--hs-radius-pill)] file:border-0 file:bg-[var(--hs-green-50)] file:px-4 file:py-2 file:text-sm file:font-medium file:text-[var(--hs-green-700)] hover:file:bg-[var(--hs-green-100)]" />
                    @error('newDocuments.*') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror
                </div>

                @if ($newDocuments)
                    <div class="mt-4">
                        <flux:button variant="primary" size="sm" wire:click="uploadDocuments">
                            {{ __('Upload :count :label', ['count' => count($newDocuments), 'label' => count($newDocuments) === 1 ? __('Document') : __('Documents')]) }}
                        </flux:button>
                    </div>
                @endif
            </div>

            {{-- Live status line so the user knows the page is checking on its own --}}
            @if ($this->hasPendingDocuments)
                <div class="mb-4 flex items-center gap-2 rounded-[var(--hs-radius-card)] border border-[var(--hs-warning-200)] bg-[var(--hs-warning-50)] px-4 py-2.5 text-sm text-[var(--hs-warning-700)]" aria-live="polite">
                    <flux:icon name="arrow-path" class="size-4 shrink-0 animate-spin" />
                    {{ __('Checking scan status automatically…') }}
                </div>
            @endif

            {{-- Documents table --}}
            <div class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] overflow-x-auto">
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
                            <flux:table.row :key="$document->id" class="hover:bg-[var(--hs-neutral-50)] transition">
                                <flux:table.cell variant="strong">{{ $document->original_filename }}</flux:table.cell>
                                <flux:table.cell>
                                    <x-hs-badge variant="tag" :label="strtoupper($document->extension)" />
                                </flux:table.cell>
                                <flux:table.cell>{{ $document->created_at->format('M d, Y') }}</flux:table.cell>
                                <flux:table.cell class="py-0">
                                    <x-hs-status-pill :status="$document->scan_status" />
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:button variant="ghost" size="sm" icon="trash" wire:click="confirmRemoveDocument({{ $document->id }})" />
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
</div>
