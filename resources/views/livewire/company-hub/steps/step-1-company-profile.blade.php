<div class="space-y-6">
    <div>
        <flux:heading size="lg">{{ __('Business Profile & Legal Identity') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Provide your registered business details and contact information.') }}</flux:text>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 sm:items-start">
        <flux:input
            wire:model.blur="form.registered_business_name"
            :label="__('Registered Business Name')"
            type="text"
            required
        />

        <flux:select wire:model.blur="form.business_structure" :label="__('Business Structure')" placeholder="{{ __('Select structure...') }}">
            <flux:select.option value="sole_proprietorship">{{ __('Sole Proprietorship') }}</flux:select.option>
            <flux:select.option value="partnership">{{ __('Partnership') }}</flux:select.option>
            <flux:select.option value="corporation">{{ __('Corporation') }}</flux:select.option>
        </flux:select>
    </div>

    <flux:textarea
        wire:model.blur="form.company_description"
        :label="__('Tell us about your business')"
        rows="4"
        required
    />

    <flux:textarea
        wire:model.blur="form.products_description"
        :label="__('Tell us about the products you sell')"
        rows="4"
        required
    />

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 sm:items-start">
        <flux:input
            wire:model.blur="form.dti_sec_number"
            :label="__('DTI/SEC Number (optional)')"
            type="text"
        />

        <flux:input
            wire:model.blur="form.tax_identification_number"
            :label="__('TIN (optional)')"
            type="text"
        />
    </div>

    <flux:separator />

    <flux:heading size="md">{{ __('Contact Information') }}</flux:heading>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 sm:items-start">
        <flux:input
            wire:model.blur="form.phone"
            :label="__('Phone')"
            type="tel"
        />

        <flux:input
            wire:model.blur="form.email"
            :label="__('Email')"
            type="email"
        />
    </div>

    <flux:separator />

    <flux:heading size="md">{{ __('Business Address') }}</flux:heading>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 sm:items-start">
        <flux:input
            wire:model.blur="form.address_1"
            :label="__('Address Line 1')"
            type="text"
        />

        <flux:input
            wire:model.blur="form.address_2"
            :label="__('Address Line 2')"
            type="text"
        />
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3 sm:items-start">
        <flux:input
            wire:model.blur="form.city"
            :label="__('City')"
            type="text"
        />

        <flux:input
            wire:model.blur="form.province"
            :label="__('Province')"
            type="text"
        />

        <flux:input
            wire:model.blur="form.zip_code"
            :label="__('Zip Code')"
            type="text"
        />
    </div>
</div>
