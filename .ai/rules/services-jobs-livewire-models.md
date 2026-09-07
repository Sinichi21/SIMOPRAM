---
paths:
  - 'app/{Services,Jobs,Livewire,Models}/**'
---

# Services Jobs Livewire Models

## Messaging integrations and delivery safety
MessagingSetting stores global provider configuration, editable only by super admins. Its options use encrypted:array and stay hidden; never hydrate stored tokens/passwords into public Livewire state. MessagingService handles Fonnte WhatsApp, linked Telegram recipients, and SMTP; account activation and judge invitations send server-side. Resolve private Telegram usernames only through active verified UserNotificationChannel records in the current SchoolContext; recipients must first start/link the bot. Outbound sends must have bounded timeouts and no blind POST retry. Announcement jobs restore SchoolContext in finally and do not resend messages whose provider outcome may be ambiguous; sent means provider acceptance, not confirmed delivery/read.
