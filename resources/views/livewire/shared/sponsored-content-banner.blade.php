<div>
@if (config('marketplace.features.sponsored_content_enabled') && $content)
    <div class="w-full my-4">
        <a
            href="{{ route('sponsored-content.click', $content) }}"
            target="_blank"
            rel="noopener noreferrer"
            class="group block relative rounded-xl overflow-hidden border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] hover:shadow-[var(--hs-shadow-md)] transition-shadow"
            aria-label="Sponsored: {{ $content->title }}"
        >
            {{-- Creative image --}}
            <img
                src="{{ route('media.view', $content->media) }}"
                alt="{{ $content->title }}"
                class="w-full h-32 sm:h-40 object-cover group-hover:scale-[1.01] transition-transform duration-300"
                loading="lazy"
            />

            {{-- Overlay label --}}
            <div class="absolute top-2 left-2">
                <span class="inline-flex items-center gap-1 rounded-full bg-black/50 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-white backdrop-blur-sm">
                    <flux:icon name="megaphone" class="size-3" />
                    Sponsored
                </span>
            </div>

            {{-- Title bar --}}
            <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/70 to-transparent px-4 py-3">
                <p class="text-sm font-semibold text-white truncate">{{ $content->title }}</p>
            </div>
        </a>
    </div>
@endif
</div>
