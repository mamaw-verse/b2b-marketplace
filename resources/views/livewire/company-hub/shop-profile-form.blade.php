<div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">

    {{-- Header --}}
    <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
        <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Shop Profile') }}</h1>
        <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ __('Update your company details shown on the storefront.') }}</p>
    </div>

    <form wire:submit="save" class="p-6 space-y-5">

        <flux:input wire:model="storefront_name" :label="__('Storefront Name')" required />
        @error('storefront_name') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <flux:input wire:model="phone" :label="__('Contact Phone')" />
                @error('phone') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
            </div>
            <div>
                <flux:input wire:model="email" type="email" :label="__('Contact Email')" />
                @error('email') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
            </div>
        </div>

        <flux:input wire:model="address_1" :label="__('Address Line 1')" required />
        @error('address_1') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror

        <flux:input wire:model="address_2" :label="__('Address Line 2')" />
        @error('address_2') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <flux:input wire:model="city" :label="__('City')" required />
                @error('city') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
            </div>
            <div>
                <flux:input wire:model="province" :label="__('Province')" required />
                @error('province') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
            </div>
            <div>
                <flux:input wire:model="zip_code" :label="__('ZIP Code')" required />
                @error('zip_code') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <flux:input wire:model="fda_lto_number" :label="__('FDA LTO Number')" />
                @error('fda_lto_number') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
            </div>
            <div>
                <flux:input wire:model="average_production_lead_time" type="number" min="0" :label="__('Avg. Production Lead Time (days)')" />
                @error('average_production_lead_time') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <flux:select wire:model="export_readiness_status" :label="__('Export Readiness')">
                <flux:select.option value="never_exported">{{ __('Never Exported') }}</flux:select.option>
                <flux:select.option value="exporting_regionally">{{ __('Exporting Regionally') }}</flux:select.option>
                <flux:select.option value="exporting_globally">{{ __('Exporting Globally') }}</flux:select.option>
            </flux:select>
            @error('export_readiness_status') <p class="mt-1 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-end gap-3 pt-2 border-t border-[var(--hs-neutral-100)]">
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                {{ __('Save Changes') }}
            </flux:button>
        </div>
    </form>
</div>
