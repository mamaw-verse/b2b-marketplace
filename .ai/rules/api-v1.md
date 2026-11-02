---
paths:
  - 'app/{Models,Policies,Livewire,Http/Controllers/Api/V1}/**.php'
---

# Api V1

## Only active (paid) subscriptions grant marketplace visibility
Trialing subscriptions do not grant access to subscription-guarded features. Guards for publishing products, storefront product/company visibility, and the hub product list must check `Shop::hasActiveSubscription()` (status = 'active') instead of including 'trialing'. This is a business decision: trials are disabled externally and sellers must pay before their products/profile become visible. Subscription lifecycle code may still reference 'trialing' when lapsing previous records on payment.
