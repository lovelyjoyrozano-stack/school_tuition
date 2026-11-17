# Implementation and Verification Status

## Included in this package
- Corrected database bootstrap loading and PDO connection setup.
- Public front controller; private PHP logic/config/database/scripts/storage folders.
- Student registration with updated instructor settings, name/address/age checks, duplicate checks, honeypot, CSRF, CAPTCHA, consent timestamp, hashed security answers, and AES-256-GCM guardian-contact encryption.
- Email OTP flow for registration, login, and password recovery; OTP hash, 5-minute expiry, attempt count, resend cooldown and resend limit.
- Password + CAPTCHA + OTP login; generic credential error; 15/30/60-second progressive lockout tiers and 10-failure IP blocking.
- Forgot-password security-question confirmation and OTP; password history checks; password-change alert email.
- Student tuition view/payment submission; cashier assessment creation and payment review; only verified payments count toward balance.
- Read-only admin security dashboard with summary cards, failed-login chart, top IPs, flags, filters, and a protected JSON refresh endpoint.
- CLI-only monitoring script, migration, sample security log records, and documentation templates.

## Must still be completed and verified on the student's own XAMPP setup
- Import the complete schema for a fresh database, or back up an existing database and apply migrations 002 (if not already applied) and 003 in order.
- Restore/configure the student's local `.env`; install PHPMailer using Composer; configure SMTP and test real OTP delivery to accessible inboxes.
- Enable GD in XAMPP and verify CAPTCHA image rendering. The build container used for syntax checks did not have GD or `pdo_mysql`, so CAPTCHA image and live database integration could not be exercised here.
- Create one admin and required test users manually; verify the admin and cashier workflows with real test accounts.
- Run the full Section 17 security test checklist, especially SQL injection, XSS, CSRF, lockout countdown, session idle/logout/back, OTP expiry/replay, role access, and upload tests.
- Field-specific error text is shown for common mapped registration errors; review every individual field against the instructor's exact error-placement checklist.
- The development journal and minimum 30 Git commits across four weeks must document actual work performed by the student. They cannot be generated honestly on the student's behalf.

## Verification performed in this environment
- `php -l` passed for all PHP source files.
- PHP built-in server returned HTTP 200 for Home, Login, Forgot Password, and Privacy Notice GET pages. Registration GET requires a working MySQL connection to load the question bank and could not be tested in this environment.
- No live MySQL database, SMTP server, or GD extension was available for full end-to-end testing. Do not describe the application as fully tested until the student's local tests pass.

## Updated Section 2.3 changes applied in this revision
- Username validation and storage: 6–20 characters.
- Name-field validation and storage: up to 50 characters.
- Password minimum: 8 characters (existing 64-character maximum retained pending confirmation in the updated handout).
- Lockout tiers: 15/30/60 seconds at 3/6/9 failures.
- OTP expiration: 5 minutes.
- Idle timeout: 15 minutes.
- IP block threshold: 10 failures in 10 minutes.
- Security question selection: any three distinct questions from all eight official Section 5 questions.
