---
paths:
  - 'app/{Models,Services,Livewire,Providers,Http}/**'
---

# Models Services Livewire Providers Http

## Central activity audit and secret exclusion
ActivityLogger stores append-only global activity_logs with audit/security/system categories, canonical config/activity-log.php modules/actions, actor/school snapshots and generated Request IDs. Only super admins may browse/detail/export; WITA dates are inclusive and PDF export applies every filter with an explicit row cap. Never pass credentials, raw request payloads/headers, provider responses, or complete document bodies; keep the safe-field allowlist conservative. Eloquent model events record changes in the mutation transaction; bulk query writes bypass them, so use per-model writes or explicit safe audit records. Queue payloads propagate only actor/school/request context. No retroactive audit history is fabricated.
