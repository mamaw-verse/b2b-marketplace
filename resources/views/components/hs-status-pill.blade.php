@props(['status'])
@php
$map = [
    'pending' => 'gold',
    'requires_revision' => 'warning',
    'approved' => 'verified',
    'active' => 'verified',
    'rejected' => 'danger',
    'published' => 'verified',
    'archived' => 'neutral',
    'clean' => 'verified',
    'infected' => 'danger',
];
$labels = [
    'pending' => __('Pending'),
    'requires_revision' => __('Requires Revision'),
    'approved' => __('Approved'),
    'active' => __('Active'),
    'rejected' => __('Rejected'),
    'published' => __('Published'),
    'archived' => __('Archived'),
    'clean' => __('Clean'),
    'infected' => __('Infected'),
];
@endphp
<x-hs-badge :variant="$map[$status] ?? 'neutral'" :label="$labels[$status] ?? ucfirst(str_replace('_', ' ', $status))" {{ $attributes }} />
