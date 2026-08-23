@props([
    'name'     => null,
    'model'    => null,
    'title'    => null,
    'subtitle' => null,
    'size'     => 'md',   // sm | md | lg | xl | 2xl
])

@php
$widths = [
    'sm'  => 'md:w-80',
    'md'  => 'md:w-[480px]',
    'lg'  => 'md:w-[640px]',
    'xl'  => 'md:w-[768px]',
    '2xl' => 'md:w-[960px]',
];
$widthClass = $widths[$size] ?? $widths['md'];
@endphp

<flux:modal
    name="{{ $name }}"
    wire:model="{{ $model }}"
    class="{{ $widthClass }}"
>
    <div class="space-y-5">
        @if ($title || $subtitle)
            <div class="border-b border-[var(--hs-neutral-100)] pb-4">
                @if ($title)
                    <flux:heading size="lg">{{ $title }}</flux:heading>
                @endif
                @if ($subtitle)
                    <flux:text class="mt-1 text-[var(--hs-neutral-500)]">{{ $subtitle }}</flux:text>
                @endif
            </div>
        @endif

        {{ $slot }}
    </div>
</flux:modal>