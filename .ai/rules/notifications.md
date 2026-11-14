---
paths:
  - 'app/Notifications/**'
---

# Notifications

## Use afterCommit() method instead of $afterCommit property in queued notifications
PHP 8.5 throws a fatal error if a notification re-declares `public bool $afterCommit = true` because `Illuminate\Bus\Queueable` already defines that property. Instead, call `$this->afterCommit()` in the constructor: `public function __construct(...) { $this->afterCommit(); }`.
