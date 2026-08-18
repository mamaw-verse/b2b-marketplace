@props(['initials', 'size' => 'md'])
@php
$sizes = [
    'sm' => 'size-8 text-xs',
    'md' => 'size-10 text-sm',
    'lg' => 'size-14 text-lg',
];
@endphp
<div {{ $attributes->merge(['class' => 'inline-flex items-center justify-center rounded-full bg-[var(--hs-green-100)] text-[var(--hs-green-700)] font-bold ' . ($sizes[$size] ?? $sizes['md'])]) }}>
    {{ strtoupper($initials) }}
</div>
