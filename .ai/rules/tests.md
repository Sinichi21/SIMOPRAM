---
paths:
  - 'tests/**'
---

# Tests

## Email notification tests require SMTP settings
MessagingService.configureMail validates enabled database SMTP settings before notifications are dispatched, including when Notification::fake() is active. Tests exercising successful activation/reset email or delivery exceptions must create an enabled email MessagingSetting with host/port/from_address. Keep notifications faked/mocked; never use real SMTP for these tests.
