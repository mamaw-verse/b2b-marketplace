@props(['variant' => 'neutral', 'icon' => null, 'label'])
@php
$styles = [
    'verified' => 'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]',
    'halal' => 'bg-[var(--hs-green-600)] text-white',
    'gold' => 'bg-[var(--hs-gold-500)]/15 text-[var(--hs-gold-700)] border border-[var(--hs-gold-400)]',
    'tag' => 'bg-[var(--hs-neutral-100)] text-[var(--hs-neutral-700)] border border-[var(--hs-neutral-200)]',
    'warning' => 'bg-amber-50 text-amber-700 border border-amber-200',
    'danger' => 'bg-red-50 text-red-700 border border-red-200',
    'info' => 'bg-blue-50 text-blue-700 border border-blue-200',
    'neutral' => 'bg-[var(--hs-neutral-100)] text-[var(--hs-neutral-600)]',
];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-[var(--hs-radius-pill)] ' . ($styles[$variant] ?? $styles['neutral'])]) }}>
    {{ $label }}
</span>
