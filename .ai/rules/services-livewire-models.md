---
paths:
  - 'app/{Services,Livewire,Models}/**'
---

# Services Livewire Models

## Manual default document signatories
SchoolDocumentSetting.manual_signatories stores validated name/position/NIP-or-NTA by principal, responsible, coordinator and default_letter slot. Saving manual mode clears that slot's user FK; user mode clears manual data. Resolve defaults through DocumentSignatoryService.configured for reports and new outgoing letters. Manual names never imply a User or approval requirement. Preserve existing archived approval snapshots; mixed documents still require approval from selected account signers under the existing approval policy.

## Master coaches as default signatories
Default document slots may select an active school Coach with no user_id. Store its coach_id in the slot's manual_signatories entry and clear the user FK; configured() resolves current master name/position/NTA without creating an account or approval. Reject inactive/foreign/deleted coaches. If later linked to an account, require reselecting via account mode rather than silently bypassing the account approval policy.

## Grade ranges are selected per scout level and assessment year
GradeRanges manages named tenant configurations, one active per scout_level_id; new configs require a level and start inactive. Resolve grades using the student's active StudentScoutLevel in the assessment academic year; prefer that level's active scale, falling back only to a legacy null-level configuration, never another level. Activation and edits validate contiguous 0–100 ranges at two decimals; active scales participate in configurationSignature. Deletion is soft, recalculation preserves manual_description, and stored grades/snapshots are not rewritten by configuration edits. This supersedes the older single-active GradeRanges editor rule.
