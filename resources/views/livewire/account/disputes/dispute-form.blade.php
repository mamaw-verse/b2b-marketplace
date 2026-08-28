<div>
    <div class="max-w-lg mx-auto bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">Open Dispute — Order #{{ $order->id }}</h1>
        </div>
        <div class="p-6 space-y-4">
            {{-- Advisory banner --}}
            <div class="flex items-start gap-2.5 rounded-[var(--hs-radius-card)] bg-[var(--hs-warning-50)] border border-[var(--hs-warning-200)] p-4">
                <flux:icon name="chat-bubble-left-ellipsis" variant="mini" class="mt-0.5 shrink-0 text-[var(--hs-warning-700)]" />
                <p class="text-sm text-[var(--hs-warning-800)]">
                    <strong class="font-semibold">{{ __('Before you proceed:') }}</strong>
                    {{ __('We recommend chatting with the seller to resolve the issue before initiating a dispute.') }}
                </p>
            </div>

            <flux:select wire:model="type" label="Dispute Type">
                <option value="refund_only">Refund Only</option>
                <option value="return_refund">Return &amp; Refund</option>
            </flux:select>

            <flux:textarea wire:model="reason" label="Reason" rows="5" placeholder="Describe the issue..." />
            @error('reason') <p class="text-sm text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror

            {{-- Photo upload --}}
            <div>
                <label class="block text-sm font-medium text-[var(--hs-neutral-700)] mb-1.5">{{ __('Supporting Photos (optional)') }}</label>
                <p class="text-xs text-[var(--hs-neutral-500)] mb-2">{{ __('Attach photos of the issue. Up to 5 images (JPG, PNG, PDF). Max 10MB each.') }}</p>
                <div class="rounded-[var(--hs-radius-card)] border border-dashed border-[var(--hs-neutral-300)] bg-[var(--hs-neutral-50)] px-4 py-4">
                    <input
                        type="file"
                        wire:model="disputePhotos"
                        multiple
                        accept=".jpg,.jpeg,.png,.gif,.webp,.pdf"
                        class="block w-full text-sm text-[var(--hs-neutral-500)] file:mr-4 file:rounded-[var(--hs-radius-pill)] file:border-0 file:bg-[var(--hs-green-50)] file:px-4 file:py-2 file:text-sm file:font-medium file:text-[var(--hs-green-700)] hover:file:bg-[var(--hs-green-100)]"
                    />
                </div>
                @error('disputePhotos.*') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" href="{{ route('account.orders.show', $order) }}" wire:navigate>Cancel</flux:button>
                <flux:button variant="primary" wire:click="submit" wire:loading.attr="disabled">Submit Dispute</flux:button>
            </div>
        </div>
    </div>
</div>
