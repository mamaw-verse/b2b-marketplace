@props(['countryCode'])

@php
    $flags = [
        'PH' => '🇵🇭',
        'MY' => '🇲🇾',
        'SA' => '🇸🇦',
        'BN' => '🇧🇳',
        'ID' => '🇮🇩',
        'TR' => '🇹🇷',
    ];
    
    $flag = $flags[strtoupper($countryCode)] ?? '🌐';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center justify-center text-lg']) }} aria-hidden="true">
    {{ $flag }}
</span>
