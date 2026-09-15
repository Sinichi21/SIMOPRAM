---
paths:
  - 'app/{Services,Models,Http}/**'
---

# Services Models Http

## Judge result columns and immutable assessment report archives
Use ActivityJudgeService rankings/resultJudges for public and admin judge columns: finalized non-revoked weighted totals only, pending judges show blank, average and shared ranks preserve existing rules. ActivityAssessmentReport snapshots global (school_id null) and school jury exports separately from school-only ReportVerification; each issue stores original PDF, SHA-256, issued_at and QR code. Never regenerate an archive on read or imply manual signatures are digitally approved. Authorize exports/archive reads for the exact activity or active matching tenant; QR exposes only document metadata and verifies stored bytes.
