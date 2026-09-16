{{-- Shared settings links — extracted for DRY reuse between admin and user portals.
     Settings is a single page (profile + appearance + security sections);
     the old security/appearance routes redirect here. --}}
@php
    $onSettingsPage = request()->routeIs('profile.edit', 'security.edit', 'appearance.edit');
@endphp
<a href="{{ route('profile.edit') }}" @class(['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border border-transparent', 'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => $onSettingsPage, 'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !$onSettingsPage]) wire:navigate>
    <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
    {{ __('Profile') }}
</a>
@if ($onSettingsPage)
    <nav class="ml-5 mt-1 space-y-0.5 border-l border-[var(--hs-neutral-200)] pl-3" aria-label="{{ __('Settings sections') }}">
        <a href="{{ route('profile.edit') }}#profile" class="block rounded-md px-2 py-1.5 text-sm text-[var(--hs-neutral-500)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)] transition">
            {{ __('Profile') }}
        </a>
        <a href="{{ route('profile.edit') }}#appearance" class="block rounded-md px-2 py-1.5 text-sm text-[var(--hs-neutral-500)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)] transition">
            {{ __('Appearance') }}
        </a>
        <a href="{{ route('profile.edit') }}#security" class="block rounded-md px-2 py-1.5 text-sm text-[var(--hs-neutral-500)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)] transition">
            {{ __('Security') }}
        </a>
    </nav>
@endif
