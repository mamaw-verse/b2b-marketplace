---
paths:
  - 'app/Livewire/**'
  - 'app/Livewire/**/*.php'
  - 'app/Livewire/**/RfqDetail.php'
---

# Livewire

## External URL redirects in Livewire components
Never use `$this->redirect($externalUrl)` for external domains — it breaks in Firefox with "'open' called on an object that does not implement interface Window". Instead dispatch a browser event and handle with Alpine.js:
- Component: `$this->dispatch('redirect-to-payment', url: $url);`
- Blade: `<div x-data x-on:redirect-to-payment.window="window.location.href = $event.detail.url">`

## Use Livewire-controlled state for modals in nested components, not \Flux\Flux::modal()
`\Flux\Flux::modal('name')->show()` does not work reliably from nested Livewire components (child embedded inside parent). Instead, use a Livewire boolean property (`$showModal = false`) toggled by a method, and bind it with `<flux:modal wire:model="showModal">` or `x-show`. This avoids cross-component Alpine scope issues.

## Full-page Livewire components MUST have #[Layout] attribute
Every full-page Livewire component (one that has its own route) must declare `#[Layout('layouts.app')]` (or the relevant layout) as a class attribute. Without it, Livewire renders the component without the app shell (no nav, no CSS, no scripts). This was a recurring bug in Phase 4. Check for this attribute on every new routed Livewire component.

## Livewire: Computed props for models, Form objects for state
Do NOT expose full Eloquent models as public Livewire properties — they get serialized to the browser snapshot (payload bloat + data leak).

1. Read-only/mutated models: store `#[Locked] int $modelId` + a `#[Computed] model()` method that re-queries with eager loads. Reference as `$this->model()` in PHP; views access it as `$model`. After creating a new record mid-request, set the id then `unset($this->model)` to bust the computed cache.

2. Large form state: extract into a `Livewire\Form` object under app/Livewire/Forms/. Component holds `public XxxForm $form`; Blade binds `wire:model="form.field"` and errors use `@error('form.field')`.

3. GOTCHA: computed properties are NOT auto-injected into `@include`d partials. Pass explicitly: `@include('partial', ['product' => $this->product])`. In-object access in Blade is `$form->field` (PHP), not `$form.field` (Livewire dot syntax).

## rfq_recipient_status enum has no 'awarded' value — awarding lives on quotations
The rfq_recipient_status Postgres enum is only: received, reviewed, in_progress, no_bid, closed. There is NO 'awarded' recipient status — awarding is a quotation status. Never write whereNotIn('status', ['no_bid','awarded']) on rfq_recipients: Postgres casts the literal 'awarded' to the enum and throws SQLSTATE 22P02. To exclude recipients with an awarded quotation, use ->whereDoesntHave('quotations', fn($q)=>$q->where('status','awarded')) instead. 'closed' was added additively via migration 2026_08_28_110000 for the buyer close-RFQ cascade (S4.T9).
