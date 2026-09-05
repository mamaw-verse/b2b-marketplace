<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Onboarding Review') }}</h1>
        <flux:button variant="ghost" icon="arrow-left" href="{{ route('admin.onboardings.index') }}" wire:navigate>
            {{ __('Back to Queue') }}
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
                    <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)]">{{ __('Business Information') }}</h2>
                </div>
                <div class="p-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] uppercase tracking-wider">{{ __('Registered Business Name') }}</p>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ $onboarding->registered_business_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] uppercase tracking-wider">{{ __('Storefront Name') }}</p>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ $onboarding->storefront_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] uppercase tracking-wider">{{ __('Business Structure') }}</p>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ str_replace('_', ' ', ucfirst($onboarding->business_structure ?? '—')) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] uppercase tracking-wider">{{ __('DTI/SEC Number') }}</p>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ $onboarding->dti_sec_number }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] uppercase tracking-wider">{{ __('TIN') }}</p>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ $onboarding->tax_identification_number }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] uppercase tracking-wider">{{ __('FDA/LTO Number') }}</p>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ $onboarding->fda_lto_number ?? '—' }}</p>
                    </div>
                </div>
            </div>

            {{-- Contact & Address --}}
            <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
                <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
                    <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)]">{{ __('Contact & Address') }}</h2>
                </div>
                <div class="p-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] uppercase tracking-wider">{{ __('Phone') }}</p>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ $onboarding->phone ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] uppercase tracking-wider">{{ __('Email') }}</p>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ $onboarding->email ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] uppercase tracking-wider">{{ __('Address') }}</p>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ $onboarding->address_1 }}{{ $onboarding->address_2 ? ', ' . $onboarding->address_2 : '' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] uppercase tracking-wider">{{ __('City / Province') }}</p>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ $onboarding->city ?? '' }}, {{ $onboarding->province ?? '' }} {{ $onboarding->zip_code ?? '' }}</p>
                    </div>
                </div>
            </div>

            {{-- Production & Capabilities --}}
            <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
                <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
                    <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)]">{{ __('Production & Capabilities') }}</h2>
                </div>
                <div class="p-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] uppercase tracking-wider">{{ __('Product Categories') }}</p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @if($this->productCategoryNames && count($this->productCategoryNames) > 0)
                                @foreach($this->productCategoryNames as $name)
                                    <x-hs-badge variant="tag" :label="$name" />
                                @endforeach
                            @else
                                <p class="text-sm text-[var(--hs-neutral-900)]">—</p>
                            @endif
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] uppercase tracking-wider">{{ __('Avg. Production Lead Time') }}</p>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ $onboarding->average_production_lead_time ? $onboarding->average_production_lead_time . ' days' : '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] uppercase tracking-wider">{{ __('Export Readiness') }}</p>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ str_replace('_', ' ', ucfirst($onboarding->export_readiness_status ?? '—')) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] uppercase tracking-wider">{{ __('Value Chain Positions') }}</p>
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
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] uppercase tracking-wider">{{ __('Capabilities') }}</p>
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

            {{-- Halal Certificates --}}
            <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
                <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
                    <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)]">{{ __('Halal Certificates') }}</h2>
                </div>
                <div class="p-6">
                    @if ($onboarding->certificates->isNotEmpty())
                        <ul class="space-y-2">
                            @foreach ($onboarding->certificates as $certificate)
                                <li class="flex items-center gap-3 rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-100)] bg-[var(--hs-neutral-50)] px-4 py-3">
                                    <flux:icon name="document-text" class="size-5 shrink-0 text-[var(--hs-neutral-400)]" />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-[var(--hs-neutral-900)]">{{ $certificate->media?->original_filename ?? '—' }}</p>
                                        @if ($certificate->expiry_date)
                                            <p class="text-xs text-[var(--hs-neutral-500)]">{{ __('Expires :date', ['date' => $certificate->expiry_date->format('M d, Y')]) }}</p>
                                        @endif
                                    </div>
                                    @if ($certificate->media_id)
                                        <flux:button variant="ghost" size="sm" icon="eye" wire:click="viewDocument({{ $certificate->media_id }})">
                                            {{ __('View') }}
                                        </flux:button>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-[var(--hs-neutral-400)]">{{ __('No certificates attached.') }}</p>
                    @endif
                </div>
            </div>

            {{-- Uploaded Documents --}}
            <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
                <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
                    <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)]">{{ __('Uploaded Documents') }}</h2>
                </div>
                <div class="p-6">
                    @if ($onboarding->documents->isNotEmpty())
                        <ul class="space-y-2">
                            @foreach ($onboarding->documents as $document)
                                <li class="flex items-center gap-3 rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-100)] bg-[var(--hs-neutral-50)] px-4 py-3">
                                    <flux:icon name="document-text" class="size-5 shrink-0 text-[var(--hs-neutral-400)]" />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-[var(--hs-neutral-900)]">{{ $document->original_filename }}</p>
                                        @if ($document->description)
                                            <p class="text-xs text-[var(--hs-neutral-500)]">{{ ucfirst(str_replace('_', ' ', $document->description)) }}</p>
                                        @endif
                                    </div>
                                    <x-hs-status-pill :status="$document->scan_status" />
                                    <flux:button variant="ghost" size="sm" icon="eye" wire:click="viewDocument({{ $document->id }})">
                                        {{ __('View') }}
                                    </flux:button>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-[var(--hs-neutral-400)]">{{ __('No documents attached.') }}</p>
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
                <h3 class="text-sm font-semibold text-[var(--hs-neutral-900)] mb-4">{{ __('Applicant') }}</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-[var(--hs-neutral-500)]">{{ __('Name') }}</p>
                        <p class="text-sm font-medium text-[var(--hs-neutral-900)]">{{ $onboarding->user?->first_name }} {{ $onboarding->user?->last_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-[var(--hs-neutral-500)]">{{ __('Email') }}</p>
                        <p class="text-sm text-[var(--hs-neutral-900)]">{{ $onboarding->user?->email }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-[var(--hs-neutral-500)]">{{ __('Submitted') }}</p>
                        <p class="text-sm text-[var(--hs-neutral-900)]">{{ $onboarding->submitted_at?->format('M d, Y \a\t g:i A') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-[var(--hs-neutral-500)]">{{ __('Status') }}</p>
                        <div class="mt-1">
                            <x-hs-status-pill :status="$onboarding->status" />
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            @if ($onboarding->status === 'submitted')
                <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] p-5">
                    <h3 class="text-sm font-semibold text-[var(--hs-neutral-900)] mb-4">{{ __('Decision') }}</h3>

                    @error('approve')
                        <div class="mb-4 rounded-[var(--hs-radius-card)] bg-[var(--hs-danger-50)] border border-[var(--hs-danger-200)] p-3 text-sm text-[var(--hs-danger-700)]">
                            {{ $message }}
                        </div>
                    @enderror

                    <div class="space-y-2">
                        <flux:modal.trigger name="approve-onboarding-confirm">
                            <flux:button variant="primary" icon="check" class="w-full">
                                {{ __('Approve') }}
                            </flux:button>
                        </flux:modal.trigger>
                        <flux:button variant="ghost" icon="arrow-path" class="w-full" wire:click="openActionModal('request_revision')">
                            {{ __('Request Revision') }}
                        </flux:button>
                        <flux:button variant="danger" icon="x-mark" class="w-full" wire:click="openActionModal('reject')">
                            {{ __('Reject') }}
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
                <flux:label>{{ __('Notes') }}</flux:label>
                <flux:textarea wire:model="notes" rows="4" placeholder="{{ __('Enter your notes...') }}" />
                <flux:error name="notes" />
            </flux:field>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('showActionModal', false)">{{ __('Cancel') }}</flux:button>
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
        title="{{ __('Approve Onboarding') }}"
        message="{{ __('Are you sure you want to approve this onboarding?') }}"
        variant="primary"
        confirmText="{{ __('Approve') }}"
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