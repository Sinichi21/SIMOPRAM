---
paths:
  - 'app/{Models,Services,Livewire,Providers,Http}/**'
---

# Models Services Livewire Providers Http

## Central activity audit and secret exclusion
ActivityLogger stores append-only global activity_logs with audit/security/system categories, canonical config/activity-log.php modules/actions, actor/school snapshots and generated Request IDs. Only super admins may browse/detail/export; WITA dates are inclusive and PDF export applies every filter with an explicit row cap. Never pass credentials, raw request payloads/headers, provider responses, or complete document bodies; keep the safe-field allowlist conservative. Eloquent model events record changes in the mutation transaction; bulk query writes bypass them, so use per-model writes or explicit safe audit records. Queue payloads propagate only actor/school/request context. No retroactive audit history is fabricated.

## Global public content and explicit assessment result publication
Global activities/announcements and their jury assessments use school_id=null, never a placeholder school; global activity participants use participant_name without Student/ScoutUnit records. Global management is super-admin-only and uses SetGlobalContentContext as persistent Livewire middleware plus explicit whereNull('school_id') queries; do not treat an absent SchoolContext as an implicit global-content filter. Public results require results_published_at and a publicly visible activity in the matching school/global scope; form activation alone is not publication. Show three scored participants initially, full standings on a scoped detail route, and exclude draft/revoked jury scores. Global judging must keep the existing token expiry/finalization and configuration-lock rules.

## Activity-specific global management supersedes super-admin-only activities
Global announcements remain super-admin-only. Global activities use GlobalActivityAccess: active school admins submit private pending requests, owners manage after super-admin approval, and approved delegates manage only the named activity. School admins may delegate within their active schools directly; other recipients require super-admin approval. Recheck access on Livewire actions and explicitly constrain school_id=null; an empty SchoolContext is not a global query scope. This supersedes the activity-management restriction in the earlier global-public-content rule.

## Agenda hierarchy is two levels without inherited participant or delegate access
Activities optionally reference a same-scope root via parent_activity_id. ActivityHierarchyService prevents self-parenting, deeper nesting and moving roots that already have children; linking a global parent requires permission to manage that parent. Retaining an existing link does not require a subagenda delegate to gain parent privileges. Registration, participants, assessments and delegation stay scoped to each activity; no automatic inheritance or aggregation. Public child lists and parent links independently enforce publication and matching school/global scope.
