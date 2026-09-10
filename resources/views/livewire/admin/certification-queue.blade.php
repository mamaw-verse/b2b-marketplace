<div class="space-y-6">
    {{-- Primary: Halal Certifications --}}
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Halal Certifications') }}</h1>
        </div>

        @if (session('message'))
            <div class="px-6 pt-5">
                <flux:callout variant="success" icon="check-circle">{{ session('message') }}</flux:callout>
            </div>
        @endif

        {{-- Filters --}}
        <div class="px-4 sm:px-6 py-5 bg-[var(--hs-neutral-50)] border-b border-[var(--hs-neutral-100)]">
            <div class="flex flex-col md:flex-row md:items-center gap-4">
                <div class="flex-1">
                    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Search by business name...') }}" />
                </div>
                <flux:select wire:model.live="statusFilter" class="w-44" placeholder="{{ __('All Statuses') }}">
                    <flux:select.option value="under_review">{{ __('Under Review') }}</flux:select.option>
                    <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                    <flux:select.option value="expiring_soon">{{ __('Expiring Soon') }}</flux:select.option>
                    <flux:select.option value="expired">{{ __('Expired') }}</flux:select.option>
                </flux:select>
            </div>
        </div>

        <div class="overflow-hidden">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Business Name') }}</flux:table.column>
                    <flux:table.column>{{ __('Expiry of Certificate') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->certificates as $certificate)
                        <flux:table.row wire:key="certificate-{{ $certificate->id }}">
                            <flux:table.cell variant="strong">{{ $certificate->shop?->registered_business_name ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $certificate->expiry_date?->format('M d, Y') }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="match ($certificate->status) {
                                    'expired' => 'red',
                                    'expiring_soon' => 'yellow',
                                    'under_review' => 'blue',
                                    default => 'green',
                                }">{{ str($certificate->status)->headline() }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($certificate->media_id)
                                    <flux:button size="sm" variant="ghost" icon="eye" wire:click="viewCertificate({{ $certificate->id }})">
                                        {{ __('View') }}
                                    </flux:button>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row><flux:table.cell colspan="4" class="text-center">{{ __('No halal certifications found.') }}</flux:table.cell></flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
            @if ($this->certificates->hasPages())
                <div class="px-6 py-4 border-t border-[var(--hs-neutral-100)]">{{ $this->certificates->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Secondary: Application Queue (read-only log) --}}
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h2 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Halal Certificate Applicants') }}</h2>
        </div>

        <div class="overflow-hidden">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Business Name') }}</flux:table.column>
                    <flux:table.column>{{ __('Date Submitted') }}</flux:table.column>
                    <flux:table.column>{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->applications as $application)
                        <flux:table.row wire:key="application-{{ $application->id }}">
                            <flux:table.cell variant="strong">{{ $application->company_name ?? $application->shop?->registered_business_name ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $application->created_at?->format('M d, Y') }}</flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $previewable = $this->canPreviewApplicationDocument($application->document_path);
                                    $downloadUrl = $previewable ? null : $this->applicationDocumentUrl($application->id, true);
                                @endphp
                                @if ($previewable)
                                    <flux:button size="sm" variant="ghost" icon="eye" wire:click="viewApplicationDocument({{ $application->id }})">
                                        {{ __('View Document') }}
                                    </flux:button>
                                @elseif ($downloadUrl)
                                    <flux:button size="sm" variant="ghost" icon="arrow-down-tray" href="{{ $downloadUrl }}">
                                        {{ __('Download') }}
                                    </flux:button>
                                @else
                                    <flux:text>—</flux:text>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row><flux:table.cell colspan="3" class="text-center">{{ __('No certification applications found.') }}</flux:table.cell></flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
            @if ($this->applications->hasPages())
                <div class="px-6 py-4 border-t border-[var(--hs-neutral-100)]">{{ $this->applications->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Certificate Document Viewer (with Approve action for under_review certs) --}}
    <flux:modal wire:model="showDocumentViewer" class="w-full md:w-[64rem] lg:w-[76rem] max-w-[calc(100vw-2rem)] h-[85vh] max-h-[85vh] overflow-hidden flex flex-col">
        <div class="flex h-full min-h-0 flex-col gap-4 overflow-hidden">
            @if ($documentPreviewUrl)
                @php
                    $media = $viewingMediaId ? \App\Models\Media::find($viewingMediaId) : null;
                    $isImage = $media && in_array($media->extension, ['jpg', 'jpeg', 'png', 'webp']);
                    $viewingCert = $viewingCertificateId ? \App\Models\ShopCertificate::find($viewingCertificateId) : null;
                @endphp

                <div class="flex items-start justify-between gap-4 pe-10">
                    <div class="min-w-0">
                        <flux:heading size="lg">{{ __('Document Preview') }}</flux:heading>
                        <p class="mt-1 truncate text-sm text-[var(--hs-neutral-500)]">{{ $media?->original_filename }}</p>
                        @if ($viewingCert)
                            <p class="mt-0.5 text-sm text-[var(--hs-neutral-500)]">
                                {{ $viewingCert->shop?->registered_business_name ?? '—' }}
                                &middot;
                                {{ __('Expires') }} {{ $viewingCert->expiry_date?->format('M d, Y') }}
                            </p>
                        @endif
                    </div>
                    <flux:button
                        variant="ghost"
                        size="sm"
                        icon="arrow-top-right-on-square"
                        href="{{ $documentPreviewUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="shrink-0"
                    >
                        {{ __('Open in new tab') }}
                    </flux:button>
                </div>

                <div class="min-h-0 flex-1 overflow-auto rounded border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)]">
                    @if ($isImage)
                        <div class="flex justify-center p-2">
                            <img src="{{ $documentPreviewUrl }}" alt="{{ $media->original_filename }}" class="max-h-full rounded">
                        </div>
                    @else
                        <iframe src="{{ $documentPreviewUrl }}" class="h-full w-full" title="{{ __('Document Preview') }}"></iframe>
                    @endif
                </div>

                <div class="flex justify-between items-center shrink-0">
                    <div>
                        @if ($viewingCert && $viewingCert->status === 'under_review')
                            <flux:button
                                variant="primary"
                                icon="check-circle"
                                x-on:click="$flux.modal('approve-certificate').show()"
                            >
                                {{ __('Approve Certificate') }}
                            </flux:button>
                        @endif
                    </div>
                    <flux:button variant="ghost" wire:click="closeDocumentViewer">{{ __('Close') }}</flux:button>
                </div>
            @else
                <flux:heading size="lg">{{ __('Document Preview') }}</flux:heading>
                <flux:text>{{ __('Loading document...') }}</flux:text>
            @endif
        </div>
    </flux:modal>

    {{-- Approve Certificate Confirmation --}}
    @if ($viewingCertificateId)
        <x-hs-confirm
            name="approve-certificate"
            title="{{ __('Approve Certificate') }}"
            message="{{ __('This will mark the certificate as active and notify the seller.') }}"
            variant="primary"
            :confirmText="__('Approve')"
        >
            <x-slot name="action">
                <flux:button variant="primary" wire:click="approveCertificate({{ $viewingCertificateId }})" wire:loading.attr="disabled">
                    {{ __('Approve') }}
                </flux:button>
            </x-slot>
        </x-hs-confirm>
    @endif
</div>
