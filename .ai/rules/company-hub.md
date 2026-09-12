---
paths:
  - 'app/Livewire/CompanyHub/**'
  - app/Livewire/CompanyHub/OnboardingWizard.php
  - app/Livewire/CompanyHub/SubscriptionPaymentPage.php
---

# Company Hub

## Use custom validation messages() for nested array fields
When validating array fields like `items.*.quantity`, Laravel generates raw messages like "The items.0.quantity field is required." which are not user-friendly. Always define a `messages()` method that maps per-index messages: `"items.{$index}.quantity.required" => "Item {n}: Quantity is required."` Loop over `$this->items` to build the messages array dynamically.

## onboarding_status enum has no 'draft' — use 'pending' as the editable state
The Postgres `onboarding_status` enum only allows: pending, submitted, requires_revision, approved, rejected. There is NO 'draft'. Writing 'draft' throws SQLSTATE[22P02] invalid enum. For re-open/re-apply flows (e.g. reapply() on a rejected onboarding) move the record back to 'pending', which is the initial editable state. The submit() guard should permit '' (new), pending, requires_revision, rejected — never allow resubmit on submitted/approved.

## Subscription payment is gated by config('marketplace.payment_method')
The app supports two mutually exclusive subscription payment flows controlled by MARKETPLACE_PAYMENT_METHOD (.env) → config('marketplace.payment_method'): 'dlt' (default, Philippines) redirects to SubscriptionPaymentPage (DLT hosted checkout); 'manual' redirects to ManualSubscriptionPaymentPage (reference number + receipt upload, admin activates). SubscriptionPage::subscribe() and resumePayment() branch on this flag. Each payment page aborts 403 if the flag doesn't match. PaymentAttempt.payment_method ('dlt'|'manual') discriminates the attempt type; manual attempts have null dlt_merchant_transaction_id and null dlt_payment_status. The admin forceActivate() handles both types — it skips setting dlt_payment_status for manual attempts. Never add another payment method without updating this flag and all three components.
