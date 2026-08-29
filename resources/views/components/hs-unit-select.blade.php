@props([
    'wireModel' => null,
    'value'     => '',
    'label'     => null,
    'size'      => null,   // 'sm' or null (default)
    'layout'    => null,   // 'grid' — render the select cell and the custom-unit
                           //   cell as two sibling grid items (root is
                           //   display:contents) so a parent grid can place them
                           //   in adjacent columns. null stacks the custom input.
    'required'  => false,
])

@php
    $units  = config('units', []);
    $groups = collect($units)->groupBy('group');
    $values = collect($units)->pluck('value')->toArray();
    $isKnown = in_array($value, $values) && $value !== 'other';
    $initSelected = ($value && ! $isKnown && $value !== '') ? 'other' : ($value ?: '');
    $initCustom   = (! $isKnown && $value !== '' && $value !== 'other') ? $value : '';

    $isGrid = $layout === 'grid';

    // Shared control classes — mirror flux:input's default metrics exactly so the
    // select/input boxes line up with sibling flux fields. appearance-none +
    // focus:outline-none removes the browser's square focus outline; the rounded
    // green ring then matches the control radius.
    $controlBase = 'w-full block appearance-none rounded-[var(--hs-radius-sm)] border border-[var(--hs-neutral-300)] bg-[var(--hs-neutral-0)] text-[var(--hs-neutral-900)] shadow-xs transition focus:border-[var(--hs-green-400)] focus:outline-none focus:ring-1 focus:ring-[var(--hs-green-400)]';
    $controlSize = $size === 'sm'
        ? 'h-8 py-1.5 text-xs'
        : 'h-10 py-2 text-base sm:text-sm leading-[1.375rem]';
    // Extra right padding on the select leaves room for the custom chevron.
    $selectClasses = "{$controlBase} {$controlSize} ps-3 pe-9";
    $inputClasses = "{$controlBase} {$controlSize} px-3 placeholder-[var(--hs-neutral-400)]";

    // Label classes match flux's rendered [data-flux-label] (see app.css:
    // grid gap-2 field + !mb-0 !leading-tight label). Using the same grid gap-2
    // wrapper per cell is what keeps the box tops aligned with flux siblings.
    $labelClasses = 'inline-flex items-center text-sm font-medium leading-tight text-[var(--hs-neutral-700)]';
@endphp

<div
    @class(['contents' => $isGrid])
    x-data="{
        selected: '{{ $initSelected }}',
        custom: '{{ $initCustom }}',
        knownValues: @js($values),
        get isOther() { return this.selected === 'other'; },
        sync() {
            const val = this.selected === 'other' ? this.custom : this.selected;
            @if ($wireModel)
                $wire.set('{{ $wireModel }}', val);
            @endif
        },
        hydrateFrom(val) {
            // Sync Alpine state FROM the Livewire value (e.g. when the form is
            // populated for editing after this component has already rendered).
            val = val ?? '';
            const isKnown = this.knownValues.includes(val) && val !== 'other';
            const nextSelected = (val && !isKnown) ? 'other' : (val || '');
            const nextCustom = (!isKnown && val !== '' && val !== 'other') ? val : '';
            if (nextSelected !== this.selected) { this.selected = nextSelected; }
            if (nextCustom !== this.custom) { this.custom = nextCustom; }
        }
    }"
    x-init="
        $watch('selected', () => sync());
        $watch('custom', () => { if (selected === 'other') sync(); });
        @if ($wireModel)
            $watch('$wire.{{ $wireModel }}', (val) => hydrateFrom(val));
            hydrateFrom($wire.get('{{ $wireModel }}'));
        @endif
    "
>
    {{-- ── Select cell ──────────────────────────────────────────────────
         In grid mode this is one grid item, laid out identically to a flux
         field (grid gap-2 → label + control) so its box top matches the
         Price / MOQ inputs on the same row. --}}
    <div @class(['grid gap-2' => $isGrid, 'flex items-center gap-2' => ! $isGrid])>
        @if ($label)
            <label class="{{ $labelClasses }}">
                {{ $label }}
                @if ($required)
                    <span class="ms-0.5 text-[var(--hs-danger-500)]">*</span>
                @endif
            </label>
        @endif

        <div class="relative {{ $size === 'sm' ? 'w-28' : 'w-full' }}">
            <select x-model="selected" class="{{ $selectClasses }}">
                <option value="">Select unit…</option>
                @foreach ($groups as $groupName => $groupUnits)
                    <optgroup label="{{ $groupName }}">
                        @foreach ($groupUnits as $unit)
                            <option value="{{ $unit['value'] }}">{{ $unit['label'] }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            {{-- Custom chevron (native arrow removed by appearance-none) --}}
            <flux:icon
                name="chevron-down"
                variant="mini"
                class="pointer-events-none absolute end-2.5 top-1/2 -translate-y-1/2 text-[var(--hs-neutral-400)]"
            />
        </div>
    </div>

    {{-- ── Custom-unit cell ─────────────────────────────────────────────
         Grid mode: a sibling grid item using the same grid gap-2 field layout,
         with an invisible label spacer so its input aligns with the select.
         Stacked mode: sits directly under the select. --}}
    <div
        x-show="isOther"
        x-cloak
        @class([
            'grid gap-2' => $isGrid,
            'mt-2' => ! $isGrid && ! $size,
            'w-24' => $size === 'sm',
        ])
    >
        @if ($isGrid && $label)
            <span aria-hidden="true" class="{{ $labelClasses }}">&nbsp;</span>
        @endif
        <input
            x-model.debounce.300ms="custom"
            type="text"
            placeholder="Custom unit"
            class="{{ $inputClasses }}"
        />
    </div>
</div>
