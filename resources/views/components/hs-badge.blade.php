@props(['variant' => 'neutral', 'icon' => null, 'label'])
@php
$styles = [
    'verified' => 'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]',
    'halal' => 'bg-[var(--hs-green-600)] text-white',
    'gold' => 'bg-[var(--hs-gold-500)]/15 text-[var(--hs-gold-700)] border border-[var(--hs-gold-400)]',
    'tag' => 'bg-[var(--hs-neutral-100)] text-[var(--hs-neutral-700)] border border-[var(--hs-neutral-200)]',
    'warning' => 'bg-[var(--hs-warning-50)] text-[var(--hs-warning-700)] border border-[var(--hs-warning-200)]',
    'danger' => 'bg-[var(--hs-danger-50)] text-[var(--hs-danger-700)] border border-[var(--hs-danger-200)]',
    'info' => 'bg-[var(--hs-info-50)] text-[var(--hs-info-700)] border border-[var(--hs-info-200)]',
    'neutral' => 'bg-[var(--hs-neutral-100)] text-[var(--hs-neutral-600)]',
];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-[var(--hs-radius-pill)] ' . ($styles[$variant] ?? $styles['neutral'])]) }}>
    {{ $label }}
</span>
