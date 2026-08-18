@props(['steps' => [], 'compact' => false])
@php
$allSteps = ['Farm', 'Processing', 'Manufacturing', 'Distribution', 'Retail', 'Food Service'];
$normalizedActive = array_map(fn($s) => strtolower(str_replace(' ', '_', $s)), $steps);
@endphp
<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-1']) }}>
    @foreach($allSteps as $index => $step)
        @php
            $isActive = in_array(strtolower(str_replace(' ', '_', $step)), $normalizedActive)
                     || in_array(strtolower($step), $normalizedActive)
                     || in_array($step, $steps);
        @endphp
        <span @class([
            'inline-flex items-center font-medium rounded-[var(--hs-radius-pill)]',
            'px-3 py-1.5 text-xs' => !$compact,
            'px-2 py-1 text-[10px]' => $compact,
            'bg-[var(--hs-green-600)] text-white' => $isActive,
            'bg-[var(--hs-neutral-100)] text-[var(--hs-neutral-500)]' => !$isActive,
        ])>
            {{ $step }}
        </span>
        @if(!$loop->last)
            <div @class([
                'bg-[var(--hs-neutral-300)]',
                'w-4 h-px' => !$compact,
                'w-2 h-px' => $compact,
            ])></div>
        @endif
    @endforeach
</div>
