# Error Message Mapping

Use these messages consistently in server-side validation and the registration evidence. The latest instructor announcement overrides only the Section 2.3 security values; the other applicable PDF validation rules remain in force.

| Field / action | Validation rule / example | Required message |
|---|---|---|
| First Name | `juan` (first letter lowercase) | First letter of your name must be capital. |
| First Name | `JUAN` (all capital letters) | Do not use all capital letters. |
| First Name | `Ju4n` (contains a number) | Numbers are not allowed in First Name. |
| First Name | `Juan!` (contains a special character) | Special characters are not allowed in First Name. |
| Any name field | Double spaces | Double space is not allowed. |
| Any name field | Three consecutive identical letters, e.g. `Juaaan` | Three consecutive identical letters are not allowed. |
| Each name field | Maximum 50 characters; middle name may be optional | The relevant field must contain 2–50 characters. |
| ID Number | Must follow `xxxx-xxxx` | ID Number must follow the format xxxx-xxxx. |
| ID Number | Duplicate | This ID Number is already registered. |
| Email | Invalid format | Please enter a valid email address. |
| Email | Duplicate | This Email Address is already registered. |
| Username | 6–20 letters or numbers; begins with a letter | Username must be 6–20 letters or numbers and start with a letter. |
| Username | Duplicate | This Username is already taken. |
| Password | Minimum 8 characters; password strength must not be Weak | Password is Weak. Include uppercase, lowercase, number, and special character. |
| Confirm Password | Must match Password | Mismatch Password. |
| Birthdate / Age | Under 18 years old | You must be at least 18 years old to register. |
| Birthdate | Invalid or future date | Birthdate must be a valid date in the past. |
| Purok/Street | 3–50 letters, numbers, and single spaces | Purok/Street must be 3–50 letters, numbers, and single spaces. |
| Barangay / City / Province / Country | 2–50 letters and single spaces; Title Case | The relevant field must contain 2–50 letters and single spaces only; use Title Case. |
| ZIP Code | Exactly four digits | ZIP Code must contain exactly 4 digits. |
| Student Number | Must follow `xxxx-xxxx` | Student Number must follow the format XXXX-XXXX. |
| Student Number | Duplicate | This Student Number is already registered. |
| Guardian Contact | 11 digits, starts with 09 | Guardian Contact Number must contain 11 digits and start with 09. |
| Security Questions | Must select three different questions from the official Section 5 bank | Select three different security questions and provide all three answers. |
| Security Answers | 2–50 letters and spaces only; no double spaces or triple repeated letters | Each security answer must be 2–50 letters with single spaces only. |
| Privacy Consent | Checkbox is not selected | You must agree to the Privacy Notice to register. |
| CAPTCHA | Incorrect answer | CAPTCHA answer is incorrect. Please try again. |
| Login | Wrong username or password | Invalid username or password. |
| OTP | Incorrect code | Incorrect verification code. |
| OTP | Expired, cancelled, or already used | The code is expired or no longer valid. Request a new code. |
| OTP | Three incorrect entries | Three incorrect attempts. This code has been cancelled. Request a new code. |
| OTP Resend | 60-second cooldown | Please wait 60 seconds before requesting another code. |
| OTP Resend | More than three resends in 15 minutes | You have reached the maximum of 3 OTP resends in 15 minutes. Please try again later. |
| Password History | Reuse of current or previous two passwords | You cannot reuse a recent password. |
| Change Password | Password mismatch | Mismatch Password. |
| Change Password | Successful update | Successfully Change Password. |
| Payment Amount | Must be greater than zero | Payment amount must be greater than zero. |
| Payment Proof | File larger than 3 MB | Proof file must be 3MB or smaller. |
| Payment Proof | Unsupported type | Proof must be a JPG, PNG, or PDF file. |
| Admin Dashboard | Non-admin access | Access denied. |

## Notes
- All validation must run on the PHP server. JavaScript validation is only for user convenience.
- CAPTCHA failures are logged as `CAPTCHA_FAILED` but do not increment account failed-login counters or the IP-block failure counter. Failed credential, OTP, and security-answer attempts count toward the 10-failure IP threshold.
- The exact Section 2.3 settings are: username 6–20, password minimum 8, name fields maximum 50, Forgot Password after 2 failed logins, lockout 15/30/60 seconds at 3/6/9 failures, OTP 5 minutes, idle timeout 15 minutes, and IP block at 10 failures within 10 minutes.
