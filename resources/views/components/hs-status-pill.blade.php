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
    'pending' => 'Pending',
    'requires_revision' => 'Requires Revision',
    'approved' => 'Approved',
    'active' => 'Active',
    'rejected' => 'Rejected',
    'published' => 'Published',
    'archived' => 'Archived',
    'clean' => 'Clean',
    'infected' => 'Infected',
];
@endphp
<x-hs-badge :variant="$map[$status] ?? 'neutral'" :label="$labels[$status] ?? ucfirst(str_replace('_', ' ', $status))" {{ $attributes }} />
