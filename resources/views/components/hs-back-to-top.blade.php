{{-- Floating "back to top" button for long pages (mobile audit #11). Appears after the first screens. --}}
<button
    type="button"
    x-data="{ show: false }"
    x-show="show"
    x-cloak
    x-transition.opacity
    @scroll.window.passive="show = window.scrollY > 800"
    @click="window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' })"
    aria-label="{{ __('Back to top') }}"
    class="fixed bottom-4 end-4 z-40 flex size-11 items-center justify-center rounded-full bg-[var(--hs-green-600)] text-white shadow-[var(--hs-shadow-md)] transition hover:bg-[var(--hs-green-700)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--hs-gold-400)] lg:hidden"
>
    <flux:icon name="arrow-up" class="size-5" />
</button>
