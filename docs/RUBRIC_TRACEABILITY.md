# IT 107 Final Project Rubric Traceability

Use this page with the exact rubric wording in the official handout. Do not mark an item complete until the feature has been demonstrated and evidence has been saved.

| Rubric category | Points | Main implementation / evidence |
|---|---:|---|
| Functionality | 20 | Student registration and verification, login/OTP, tuition assessments, payment submission/history, cashier review, role-based dashboards; use the self-test checklist and screenshots. |
| Security Implementation | 25 | The 13 required protections, OTP, CAPTCHA, lockout/IP blocking, security alerts, password history, encryption, activity logging, and CLI monitoring; use the threat model and security test evidence. |
| Live Demo and Presentation | 20 | Follow `DEMO_PLAN.md`; prepare test accounts, inbox access, database, and monitor script before presenting. |
| Additional Requirement | 10 | Reserve this portion for the instructor's live additional requirement. Implement only the requirement actually given during the demonstration and document the change. |
| Design / UI | 15 | Responsive green/white interface, forms, status tables, cards, accessible controls, and consistent CSS in `public/assets/css/app.css`. |
| Documentation / Process | 10 | System Proposal, Appendix B Threat Model, Design Decisions, Privacy Note, Error Message Mapping, Appendix C Development Journal, SQL export, tests, and authentic Git history. |

The handout's detailed sub-item labels (F1–F9, S1–S11, D1–D2, A1–A2, G1–G3, P1–P6) should be checked against the original rubric text. Keep the exact wording and attach evidence for each item rather than guessing what a code means.

## Evidence checklist
- [ ] Registration and validation screenshots.
- [ ] Email verification and login OTP evidence.
- [ ] Lockout Tier 1, Tier 2, and Tier 3 evidence.
- [ ] Forgot Password, security-question, reset OTP, and Change Password evidence.
- [ ] CAPTCHA, CSRF, SQL injection, XSS, session, and IP-block test evidence.
- [ ] Admin dashboard, filtered event logs, and security flags.
- [ ] CLI monitor report.
- [ ] Student payment submission and cashier verification evidence.
- [ ] SQL database export.
- [ ] Completed Development Journal with real dates and work performed.
- [ ] At least 30 meaningful Git commits spread across at least four weeks, created honestly and not backdated.
