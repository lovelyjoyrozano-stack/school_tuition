# Design Decisions

1. **Account roles:** Student, cashier, and administrator are separate roles because each has different responsibilities. Public registration always creates a student; privileged accounts are created manually by the database administrator.
2. **Payment gateway:** The project accepts manual payment information and proof instead of integrating a real bank/GCash gateway. This keeps the scope achievable while preserving cashier verification.
3. **Verified payments:** Only payments with status `verified` are included in the paid total. Pending and rejected submissions do not reduce the balance.
4. **Sensitive field:** Guardian contact is encrypted because it is personal contact information. Only the owning student can view the decrypted value on their own dashboard; cashier and admin views do not expose it.
5. **Encryption mode:** AES-256-GCM is used because it provides confidentiality and integrity authentication. Each encryption uses a new random 12-byte IV; the tag is appended to the ciphertext.
6. **OTP storage:** Only password hashes of OTP values are stored. OTPs are six digits, expire after five minutes, allow three incorrect entries, and can be resent after a 60-second cooldown up to three times in 15 minutes.
7. **Email delivery:** PHPMailer over SMTP is used. SMTP credentials stay in `.env`; Mailpit may be used for local demonstration when approved by the instructor.
8. **CAPTCHA:** A server-verified PHP GD CAPTCHA is used instead of relying on client-side button disabling. The session answer is one-time and refreshed for each attempt.
9. **Session duration:** Sessions expire after fifteen minutes of inactivity. A warning is displayed one minute before the limit; the server remains authoritative.
10. **Monitoring script:** A CLI-only PHP script scans recent security events and inserts daily deduplicated flags. It is scheduled every 15 minutes using Windows Task Scheduler.
11. **Admin dashboard:** The dashboard is read-only so that security evidence cannot be altered or deleted through the application.
12. **Public PHP entry point:** Requests are routed through `public/index.php`; application logic, configuration, database scripts, monitoring scripts, logs, and uploaded proofs remain outside the public web root.
13. **JavaScript visibility:** Browser-delivered JavaScript is necessarily viewable by visitors. Sensitive validation, access control, and security decisions are therefore performed again on the server; client-side JavaScript is not treated as secret.
14. **Commit history:** Git commits and development journal entries must reflect actual work performed over the required four-week period. They must not be fabricated or backdated.

15. **Updated instructor settings:** Username length is 6–20 characters, each name field is limited to 50 characters, password minimum is 8 characters and only Weak strength is rejected (Medium and Strong are accepted), lockout tiers are 15/30/60 seconds after 3/6/9 failures, OTP expires after 5 minutes, idle timeout is 15 minutes, and IP blocking begins at 10 failures in 10 minutes. Each user selects any three distinct questions from the official Section 5 bank. No student-number seed calculations are used.
