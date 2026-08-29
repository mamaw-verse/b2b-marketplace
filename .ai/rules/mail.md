---
paths:
  - 'resources/views/vendor/mail/**'
---

# Mail

## Published mail views must live under resources/views/vendor/mail/
The branded email header (images/brand-logo.png, alt "1Halal Store") lives in resources/views/vendor/mail/html/header.blade.php. Laravel's mail markdown component namespace ONLY resolves overrides at resources/views/vendor/mail/{html,text}/ — NOT resources/views/vendor/{html,text}/. If published to the wrong path, Laravel silently falls back to the framework default header and OnboardingNotificationTest ("mail renders the 1Halal Store brand logo") fails with "false is true". After moving/publishing mail views, run `artisan view:clear` before testing. Verify with: vendor/bin/sail artisan test --filter=OnboardingNotificationTest
