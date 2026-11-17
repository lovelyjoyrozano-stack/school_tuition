# School Tuition and Payment Monitoring System

A PHP/MySQL school system with student registration, email OTP, role-based tuition/payment workflows, cashier verification, and an administrator security monitoring dashboard.

**Read `docs/SETUP.md` before running the application.** For a fresh database, import `database/school_tuition_complete_schema.sql`. For an existing database created from the original schema, back it up, apply `database/migrations/002_security_requirements.sql` if it has not already been applied, then apply `database/migrations/003_updated_instructor_settings.sql` once. The project ZIP intentionally excludes `.env` and `vendor/`.

## Main routes
- `/` — Home
- `/?page=register` — Student registration
- `/?page=login` — Password + CAPTCHA + email OTP login
- `/?page=forgot` — Security-question and OTP password recovery
- `/?page=dashboard` — Role-based dashboard
- `/?page=admin_dashboard` — Admin-only security dashboard

## Folder layout
- `config/` — environment and database configuration (private)
- `app/` — PHP helpers and route/controller logic (private)
- `public/` — only public entry point and static assets
- `database/` — original schema and migration
- `scripts/` — CLI monitoring script
- `storage/` — private logs and uploaded payment proofs
- `docs/` — proposal, security, privacy, test, and setup documentation

## Important
This is a generated implementation package and still requires local database migration, SMTP configuration, test accounts, and the test checklist in `docs/SETUP.md`. Do not submit it as tested until you have performed those steps yourself.
