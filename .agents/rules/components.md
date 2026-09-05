---
paths:
  - 'resources/views/components/**'
  - resources/views/components/hs-confirm.blade.php
  - resources/views/components/hs-alert.blade.php
  - resources/views/components/hs-modal.blade.php
  - 'resources/views/components/hs-confirm*'
  - 'resources/views/components/hs-alert*'
  - resources/views/components/hs-unit-select.blade.php
---

# Components

## Do NOT create wrapper components around Flux UI
Never create hs-* wrapper components that wrap flux:button, flux:input, flux:select, etc. Blaze (livewire/blaze) precompiles anonymous components and breaks on @php match() blocks or $attributes->merge() with runtime logic inside wrappers. Theme enforcement is done via CSS overrides on [data-flux-*] attributes in app.css (see the table, button, and input override blocks). The existing hs-* components (hs-badge, hs-avatar, hs-modal, hs-product-card, etc.) are custom UI pieces — not Flux wrappers.

## hs-confirm component API reference
hs-confirm is a confirmation dialog built on flux:modal. Props: `name` (required, unique modal name), `title`, `message`, `variant` (primary|danger|warning — controls icon/color), `confirmText`, `cancelText`. Required slot: `action` — the confirm button. Cancel is built-in via flux:modal.close. The component does NOT render a `<flux:button variant="warning">` — action slot buttons must use valid Flux variants (primary, filled, danger, ghost, subtle).

## hs-alert component API reference
hs-alert is an informational dialog built on flux:modal. Props: `name` (required, unique modal name), `title`, `message`, `variant` (info|success|warning|danger — controls icon/color), `buttonText` (default "OK"). Open via `<flux:modal.trigger name="...">` or `\Flux\Flux::modal('name')->show()` from Livewire.

## hs-modal component API with fixed header/footer
hs-modal has fixed header/footer with scrollable body. Props: `name`, `model` (wire:model), `title`, `subtitle`, `size` (sm|md|lg|xl|2xl). Optional named slot: `footer` — renders as a sticky bottom bar with top border. The default slot is the scrollable body area. Max height is 85vh.

## hs-confirm action slot must auto-close the modal on click
The action slot in hs-confirm is wrapped in `<div x-on:click="$flux.modal('{{ $name }}').close()">` so that clicking the confirm button closes the dialog immediately. Without this wrapper, the modal stays open after the Livewire action fires. Do not remove this wrapper div — it is intentional UX behavior.

## hs-alert is a MODAL component — never use for inline flash messages
hs-alert is built on flux:modal and requires a `name` prop. It is NOT an inline alert/banner. For inline flash messages or status banners, use plain styled divs with design tokens (e.g., `bg-[var(--hs-success-50)] border border-[var(--hs-success-200)] text-[var(--hs-success-700)]`). Never substitute hs-alert where you need an inline notification.

## hs-unit-select must hydrate FROM the Livewire value, not just on first render
The component syncs Alpine → Livewire via $wire.set(). It ALSO must sync Livewire → Alpine: it has a hydrateFrom(val) method and $watch('$wire.<model>') plus a one-time hydrateFrom($wire.get(...)) in x-init. Without this, editing a record (e.g. variant edit modal) leaves the unit select blank because the component's x-data initialized with an empty value before the form was populated. Do not remove the $wire watcher/hydrate logic. Applies to every consumer (variant-manager, order-builder).

## hs-unit-select must mirror flux field metrics to stay aligned in a grid row
This component hand-rolls its label and controls, so it only lines up with sibling flux:input/flux:select cells if it copies their metrics: label `mb-2 inline-flex items-center text-sm font-medium leading-tight` (flux sets `[data-flux-field]{grid gap-2}` and `[data-flux-label]{!mb-0 !leading-tight}` in app.css) and controls `h-10 py-2 text-base sm:text-sm leading-[1.375rem] shadow-xs`. A plain `block` label with the default line-height sits ~3px lower than its neighbours. Radius already matches globally via `[data-flux-control]{border-radius: var(--hs-radius-sm)}`. Default size stacks the custom-unit input below the select (`space-y-2`); side by side it truncates in a 1/3-width grid column. The `sm` size stays inline (w-28/w-24) for table rows.

## hs-unit-select: focus-ring, chevron, and layout=grid for aligned rows
This is a hand-rolled control (not flux:input), so three things must hold to look right beside flux fields. (1) The native <select> needs `appearance-none focus:outline-none` plus a custom `flux:icon chevron-down` (absolute, end-2.5, pointer-events-none) — without appearance-none the browser draws a square black focus outline that ignores the rounded radius. (2) Metrics mirror flux: label `mb-2 inline-flex items-center text-sm font-medium leading-tight`; controls `h-10 py-2 text-base sm:text-sm leading-[1.375rem] shadow-xs rounded-[var(--hs-radius-sm)]`. (3) `layout="grid"` sets the root to `display:contents` so the select cell and the custom-unit cell become sibling grid items — the parent uses one extra column (e.g. grid-cols-4) so the "Other" custom input lands in its own column instead of stacking under the select and pushing later fields down. In grid mode the custom cell prepends an invisible `&nbsp;` label spacer to keep its input baseline-aligned. Consumers: variant-manager (grid), order-builder (size=sm inline).

## hs-unit-select: match flux field layout exactly + conditional grid column
Alignment fix (supersedes earlier note): each cell must use the SAME layout flux uses — a `grid gap-2` wrapper holding label + control — not a block label with mb-2. flux renders `[data-flux-field]{grid gap-2}` + `[data-flux-label]{!mb-0 !leading-tight}` (app.css); replicating that grid is what makes the select box top line up with sibling flux inputs. Label classes: `inline-flex items-center text-sm font-medium leading-tight`. Controls: `block appearance-none rounded-[var(--hs-radius-sm)] h-10 py-2 text-base sm:text-sm leading-[1.375rem] shadow-xs focus:outline-none focus:ring-1 focus:ring-[var(--hs-green-400)]` — appearance-none removes the native square focus outline AND the dropdown arrow, so add a `flux:icon chevron-down` (absolute end-2.5, pointer-events-none) and `pe-9` on the select. layout="grid" sets root to display:contents so select + custom-unit cells become sibling grid items. The PARENT decides column count reactively: wrap the row in `x-data` exposing `isCustomUnit` (value present AND not in config('units') values) and bind `:class="isCustomUnit ? 'grid-cols-4' : 'grid-cols-3'"` — so the extra column only appears when "Other" is active and MOQ never shifts for standard units. variant-manager modal is size="xl" to fit 4 cols. order-builder uses size=sm inline (unaffected).
