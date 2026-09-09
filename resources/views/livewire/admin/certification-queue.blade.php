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
                                    default => 'green',
                                }">{{ str($certificate->status)->headline() }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:button size="sm" variant="ghost" icon="eye" wire:click="viewDocument({{ $certificate->media_id }})">
                                    {{ __('View') }}
                                </flux:button>
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

    @include('livewire.admin.document-viewer')
</div>
