<div class="space-y-6">
    <div>
        <flux:heading size="lg">{{ __('Operation Capacity') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Tell us about your production capabilities and export readiness.') }}</flux:text>
    </div>

    <flux:textarea
        wire:model.blur="formData.product_categories"
        :label="__('Product Categories')"
        :description="__('Describe the product categories you sell or manufacture.')"
        rows="3"
    />

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <flux:input
            wire:model.blur="formData.fda_lto_number"
            :label="__('FDA LTO Number')"
            type="text"
            :description="__('Food and Drug Administration License to Operate number.')"
        />

        <flux:input
            wire:model.blur="formData.average_production_lead_time"
            :label="__('Average Production Lead Time (days)')"
            type="number"
            min="1"
            :description="__('How many days on average to fulfill an order.')"
        />
    </div>

    <flux:select wire:model.blur="formData.export_readiness_status" :label="__('Export Readiness')" placeholder="Select status...">
        <flux:select.option value="never_exported">{{ __('Never Exported') }}</flux:select.option>
        <flux:select.option value="exporting_regionally">{{ __('Exporting Regionally') }}</flux:select.option>
        <flux:select.option value="exporting_globally">{{ __('Exporting Globally') }}</flux:select.option>
    </flux:select>
</div>
