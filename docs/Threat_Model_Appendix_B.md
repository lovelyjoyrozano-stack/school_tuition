# Appendix B — Threat Model and CIA Mapping

| # | Protection | Threat addressed | Confidentiality | Integrity | Availability |
|---:|---|---|:---:|:---:|:---:|
| 1 | PDO prepared statements | SQL injection and unauthorized query changes | ✓ | ✓ | ✓ |
| 2 | `htmlspecialchars` output escaping | Stored/reflected XSS and session theft | ✓ | ✓ |  |
| 3 | CSRF token on POST forms | Forged account/payment actions |  | ✓ |  |
| 4 | Generic authentication errors | Username enumeration | ✓ |  |  |
| 5 | IP blocking after 10 failures/10 minutes | Repeated credential and OTP guessing | ✓ |  | ✓ |
| 6 | Strict session cookies, ID regeneration, idle timeout, logout | Session fixation and session hijacking | ✓ | ✓ |  |
| 7 | Last-three password history | Password reuse after compromise | ✓ | ✓ |  |
| 8 | Registration honeypot | Automated registration bots |  | ✓ | ✓ |
| 9 | Append-only security activity log | Undetected suspicious activity and repudiation |  | ✓ |  |
| 10 | Generic public errors; private error log | Database/path/stack trace disclosure | ✓ |  |  |
| 11 | Secrets in root `.env`, excluded from Git | Credential and encryption-key exposure | ✓ | ✓ |  |
| 12 | X-Frame-Options DENY and nosniff | Clickjacking and MIME sniffing | ✓ | ✓ |  |
| 13 | AES-256-GCM encrypted guardian contact | Database theft exposing sensitive contact data | ✓ |  |  |
