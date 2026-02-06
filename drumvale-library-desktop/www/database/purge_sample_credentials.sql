-- Purge Sample/Default Credentials Script
-- Drumvale Library Management System
-- 
-- This script removes all default/sample login credentials from the system
-- Use this to clean up demo accounts before production deployment
--
-- ⚠️  WARNING: This will remove ALL sample admin accounts!
-- Make sure you have created your own admin account before running this!

USE drumvale_library;

-- Show current admin accounts before deletion
SELECT 'Current admin accounts before purge:' as Info;
SELECT 
    admin_id,
    username,
    full_name,
    email,
    created_at,
    last_login,
    status
FROM admins;

-- Delete default/sample admin accounts
-- Remove the default 'admin' user
DELETE FROM admins WHERE username = 'admin';

-- Remove any other common sample usernames
DELETE FROM admins WHERE username IN ('test', 'demo', 'sample', 'administrator', 'root');

-- Remove accounts with sample email domains
DELETE FROM admins WHERE email LIKE '%@example.com' OR email LIKE '%@test.com' OR email LIKE '%@demo.com';

-- Show remaining admin accounts after purge
SELECT 'Remaining admin accounts after purge:' as Info;
SELECT 
    admin_id,
    username,
    full_name,
    email,
    created_at,
    last_login,
    status
FROM admins;

-- Display completion message
SELECT 'Sample credentials purge completed!' as Status;
SELECT 'Verify that you have at least one admin account remaining!' as Warning;
SELECT 'If no accounts remain, you will need to create a new admin account.' as Notice;