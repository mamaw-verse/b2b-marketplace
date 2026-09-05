@props(['status'])
@php
$map = [
    'active' => 'verified',
    'expiring_soon' => 'warning',
    'expired' => 'danger',
];
$labels = [
    'active' => __('Certified'),
    'expiring_soon' => __('Expiring Soon'),
    'expired' => __('Expired'),
];
@endphp
<x-hs-badge :variant="$map[$status] ?? 'neutral'" :label="$labels[$status] ?? ucfirst($status)" {{ $attributes }} />
