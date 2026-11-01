---
paths:
  - '{routes,app/Http/Controllers/Api,app/Http/Requests,app/Http/Resources}/**'
---

# Http Resources

## Inventory mobile API uses Fortify sessions and shared inventory services
Inventory API v1 is loaded by routes/web.php through inventory-api.php and deliberately retains web session cookies, CSRF protection and existing Fortify login/2FA (no token dependency). Bootstrap at GET /api/v1/session; authenticated clients select only authorized schools and retain the cookie plus X-CSRF-TOKEN for writes. Require active accounts, active school context and matching inventory permissions; resources allowlist fields and never expose private storage paths. Reuse inventory selection, registration, stock, loan and document services for web/mobile parity. Use distinct throttle prefixes for general requests, uploads and BA issuance to avoid sharing incompatible counters.

## Shared Sanctum Bearer authentication supersedes inventory sessions
Inventory, activity and attendance mobile APIs now load through routes/api.php using auth:sanctum plus EnsureApiAccountActive. ResolveApiSchool requires X-School-Id per inventory request, rechecks active membership/system-admin access and clears school/team permission context even on rejection. Android clients reuse POST /api/v1/auth/login tokens across modules and revoke via /api/v1/auth/logout; inventory API writes require neither session cookies nor CSRF. The former /api/v1/session and /api/v1/schools/select endpoints are retired. Web/Fortify sessions retain CSRF protection. Mobile login currently rejects confirmed-2FA accounts rather than bypassing 2FA. This supersedes the earlier inventory session-cookie API rule.
