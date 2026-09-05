<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        {{-- Card Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">RFQs</h1>
            <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">Manage and assign Request for Quotations.</p>
        </div>

        {{-- Filters --}}
        <div class="px-4 sm:px-6 py-5 bg-[var(--hs-neutral-50)] border-b border-[var(--hs-neutral-100)]">
            <div class="flex flex-col md:flex-row md:items-center gap-4">
                <div class="flex-1">
                    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search by RFQ title, buyer email, or shop name..." />
                </div>
                
                <div class="flex gap-2">
                    <flux:select wire:model.live="statusFilter" class="w-48" placeholder="All Admin Statuses">
                        <flux:select.option value="pending">Pending</flux:select.option>
                        <flux:select.option value="in_progress">In Progress</flux:select.option>
                        <flux:select.option value="resolved">Resolved</flux:select.option>
                        <flux:select.option value="closed">Closed</flux:select.option>
                    </flux:select>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-hidden">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>RFQ / Buyer</flux:table.column>
                    <flux:table.column>Target Shop</flux:table.column>
                    <flux:table.column>Admin Status</flux:table.column>
                    <flux:table.column>Managed By</flux:table.column>
                    <flux:table.column>Date</flux:table.column>
                    <flux:table.column class="text-right">Actions</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($this->rfqs as $recipient)
                        <flux:table.row>
                            <flux:table.cell>
                                <div class="font-medium text-zinc-900 dark:text-white line-clamp-1" title="{{ $recipient->rfq->title }}">{{ $recipient->rfq->title }}</div>
                                <div class="text-xs text-zinc-500">{{ $recipient->rfq->buyer->email ?? 'Unknown Buyer' }}</div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="font-medium text-zinc-900 dark:text-white">{{ $recipient->shop->storefront_name }}</div>
                            </flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $adminStatus = $recipient->admin_status ?? 'pending';
                                    $color = match($adminStatus) {
                                        'pending' => 'yellow',
                                        'in_progress' => 'blue',
                                        'resolved' => 'green',
                                        'closed' => 'zinc',
                                        default => 'zinc',
                                    };
                                @endphp
                                <flux:badge :color="$color">
                                    {{ str_replace('_', ' ', ucfirst($adminStatus)) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($recipient->managedBy)
                                    <span class="text-sm text-zinc-900">{{ $recipient->managedBy->name }}</span>
                                @else
                                    <span class="text-sm text-zinc-500">Unassigned</span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $recipient->created_at->format('M d, Y g:i A') }}</flux:table.cell>
                            <flux:table.cell class="text-right">
                                <flux:button variant="ghost" size="sm" href="{{ route('admin.rfqs.show', $recipient->rfq_id) }}" wire:navigate>
                                    View
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center text-zinc-500 py-8">
                                No RFQs found matching your criteria.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            @if ($this->rfqs->hasPages())
                <div class="px-6 py-4 border-t border-[var(--hs-neutral-100)]">
                    {{ $this->rfqs->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
