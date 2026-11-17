# Privacy Note — RA 10173

The system collects personal information needed to establish a student account and administer tuition/payment records: ID number, first/middle/family name, name extension, birthdate, calculated age, address, email, username, student number, course/program, year level, semester, guardian contact, tuition assessments, payment details, and uploaded payment proof.

The ID number and student number identify the student in school records. Name, birthdate, and address support identity verification and account administration. Email and username support account access, verification, OTP delivery, and recovery. Course, year level, and semester identify the correct tuition assessment. Guardian contact supports authorized school communication and is encrypted at rest. Payment information and proof support cashier review and financial reconciliation. Security logs, IP address, browser information, OTP metadata, and timestamps support fraud prevention, incident investigation, and accountability.

Passwords, security answers, OTPs, and reset tokens are never stored in plaintext. Guardian contact is encrypted with AES-256-GCM. Access is role-based. Logs and uploaded proofs are stored outside the public web root. Retention and disposal must follow the school-approved retention schedule and applicable law. Data subjects may contact the school/system privacy contact for access, correction, or privacy concerns.

The system records the student's consent and timestamp before account creation. This note is an academic project privacy statement and should be reviewed by the institution's authorized privacy officer before real deployment.
