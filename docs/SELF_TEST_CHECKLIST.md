# Section 17 — Security Self-Test Checklist

Mark each item only after personally testing it on the local XAMPP installation. Keep screenshots or screen recordings as evidence where appropriate.

## Registration and validation
- [ ] Username accepts 6–20 characters and rejects values outside that range.
- [ ] Each name field accepts no more than 50 characters.
- [ ] Exactly three different Section 5 questions can be selected; duplicate selections are blocked.
- [ ] Invalid ID format is rejected.
- [ ] Duplicate ID, email, username, and student number are rejected.
- [ ] Name fields reject digits, special characters, double spaces, all caps, incorrect Title Case, and three identical consecutive letters.
- [ ] Middle Name and Name Extension may be left blank.
- [ ] Birthdate in the future and age below 18 are rejected; age preview is read-only.
- [ ] Address and ZIP rules are enforced.
- [ ] Username availability updates while typing.
- [ ] Weak password and mismatched confirmation are rejected.
- [ ] Privacy consent is required and timestamp is saved.
- [ ] CAPTCHA is checked on the server; refresh changes the answer.
- [ ] Filled honeypot is silently refused.

## OTP and authentication
- [ ] Registration requires email OTP before account activation.
- [ ] Login requires password/CAPTCHA and then email OTP; no authenticated session exists before OTP success.
- [ ] Wrong OTP, expired OTP, three failed attempts, resend cooldown, resend maximum, and old-code invalidation are tested.
- [ ] Wrong username and wrong password produce the same message.
- [ ] Forgot Password appears after two consecutive failed logins.
- [ ] Lockout tiers occur at 3/6/9 failures for 15/30/60 seconds.
- [ ] Lockout countdown remains enforced after reload.
- [ ] Register link and login button are disabled while locked.
- [ ] 10 failures from one IP within 10 minutes block that IP for 10 minutes across accounts.

## Password recovery and sessions
- [ ] Forgot Password requires username, CAPTCHA, the three questions registered by the user, and matching re-entered answers.
- [ ] Wrong security answers count toward the same account lockout.
- [ ] Reset OTP is required before Change Password.
- [ ] Direct Change Password URL is denied without reset authorization.
- [ ] Current and last two previous passwords cannot be reused.
- [ ] Password changed alert is attempted and logged.
- [ ] Session ID changes after successful OTP login.
- [ ] Idle warning appears at 14 minutes; server expires the session at 15 minutes.
- [ ] Logout destroys the session; protected page is not available through browser Back.

## Roles and monitoring
- [ ] Student cannot access cashier functions or admin security dashboard.
- [ ] Cashier cannot access admin security dashboard.
- [ ] Non-admin admin-dashboard attempt logs `ADMIN_ACCESS_DENIED`.
- [ ] Admin dashboard open logs `ADMIN_VIEWED_DASHBOARD`.
- [ ] Admin dashboard has no edit/delete/clear-log controls.
- [ ] Admin events JSON endpoint returns no event data to logged-out users.
- [ ] Monitor script runs only from CLI and creates deduplicated flags.
- [ ] Security logs include timestamp, IP, and browser user-agent.

## Common web security checks
- [ ] SQL injection strings do not bypass authentication or modify queries.
- [ ] XSS payloads are displayed as text, not executed.
- [ ] POST without a valid CSRF token is rejected.
- [ ] CAPTCHA cannot be bypassed by disabling JavaScript or editing the page.
- [ ] Secrets and `.env` are not in Git.
- [ ] `config/`, `app/`, `scripts/`, `storage/`, and `database/` are not web-accessible.
- [ ] Payment proof rejects unsupported file types and files over 3 MB.
- [ ] Only verified payments reduce the outstanding balance.

## Additional Security Testing Results

| Security Test | Result | Notes |
|---|---|---|
| Progressive Account Lockout | PASSED | 15s, 30s, and 60s lockout tiers verified |
| IP Blocking | PASSED | 10 failed attempts within 10 minutes; 10-minute block |
| CAPTCHA Validation | PASSED | Tested on Registration, Login, and Forgot Password |
| OTP Expiration | PASSED | Expired OTP rejected after 5 minutes |
| OTP Resend | PASSED | Previous OTP rejected after resend |
| Security Questions | PASSED | Incorrect answers rejected; correct answers accepted |
| Password History | PASSED | Recent password reuse rejected |
| CSRF Protection | PASSED | Invalid CSRF token rejected |
| Honeypot Protection | PASSED | Bot test submission rejected and logged |
| Security Headers | PASSED | X-Frame-Options and X-Content-Type-Options verified |
| Session Cookie | PASSED | HttpOnly and SameSite=Lax verified |
| Session ID Regeneration | PASSED | Session ID changed after re-login |
| Logout Protection | PASSED | Protected dashboard inaccessible after logout |
| Idle Session Timeout | PASSED | 15-minute inactivity timeout verified |
| AES-256-GCM Encryption | PASSED | Guardian contact stored as ciphertext |
| Monitoring Script | PASSED | CLI scan and duplicate flag prevention verified |
| Monitoring Dashboard | PASSED | Saved security flags displayed |

**Note:** Owner-only decryption was verified through code review. A separate cross-account runtime test has not yet been performed. The Secure cookie attribute was not enabled during localhost HTTP testing.
