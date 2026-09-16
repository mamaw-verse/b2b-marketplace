{{-- Legal documents sidebar nav. Usage: @include('partials.legal.doc-nav', ['active' => 'terms']) --}}
@props(['active' => 'terms'])

@php
$docs = [
    [
        'key' => 'terms',
        'title' => __('Terms of Service'),
        'desc' => __('Marketplace rules and obligations'),
        'icon' => 'document-text',
        'url' => route('legal.terms'),
    ],
    [
        'key' => 'privacy',
        'title' => __('Privacy Policy'),
        'desc' => __('How we handle your data'),
        'icon' => 'shield-check',
        'url' => route('legal.privacy'),
    ],
    [
        'key' => 'faq',
        'title' => __('FAQs'),
        'desc' => __('Answers about the marketplace'),
        'icon' => 'question-mark-circle',
        'url' => route('help.faq'),
    ],
];
@endphp

<div class="rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-4">
    <h2 class="px-1 text-sm font-semibold uppercase tracking-wide text-[var(--hs-neutral-500)]">{{ __('Documents') }}</h2>
    <ul class="mt-3 space-y-2">
        @foreach ($docs as $doc)
            <li>
                <a
                    href="{{ $doc['url'] }}"
                    wire:navigate
                    @if ($active === $doc['key']) aria-current="page" @endif
                    @class([
                        'flex items-center gap-3 rounded-xl border px-3 py-2.5 no-underline transition',
                        'border-[var(--hs-green-200)] bg-[var(--hs-green-50)]' => $active === $doc['key'],
                        'border-transparent hover:border-[var(--hs-neutral-200)] hover:bg-[var(--hs-neutral-50)]' => $active !== $doc['key'],
                    ])
                >
                    <span @class([
                        'flex size-9 shrink-0 items-center justify-center rounded-lg',
                        'bg-[var(--hs-green-600)] text-white' => $active === $doc['key'],
                        'bg-[var(--hs-neutral-100)] text-[var(--hs-neutral-500)]' => $active !== $doc['key'],
                    ])>
                        <flux:icon :icon="$doc['icon']" class="size-4" />
                    </span>
                    <span class="min-w-0">
                        <span @class([
                            'block truncate text-sm font-semibold',
                            'text-[var(--hs-green-800)]' => $active === $doc['key'],
                            'text-[var(--hs-neutral-800)]' => $active !== $doc['key'],
                        ])>{{ $doc['title'] }}</span>
                        <span class="block truncate text-xs text-[var(--hs-neutral-500)]">{{ $doc['desc'] }}</span>
                    </span>
                </a>
            </li>
        @endforeach
    </ul>
</div>
