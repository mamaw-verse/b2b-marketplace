<div class="space-y-6">
    <div>
        <flux:heading size="xl">{{ __('Halal Certification') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Tell the marketplace team that you are interested in applying for external halal certification.') }}</flux:text>
    </div>

    @if (session('message'))
        <flux:callout variant="success" icon="check-circle">{{ session('message') }}</flux:callout>
    @endif

    @if ($this->application)
        <flux:card>
            <div class="flex items-start justify-between gap-4">
                <div>
                    <flux:heading size="lg">{{ __('Certification interest sent') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('Sent :date', ['date' => $this->application->created_at?->format('M d, Y')]) }}.</flux:text>
                </div>
                <flux:badge :color="$this->application->status === 'contacted' ? 'green' : 'yellow'">
                    {{ str($this->application->status)->headline() }}
                </flux:badge>
            </div>
            <flux:text class="mt-4">{{ __('The marketplace team will contact you with next steps.') }}</flux:text>
        </flux:card>
    @endif

    @if (! $this->application)
        <flux:card>
            <form wire:submit="submit">
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">{{ __("I'm Interested in Halal Certification") }}</flux:button>
            </form>
        </flux:card>
    @endif
</div>
