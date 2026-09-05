<div>
    <div class="mb-6">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('admin.rfqs.index') }}">RFQs</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $rfq->title }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <div class="flex items-center justify-between mb-8">
        <div>
            <flux:heading size="xl">{{ $rfq->title }}</flux:heading>
            <flux:subheading>Manage RFQ from {{ $rfq->buyer->email ?? 'Unknown Buyer' }}</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Left Column: Buyer & RFQ Info --}}
        <div class="space-y-6 md:col-span-2">
            
            {{-- RFQ Content --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">RFQ Details</flux:heading>
                <div class="space-y-4">
                    <div>
                        <div class="text-sm font-medium text-zinc-500">Target Shop</div>
                        <div class="mt-1 text-sm text-zinc-900 dark:text-white">
                            <a href="{{ route('admin.merchants.show', $recipient->shop_id) }}" class="text-[var(--hs-green-700)] hover:underline" wire:navigate>
                                {{ $recipient->shop->storefront_name }}
                            </a>
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-zinc-500">Description</div>
                        <div class="mt-1 text-sm text-zinc-900 dark:text-white whitespace-pre-wrap">{{ $rfq->description ?: 'No description provided.' }}</div>
                    </div>
                </div>

                @if($rfq->attachments->isNotEmpty())
                    <div class="mt-6">
                        <div class="text-sm font-medium text-zinc-500 mb-2">Attachments</div>
                        <div class="flex flex-col gap-2">
                            @foreach($rfq->attachments as $attachment)
                                <a href="{{ route('media.view', $attachment) }}" target="_blank" class="flex items-center gap-2 p-3 rounded-lg border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] hover:bg-[var(--hs-neutral-100)] transition group">
                                    <svg class="size-5 text-[var(--hs-neutral-400)] group-hover:text-[var(--hs-green-600)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                                    </svg>
                                    <span class="text-sm font-medium text-[var(--hs-neutral-700)]">{{ $attachment->original_filename }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </flux:card>

            {{-- Buyer Snapshot --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Buyer Information</flux:heading>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-6">
                    <div>
                        <dt class="text-sm font-medium text-zinc-500">Business Name</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $rfq->buyer_snapshot['business_name'] ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-zinc-500">Contact Email</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $rfq->buyer_snapshot['contact_email'] ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-zinc-500">Contact Phone</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $rfq->buyer_snapshot['contact_phone'] ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-zinc-500">Location</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">
                            {{ $rfq->buyer_snapshot['shipping_city'] ?? '' }}
                            @if(!empty($rfq->buyer_snapshot['shipping_city']) && !empty($rfq->buyer_snapshot['shipping_country_code'])) , @endif
                            {{ $rfq->buyer_snapshot['shipping_country_code'] ?? '' }}
                        </dd>
                    </div>
                </dl>
            </flux:card>
        </div>

        {{-- Right Column: Admin Management --}}
        <div class="space-y-6">
            <flux:card>
                <form wire:submit="saveAdminDetails" class="space-y-5">
                    <flux:heading size="lg" class="mb-2">Admin Management</flux:heading>
                    
                    <flux:select wire:model="adminStatus" label="Admin Status">
                        <flux:select.option value="pending">Pending</flux:select.option>
                        <flux:select.option value="in_progress">In Progress</flux:select.option>
                        <flux:select.option value="resolved">Resolved</flux:select.option>
                        <flux:select.option value="closed">Closed</flux:select.option>
                    </flux:select>

                    <flux:textarea wire:model="adminNotes" label="Internal Notes" rows="5" placeholder="Add private notes for admins..." />

                    <div class="pt-2">
                        <flux:button type="submit" variant="primary" class="w-full">Save Changes</flux:button>
                    </div>

                    @if($recipient->managedBy)
                        <div class="mt-4 text-xs text-zinc-500 border-t border-[var(--hs-neutral-100)] pt-4">
                            Managed by: <span class="font-medium text-zinc-700">{{ $recipient->managedBy->name }}</span>
                        </div>
                    @endif
                </form>
            </flux:card>
        </div>
    </div>
</div>
