<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Sourcing Request') }}</h1>
        <flux:button variant="ghost" icon="arrow-left" href="{{ route('admin.sourcing-requests.index') }}" wire:navigate>
            {{ __('Back to Queue') }}
        </flux:button>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3 xl:items-start">
        {{-- Main Content --}}
        <div class="xl:col-span-2 space-y-6">
            {{-- Product Details --}}
            <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
                <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
                    <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)]">{{ __('Product Details') }}</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] tracking-wider">{{ __('Product Name') }}</p>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ $sourcingRequest->product_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] tracking-wider">{{ __('Description & Specifications') }}</p>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-900)] whitespace-pre-wrap">{{ $sourcingRequest->product_description ?: '—' }}</p>
                    </div>
                </div>
            </div>

            {{-- Reference File --}}
            @if ($sourcingRequest->reference_file_path)
                <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
                    <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
                        <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)]">{{ __('Reference File') }}</h2>
                    </div>
                    <div class="p-6">
                        @if ($this->isImage())
                            <div class="overflow-hidden rounded-lg border border-[var(--hs-neutral-200)]">
                                <img
                                    src="{{ $this->referenceFileUrl() }}"
                                    alt="{{ __('Reference image') }}"
                                    class="max-h-96 w-full object-contain"
                                    loading="lazy"
                                />
                            </div>
                        @elseif ($sourcingRequest->reference_file_path)
                            <div class="flex items-center gap-3 rounded-lg border border-[var(--hs-neutral-100)] bg-[var(--hs-neutral-50)] px-4 py-3">
                                <flux:icon name="document-text" class="size-5 shrink-0 text-[var(--hs-neutral-400)]" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-[var(--hs-neutral-900)]">
                                        {{ basename($sourcingRequest->reference_file_path) }}
                                    </p>
                                    <p class="text-xs text-[var(--hs-neutral-500)]">
                                        {{ strtoupper(pathinfo($sourcingRequest->reference_file_path, PATHINFO_EXTENSION)) }} {{ __('file') }}
                                    </p>
                                </div>
                                @if ($this->canPreviewDocument())
                                    <flux:button variant="ghost" size="sm" icon="eye" wire:click="viewReferenceDocument">
                                        {{ __('View') }}
                                    </flux:button>
                                @endif
                                <flux:button variant="ghost" size="sm" icon="arrow-down-tray" href="{{ $this->referenceFileUrl(true) }}">
                                    {{ __('Download') }}
                                </flux:button>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Requester Info --}}
            <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
                <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
                    <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)]">{{ __('Requester Information') }}</h2>
                </div>
                <div class="p-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] tracking-wider">{{ __('Name') }}</p>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ $sourcingRequest->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] tracking-wider">{{ __('Email') }}</p>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ $sourcingRequest->email }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-[var(--hs-neutral-500)] tracking-wider">{{ __('Submitted') }}</p>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ $sourcingRequest->created_at?->format('M d, Y \a\t g:i A') ?? '—' }}</p>
                    </div>
                    @if ($sourcingRequest->user)
                        <div>
                            <p class="text-xs font-medium text-[var(--hs-neutral-500)] tracking-wider">{{ __('Linked Account') }}</p>
                            <p class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ $sourcingRequest->user->first_name }} {{ $sourcingRequest->user->last_name }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar: Status Management --}}
        <div class="space-y-6 xl:sticky xl:top-6">
            <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] p-5">
                <h3 class="text-sm font-semibold text-[var(--hs-neutral-900)] mb-4">{{ __('Status') }}</h3>
                <div class="mb-4">
                    <flux:badge :color="match ($sourcingRequest->status) {
                        'completed' => 'green',
                        'in_progress' => 'yellow',
                        default => 'gray',
                    }">{{ str($sourcingRequest->status)->headline() }}</flux:badge>
                </div>

                <form wire:submit="save" class="space-y-4">
                    <flux:select wire:model="status" label="{{ __('Update Status') }}">
                        <flux:select.option value="pending">{{ __('Pending') }}</flux:select.option>
                        <flux:select.option value="in_progress">{{ __('In Progress') }}</flux:select.option>
                        <flux:select.option value="completed">{{ __('Completed') }}</flux:select.option>
                    </flux:select>

                    <flux:textarea wire:model="notes" label="{{ __('Internal Notes') }}" rows="4" placeholder="{{ __('Add private notes...') }}" />

                    <div class="pt-2">
                        <flux:button type="submit" variant="primary" class="w-full">{{ __('Save Changes') }}</flux:button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('livewire.admin.document-viewer')
</div>
