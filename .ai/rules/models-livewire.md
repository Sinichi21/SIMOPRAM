---
paths:
  - 'app/{Models,Livewire}/**'
---

# Models Livewire

## Suggested and manual grade descriptions
GradeRanges edits tenant-local active GradeScaleConfig with nonoverlapping contiguous 0–100 ranges at two-decimal precision. GradeScale.description supplies calculated FinalGrade.description. FinalGrade.manual_description is an optional per-student/config override; the description accessor returns it when present so reports and semester snapshots use the effective text. Recalculation must retain this override; reset clears it. Manual edits require scores.manage, tenant scoping, and an open semester.
