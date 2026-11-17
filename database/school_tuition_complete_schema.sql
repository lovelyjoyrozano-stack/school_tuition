-- School Tuition and Payment Monitoring System
-- MySQL 8.0+ | XAMPP/phpMyAdmin compatible
-- Step 1: Database schema only. Do not put encryption keys or passwords in this file.

CREATE DATABASE IF NOT EXISTS school_tuition_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE school_tuition_db;

-- 1. User accounts and authentication
CREATE TABLE IF NOT EXISTS users (
    user_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_number CHAR(9) NOT NULL UNIQUE, -- format XXXX-XXXX
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50) NULL,
    family_name VARCHAR(50) NOT NULL,
    name_extension VARCHAR(20) NULL,
    birthdate DATE NOT NULL,
    street VARCHAR(50) NOT NULL,
    barangay VARCHAR(50) NOT NULL,
    municipality_city VARCHAR(50) NOT NULL,
    province VARCHAR(50) NOT NULL,
    zip_code VARCHAR(4) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    username VARCHAR(20) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'cashier', 'admin') NOT NULL DEFAULT 'student',
    privacy_consent TINYINT(1) NOT NULL DEFAULT 0,
    privacy_consent_at DATETIME NULL,
    email_verified_at DATETIME NULL,
    account_status ENUM('pending_verification', 'active', 'locked', 'disabled') NOT NULL DEFAULT 'pending_verification',
    failed_login_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_users_id_number CHECK (id_number REGEXP '^[0-9]{4}-[0-9]{4}$'),
    CONSTRAINT chk_users_privacy_consent CHECK (privacy_consent IN (0, 1))
) ENGINE=InnoDB;

-- 2. Student-specific registration details
CREATE TABLE IF NOT EXISTS student_profiles (
    student_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    student_number VARCHAR(9) NOT NULL UNIQUE, -- format XXXX-XXXX
    course_program VARCHAR(120) NOT NULL,
    year_level VARCHAR(30) NOT NULL,
    enrollment_semester VARCHAR(40) NOT NULL,
    guardian_contact_ciphertext VARBINARY(512) NOT NULL,
    guardian_contact_iv VARBINARY(16) NOT NULL,
    encryption_key_version VARCHAR(30) NOT NULL DEFAULT 'v1',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_student_profiles_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE,
    CONSTRAINT chk_student_number CHECK (student_number REGEXP '^[0-9]{4}-[0-9]{4}$')
) ENGINE=InnoDB;

-- 3. Approved authentication questions.
-- Insert the exact questions from Section 5 of your handout before building registration.
CREATE TABLE IF NOT EXISTS security_questions (
    question_id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_text VARCHAR(255) NOT NULL UNIQUE,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- Store answers as password hashes; do not store plain-text answers.
CREATE TABLE IF NOT EXISTS user_security_answers (
    answer_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    question_id SMALLINT UNSIGNED NOT NULL,
    answer_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_question (user_id, question_id),
    CONSTRAINT fk_security_answers_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_security_answers_question
        FOREIGN KEY (question_id) REFERENCES security_questions(question_id)
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 4. Email verification tokens (store token hashes, not raw tokens)
CREATE TABLE IF NOT EXISTS email_verification_tokens (
    token_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_email_token_user (user_id),
    CONSTRAINT fk_email_tokens_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. OTP challenges (store OTP hashes, not raw OTP values)
CREATE TABLE IF NOT EXISTS otp_challenges (
    otp_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    otp_hash VARCHAR(255) NOT NULL,
    purpose ENUM('login', 'email_change', 'sensitive_action') NOT NULL DEFAULT 'login',
    expires_at DATETIME NOT NULL,
    attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    verified_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_otp_user_purpose (user_id, purpose),
    CONSTRAINT fk_otp_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Password reset tokens (store token hashes, not raw tokens)
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    reset_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_reset_user (user_id),
    CONSTRAINT fk_password_reset_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. Tuition assessment per student and term
CREATE TABLE IF NOT EXISTS tuition_assessments (
    assessment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    school_year VARCHAR(20) NOT NULL,
    semester VARCHAR(40) NOT NULL,
    total_assessed DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    due_date DATE NULL,
    assessment_status ENUM('unpaid', 'partially_paid', 'paid', 'cancelled') NOT NULL DEFAULT 'unpaid',
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_student_term (student_id, school_year, semester),
    CONSTRAINT chk_assessed_nonnegative CHECK (total_assessed >= 0),
    CONSTRAINT fk_assessment_student
        FOREIGN KEY (student_id) REFERENCES student_profiles(student_id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_assessment_creator
        FOREIGN KEY (created_by) REFERENCES users(user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- 8. Itemized tuition and other school fees
CREATE TABLE IF NOT EXISTS tuition_fee_items (
    fee_item_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_id BIGINT UNSIGNED NOT NULL,
    fee_name VARCHAR(120) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_fee_amount_nonnegative CHECK (amount >= 0),
    CONSTRAINT fk_fee_item_assessment
        FOREIGN KEY (assessment_id) REFERENCES tuition_assessments(assessment_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9. Student-submitted payments
CREATE TABLE IF NOT EXISTS payments (
    payment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_method ENUM('cash', 'bank_transfer', 'gcash', 'other') NOT NULL,
    reference_number VARCHAR(100) NULL,
    proof_file_path VARCHAR(255) NULL,
    payment_date DATE NOT NULL,
    status ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    rejection_reason VARCHAR(500) NULL,
    CONSTRAINT chk_payment_amount_positive CHECK (amount > 0),
    UNIQUE KEY uq_payment_reference (reference_number),
    KEY idx_payment_assessment_status (assessment_id, status),
    CONSTRAINT fk_payment_assessment
        FOREIGN KEY (assessment_id) REFERENCES tuition_assessments(assessment_id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_payment_reviewer
        FOREIGN KEY (reviewed_by) REFERENCES users(user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- 10. Append-only security/audit events.
-- Application code should INSERT only; do not expose edit/delete actions in admin UI.
CREATE TABLE IF NOT EXISTS security_logs (
    log_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    event_type VARCHAR(80) NOT NULL,
    event_description VARCHAR(500) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_security_event_time (event_type, created_at),
    KEY idx_security_user_time (user_id, created_at),
    CONSTRAINT fk_security_log_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- Note:
-- 1) Age should be calculated from birthdate in PHP when displayed.
-- 2) Only VERIFIED payments count toward the paid total.
-- 3) Never store plain-text passwords, OTPs, verification tokens, or reset tokens.
-- 4) Keep the AES-256 key outside the public web root and outside Git.
-- 5) Add only the exact approved authentication questions from the handout.


-- ============================================================
-- Security extensions migration (same as 002_security_requirements.sql)
-- ============================================================
-- Run once after importing school_tuition_payment.sql.
USE school_tuition_db;

ALTER TABLE users
  ADD COLUMN country VARCHAR(80) NOT NULL DEFAULT 'Philippines' AFTER province,
  ADD COLUMN last_failed_login_at DATETIME NULL AFTER failed_login_count;

ALTER TABLE otp_challenges
  MODIFY purpose ENUM('register','login','reset','email_change','sensitive_action') NOT NULL DEFAULT 'login';

ALTER TABLE security_logs
  ADD COLUMN username_typed VARCHAR(255) NULL AFTER user_id,
  ADD COLUMN details JSON NULL AFTER event_description;

CREATE TABLE IF NOT EXISTS password_history (
  history_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_password_history_user (user_id, created_at),
  CONSTRAINT fk_password_history_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ip_blocks (
  ip_block_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip_address VARCHAR(45) NOT NULL,
  reason VARCHAR(255) NOT NULL,
  blocked_until DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_ip_blocks_ip_until (ip_address, blocked_until)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS security_flags (
  flag_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pattern_key VARCHAR(100) NOT NULL,
  target_value VARCHAR(100) NOT NULL,
  severity ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  description VARCHAR(500) NOT NULL,
  detected_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  detection_date DATE NOT NULL,
  UNIQUE KEY uq_flag_daily (pattern_key, target_value, detection_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS email_resend_limits (
  limit_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  purpose VARCHAR(20) NOT NULL,
  is_resend TINYINT(1) NOT NULL DEFAULT 0,
  sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_resend_user_time (user_id, purpose, sent_at),
  CONSTRAINT fk_resend_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Official Section 5 authentication question bank. Each user selects any three distinct questions.
INSERT IGNORE INTO security_questions (question_id, question_text, is_active) VALUES
(1, 'What was the name of your first pet?', 1),
(2, 'Who was your best friend in elementary school?', 1),
(3, 'Who was your favorite teacher in high school?', 1),
(4, 'What is your mother’s maiden first name?', 1),
(5, 'What street did you live on in Grade 1?', 1),
(6, 'What was the first song you learned by heart?', 1),
(7, 'What was the name of your elementary school?', 1),
(8, 'What dish did you cook for the first time?', 1);

-- Existing registrations may have been created before the new country column.
UPDATE users SET country='Philippines' WHERE country IS NULL OR country='';


-- ============================================================
-- Five demonstration audit records (TEST-NET IP addresses only)
-- ============================================================
USE school_tuition_db;
INSERT INTO security_logs(user_id,username_typed,event_type,event_description,details,ip_address,user_agent,created_at) VALUES
(NULL,'demo_student','LOGIN_FAILED','Demonstration failed login',JSON_OBJECT('demo',true),'192.0.2.10','Academic test record',NOW()),
(NULL,'demo_student','OTP_FAILED','Demonstration incorrect OTP',JSON_OBJECT('demo',true),'192.0.2.10','Academic test record',NOW()),
(NULL,'demo_user2','LOGIN_SUCCESS','Demonstration successful login',JSON_OBJECT('demo',true),'198.51.100.20','Academic test record',NOW()),
(NULL,'demo_user2','OTP_SENT','Demonstration OTP sent event',JSON_OBJECT('demo',true),'198.51.100.20','Academic test record',NOW()),
(NULL,'demo_user3','ADMIN_ACCESS_DENIED','Demonstration denied admin access',JSON_OBJECT('demo',true),'203.0.113.30','Academic test record',NOW());
