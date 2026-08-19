<div class="space-y-6">
    <div>
        <flux:heading size="lg">{{ __('Halal Certification') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Add your halal certification details. You may add multiple certificates.') }}</flux:text>
    </div>

    @forelse ($certificates as $index => $certificate)
        <flux:card wire:key="certificate-{{ $certificate['id'] ?? 'new-' . $index }}" class="relative space-y-4 p-4">
            <div class="flex items-center justify-between">
                <flux:heading size="sm">{{ __('Certificate') }} #{{ $index + 1 }}</flux:heading>
                <flux:button wire:click="removeCertificate({{ $index }})" variant="ghost" size="sm" icon="trash" class="text-red-500 hover:text-red-700">
                    {{ __('Remove') }}
                </flux:button>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:select wire:model.blur="certificates.{{ $index }}.certification_body" :label="__('Certification Body')" placeholder="Select body...">
                    <flux:select.option value="IDCP">{{ __('IDCP') }}</flux:select.option>
                    <flux:select.option value="HDIP">{{ __('HDIP') }}</flux:select.option>
                    <flux:select.option value="PRIME_CERTIFICATION">{{ __('Prime Certification') }}</flux:select.option>
                    <flux:select.option value="OTHER_NCMF">{{ __('Other (NCMF)') }}</flux:select.option>
                </flux:select>

                <flux:input
                    wire:model.blur="certificates.{{ $index }}.registration_number"
                    :label="__('Registration Number')"
                    type="text"
                />
            </div>

            @if (($certificates[$index]['certification_body'] ?? '') === 'OTHER_NCMF')
                <flux:input
                    wire:model.blur="certificates.{{ $index }}.certification_body_other"
                    :label="__('Other Certification Body Name')"
                    type="text"
                />
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input
                    wire:model.blur="certificates.{{ $index }}.expiry_date"
                    :label="__('Expiry Date')"
                    type="date"
                />

                <div>
                    <flux:input
                        wire:model="certificateFiles.{{ $index }}"
                        :label="__('Certificate File')"
                        type="file"
                    />
                    @if (!empty($certificate['media_id']))
                        <flux:text class="mt-1 text-green-600">{{ __('File already uploaded') }}</flux:text>
                    @endif
                </div>
            </div>
        </flux:card>
    @empty
        <div class="rounded-lg border border-dashed border-[var(--hs-neutral-300)] p-8 text-center">
            <flux:icon name="document-text" class="mx-auto size-10 text-[var(--hs-neutral-400)]" />
            <flux:text class="mt-2">{{ __('No certificates added yet.') }}</flux:text>
        </div>
    @endforelse

    <flux:button wire:click="addCertificate" variant="subtle" icon="plus">
        {{ __('Add Certificate') }}
    </flux:button>
</div>
