---
paths:
  - 'app/Services/**'
---

# Services

## Bidirectional student transfer approval
Transfers may be proposed by the source school (destination approves) or, for students identified by exact NISN, the destination school (source approves). Only the requesting school cancels; only the other school accepts/rejects. Destination proposals store destination year/class before approval. When resolving from source context, temporarily scope student placement to destination and always restore SchoolContext in finally. Preserve source history and one active student school; do not expose source student rosters.

## Student transfers do not require login accounts
Student transfers reference student_id and target_student_id; user_id is optional. Source admins select existing local students (including no NISN); destination admins identify students by exact source-school NISN. Never create a User to transfer a student. On approval reread the student's current user_id, preserve original IDs/history, close source enrollments and synchronize memberships only if an account exists. Reject duplicate pending requests by student or linked user, and refuse transferred historical records. Retain compatibility with earlier transfers referencing only user_id.

## Returning students reuse transfer-linked records
Resolve returning students through the connected student_id/target_student_id links of accepted transfers, including multi-school chains. Reuse the destination record even without account or NISN; never merge by name or NISN alone. Reject ambiguous, deleted, or conflicting-account destinations and existing yearly enrollment instead of overwriting history. Attach the current account to an unlinked reused record when applicable.

## Same-year return enrollment exception
For a returning student proven by accepted transfer links, reuse an existing destination enrollment in the same year only if inactive or transferred. Before updating placement, snapshot its ID, school, year, class, status and dates in UserTransfer.previous_target_enrollment. Active/graduated enrollments remain protected. This supersedes the blanket existing-year rejection in the returning-students rule.
