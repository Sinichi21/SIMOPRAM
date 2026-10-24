# Global Person Identity — Safe Migration Notes

## Scope of this branch

This branch introduces an additive schema foundation only:

- A global `people` table for identity attributes shared by profiles.
- Nullable `person_id` foreign keys on `users`, `students`, and `coaches`.
- Eloquent relationships to navigate those links.

It deliberately does **not** backfill, merge, or delete existing records. Existing profile fields and school scoping remain in place.

## Data safety rules

1. Test only against a local database restored from a VPS backup. Do not point local `.env` at production.
2. Preserve the original SQL dump and file backup unchanged; create a separate working copy for restoration.
3. Do not auto-merge people using name, birth date, or phone alone.
4. Keep school-specific values such as NIS, NISN, NIP/NTA, phone/contact details, address, class, attendance, grades, and school memberships on their existing records until their ownership and semantics are verified.
5. Never remove legacy columns as part of this foundation migration.
6. Before any production rollout, verify a tested database-and-storage restore and take a fresh pre-deployment backup.

## Local validation

After restoring the backup into a dedicated local database and checking `.env`:

```bash
php artisan config:clear
php artisan migrate:status
php artisan migrate
php artisan test --filter=PeopleIdentityFoundationTest
php artisan test
```

Use the project’s normal test database configuration. Do not run these commands against production.

## Next phase — not included here

A separate reviewed backfill command should generate a reconciliation report first. It should link records only when a stable identifier or an explicit human-reviewed decision supports the match, log every decision, and leave ambiguous cases unlinked. A later change can switch registration and profile workflows after those reports are reviewed.
