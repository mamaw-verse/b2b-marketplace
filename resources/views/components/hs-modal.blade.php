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
    class="{{ $widthClass }} !p-0"
>
    <div class="flex flex-col max-h-[85vh]">
        {{-- Fixed header --}}
        @if ($title || $subtitle)
            <div class="shrink-0 border-b border-[var(--hs-neutral-100)] px-6 py-4">
                @if ($title)
                    <flux:heading size="lg">{{ $title }}</flux:heading>
                @endif
                @if ($subtitle)
                    <flux:text class="mt-1 text-[var(--hs-neutral-500)]">{{ $subtitle }}</flux:text>
                @endif
            </div>
        @endif

        {{-- Scrollable body --}}
        <div class="flex-1 overflow-y-auto px-6 py-5">
            {{ $slot }}
        </div>

        {{-- Fixed footer --}}
        @if (isset($footer))
            <div class="shrink-0 border-t border-[var(--hs-neutral-100)] px-6 py-4">
                {{ $footer }}
            </div>
        @endif
    </div>
</flux:modal>
