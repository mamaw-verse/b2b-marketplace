<div class="space-y-6">
    <div>
        <flux:heading size="lg">{{ __('Operation Capacity') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Tell us about your production capabilities and export readiness.') }}</flux:text>
    </div>

    <flux:checkbox.group wire:model="form.product_categories" :label="__('Product Categories')" :description="__('Select the product categories you sell or manufacture.')">
        @if ($this->categoryGroups->isEmpty())
            <p class="text-sm text-[var(--hs-neutral-500)]">{{ __('No categories are available yet.') }}</p>
        @else
            <div class="space-y-4">
                @foreach ($this->categoryGroups as $group)
                    <div wire:key="category-group-{{ $group->id }}">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-[var(--hs-neutral-500)]">{{ $group->name }}</p>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            @foreach ($group->children->isNotEmpty() ? $group->children : collect([$group]) as $category)
                                <flux:checkbox wire:key="category-{{ $category->id }}" value="{{ $category->id }}" :label="$category->name" />
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </flux:checkbox.group>

    @error('form.product_categories')
        <p class="-mt-4 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p>
    @enderror

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

    <flux:select wire:model.blur="form.export_readiness_status" :label="__('Export Readiness')" placeholder="{{ __('Select status...') }}">
        <flux:select.option value="never_exported">{{ __('Never Exported') }}</flux:select.option>
        <flux:select.option value="exporting_regionally">{{ __('Exporting Regionally') }}</flux:select.option>
        <flux:select.option value="exporting_globally">{{ __('Exporting Globally') }}</flux:select.option>
    </flux:select>
</div>
