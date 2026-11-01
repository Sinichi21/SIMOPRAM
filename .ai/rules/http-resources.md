---
paths:
  - '{routes,app/Http/Controllers/Api,app/Http/Requests,app/Http/Resources}/**'
---

# Http Resources

## Inventory mobile API uses Fortify sessions and shared inventory services
Inventory API v1 is loaded by routes/web.php through inventory-api.php and deliberately retains web session cookies, CSRF protection and existing Fortify login/2FA (no token dependency). Bootstrap at GET /api/v1/session; authenticated clients select only authorized schools and retain the cookie plus X-CSRF-TOKEN for writes. Require active accounts, active school context and matching inventory permissions; resources allowlist fields and never expose private storage paths. Reuse inventory selection, registration, stock, loan and document services for web/mobile parity. Use distinct throttle prefixes for general requests, uploads and BA issuance to avoid sharing incompatible counters.
