@props([
    'name',
    'title'       => __('Confirm'),
    'message'     => __('Are you sure you want to proceed?'),
    'variant'     => 'primary', // primary | danger | warning
    'confirmText' => __('Confirm'),
    'cancelText'  => __('Cancel'),
])

@php
$variantClasses = [
    'primary' => 'primary',
    'danger'  => 'danger',
    'warning' => 'warning',
];
$buttonVariant = $variantClasses[$variant] ?? 'primary';

$iconColors = [
    'primary' => 'text-[var(--hs-info-500)] bg-[var(--hs-info-50)]',
    'danger'  => 'text-[var(--hs-danger-500)] bg-[var(--hs-danger-50)]',
    'warning' => 'text-[var(--hs-warning-500)] bg-[var(--hs-warning-50)]',
];
$iconClass = $iconColors[$variant] ?? $iconColors['primary'];

$icons = [
    'primary' => 'information-circle',
    'danger'  => 'exclamation-triangle',
    'warning' => 'exclamation-triangle',
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
                    <p class="mt-1 text-sm text-[var(--hs-neutral-600)]">{{ $message }}</p>
                    @if ($slot->isNotEmpty())
                        <div class="mt-3">{{ $slot }}</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="shrink-0 border-t border-[var(--hs-neutral-100)] px-4 sm:px-6 py-4">
            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" class="w-full sm:w-auto">{{ $cancelText }}</flux:button>
                </flux:modal.close>
                <div x-on:click="$flux.modal('{{ $name }}').close()">
                    {{ $action }}
                </div>
            </div>
        </div>
    </div>
</flux:modal>
