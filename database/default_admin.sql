-- Default Admin User Setup for Drumvale Library Management System
-- This file creates the default administrator account
-- 
-- Default Credentials:
-- Username: admin
-- Password: admin123
--
-- ⚠️  SECURITY WARNING: Change these credentials immediately after first login!

USE drumvale_library;

-- Insert default admin user
-- Password hash for 'admin123' using PHP password_hash() with PASSWORD_DEFAULT
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

-- Verify the admin user was created
SELECT 
    admin_id,
    username,
    full_name,
    email,
    created_at,
    last_login,
    status
FROM admins 
WHERE username = 'admin';

-- Display success message
SELECT 'Default admin user created successfully!' as Status;
SELECT 'Username: admin' as Credentials;
SELECT 'Password: admin123' as Password;
SELECT '⚠️  IMPORTANT: Change the default password after first login!' as SecurityWarning;