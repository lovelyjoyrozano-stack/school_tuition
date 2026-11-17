-- Optional demonstration log records. Run only after the migration.
-- These use reserved TEST-NET IP addresses and do not create real user accounts.
USE school_tuition_db;
INSERT INTO security_logs(user_id,username_typed,event_type,event_description,details,ip_address,user_agent,created_at) VALUES
(NULL,'demo_student','LOGIN_FAILED','Demonstration failed login',JSON_OBJECT('demo',true),'192.0.2.10','Academic test record',NOW()),
(NULL,'demo_student','OTP_FAILED','Demonstration incorrect OTP',JSON_OBJECT('demo',true),'192.0.2.10','Academic test record',NOW()),
(NULL,'demo_user2','LOGIN_SUCCESS','Demonstration successful login',JSON_OBJECT('demo',true),'198.51.100.20','Academic test record',NOW()),
(NULL,'demo_user2','OTP_SENT','Demonstration OTP sent event',JSON_OBJECT('demo',true),'198.51.100.20','Academic test record',NOW()),
(NULL,'demo_user3','ADMIN_ACCESS_DENIED','Demonstration denied admin access',JSON_OBJECT('demo',true),'203.0.113.30','Academic test record',NOW());
