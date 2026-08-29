<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">Onboarding Review</h1>
        <flux:button variant="ghost" icon="arrow-left" href="{{ route('admin.onboardings.index') }}" wire:navigate>
            Back to Queue
        </flux:button>
    </div>

    @if (session()->has('message'))
        <div class="mb-6 rounded-[var(--hs-radius-card)] bg-[var(--hs-green-50)] border border-[var(--hs-green-200)] p-4 text-sm text-[var(--hs-green-700)]">
            {{ session('message') }}
        </div>
    @endif

    {{--
        xl:items-start stops the grid from stretching the sidebar column to match
        the (much taller) main column's height — required for xl:sticky below to
        actually have room to stick within.
    --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3 xl:items-start">
        {{-- Main Content (2 cols) — every card in this column shares the same width,
             including the two table cards that used to break out to full width. --}}
        <div class="xl:col-span-2 space-y-6">
            {{-- Business Information --}}
            <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
                <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
                    <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)]">Business Information</h2>
                </div>
                <div class="p-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] uppercase tracking-wider">Registered Business Name</p>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ $onboarding->registered_business_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] uppercase tracking-wider">Storefront Name</p>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ $onboarding->storefront_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] uppercase tracking-wider">Business Structure</p>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ str_replace('_', ' ', ucfirst($onboarding->business_structure ?? '—')) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] uppercase tracking-wider">DTI/SEC Number</p>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ $onboarding->dti_sec_number }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] uppercase tracking-wider">TIN</p>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ $onboarding->tax_identification_number }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] uppercase tracking-wider">FDA/LTO Number</p>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ $onboarding->fda_lto_number ?? '—' }}</p>
                    </div>
                </div>
            </div>

            {{-- Contact & Address --}}
            <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
                <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
                    <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)]">Contact & Address</h2>
                </div>
                <div class="p-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] uppercase tracking-wider">Phone</p>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ $onboarding->phone ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] uppercase tracking-wider">Email</p>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ $onboarding->email ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] uppercase tracking-wider">Address</p>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ $onboarding->address_1 }}{{ $onboarding->address_2 ? ', ' . $onboarding->address_2 : '' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] uppercase tracking-wider">City / Province</p>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ $onboarding->city ?? '' }}, {{ $onboarding->province ?? '' }} {{ $onboarding->zip_code ?? '' }}</p>
                    </div>
                </div>
            </div>

            {{-- Production & Capabilities --}}
            <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
                <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
                    <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)]">Production & Capabilities</h2>
                </div>
                <div class="p-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] uppercase tracking-wider">Product Categories</p>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ $onboarding->product_categories ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] uppercase tracking-wider">Avg. Production Lead Time</p>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ $onboarding->average_production_lead_time ? $onboarding->average_production_lead_time . ' days' : '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] uppercase tracking-wider">Export Readiness</p>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ str_replace('_', ' ', ucfirst($onboarding->export_readiness_status ?? '—')) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] uppercase tracking-wider">Value Chain Positions</p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @if(is_array($onboarding->value_chain_positions) && count($onboarding->value_chain_positions) > 0)
                                @foreach($onboarding->value_chain_positions as $pos)
                                    <x-hs-badge variant="tag" :label="ucfirst(str_replace('_', ' ', $pos))" />
                                @endforeach
                            @else
                                <p class="text-sm text-[var(--hs-neutral-900)]">—</p>
                            @endif
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] uppercase tracking-wider">Capabilities</p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @if(is_array($onboarding->capabilities))
                                @foreach($onboarding->capabilities as $cap)
                                    <x-hs-badge variant="tag" :label="ucfirst(str_replace('_', ' ', $cap))" />
                                @endforeach
                            @else
                                <p class="text-sm text-[var(--hs-neutral-900)]">—</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Halal Certificates — moved back into the main column. The
                 overflow-x-auto wrapper already handles wide table content,
                 so this card doesn't need to break the grid to avoid clipping. --}}
            <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
                <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
                    <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)]">Halal Certificates</h2>
                </div>
                <div class="p-6">
                    @if ($onboarding->certificates->isNotEmpty())
                        <div class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] overflow-x-auto">
                            <flux:table>
                                <flux:table.columns>
                                    <flux:table.column>Certification Body</flux:table.column>
                                    <flux:table.column>Registration #</flux:table.column>
                                    <flux:table.column>Expiry Date</flux:table.column>
                                    <flux:table.column>Document</flux:table.column>
                                </flux:table.columns>
                                <flux:table.rows>
                                    @foreach ($onboarding->certificates as $certificate)
                                        <flux:table.row :key="$certificate->id" class="hover:bg-[var(--hs-neutral-50)] transition">
                                            <flux:table.cell>{{ $certificate->certification_body }}{{ $certificate->certification_body_other ? ' (' . $certificate->certification_body_other . ')' : '' }}</flux:table.cell>
                                            <flux:table.cell>{{ $certificate->registration_number }}</flux:table.cell>
                                            <flux:table.cell>{{ $certificate->expiry_date?->format('M d, Y') ?? '—' }}</flux:table.cell>
                                            <flux:table.cell>
                                                @if ($certificate->media_id)
                                                    <flux:button variant="ghost" size="sm" icon="eye" wire:click="viewDocument({{ $certificate->media_id }})">
                                                        View
                                                    </flux:button>
                                                @else
                                                    <span class="text-[var(--hs-neutral-400)]">—</span>
                                                @endif
                                            </flux:table.cell>
                                        </flux:table.row>
                                    @endforeach
                                </flux:table.rows>
                            </flux:table>
                        </div>
                    @else
                        <p class="text-sm text-[var(--hs-neutral-400)]">No certificates attached.</p>
                    @endif
                </div>
            </div>

            {{-- Uploaded Documents — moved back into the main column, same reasoning. --}}
            <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
                <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
                    <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)]">Uploaded Documents</h2>
                </div>
                <div class="p-6">
                    @if ($onboarding->documents->isNotEmpty())
                        <div class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] overflow-x-auto">
                            <flux:table>
                                <flux:table.columns>
                                    <flux:table.column>Document</flux:table.column>
                                    <flux:table.column>Type</flux:table.column>
                                    <flux:table.column>Scan Status</flux:table.column>
                                    <flux:table.column>Actions</flux:table.column>
                                </flux:table.columns>
                                <flux:table.rows>
                                    @foreach ($onboarding->documents as $document)
                                        <flux:table.row :key="$document->id" class="hover:bg-[var(--hs-neutral-50)] transition">
                                            <flux:table.cell variant="strong">{{ $document->original_filename }}</flux:table.cell>
                                            <flux:table.cell>{{ $document->description ?? '—' }}</flux:table.cell>
                                            <flux:table.cell class="py-0">
                                                <x-hs-status-pill :status="$document->scan_status" />
                                            </flux:table.cell>
                                            <flux:table.cell>
                                                <flux:button variant="ghost" size="sm" icon="eye" wire:click="viewDocument({{ $document->id }})">
                                                    View
                                                </flux:button>
                                            </flux:table.cell>
                                        </flux:table.row>
                                    @endforeach
                                </flux:table.rows>
                            </flux:table>
                        </div>
                    @else
                        <p class="text-sm text-[var(--hs-neutral-400)]">No documents attached.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Decision Panel (sidebar) — now sticky, so Applicant info + the
             Approve/Revise/Reject actions stay in view as the admin scrolls
             through the (now longer) main column instead of scrolling out of
             sight after Production & Capabilities. --}}
        <div class="space-y-6 xl:sticky xl:top-6">
            {{-- Applicant Info --}}
            <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] p-5">
                <h3 class="text-sm font-semibold text-[var(--hs-neutral-900)] mb-4">Applicant</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-[var(--hs-neutral-500)]">Name</p>
                        <p class="text-sm font-medium text-[var(--hs-neutral-900)]">{{ $onboarding->user?->first_name }} {{ $onboarding->user?->last_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-[var(--hs-neutral-500)]">Email</p>
                        <p class="text-sm text-[var(--hs-neutral-900)]">{{ $onboarding->user?->email }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-[var(--hs-neutral-500)]">Submitted</p>
                        <p class="text-sm text-[var(--hs-neutral-900)]">{{ $onboarding->submitted_at?->format('M d, Y \a\t g:i A') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-[var(--hs-neutral-500)]">Status</p>
                        <div class="mt-1">
                            <x-hs-status-pill :status="$onboarding->status" />
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            @if (in_array($onboarding->status, ['pending', 'submitted', 'requires_revision']))
                <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] p-5">
                    <h3 class="text-sm font-semibold text-[var(--hs-neutral-900)] mb-4">Decision</h3>

                    @error('approve')
                        <div class="mb-4 rounded-[var(--hs-radius-card)] bg-[var(--hs-danger-50)] border border-[var(--hs-danger-200)] p-3 text-sm text-[var(--hs-danger-700)]">
                            {{ $message }}
                        </div>
                    @enderror

                    <div class="space-y-2">
                        <flux:modal.trigger name="approve-onboarding-confirm">
                            <flux:button variant="primary" icon="check" class="w-full">
                                Approve
                            </flux:button>
                        </flux:modal.trigger>
                        <flux:button variant="ghost" icon="arrow-path" class="w-full" wire:click="openActionModal('request_revision')">
                            Request Revision
                        </flux:button>
                        <flux:button variant="danger" icon="x-mark" class="w-full" wire:click="openActionModal('reject')">
                            Reject
                        </flux:button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Action Modal (Request Revision / Reject) --}}
    <flux:modal wire:model="showActionModal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $action === 'request_revision' ? 'Request Revision' : 'Reject Onboarding' }}
                </flux:heading>
                <flux:text class="mt-2">
                    {{ $action === 'request_revision' ? 'Provide notes explaining what needs to be revised.' : 'Provide a reason for rejecting this application.' }}
                </flux:text>
            </div>

            <flux:field>
                <flux:label>Notes</flux:label>
                <flux:textarea wire:model="notes" rows="4" placeholder="Enter your notes..." />
                <flux:error name="notes" />
            </flux:field>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('showActionModal', false)">Cancel</flux:button>
                <flux:button
                    :variant="$action === 'reject' ? 'danger' : 'primary'"
                    wire:click="submitAction"
                >
                    {{ $action === 'request_revision' ? 'Submit Revision Request' : 'Confirm Rejection' }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Approve Onboarding Confirmation --}}
    <x-hs-confirm
        name="approve-onboarding-confirm"
        title="Approve Onboarding"
        message="Are you sure you want to approve this onboarding?"
        variant="primary"
        confirmText="Approve"
    >
        <x-slot name="action">
            <flux:button variant="primary" wire:click="approve" wire:loading.attr="disabled">
                {{ __('Approve') }}
            </flux:button>
        </x-slot>
    </x-hs-confirm>

    {{-- Document Viewer Modal --}}
    @include('livewire.admin.document-viewer')
</div>