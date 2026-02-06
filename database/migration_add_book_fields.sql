-- Migration script to add missing columns to existing database
-- Run this if you already have the database created

USE drumvale_library;

-- Add missing columns to books table if they don't exist
ALTER TABLE books 
ADD COLUMN IF NOT EXISTS edition VARCHAR(50) NULL AFTER publication_year,
ADD COLUMN IF NOT EXISTS pages INT NULL AFTER edition,
ADD COLUMN IF NOT EXISTS language VARCHAR(50) DEFAULT 'English' AFTER pages,
ADD COLUMN IF NOT EXISTS description TEXT NULL AFTER price;

-- Update status enum to include 'Deleted'
ALTER TABLE books 
MODIFY COLUMN status ENUM('Active', 'Archived', 'Deleted') DEFAULT 'Active';

-- Verify changes
DESCRIBE books;
