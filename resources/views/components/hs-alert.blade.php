@props([
    'name',
    'title'      => __('Notice'),
    'message'    => '',
    'variant'    => 'info', // info | success | warning | danger
    'buttonText' => __('OK'),
])

@php
$iconColors = [
    'info'    => 'text-[var(--hs-info-500)] bg-[var(--hs-info-50)]',
    'success' => 'text-[var(--hs-success-500)] bg-[var(--hs-success-50)]',
    'warning' => 'text-[var(--hs-warning-500)] bg-[var(--hs-warning-50)]',
    'danger'  => 'text-[var(--hs-danger-500)] bg-[var(--hs-danger-50)]',
];
$iconClass = $iconColors[$variant] ?? $iconColors['info'];

$icons = [
    'info'    => 'information-circle',
    'success' => 'check-circle',
    'warning' => 'exclamation-triangle',
    'danger'  => 'x-circle',
];
$icon = $icons[$variant] ?? 'information-circle';
@endphp

<flux:modal name="{{ $name }}" class="w-full sm:w-auto md:w-[400px] !p-0">
    <div class="flex flex-col">
        {{-- Body --}}
        <div class="px-4 sm:px-6 py-5">
            <div class="flex items-start gap-4">
                <div class="shrink-0 flex items-center justify-center size-10 rounded-full {{ $iconClass }}">
                    <flux:icon name="{{ $icon }}" variant="mini" class="size-5" />
                </div>
                <div class="min-w-0">
                    <h3 class="text-base font-semibold text-[var(--hs-neutral-900)]">{{ $title }}</h3>
                    @if ($message)
                        <p class="mt-1 text-sm text-[var(--hs-neutral-600)]">{{ $message }}</p>
                    @endif
                    @if ($slot->isNotEmpty())
                        <div class="mt-3">{{ $slot }}</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="shrink-0 border-t border-[var(--hs-neutral-100)] px-4 sm:px-6 py-4">
            <div class="flex justify-end">
                <flux:modal.close>
                    <flux:button variant="primary" class="w-full sm:w-auto">{{ $buttonText }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </div>
</flux:modal>
