<div class="w-full">
    <div class="w-full {{ $width ?? 'max-w-lg' }} bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] p-6 sm:p-8">
        <flux:heading>{{ $heading ?? '' }}</flux:heading>
        <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>

        <div class="mt-5">
            {{ $slot }}
        </div>
    </div>
</div>
