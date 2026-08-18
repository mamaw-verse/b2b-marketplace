@props(['status'])
@php
$map = [
    'active' => 'verified',
    'expiring_soon' => 'warning',
    'expired' => 'danger',
];
$labels = [
    'active' => 'Certified',
    'expiring_soon' => 'Expiring Soon',
    'expired' => 'Expired',
];
@endphp
<x-hs-badge :variant="$map[$status] ?? 'neutral'" :label="$labels[$status] ?? ucfirst($status)" {{ $attributes }} />
