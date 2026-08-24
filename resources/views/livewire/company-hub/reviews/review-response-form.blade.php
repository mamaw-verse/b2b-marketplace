<div>
    @if($review->response && !$editing)
        <div class="rounded-md border-l-4 border-blue-300 bg-blue-50 p-3 dark:border-blue-700 dark:bg-blue-900/20">
            <p class="text-xs font-medium text-blue-700 dark:text-blue-300">Your Response</p>
            <p class="mt-1 text-sm text-blue-600 dark:text-blue-400">{{ $review->response->body }}</p>
            <flux:button wire:click="$set('editing', true)" variant="ghost" size="xs" class="mt-2">
                Edit
            </flux:button>
        </div>
    @else
        <div class="space-y-3">
            <flux:textarea wire:model="body" placeholder="Write your response..." rows="3" />
            @error('body')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror
            <div class="flex gap-2">
                <flux:button wire:click="submit" variant="primary" size="sm">
                    {{ $review->response ? 'Update Response' : 'Respond' }}
                </flux:button>
                @if($editing)
                    <flux:button wire:click="$set('editing', false)" variant="ghost" size="sm">
                        Cancel
                    </flux:button>
                @endif
            </div>
        </div>
    @endif
</div>
