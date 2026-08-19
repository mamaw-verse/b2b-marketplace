<div>
    @include('livewire.company-hub.partials.product-tabs')

    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        {{-- Card Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)] flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">Documents</h1>
                <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ $product->name }}</p>
            </div>
        </div>

        <div class="p-6">

            {{-- Upload section --}}
            <div class="mb-6 rounded-[var(--hs-radius-card)] border border-dashed border-[var(--hs-neutral-300)] bg-[var(--hs-neutral-50)] p-6">
                <h3 class="text-sm font-semibold text-[var(--hs-neutral-900)] mb-3">Upload Documents</h3>
                <div>
                    <input type="file" wire:model="newDocuments" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp" multiple class="block w-full text-sm text-[var(--hs-neutral-500)] file:mr-4 file:rounded-[var(--hs-radius-pill)] file:border-0 file:bg-[var(--hs-green-50)] file:px-4 file:py-2 file:text-sm file:font-medium file:text-[var(--hs-green-700)] hover:file:bg-[var(--hs-green-100)]" />
                    @error('newDocuments.*') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror
                </div>

                @if ($newDocuments)
                    <div class="mt-4">
                        <flux:button variant="primary" size="sm" wire:click="uploadDocuments">
                            Upload {{ count($newDocuments) }} {{ count($newDocuments) === 1 ? 'Document' : 'Documents' }}
                        </flux:button>
                    </div>
                @endif
            </div>

            {{-- Documents table --}}
            <div class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Filename</flux:table.column>
                        <flux:table.column>Type</flux:table.column>
                        <flux:table.column>Uploaded</flux:table.column>
                        <flux:table.column>Scan Status</flux:table.column>
                        <flux:table.column>Actions</flux:table.column>
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
                                    <flux:button variant="ghost" size="sm" icon="trash" wire:click="removeDocument({{ $document->id }})" wire:confirm="Remove this document?" />
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="5" class="text-center">
                                    <x-hs-empty-state icon="document" title="No documents attached" subtitle="Upload documents for your product." />
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>
    </div>
</div>
