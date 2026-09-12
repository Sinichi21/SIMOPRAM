---
paths:
  - 'app/Livewire/SchoolRegistrations/**'
---

# School Registrations

## Global school registration review
School registration requests from the landing page are global and reviewed only by super admins, without requiring an active school. Approve/reject must lock and recheck pending status within a transaction; approval creates an active School and stores school_id, reviewer and time. Check NPSN/slug against soft-deleted schools too. Contact details remain applicant contacts; approval does not create user accounts or send notifications.
