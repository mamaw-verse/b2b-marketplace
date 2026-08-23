@props([
    'wireModel' => null,
    'value'     => '',
    'label'     => null,
    'size'      => null,   // 'sm' or null (default)
    'required'  => false,
])

@php
    $units  = config('units', []);
    $groups = collect($units)->groupBy('group');
    $values = collect($units)->pluck('value')->toArray();
    $isKnown = in_array($value, $values) && $value !== 'other';
    $initSelected = ($value && ! $isKnown && $value !== '') ? 'other' : ($value ?: '');
    $initCustom   = (! $isKnown && $value !== '' && $value !== 'other') ? $value : '';
@endphp

<div
    x-data="{
        selected: '{{ $initSelected }}',
        custom: '{{ $initCustom }}',
        get isOther() { return this.selected === 'other'; },
        sync() {
            const val = this.selected === 'other' ? this.custom : this.selected;
            @if ($wireModel)
                $wire.set('{{ $wireModel }}', val);
            @endif
        }
    }"
    x-init="$watch('selected', () => sync()); $watch('custom', () => { if (selected === 'other') sync(); })"
    class="flex items-end gap-2"
>
    <div class="{{ $size === 'sm' ? 'w-28' : 'flex-1' }}">
        @if ($label)
            <label class="block mb-1 text-sm font-medium text-[var(--hs-neutral-700)]">
                {{ $label }}
                @if ($required)
                    <span class="text-[var(--hs-danger-500)]">*</span>
                @endif
            </label>
        @endif
        <select
            x-model="selected"
            class="w-full rounded-[var(--hs-radius-input)] border border-[var(--hs-neutral-300)] bg-[var(--hs-neutral-0)] px-3 py-2 text-sm text-[var(--hs-neutral-900)] shadow-sm focus:border-[var(--hs-green-400)] focus:ring-1 focus:ring-[var(--hs-green-400)] transition {{ $size === 'sm' ? 'py-1.5 text-xs' : '' }}"
        >
            <option value="">Select unit…</option>
            @foreach ($groups as $groupName => $groupUnits)
                <optgroup label="{{ $groupName }}">
                    @foreach ($groupUnits as $unit)
                        <option value="{{ $unit['value'] }}">{{ $unit['label'] }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
    </div>

    <div x-show="isOther" x-cloak class="{{ $size === 'sm' ? 'w-24' : 'flex-1' }}">
        <input
            x-model.debounce.300ms="custom"
            type="text"
            placeholder="Custom unit"
            class="w-full rounded-[var(--hs-radius-input)] border border-[var(--hs-neutral-300)] bg-[var(--hs-neutral-0)] px-3 py-2 text-sm text-[var(--hs-neutral-900)] shadow-sm focus:border-[var(--hs-green-400)] focus:ring-1 focus:ring-[var(--hs-green-400)] transition {{ $size === 'sm' ? 'py-1.5 text-xs' : '' }}"
        />
    </div>
</div>
