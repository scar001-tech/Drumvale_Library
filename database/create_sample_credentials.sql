-- Create Sample Credentials Script
-- Drumvale Library Management System
-- 
-- This script creates sample/default login credentials for testing and demo purposes
-- Matching the exact admins table structure:
-- admin_id (int(11) AI PK), username (varchar(50)), password_hash (varchar(255)),
-- full_name (varchar(100)), email (varchar(100)), created_at (timestamp),
-- last_login (timestamp), status
--
-- ⚠️  SECURITY WARNING: Only use for development/testing! Remove before production!

USE drumvale_library;

-- Insert sample admin users with various credentials
-- All passwords are hashed using PHP password_hash() with PASSWORD_DEFAULT

-- 1. Default Admin User (admin/admin123)
INSERT INTO admins (username, password_hash, full_name, email, status) 
VALUES (
    'admin', 
    '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeFVNYZNx/gYP3vGLCNhJnLLiDhqCqG6m', 
    'Library Administrator', 
    'admin@drumvale.edu', 
    'Active'
) ON DUPLICATE KEY UPDATE 
    password_hash = VALUES(password_hash),
    full_name = VALUES(full_name),
    email = VALUES(email),
    status = VALUES(status);

-- 2. Demo User (demo/demo123)
INSERT INTO admins (username, password_hash, full_name, email, status) 
VALUES (
    'demo', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
    'Demo Administrator', 
    'demo@drumvale.edu', 
    'Active'
) ON DUPLICATE KEY UPDATE 
    password_hash = VALUES(password_hash),
    full_name = VALUES(full_name),
    email = VALUES(email),
    status = VALUES(status);

-- 3. Test User (test/test123)
INSERT INTO admins (username, password_hash, full_name, email, status) 
VALUES (
    'test', 
    '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', 
    'Test Administrator', 
    'test@drumvale.edu', 
    'Active'
) ON DUPLICATE KEY UPDATE 
    password_hash = VALUES(password_hash),
    full_name = VALUES(full_name),
    email = VALUES(email),
    status = VALUES(status);

-- Verify all sample users were created
SELECT 'Sample admin accounts created:' as Info;
SELECT 
    admin_id,
    username,
    full_name,
    email,
    created_at,
    last_login,
    status
FROM admins 
WHERE username IN ('admin', 'demo', 'test')
ORDER BY admin_id;

-- Display credentials information
SELECT 'Sample Login Credentials:' as Info;
SELECT '1. Username: admin | Password: admin123' as Credential1;
SELECT '2. Username: demo  | Password: demo123' as Credential2;
SELECT '3. Username: test  | Password: test123' as Credential3;
SELECT '' as Separator;
SELECT '⚠️  SECURITY WARNING: Remove these accounts before production!' as SecurityWarning;
SELECT 'Use purge_sample_credentials.sql to remove all sample accounts.' as PurgeInfo;