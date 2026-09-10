---
paths:
  - 'tests/**'
---

# Tests

## Email notification tests require SMTP settings
MessagingService.configureMail validates enabled database SMTP settings before notifications are dispatched, including when Notification::fake() is active. Tests exercising successful activation/reset email or delivery exceptions must create an enabled email MessagingSetting with host/port/from_address. Keep notifications faked/mocked; never use real SMTP for these tests.

## Isolated compiled Blade cache on Windows
phpunit.xml boots tests/bootstrap.php, which sets VIEW_COMPILED_PATH to a unique storage/framework/testing-views directory per test process before Laravel boots. Do not share storage/framework/views with web requests or other test processes: concurrent replacement of compiled Blade files can cause Windows rename Access denied (code 5). Test view caches are disposable and ignored by Git.
