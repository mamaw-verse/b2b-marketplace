@props(['amount', 'currency' => 'PHP'])
@php
    // Single source of truth for money display: ₱1,234.00 for PHP, "USD 1,234.00" otherwise.
    $symbol = ['PHP' => '₱'][$currency ?? 'PHP'] ?? (($currency ?? 'PHP').' ');
@endphp
<span {{ $attributes->class('tabular-nums whitespace-nowrap') }}>{{ $symbol }}{{ number_format((float) $amount, 2) }}</span>
