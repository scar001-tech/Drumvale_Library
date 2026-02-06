-- Drumvale Secondary School Library Management System
-- Database Schema for Admin-Only Physical Book Tracking

CREATE DATABASE IF NOT EXISTS drumvale_library;
USE drumvale_library;

-- Admin table (for librarian login)
CREATE TABLE admins (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active'
);

-- Books table (Physical Inventory)
CREATE TABLE books (
    book_id INT PRIMARY KEY AUTO_INCREMENT,
    accession_number VARCHAR(20) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    subject ENUM(
        'Mathematics', 'English', 'Kiswahili', 'Biology', 'Chemistry', 'Physics',
        'History', 'Geography', 'CRE', 'IRE', 'HRE', 'French', 'German',
        'Business Studies', 'Agriculture', 'Home Science', 'Art & Design',
        'Music', 'Computer Studies', 'Literature'
    ) NOT NULL,
    category ENUM('Textbook', 'Novel', 'Reference', 'Magazine', 'Journal') NOT NULL,
    total_copies INT NOT NULL DEFAULT 1,
    available_copies INT NOT NULL DEFAULT 1,
    shelf_location VARCHAR(50) NOT NULL,
    barcode VARCHAR(50) NULL,
    isbn VARCHAR(20) NULL,
    publisher VARCHAR(100) NULL,
    publication_year YEAR NULL,
    edition VARCHAR(50) NULL,
    pages INT NULL,
    language VARCHAR(50) DEFAULT 'English',
    price DECIMAL(10,2) NULL,
    description TEXT NULL,
    condition_status ENUM('Good', 'Fair', 'Damaged', 'Lost') DEFAULT 'Good',
    status ENUM('Active', 'Archived', 'Deleted') DEFAULT 'Active',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_accession (accession_number),
    INDEX idx_title_author (title, author),
    INDEX idx_subject_category (subject, category),
    INDEX idx_status (status, condition_status),
    FULLTEXT idx_search (title, author, subject)
);

-- Members table (Students & Teachers - No login access)
CREATE TABLE members (
    member_id INT PRIMARY KEY AUTO_INCREMENT,
    unique_identifier VARCHAR(20) UNIQUE NOT NULL, -- Admission No or Staff ID
    full_name VARCHAR(100) NOT NULL,
    member_type ENUM('Student', 'Teacher') NOT NULL,
    class_or_department VARCHAR(50) NOT NULL,
    phone_number VARCHAR(15) NULL,
    email VARCHAR(100) NULL,
    address TEXT NULL,
    parent_guardian_name VARCHAR(100) NULL, -- For students
    parent_guardian_phone VARCHAR(15) NULL, -- For students
    status ENUM('Active', 'Left School') DEFAULT 'Active',
    registration_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_identifier (unique_identifier),
    INDEX idx_type_status (member_type, status),
    INDEX idx_class_dept (class_or_department),
    FULLTEXT idx_search (full_name, unique_identifier)
);

-- Transactions table (Borrow History - Heart of Accountability)
CREATE TABLE transactions (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    book_id INT NOT NULL,
    member_id INT NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE NULL,
    status ENUM('Issued', 'Returned', 'Overdue', 'Lost') DEFAULT 'Issued',
    renewal_count INT DEFAULT 0,
    max_renewals INT DEFAULT 2,
    handled_by INT NOT NULL, -- Admin ID
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (handled_by) REFERENCES admins(admin_id),
    
    INDEX idx_book_status (book_id, status),
    INDEX idx_member_status (member_id, status),
    INDEX idx_dates (issue_date, due_date, return_date),
    INDEX idx_overdue (status, due_date)
);

-- Fines table (Automatic calculation)
CREATE TABLE fines (
    fine_id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_id INT NOT NULL,
    member_id INT NOT NULL,
    fine_type ENUM('Overdue', 'Damage', 'Lost Book', 'Other') NOT NULL,
    days_overdue INT DEFAULT 0,
    fine_rate DECIMAL(5,2) NOT NULL DEFAULT 5.00, -- KSh per day
    total_fine DECIMAL(10,2) NOT NULL,
    amount_paid DECIMAL(10,2) DEFAULT 0.00,
    payment_status ENUM('Pending', 'Partial', 'Paid', 'Waived') DEFAULT 'Pending',
    payment_date DATE NULL,
    waived_by INT NULL, -- Admin ID who waived
    waive_reason TEXT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (waived_by) REFERENCES admins(admin_id),
    
    INDEX idx_member_status (member_id, payment_status),
    INDEX idx_transaction (transaction_id),
    INDEX idx_payment_status (payment_status)
);

-- System settings table
CREATE TABLE system_settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    description TEXT NULL,
    updated_by INT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (updated_by) REFERENCES admins(admin_id)
);

-- Activity log table (Audit trail)
CREATE TABLE activity_log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    table_affected VARCHAR(50) NOT NULL,
    record_id INT NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (admin_id) REFERENCES admins(admin_id),
    INDEX idx_admin_action (admin_id, action),
    INDEX idx_table_record (table_affected, record_id),
    INDEX idx_created_at (created_at)
);

-- Insert default admin user (password: admin123)
INSERT INTO admins (username, password_hash, full_name, email) VALUES 
('admin', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeFVNYZNx/gYP3vGLCNhJnLLiDhqCqG6m', 'Library Administrator', 'admin@drumvale.edu');

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, description, updated_by) VALUES
('student_borrow_limit', '2', 'Maximum books a student can borrow at once', 1),
('teacher_borrow_limit', '5', 'Maximum books a teacher can borrow at once', 1),
('student_loan_duration', '14', 'Default loan duration for students (days)', 1),
('teacher_loan_duration', '30', 'Default loan duration for teachers (days)', 1),
('fine_rate_per_day', '5.00', 'Fine rate per day for overdue books (KSh)', 1),
('max_renewals', '2', 'Maximum number of renewals allowed', 1),
('library_name', 'Drumvale Secondary School Library', 'Library name for reports', 1),
('academic_year', '2024', 'Current academic year', 1);

-- Fresh installation: No sample data inserted.