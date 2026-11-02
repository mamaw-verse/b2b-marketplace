@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[var(--hs-neutral-50)] antialiased">
        <div class="bg-muted flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-md flex-col gap-6">
                <a href="{{ route('home') }}" class="flex items-center justify-center" wire:navigate>
                    <img src="/images/brand-logo.png" alt="{{ config('app.name', 'Laravel') }}" class="h-10 w-auto">
                </a>

                <div class="flex flex-col gap-6">
                    <div class="rounded-xl border bg-[var(--hs-neutral-0)] text-[var(--hs-neutral-900)] shadow-xs">
                        <div class="px-5 sm:px-10 py-6 sm:py-8">{{ $slot }}</div>
                    </div>
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        <x-turnstile-gate />

        @livewireScripts
        @fluxScripts
    </body>
</html>
