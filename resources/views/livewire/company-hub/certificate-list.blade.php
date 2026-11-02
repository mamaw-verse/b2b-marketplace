<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">

        {{-- Card Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)] flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Certificates') }}</h1>
                <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ __('Your halal certification records.') }}</p>
            </div>

            <div class="flex items-center gap-2">
                <flux:button variant="primary" icon="plus" wire:click="openAddModal">
                    {{ __('Add Certificate') }}
                </flux:button>
                <flux:button variant="ghost" icon="check-badge" href="{{ route('apply.certificate') }}" wire:navigate>
                    {{ __('Apply for Certificate') }}
                </flux:button>
            </div>
        </div>

        {{-- Certificates: card list on mobile, table on sm+ --}}

        {{-- Mobile card list (hidden on sm+) --}}
        <div class="sm:hidden divide-y divide-[var(--hs-neutral-100)]">
            @forelse ($this->certificates as $certificate)
                <div class="flex items-start justify-between gap-3 px-5 py-4" wire:key="cert-card-{{ $certificate->id }}">
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] uppercase tracking-wide">{{ __('Expires') }}</p>
                        <p class="mt-0.5 font-semibold text-[var(--hs-neutral-900)]">
                            {{ $certificate->expiry_date?->format('M d, Y') ?? '—' }}
                        </p>
                        <div class="mt-1.5">
                            @switch($certificate->status)
                                @case('active')
                                    <flux:badge color="green" size="sm" icon="check-circle">{{ __('Active') }}</flux:badge>
                                    @break
                                @case('expiring_soon')
                                    <flux:badge color="yellow" size="sm" icon="clock">{{ __('Expiring Soon') }}</flux:badge>
                                    @break
                                @case('expired')
                                    <flux:badge color="red" size="sm" icon="x-circle">{{ __('Expired') }}</flux:badge>
                                    @break
                                @case('under_review')
                                    <flux:badge color="blue" size="sm" icon="magnifying-glass">{{ __('Under Review') }}</flux:badge>
                                    @break
                                @default
                                    <flux:badge size="sm">{{ str($certificate->status)->headline() }}</flux:badge>
                            @endswitch
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        @if ($certificate->media_id)
                            <flux:button variant="ghost" size="sm" icon="eye" wire:click="viewDocument({{ $certificate->media_id }})">
                                {{ __('View') }}
                            </flux:button>
                        @endif
                        @if ($certificate->status === 'expired')
                            <flux:button variant="primary" size="sm" icon="check-badge" href="{{ route('apply.certificate') }}" wire:navigate>
                                {{ __('Apply') }}
                            </flux:button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-5 py-4">
                    <x-hs-empty-state icon="package" title="{{ __('No certificates yet') }}" subtitle="{{ __('Add your halal certificates to get started.') }}" />
                </div>
            @endforelse
        </div>

        {{-- Desktop table (hidden on mobile) --}}
        <div class="hidden sm:block overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Expiry Date') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->certificates as $certificate)
                        <flux:table.row
                            :key="$certificate->id"
                            wire:key="certificate-{{ $certificate->id }}"
                            class="hover:bg-[var(--hs-neutral-50)] transition"
                        >
                            {{-- Expiry Date --}}
                            <flux:table.cell variant="strong" class="text-[var(--hs-neutral-600)]">
                                {{ $certificate->expiry_date?->format('M d, Y') ?? '—' }}
                            </flux:table.cell>

                            {{-- Status --}}
                            <flux:table.cell>
                                @switch($certificate->status)
                                    @case('active')
                                        <flux:badge color="green" size="sm" icon="check-circle">{{ __('Active') }}</flux:badge>
                                        @break
                                    @case('expiring_soon')
                                        <flux:badge color="yellow" size="sm" icon="clock">{{ __('Expiring Soon') }}</flux:badge>
                                        @break
                                    @case('expired')
                                        <flux:badge color="red" size="sm" icon="x-circle">{{ __('Expired') }}</flux:badge>
                                        @break
                                    @case('under_review')
                                        <flux:badge color="blue" size="sm" icon="magnifying-glass">{{ __('Under Review') }}</flux:badge>
                                        @break
                                    @default
                                        <flux:badge size="sm">{{ str($certificate->status)->headline() }}</flux:badge>
                                @endswitch
                            </flux:table.cell>

                            {{-- Actions --}}
                            <flux:table.cell align="end">
                                <div class="flex justify-end gap-1">
                                    @if ($certificate->media_id)
                                        <flux:button variant="ghost" size="sm" icon="eye" wire:click="viewDocument({{ $certificate->media_id }})">
                                            {{ __('View') }}
                                        </flux:button>
                                    @endif
                                    @if ($certificate->status === 'expired')
                                        <flux:button variant="primary" size="sm" icon="check-badge" href="{{ route('apply.certificate') }}" wire:navigate>
                                            {{ __('Apply') }}
                                        </flux:button>
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="3" class="text-center">
                                <x-hs-empty-state icon="package" title="{{ __('No certificates yet') }}" subtitle="{{ __('Add your halal certificates to get started.') }}" />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

    </div>

    {{-- Add Certificate Modal --}}
    <flux:modal wire:model="showAddModal" class="w-full sm:w-auto md:w-[32rem]">
        <form wire:submit="saveCertificate" class="space-y-5" @if ($uploadedScanStatus === 'pending') wire:poll.5s="refreshScanStatus" @endif>
            <div>
                <flux:heading size="lg">{{ __('Add Certificate') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Provide the expiry date and upload the certificate file.') }}</flux:text>
            </div>

            <flux:field>
                <flux:label>{{ __('Expiry Date') }}</flux:label>
                <flux:input wire:model.live="expiry_date" type="date" min="{{ now()->addDay()->format('Y-m-d') }}" />
                <flux:error name="expiry_date" />
            </flux:field>

            {{-- Certificate File --}}
            <div>
                <flux:label>{{ __('Certificate File') }}</flux:label>

                @if ($uploadedMediaId)
                    @switch($uploadedScanStatus)
                        @case('clean')
                            <div class="mt-1 flex items-center gap-3 rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-100)] bg-[var(--hs-neutral-0)] px-4 py-3">
                                <flux:icon name="document-check" class="size-5 shrink-0 text-[var(--hs-green-600)]" />
                                <p class="min-w-0 flex-1 truncate text-sm font-medium text-[var(--hs-neutral-900)]">{{ $uploadedFilename }}</p>
                                <flux:icon name="check-circle" class="size-5 shrink-0 text-[var(--hs-green-600)]" />
                                <flux:button
                                    wire:click="replaceCertificateFile"
                                    variant="ghost"
                                    size="sm"
                                    icon="arrow-path"
                                    class="shrink-0"
                                >
                                    {{ __('Replace') }}
                                </flux:button>
                            </div>
                            @break
                        @case('pending')
                            <div class="mt-1 flex items-center gap-3 rounded-[var(--hs-radius-card)] border border-[var(--hs-warning-200)] bg-[var(--hs-warning-50)] px-4 py-3">
                                <flux:icon name="clock" class="size-5 shrink-0 text-[var(--hs-warning-700)]" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-[var(--hs-neutral-900)]">{{ $uploadedFilename }}</p>
                                    <p class="text-xs text-[var(--hs-warning-700)]">{{ __('Security scan in progress.') }}</p>
                                </div>
                                <flux:button
                                    wire:click="replaceCertificateFile"
                                    variant="ghost"
                                    size="sm"
                                    icon="arrow-path"
                                    class="shrink-0"
                                >
                                    {{ __('Re-upload') }}
                                </flux:button>
                            </div>
                            @break
                        @default
                            <p class="mt-2 flex items-center gap-1.5 text-xs text-[var(--hs-danger-700)]">
                                <flux:icon name="exclamation-circle" class="size-4 shrink-0" />
                                {{ __('":file" failed the security scan. Please upload a different file.', ['file' => $uploadedFilename]) }}
                            </p>
                            <div class="mt-2">
                                @include('livewire.company-hub.partials.certificate-dropzone')
                            </div>
                    @endswitch
                @else
                    @include('livewire.company-hub.partials.certificate-dropzone')
                @endif
            </div>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('showAddModal', false)">{{ __('Cancel') }}</flux:button>
                @php $ready = $this->isReadyToSave(); @endphp
                @if (! $ready)
                    <flux:button
                        type="submit"
                        variant="primary"
                        wire:loading.attr="disabled"
                        disabled
                        tooltip="{{ $uploadedScanStatus === 'pending' ? __('Waiting for security scan to complete…') : __('Please fill in all fields and upload a certificate file.') }}"
                    >
                        {{ __('Save Certificate') }}
                    </flux:button>
                @else
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        {{ __('Save Certificate') }}
                    </flux:button>
                @endif
            </div>
        </form>
    </flux:modal>

    {{-- Document Viewer Modal --}}
    @include('livewire.admin.document-viewer')
</div>
