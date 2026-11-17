# Updated IT 107 Requirements Audit

This revision applies the latest instructor announcement to the existing School Tuition and Payment Monitoring System. It does not replace the project with a new application.

## Section 2.3 values now reflected in code and documentation

| Requirement | Applied value | Main implementation location |
|---|---:|---|
| Username length | 6–20 characters | `app/routes.php`, `users.username`, username availability endpoint |
| Password minimum | 8 characters | `app/routes.php`, `app/helpers.php`, registration and Change Password forms |
| Name field maximum | 50 characters per field | `app/routes.php`, registration form, users table |
| Forgot Password visibility | After 2 failed logins | `app/routes.php` login page |
| Lockout Tier 1 | 3 failures / 15 seconds | `app/helpers.php`, `app/routes.php` |
| Lockout Tier 2 | 6 total failures / 30 seconds | `app/helpers.php`, `app/routes.php` |
| Lockout Tier 3 | 9 total failures / 60 seconds | `app/helpers.php`, `app/routes.php` |
| OTP expiration | 5 minutes | `app/helpers.php`, `app/routes.php` |
| Idle session timeout | 15 minutes; warning at 14 minutes | `app/helpers.php`, `app/routes.php`, `public/assets/js/app.js` |
| IP blocking | 10 failures / 10 minutes across accounts; 10-minute block | `app/helpers.php`, `scripts/monitor.php` |
| Security questions | Any 3 distinct questions from official 8-question bank | Registration, Forgot Password, SQL schema and migrations |

No D1, D2, MOD, student-number seed calculations, fixed Q7/Q2/Q4 selection, or seed table remain in the documentation.

## Additional corrections included

- Updated SQL field sizes for names, username, address, ZIP code, email, and attempted-username audit storage.
- Added the full official Section 5 question bank and user-selected question flow.
- Forgot Password now loads the account's saved questions; unknown accounts receive a same-shape fallback form and the same generic failure response.
- Added tracking for original OTP sends versus resends so the limit is three resends per 15 minutes, with a 60-second cooldown.
- Updated security event names for registration, account verification, lockout tiers, CAPTCHA failures, CSRF rejection, and reset requests.
- Preserved the exact username typed in login-failure audit records.
- Removed inline admin dashboard JavaScript; the existing external `public/assets/js/app.js` handles the 60-second JSON refresh.
- Added the Change Password strength meter and student-only decryption of the student's own guardian contact field.
- Disabled SMTP debug output to avoid writing SMTP protocol details into application logs.
- Added `config.sample.php` with fake values only.

## Verification completed in this workspace

- PHP syntax check passed for every project PHP source file.
- JavaScript syntax check passed for `public/assets/js/app.js`.
- PHP built-in server returned HTTP 200 for Home, Login, Forgot Password, and Privacy Notice pages.
- Static scans found no inline script blocks, inline style attributes, or activity-log UPDATE/DELETE statements.

## Must still be tested on the student's XAMPP installation

- Import the fresh schema or back up the existing database and apply migration 002 if needed, followed by migration 003.
- Confirm `pdo_mysql` and `gd` are enabled. This workspace did not have either extension, so database-backed Registration, CAPTCHA image rendering, and database migrations could not be executed here.
- Configure the student's local `.env`, encryption key, and SMTP; test actual OTP and security-alert delivery.
- Test every validation, lockout tier, OTP expiry/replay/resend, IP block, password history, session timeout, role restriction, file upload, SQL injection, XSS, and CSRF case using the checklist.
- Create admin/cashier accounts directly in the database.
- Complete the student's real Development Journal and the required 30 meaningful Git commits spread across at least four weeks. Do not fabricate or backdate them.
