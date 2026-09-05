<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        {{-- Card Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">Sellers</h1>
            <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">Manage sellers across the marketplace.</p>
        </div>

        {{-- Filters --}}
        <div class="px-4 sm:px-6 py-5 bg-[var(--hs-neutral-50)] border-b border-[var(--hs-neutral-100)]">
            <div class="flex flex-col md:flex-row md:items-center gap-4">
                <div class="flex-1">
                    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search by name or email..." />
                </div>
                
                <div class="flex gap-2">
                    <flux:select wire:model.live="statusFilter" class="w-40" placeholder="All Statuses">
                        <flux:select.option value="active">Active</flux:select.option>
                        <flux:select.option value="suspended">Suspended</flux:select.option>
                        <flux:select.option value="onboarding">Onboarding</flux:select.option>
                    </flux:select>

                    <flux:select wire:model.live="countryFilter" class="w-40" placeholder="All Countries">
                        <flux:select.option value="PH">Philippines</flux:select.option>
                        <flux:select.option value="MY">Malaysia</flux:select.option>
                        <flux:select.option value="ID">Indonesia</flux:select.option>
                        <flux:select.option value="SA">Saudi Arabia</flux:select.option>
                    </flux:select>
                    
                    <flux:select wire:model.live="subscriptionFilter" class="w-40" placeholder="Any Subscription">
                        <flux:select.option value="trialing">Trialing</flux:select.option>
                        <flux:select.option value="active">Active</flux:select.option>
                        <flux:select.option value="lapsed">Lapsed</flux:select.option>
                        <flux:select.option value="none">No Subscription</flux:select.option>
                    </flux:select>

                    <flux:select wire:model.live="certificationFilter" class="w-44" placeholder="Any Certification">
                        <flux:select.option value="certified">Halal Certified</flux:select.option>
                        <flux:select.option value="not_certified">Not Certified</flux:select.option>
                    </flux:select>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-hidden">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Storefront</flux:table.column>
                    <flux:table.column>Country</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Subscription</flux:table.column>
                    <flux:table.column>Certified</flux:table.column>
                    <flux:table.column>Joined</flux:table.column>
                    <flux:table.column class="text-right">Actions</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($this->merchants as $merchant)
                        <flux:table.row>
                            <flux:table.cell>
                                <div class="font-medium text-zinc-900 dark:text-white">{{ $merchant->storefront_name }}</div>
                                <div class="text-xs text-zinc-500">{{ $merchant->user->email }}</div>
                            </flux:table.cell>
                            <flux:table.cell>{{ $merchant->country_code }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="{{ $merchant->status === 'active' ? 'green' : ($merchant->status === 'suspended' ? 'red' : 'zinc') }}">
                                    {{ ucfirst($merchant->status) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($merchant->latestSubscription)
                                    <flux:badge color="{{ in_array($merchant->latestSubscription->status, ['active', 'trialing']) ? 'green' : 'zinc' }}">
                                        {{ ucfirst($merchant->latestSubscription->status) }}
                                    </flux:badge>
                                @else
                                    <span class="text-xs text-zinc-500">None</span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($merchant->is_halal_certified)
                                    <flux:badge color="green">Yes</flux:badge>
                                @else
                                    <span class="text-xs text-zinc-500">No</span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $merchant->created_at->format('M d, Y') }}</flux:table.cell>
                            <flux:table.cell class="text-right">
                                <flux:button variant="ghost" size="sm" href="{{ route('admin.merchants.show', $merchant) }}" wire:navigate>
                                    View
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7" class="text-center text-zinc-500 py-8">
                                No merchants found matching your filters.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            @if ($this->merchants->hasPages())
                <div class="px-6 py-4 border-t border-[var(--hs-neutral-100)]">
                    {{ $this->merchants->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
