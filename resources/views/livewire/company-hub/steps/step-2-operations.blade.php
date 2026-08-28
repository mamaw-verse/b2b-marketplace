<div class="space-y-6">
    <div>
        <flux:heading size="lg">{{ __('Operation Capacity') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Tell us about your production capabilities and export readiness.') }}</flux:text>
    </div>

    <div>
        <flux:label>{{ __('Product Categories') }}</flux:label>
        <flux:text class="mt-1 mb-3">{{ __('Select the product categories you sell or manufacture.') }}</flux:text>

        @if ($this->categories->isEmpty())
            <p class="text-sm text-[var(--hs-neutral-500)]">{{ __('No categories are available yet.') }}</p>
        @else
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                @foreach ($this->categories as $category)
                    <label
                        wire:key="category-{{ $category->id }}"
                        class="flex items-center gap-2 rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] px-3 py-2 text-sm text-[var(--hs-neutral-800)] cursor-pointer hover:border-[var(--hs-green-400)] transition"
                    >
                        <flux:checkbox wire:model="form.product_categories" value="{{ $category->id }}" />
                        <span>{{ $category->name }}</span>
                    </label>
                @endforeach
            </div>
        @endif

        @error('form.product_categories')
            <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <flux:input
            wire:model.blur="form.fda_lto_number"
            :label="__('FDA LTO Number')"
            type="text"
            :description="__('Food and Drug Administration License to Operate number.')"
        />

        <flux:input
            wire:model.blur="form.average_production_lead_time"
            :label="__('Average Production Lead Time (days)')"
            type="number"
            min="1"
            :description="__('How many days on average to fulfill an order.')"
        />
    </div>

    <flux:select wire:model.blur="form.export_readiness_status" :label="__('Export Readiness')" placeholder="Select status...">
        <flux:select.option value="never_exported">{{ __('Never Exported') }}</flux:select.option>
        <flux:select.option value="exporting_regionally">{{ __('Exporting Regionally') }}</flux:select.option>
        <flux:select.option value="exporting_globally">{{ __('Exporting Globally') }}</flux:select.option>
    </flux:select>
</div>
