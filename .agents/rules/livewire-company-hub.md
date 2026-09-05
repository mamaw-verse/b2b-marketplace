---
paths:
  - resources/views/livewire/company-hub/rfq-detail.blade.php
  - resources/views/livewire/company-hub/quotation-builder.blade.php
  - 'resources/views/livewire/company-hub/**'
---

# Livewire Company Hub

## Create Quotation button requires in_progress status
The "Create Quotation" button is hidden until the seller clicks "Start Working" (status transitions from `reviewed` → `in_progress`). This applies to all three places the button appears: the actions bar, the empty quotations state, and the revised quotation prompt.

## Quotation builder includes a payout estimate panel
When the running total is > 0, a "Payout Estimate" panel shows below the total, computed server-side using `PayoutSetting::getCommissionFeeRate()` and `PayoutSetting::getTransactionFeeAmount()`. It shows: total, commission deduction, transaction fee deduction, and net estimated payout. Updates live as user changes qty/price (wire:model.live.debounce.300ms).

## Scan-status views auto-refresh with a gated wire:poll
Media scan_status is written asynchronously by ScanUploadedFileJob, so any view showing it must refresh itself — a plain render never updates. Convention: expose a `#[Computed] hasPendingDocuments(): bool` on the component and gate polling on it in the root element: `<div @if ($this->hasPendingDocuments) wire:poll.5s @endif>`. Polling stops once every document is settled ('clean'/'infected'); 'pending' is the only non-terminal status. If the documents come from a `#[Computed]` query, a bare `wire:poll` suffices (computed caches don't survive a request); if they live in an array property, poll a method that reloads them (see OnboardingWizard::refreshDocumentStatuses). Also `unset($this->documents, $this->hasPendingDocuments)` after attach/detach in the same request. Live examples: DocumentManager, steps/step-5-documents.blade.php.
