---
paths:
  - 'resources/views/**'
  - 'resources/views/**/*.blade.php'
---

# Views

## Use semantic design tokens for all colors in Blade templates (dark mode)
Never use raw Tailwind color utilities (bg-green-50, text-red-700, border-blue-200, etc.) in Blade templates. Always use the project's CSS variable tokens instead:\n\n- Surfaces: `bg-[var(--hs-neutral-0)]` (card), `bg-[var(--hs-neutral-50)]` (page)\n- Text: `text-[var(--hs-neutral-900)]` (primary), `text-[var(--hs-neutral-600)]` (muted)\n- Borders: `border-[var(--hs-neutral-200)]`\n- Success states: `bg-[var(--hs-success-50)]`, `border-[var(--hs-success-200)]`, `text-[var(--hs-success-700)]`\n- Danger/error states: `bg-[var(--hs-danger-50)]`, `border-[var(--hs-danger-200)]`, `text-[var(--hs-danger-700)]`\n- Warning states: `bg-[var(--hs-warning-50)]`, `border-[var(--hs-warning-200)]`, `text-[var(--hs-warning-700)]`\n- Info states: `bg-[var(--hs-info-50)]`, `border-[var(--hs-info-200)]`, `text-[var(--hs-info-700)]`\n\nException: `text-white` on solid brand-color buttons is fine. Validation error text (`text-red-500`) is acceptable as it reads well on both light/dark backgrounds.\n\nThe .dark class in tokens.css automatically inverts all values. See .kiro/skills/dark-theme-system/SKILL.md for full reference.

## Three acceptable dark-mode exceptions in Blade
These three patterns are intentional and should NOT be converted to hs-neutral tokens:
1. `components/app-logo.blade.php`: `text-white dark:text-black` — logo icon sits on Flux's `bg-accent-content` branded surface, which inverts in dark mode; the dark: class is correct here.
2. `pages/settings/⚡two-factor-setup-modal.blade.php`: `bg-white` on QR code inner div — QR codes require a white background to scan; Alpine.js `filter: invert(1)` handles dark mode correctly for this specific element.
3. `layouts/auth/split.blade.php`: `bg-neutral-900` on the decorative left panel — always intentionally dark, not an adaptive surface.

## Use hs-confirm and hs-alert instead of wire:confirm or native dialogs
Never use `wire:confirm` or native `alert()`/`confirm()` JavaScript calls. Use the project's custom dialog components instead:

**`<x-hs-confirm>`** — For destructive/irreversible actions:
```blade
{{-- Simple (no loop param): wrap trigger in flux:modal.trigger --}}
<flux:modal.trigger name="unique-name">
    <flux:button>Delete</flux:button>
</flux:modal.trigger>

<x-hs-confirm
    name="unique-name"
    title="Delete Item"
    message="Are you sure?"
    variant="danger"
>
    <x-slot name="action">
        <flux:button variant="danger" wire:click="delete" wire:loading.attr="disabled">Delete</flux:button>
    </x-slot>
</x-hs-confirm>
```

For loops (parameterized actions): add a Livewire `$confirmingXxxId` property, a `confirmXxx(int $id)` method that sets the property and calls `\Flux\Flux::modal('name')->show()`, then the action method uses the property.

**`<x-hs-alert>`** — For informational notices:
```blade
<x-hs-alert name="my-alert" title="Done" message="Upload complete." variant="success" />
```

Variants: `primary` | `danger` | `warning` (hs-confirm), `info` | `success` | `warning` | `danger` (hs-alert).
Action slot buttons must use valid Flux button variants: `primary`, `filled`, `danger`, `ghost`, `subtle` — never `variant="warning"` on `<flux:button>`.

## Valid CSS design token variable names — check before using
The design token system (resources/css/tokens.css) does NOT define every shade for every scale. Key gaps:
- `--hs-success-600` DOES NOT EXIST. Available success: 50, 100, 200, 500, 700, 800.
- For "completed/active" green states, use `--hs-green-600` (#158A5A, the primary brand green).
- Neutrals ARE complete: 0, 50, 100, 200, 300, 400, 500, 600, 700, 800, 900.
- Danger: 50, 100, 200, 500, 700, 800 (no 300, 400, 600).
- Warning: 50, 100, 200, 500, 700, 800 (no 300, 400, 600).
- Info: 50, 100, 200, 500, 700, 800 (no 300, 400, 600).
Always verify a token exists in tokens.css before using it in a class like `bg-[var(--hs-*)]`.

## No native <select> or <input type="date"> — use Flux UI components
Native HTML `<select>` and `<input type="date">` render inconsistently across browsers (especially Safari). Always use `<flux:select>` / `<flux:select.option>` for dropdowns and `<flux:date-picker>` for date inputs. Exception: the pattern in views-livewire rules for custom-styled inputs when flux label auto-rendering causes layout issues — but those must still avoid native date pickers.
