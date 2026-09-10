---
paths:
  - 'app/{Services,Jobs,Livewire,Models}/**'
---

# Services Jobs Livewire Models

## Messaging integrations and delivery safety
MessagingSetting stores global provider configuration, editable only by super admins. Its options use encrypted:array and stay hidden; never hydrate stored tokens/passwords into public Livewire state. MessagingService handles Fonnte WhatsApp, linked Telegram recipients, and SMTP; account activation and judge invitations send server-side. Resolve private Telegram usernames only through active verified UserNotificationChannel records in the current SchoolContext; recipients must first start/link the bot. Outbound sends must have bounded timeouts and no blind POST retry. Announcement jobs restore SchoolContext in finally and do not resend messages whose provider outcome may be ambiguous; sent means provider acceptance, not confirmed delivery/read.

## Principal approval applies to the archived PDF and named account
Principal accounts are created by an authorized school admin and use the existing activation flow. Require approval only for explicitly selected principal user IDs rendered as document signatories; never infer identity from a manually typed name. Snapshot required users at archival, preserve the PDF and verification code, and authorize approval against active school membership and the stored signers. Approval records bind the actor and timestamp to the archived file hash; public verification remains pending until all required signers approve.

## School membership, transfer, and student progression
Account is_active controls login; school membership is_active/left_at controls access to one school. School-only exits must not disable the account globally. Student membership activation must use Eloquent SchoolUserMembership::save (it locks the user and enforces one active school); never bypass it with bulk activation updates. Transfers require source request and destination acceptance; preserve original student/coach IDs and history, copying only biodata into destination placement. Promotion creates a new StudentEnrollment per year and closes the old one; graduation preserves a graduated/alumni record and ends membership while retaining login. Do not automatically promote all students merely because the calendar year changes.
