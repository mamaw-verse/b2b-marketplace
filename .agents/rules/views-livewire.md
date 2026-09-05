---
paths:
  - 'resources/views/livewire/**/order-detail*'
  - 'resources/views/livewire/**'
---

# Views Livewire

## Order Detail Page layout structure
One outer white/bordered/shadowed card at the page level — nothing inside repeats that treatment.
Inside: two-column grid (`grid-cols-[1fr_300px]`), main content (flexible) + sidebar (300px fixed).
Main column: flat sections separated by hairline dividers (`border-b border-[var(--hs-neutral-100)]`), each with a small uppercase label heading (13px, font-650, neutral-500, tracking-[.05em]). No nested card chrome (no background, border, or shadow on sections).
Sidebar: single tinted panel — `bg-[var(--hs-neutral-50)]` + `border border-[var(--hs-neutral-100)]` + rounded. Order Total block on top, Customer & Shipping below, separated by internal `border-t border-[var(--hs-neutral-200)]`. Never two separate white cards.
Active disputes get their own section (danger styling). Resolved dispute notes are muted gray boxes inside the fulfilment section.

## Horizontal timeline/stepper pattern
Horizontal timeline/stepper (e.g. Paid → Shipped → Delivered):
- 28px circles (`w-7 h-7 rounded-full`), flexbox row with connecting bars.
- Completed nodes: `bg-[var(--hs-green-600)] text-white` with checkmark SVG (`size-4`, stroke-width 2.5, path `M4.5 12.75l6 6 9-13.5`).
- Incomplete nodes: `border-2 border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)]` with inner dot (`w-2 h-2 rounded-full bg-[var(--hs-neutral-400)]`).
- Connecting bars: `h-[3px] rounded-full`, green (`bg-[var(--hs-green-600)]`) if the step it leads TO is done, neutral (`bg-[var(--hs-neutral-200)]`) otherwise.
- Below each node: label (13px font-semibold) + date (12px neutral-500).
- Use `--hs-green-600` for completion state, NEVER `--hs-success-600` (doesn't exist in the token system).

## Avoid Flux input/select with label when custom error rendering is needed
flux:input and flux:select with a `label` prop auto-render validation errors via flux:with-field. This causes duplicate errors if you also add manual @error blocks, and the error font doesn't match project tokens. For forms where you need: (a) consistent red ring on ALL field types (including select), (b) error messages in project font style, (c) no grid misalignment from per-cell error height — use plain HTML `<input>` / `<select>` elements styled with the Flux-matching classes: `w-full border block appearance-none text-sm rounded-md py-1.5 h-8 leading-[1.125rem] ps-3 pe-3 bg-white text-zinc-700 placeholder-zinc-400 shadow-xs border-zinc-200 border-b-zinc-300/80 focus:outline-none focus:border-[var(--hs-green-400)] focus:ring-1 focus:ring-[var(--hs-green-400)] transition`. Invalid state: `!border-red-500 !ring-1 !ring-red-500 !shadow-none`. Render errors in a single block below the row using `text-xs text-[var(--hs-danger-500)]`.
