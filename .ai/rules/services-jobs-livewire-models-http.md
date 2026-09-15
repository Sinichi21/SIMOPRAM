---
paths:
  - 'app/{Services,Jobs,Livewire,Models,Http}/**'
---

# Services Jobs Livewire Models Http

## Activity entries, participant access and registration privacy
ActivityEntry is one individual/group/team registration with exactly one accompanying coach and at most one reserve outside primary headcount; ActivityRegistration represents each person. Reuse authorized Student/Coach identities without creating duplicate profiles. Keep original form/terms snapshots and registration attachments private; public participant lists show only active validated entries and name/identifier/school/role. Participant access uses a separate activity-only session, SHA-256 token hashes and a revocation version; never expose raw links in admin state or queue payloads. Generate/send after commit, resend rotates access, no blind delivery retries, enforce activity lifetime and active entry on every portal request.

## Global attendance is per registered person and separate from school session attendance
GlobalActivityAttendance marks only active people in active validated entries for the exact activity, using GlobalActivityAccess for every management action. attendance_status null means unmarked, never automatic absence; self check-in sets present and cannot override a manager's sick/excused/absent correction. Parent agendas do not aggregate child attendance. PDF forms omit attendance values for signatures; recaps include them. Global print filters share the attendance roster query; school print uses an explicitly school/activity-scoped AttendanceSession roster. Never print contact destinations, answers or access credentials.
