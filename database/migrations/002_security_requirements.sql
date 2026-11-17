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
