# School Tuition and Payment Monitoring System

## Important
This package is an implementation build based on the provided student handout and the existing SQL schema. It has not been tested against your local XAMPP database or SMTP account in this environment. Run the setup and test checklist below before presenting it as complete.

## 1. Files and security
- Keep `.env` in the project root; never place it in `public/` and never commit it.
- `config/`, `app/`, `database/`, `scripts/`, and `storage/` are outside the public web root.
- Only `public/index.php` is an executable public PHP entry point.
- Do not upload `.env` to GitHub or share it in screenshots.

## 2. Install
1. Back up your current `school_tuition_db` first. Keep a separate copy of your existing local `.env`; this package deliberately excludes it. If the uploaded `.env` contained real credentials, rotate those credentials before using the revised project.
2. Copy this folder to `C:\xampp\htdocs\school_tuition`.
3. For a new database, import `database/school_tuition_complete_schema.sql` into phpMyAdmin. This includes the original tables and the security extensions.
4. If you already imported `school_tuition_payment.sql`, back up first. Run `database/migrations/002_security_requirements.sql` only if it has not already been applied, then run `database/migrations/003_updated_instructor_settings.sql` once. Do not rerun migrations that have already succeeded. The complete schema also inserts five clearly labeled demonstration security-log records using reserved TEST-NET IP addresses. Do not import `sample_test_data.sql` again unless you intentionally want duplicate demo events.
5. Keep your existing local `.env` and merge in any missing settings from `.env.example`. If it is missing, copy `.env.example` to `.env` and set your DB credentials. `config.sample.php` contains fake sample values only; do not put real secrets there.
6. Generate a 32-byte encryption key using:
   `php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"`
   Paste the output into `APP_ENCRYPTION_KEY` in `.env`.
7. Install PHP dependencies from the project root:
   `composer install`
8. Configure SMTP. For local testing, Mailpit can be used with `SMTP_HOST=127.0.0.1` and `SMTP_PORT=1025`. For Gmail, use `SMTP_HOST=smtp.gmail.com`, `SMTP_PORT=587`, `SMTP_ENCRYPTION=tls`, your Gmail address, and a Google app password; keep the app password only in `.env`.
9. Ensure PHP extensions `pdo_mysql`, `openssl`, `fileinfo`, and `gd` are enabled in XAMPP. GD is required for the CAPTCHA image.
10. In XAMPP Apache configuration, ensure `mod_rewrite` is enabled and the project directory allows `.htaccess` overrides (`AllowOverride All`).
11. Open `http://localhost/school_tuition/`.

## 3. Admin, cashier, and test accounts
Do not create privileged accounts through public registration. Generate a password hash with PHP:
`php -r "echo password_hash('YourStrongPassword!', PASSWORD_DEFAULT), PHP_EOL;"`
Then insert the admin account manually (replace the sample identity, email, username, and hash with your own values):

```sql
INSERT INTO users
(id_number,first_name,family_name,birthdate,street,barangay,municipality_city,province,country,zip_code,email,username,password_hash,role,privacy_consent,privacy_consent_at,email_verified_at,account_status)
VALUES
('9999-0001','System','Admin','1990-01-01','School Road','Poblacion','Cabadbaran City','Agusan Del Norte','Philippines','8605','admin@example.com','admin86','PASTE_PASSWORD_HASH_HERE','admin',1,NOW(),NOW(),'active');
```

Create a cashier in the same way with a different unique ID/email/username and `role='cashier'`. The admin and cashier both use the regular password + CAPTCHA + email OTP login. Register two student test accounts through the public registration form using inboxes you can open, and use different test identity/student numbers. Never use real classmates' personal information without permission.

## 4. Monitoring script
Run from the project root using CLI only:
`php scripts/monitor.php`
Schedule it every 15 minutes using Windows Task Scheduler. Do not move this script under `public/`.

## 5. Latest instructor security settings (Section 2.3)
Use these fixed values; do not calculate D1, D2, MOD, or student-number-based seeds:
- Username: 6–20 characters; starts with a letter and contains letters/numbers.
- Password: minimum 8 characters; retain the applicable password-strength requirement from the handout.
- Each name field: maximum 50 characters.
- Forgot Password link: appears after 2 failed logins.
- Lockout tiers: 15 seconds after 3 failures, 30 seconds after 6 total failures, and 60 seconds after 9 total failures.
- OTP expiry: 5 minutes; 3 incorrect entries cancel the code.
- OTP resend: 60-second cooldown; maximum 3 resends in 15 minutes.
- Idle session timeout: 15 minutes, with a warning one minute before expiry.
- IP block: 10 failures within 10 minutes across any account; block for 10 minutes.
- Authentication questions: user selects any 3 different questions from the official 8-question Section 5 bank.
- Do not include a seed table in the project documentation.

For an existing database, back it up and run `database/migrations/003_updated_instructor_settings.sql` once after migration 002. The code and SQL seed all eight official questions; existing registered users retain their previously saved question IDs and answers.

## 6. Test before demonstration
- Registration: invalid ID, duplicate ID/email/username, name case/length, age below 18, invalid ZIP, weak password, mismatched password, unchecked consent, wrong CAPTCHA, filled honeypot.
- OTP: wrong, expired, 3 failed attempts, resend cooldown, resend maximum, new code invalidates old code.
- Login: wrong username and wrong password return the same message; lockout tiers; IP block; successful login requires OTP.
- Session: direct protected URL, 15-minute idle timeout, logout, browser back button.
- Recovery: invalid security answers, reset OTP, direct `change_password` URL, recent password reuse.
- Roles: student cannot open cashier/admin pages; cashier cannot open admin dashboard; admin dashboard is read-only.
- SQL injection, XSS, CSRF, uploaded file validation, no secrets in Git, and CLI-only monitor script.

## 7. Known integration items to verify
- The original schema uses a particular MySQL version and must be backed up before migration.
- SMTP delivery depends on your own SMTP settings and accessible inbox.
- Create admin/cashier accounts manually; no public admin registration is provided.
- Verify your PHP/MySQL version supports the SQL CHECK constraints and JSON column.
