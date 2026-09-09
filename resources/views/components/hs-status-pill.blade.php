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
    'in_progress' => 'info',
    'resolved' => 'verified',
    'closed' => 'neutral',
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
    'in_progress' => __('In Progress'),
    'resolved' => __('Resolved'),
    'closed' => __('Closed'),
];
@endphp
<x-hs-badge :variant="$map[$status] ?? 'neutral'" :label="$labels[$status] ?? ucfirst(str_replace('_', ' ', $status))" {{ $attributes }} />
