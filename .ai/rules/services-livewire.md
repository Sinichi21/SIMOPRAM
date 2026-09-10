---
paths:
  - 'app/{Services,Livewire}/**'
---

# Services Livewire

## Attendance detail periods and routine scoring
AttendanceDetail complements the original recap with per-session cells grouped by month/semester, participant-based totals, period/type filters and Excel export. Default type regular excludes all special types; historical students with enrollment in the selected year remain visible. Missing attendance is '?' only for actual participants, '-' for nonparticipants; no synthetic absences for days without sessions. AssessmentService.attendanceScore includes only regular activities. Special events affect semester grades only through explicitly selected assessment factors; jury-only is_special assessments remain excluded.
