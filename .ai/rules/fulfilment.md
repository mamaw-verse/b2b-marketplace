---
paths:
  - 'app/Livewire/CompanyHub/Fulfilment/**'
---

# Fulfilment

## Fulfilment requires carrier, tracking code, and tracking URL
All three fields (carrier, trackingCode, trackingUrl) are required in both `createFulfilment()` and `markShipped()`. Validation uses custom messages: "Please enter the carrier name.", "Please enter the tracking code.", "Please enter the tracking URL.", "Please enter a valid URL (e.g. https://...)." The trackingUrl rule includes `url` validation.
