<div class="space-y-6">
    <div>
        <flux:heading size="lg">{{ __('Value Chain Position & Capabilities') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Select where you operate in the value chain and your capabilities.') }}</flux:text>
    </div>

    <flux:checkbox.group wire:model="form.value_chain_positions" :label="__('Value Chain Positions')" :description="__('Select all that apply to your business.')">
        <flux:checkbox value="farm" :label="__('Farm / Raw Materials')" />
        <flux:checkbox value="processing" :label="__('Processing')" />
        <flux:checkbox value="manufacturing" :label="__('Manufacturing')" />
        <flux:checkbox value="distribution" :label="__('Distribution')" />
        <flux:checkbox value="retail" :label="__('Retail')" />
        <flux:checkbox value="food_service" :label="__('Food Service')" />
    </flux:checkbox.group>

    <flux:separator />

    <flux:checkbox.group wire:model="form.capabilities" :label="__('Capabilities')" :description="__('Select all capabilities your company offers.')">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <flux:checkbox value="processing" :label="__('Processing')" />
            <flux:checkbox value="contract_processing" :label="__('Contract Processing')" />
            <flux:checkbox value="packaging" :label="__('Packaging')" />
            <flux:checkbox value="contract_manufacturing" :label="__('Contract Manufacturing')" />
            <flux:checkbox value="oem_odm" :label="__('OEM / ODM')" />
            <flux:checkbox value="private_label" :label="__('Private Label')" />
            <flux:checkbox value="cold_storage" :label="__('Cold Storage')" />
            <flux:checkbox value="warehousing" :label="__('Warehousing')" />
            <flux:checkbox value="distribution" :label="__('Distribution')" />
            <flux:checkbox value="wholesale" :label="__('Wholesale')" />
            <flux:checkbox value="importing" :label="__('Importing')" />
            <flux:checkbox value="exporting" :label="__('Exporting')" />
        </div>
    </flux:checkbox.group>
</div>
