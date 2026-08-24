---
paths:
  - 'app/Livewire/CompanyHub/**'
---

# Company Hub

## Use custom validation messages() for nested array fields
When validating array fields like `items.*.quantity`, Laravel generates raw messages like "The items.0.quantity field is required." which are not user-friendly. Always define a `messages()` method that maps per-index messages: `"items.{$index}.quantity.required" => "Item {n}: Quantity is required."` Loop over `$this->items` to build the messages array dynamically.
