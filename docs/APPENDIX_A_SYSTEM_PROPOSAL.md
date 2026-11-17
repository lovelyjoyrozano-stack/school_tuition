# Appendix A — System Proposal

## Proposed System Title
**School Tuition and Payment Monitoring System**

## Problem and Purpose
The proposed system provides a centralized way for students to view tuition assessments, submit payment details, and monitor payment status. Cashiers can create tuition assessments and review submitted payments. An administrator can monitor security events through a read-only dashboard. The system aims to reduce manual tracking errors and protect student and account information.

## Intended Users
- **Student:** creates an account, verifies email, views assessment/balance, submits payment details and proof, and views payment history.
- **Cashier:** creates tuition assessments and verifies or rejects student payment submissions.
- **Administrator:** reviews security summaries, flags, and audit events. The dashboard is read-only.

## Data Stored
Student ID number, name, birthdate, address, email, username, password hash, role, student number, course/program, year level, enrollment semester, encrypted guardian contact, hashed security answers, OTP hashes and expiry metadata, tuition assessment, fee/payment details, uploaded payment proof, and security audit events.

## Why Accounts Need Protection
The system stores identity, education, contact, financial, and authentication information. Unauthorized access could expose student records, allow fraudulent payment updates, or alter tuition balances. Authentication, role restrictions, encryption, logging, and monitoring are therefore required.

## Sensitive Field to Encrypt
Parent/Guardian Contact Number. It is encrypted at rest using AES-256-GCM with a random 12-byte IV and an authentication tag appended to the ciphertext. The key is kept in `.env` outside the public web root and is not committed to Git.

## Custom System Fields and Validation
| Field | Input type | Required? | Rule | Error message |
|---|---|---:|---|---|
| Student Number | Text | Yes | `XXXX-XXXX`, unique | Student Number must follow the format XXXX-XXXX. |
| Course/Program | Dropdown | Yes | Approved program list | Please select a valid course or program. |
| Year Level | Dropdown | Yes | 1st–5th Year | Please select a valid year level. |
| Enrollment Semester | Dropdown | Yes | 1st Semester, 2nd Semester, Summer | Please select an available semester. |
| Guardian Contact | Telephone | Yes | 11 digits, starts 09; AES-256-GCM encrypted | Guardian Contact Number must contain 11 digits and start with 09. |
| Payment Amount | Number | Yes | Greater than zero | Payment amount must be greater than zero. |
| Payment Method | Dropdown | Yes | Cash, Bank Transfer, GCash, Other | Select a valid payment method. |
| Payment Proof | File | Optional | JPG/PNG/PDF, max 3 MB | Proof must be a JPG, PNG, or PDF file. |

## Updated Instructor Settings — Section 2.3
The latest instructor announcement replaces all seed-derived settings. Username length is 6–20 characters, password minimum is 8 characters, each name field is limited to 50 characters, Forgot Password appears after 2 failed logins, lockout tiers are 15/30/60 seconds at 3/6/9 failures, OTP expires in 5 minutes, idle session timeout is 15 minutes, and IP blocking begins at 10 failures within 10 minutes across any account. Each user selects any three different questions from the official Section 5 question bank. No D1, D2, MOD, or student-number seed calculations are used.

## Scope and Limitations
The system records manual payment submissions and cashier decisions. It does not connect directly to a bank, GCash, or other payment gateway. Only payments marked VERIFIED count toward the paid total.

Student Proponent: __________________________

Instructor Approval: _________________________  Date: ______________

## Core Registration Fields and Validation

| Field | Required? | Validation / input | Error message |
|---|---:|---|---|
| ID Number | Yes | Text; `XXXX-XXXX`; unique | ID Number must follow the format XXXX-XXXX. / This ID Number is already registered. |
| First Name | Yes | 2–50 letters; first letter capitalized; no all-caps, numbers, special characters, double spaces, or three repeated letters | First Name must contain 2–50 letters, single spaces only, and no three repeated letters. |
| Middle Name | No | Same rule as First Name when provided | Middle Name must contain 2–50 letters, single spaces only, and no three repeated letters. |
| Family Name | Yes | Same rule as First Name | Family Name must contain 2–50 letters, single spaces only, and no three repeated letters. |
| Name Extension | No | Jr., Sr., or Other; Other accepts I, II, III, IV, IX, X | Name Extension must be Jr., Sr., or Other with I, II, III, IV, IX, or X. |
| Birthdate / Age | Yes | Date picker; age calculated and read-only; minimum age 18; no future date | You must be at least 18 years old to register. |
| Purok/Street | Yes | Text; 3–50 letters/numbers/spaces | Purok/Street must be 3–50 letters, numbers, and single spaces. |
| Barangay, City/Municipality, Province, Country | Yes | Text; 2–50 letters/spaces; Title Case | [Field] must contain 2–50 letters and single spaces only; use Title Case. |
| ZIP Code | Yes | Text; exactly four digits | ZIP Code must contain exactly 4 digits. |
| Email Address | Yes | Email; valid format, lowercase, no spaces, max 100, unique | Enter a valid email address (maximum 100 characters, no spaces). |
| Username | Yes | Text; 6–20 letters/numbers; begins with a letter; unique | Username must be 6–20 letters or numbers and start with a letter. |
| Password | Yes | Password; 8–64 characters; Weak/Medium/Strong meter; reject Weak | Password is Weak. Include uppercase, lowercase, number, and special character. |
| Re-enter Password | Yes | Password; must match | Mismatch Password. |
| Security Questions / Answers | Yes | Any three distinct questions from Section 5; answers 2–50 letters/spaces; hashed | Select three different security questions from the question bank. |
| Privacy Consent | Yes | Unchecked checkbox; must be accepted | You must agree to the Privacy Notice before registering. |
| CAPTCHA | Yes | Five-character server-verified CAPTCHA | CAPTCHA answer is incorrect. Please try again. |
