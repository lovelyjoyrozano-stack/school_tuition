-- IT 107 updated instructor settings (Section 2.3 announcement).
-- Run once after migration 002 on an existing database; back up first.
USE school_tuition_db;

ALTER TABLE users
  MODIFY first_name VARCHAR(50) NOT NULL,
  MODIFY middle_name VARCHAR(50) NULL,
  MODIFY family_name VARCHAR(50) NOT NULL,
  MODIFY street VARCHAR(50) NOT NULL,
  MODIFY barangay VARCHAR(50) NOT NULL,
  MODIFY municipality_city VARCHAR(50) NOT NULL,
  MODIFY province VARCHAR(50) NOT NULL,
  MODIFY zip_code VARCHAR(4) NOT NULL,
  MODIFY email VARCHAR(100) NOT NULL,
  MODIFY username VARCHAR(20) NOT NULL;

ALTER TABLE security_logs
  MODIFY username_typed VARCHAR(255) NULL;

-- Ensure all eight official Section 5 questions are available for user selection.
INSERT IGNORE INTO security_questions (question_id, question_text, is_active) VALUES
(1, 'What was the name of your first pet?', 1),
(2, 'Who was your best friend in elementary school?', 1),
(3, 'Who was your favorite teacher in high school?', 1),
(4, 'What is your mother’s maiden first name?', 1),
(5, 'What street did you live on in Grade 1?', 1),
(6, 'What was the first song you learned by heart?', 1),
(7, 'What was the name of your elementary school?', 1),
(8, 'What dish did you cook for the first time?', 1);

-- Distinguish original OTP sends from resends (maximum three resends per 15 minutes).
SET @has_is_resend = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'email_resend_limits'
    AND COLUMN_NAME = 'is_resend'
);
SET @add_resend_sql = IF(
  @has_is_resend = 0,
  'ALTER TABLE email_resend_limits ADD COLUMN is_resend TINYINT(1) NOT NULL DEFAULT 0 AFTER purpose',
  'SELECT 1'
);
PREPARE add_resend_column FROM @add_resend_sql;
EXECUTE add_resend_column;
DEALLOCATE PREPARE add_resend_column;
