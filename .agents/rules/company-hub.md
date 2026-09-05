---
paths:
  - 'app/Livewire/CompanyHub/**'
  - app/Livewire/CompanyHub/OnboardingWizard.php
---

# Company Hub

## Use custom validation messages() for nested array fields
When validating array fields like `items.*.quantity`, Laravel generates raw messages like "The items.0.quantity field is required." which are not user-friendly. Always define a `messages()` method that maps per-index messages: `"items.{$index}.quantity.required" => "Item {n}: Quantity is required."` Loop over `$this->items` to build the messages array dynamically.

## onboarding_status enum has no 'draft' — use 'pending' as the editable state
The Postgres `onboarding_status` enum only allows: pending, submitted, requires_revision, approved, rejected. There is NO 'draft'. Writing 'draft' throws SQLSTATE[22P02] invalid enum. For re-open/re-apply flows (e.g. reapply() on a rejected onboarding) move the record back to 'pending', which is the initial editable state. The submit() guard should permit '' (new), pending, requires_revision, rejected — never allow resubmit on submitted/approved.
