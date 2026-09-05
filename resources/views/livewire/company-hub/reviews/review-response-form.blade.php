<div>
    @if($review->response && !$editing)
        <div class="rounded-md border-l-4 border-[var(--hs-info-500)] bg-[var(--hs-info-50)] p-3">
            <p class="text-xs font-medium text-[var(--hs-info-700)]">{{ __('Your Response') }}</p>
            <p class="mt-1 text-sm text-[var(--hs-info-700)]">{{ $review->response->body }}</p>
            <flux:button wire:click="$set('editing', true)" variant="ghost" size="xs" class="mt-2">
                {{ __('Edit') }}
            </flux:button>
        </div>
    @else
        <div class="space-y-3">
            <flux:textarea wire:model="body" placeholder="{{ __('Write your response...') }}" rows="3" />
            @error('body')
                <p class="text-sm text-red-500">{{ $message }}</p>
            @enderror
            <div class="flex gap-2">
                <flux:button wire:click="submit" variant="primary" size="sm">
                    {{ $review->response ? 'Update Response' : 'Respond' }}
                </flux:button>
                @if($editing)
                    <flux:button wire:click="$set('editing', false)" variant="ghost" size="sm">
                        {{ __('Cancel') }}
                    </flux:button>
                @endif
            </div>
        </div>
    @endif
</div>
