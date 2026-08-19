<div class="space-y-6">
    <div>
        <flux:heading size="lg">{{ __('Company Profile & Legal Identity') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Provide your registered business details and contact information.') }}</flux:text>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <flux:input
            wire:model.blur="formData.registered_business_name"
            :label="__('Registered Business Name')"
            type="text"
            required
        />

        <flux:input
            wire:model.blur="formData.storefront_name"
            :label="__('Storefront Name')"
            type="text"
            required
        />
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
        <flux:select wire:model.blur="formData.business_structure" :label="__('Business Structure')" placeholder="Select structure...">
            <flux:select.option value="sole_proprietorship">{{ __('Sole Proprietorship') }}</flux:select.option>
            <flux:select.option value="partnership">{{ __('Partnership') }}</flux:select.option>
            <flux:select.option value="corporation">{{ __('Corporation') }}</flux:select.option>
        </flux:select>

        <flux:input
            wire:model.blur="formData.dti_sec_number"
            :label="__('DTI/SEC Number')"
            type="text"
        />

        <flux:input
            wire:model.blur="formData.tax_identification_number"
            :label="__('TIN')"
            type="text"
        />
    </div>

    <flux:separator />

    <flux:heading size="md">{{ __('Contact Information') }}</flux:heading>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <flux:input
            wire:model.blur="formData.phone"
            :label="__('Phone')"
            type="tel"
        />

        <flux:input
            wire:model.blur="formData.email"
            :label="__('Email')"
            type="email"
        />
    </div>

    <flux:separator />

    <flux:heading size="md">{{ __('Business Address') }}</flux:heading>

    <flux:input
        wire:model.blur="formData.address_1"
        :label="__('Address Line 1')"
        type="text"
    />

    <flux:input
        wire:model.blur="formData.address_2"
        :label="__('Address Line 2')"
        type="text"
    />

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
        <flux:input
            wire:model.blur="formData.city"
            :label="__('City')"
            type="text"
        />

        <flux:input
            wire:model.blur="formData.province"
            :label="__('Province')"
            type="text"
        />

        <flux:input
            wire:model.blur="formData.zip_code"
            :label="__('Zip Code')"
            type="text"
        />
    </div>

    <flux:select wire:model.blur="formData.country_code" :label="__('Country')">
        <flux:select.option value="PH">{{ __('Philippines') }}</flux:select.option>
        <flux:select.option value="MY">{{ __('Malaysia') }}</flux:select.option>
        <flux:select.option value="ID">{{ __('Indonesia') }}</flux:select.option>
        <flux:select.option value="SG">{{ __('Singapore') }}</flux:select.option>
        <flux:select.option value="TH">{{ __('Thailand') }}</flux:select.option>
    </flux:select>
</div>
