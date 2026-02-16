# Drumvale Secondary School Library Management System

A practical, robust, and easy-to-use library management system built specifically for the needs of physical book tracking in Kenyan secondary schools. This system is designed to run in two ways: as a traditional web application (via XAMPP/MySQL) and as a zero-configuration desktop application for Windows.

---

### Table of Contents
1. [Overview](#overview)
2. [Key Features](#key-features)
3. [The Dual-Database System](#the-dual-database-system)
4. [New Format: B-Series Accessioning](#new-format-b-series-accessioning)
5. [Installation & Setup](#installation--setup)
    - [Web Version (XAMPP)](#web-version-xampp)
    - [Desktop Version (Standalone)](#desktop-version-standalone)
6. [Bulk Data Import](#bulk-data-import)
7. [System Requirements](#system-requirements)
8. [Directory Structure](#directory-structure)
9. [Developer Notes](#developer-notes)

---

## Overview

The Drumvale Library Management System was created to provide a centralized register for school librarians. It is an **admin-only** system, meaning students and teachers do not log in. Instead, the librarian manages all book issuances, returns, and inventory tracking from a single dashboard.

The system is built to be resilient. Whether you have a dedicated server with a MySQL database or just a single Windows laptop with no internet, the application adapts and works out of the box.

## Key Features

###  Comprehensive Book Registry
- **Bulk Upload**: Import entire book lists from Excel or CSV files with a flexible column mapping tool.
- **Multiple Copies**: Add multiple physical copies of the same book title in one go.
- **Auto-Accessioning**: The system generates unique, sequential accession numbers starting from `B001`.
- **Subject Tracking**: Organized by KCSE subjects (Mathematics, Chemistry, Literature, etc.).

###  Circulation & Accountability
- **Issue/Return Workflow**: Quick forms to check books in and out.
- **Overdue Monitoring**: Instant visibility into which books are past their due dates.
- **Fine Management**: Automatic calculation of fines based on days overdue (default KSh 5/day, configurable in settings).

###  Member Tracking
- Register both Students and Teachers.
- Track borrow history per member to see who has which book.
- Simple status management (Active / Left School).

###  Reports & Analytics
- Inventory status reports.
- Fine collection records.
- Transaction logs for audit purposes.

## The Dual-Database System

This application features a "smart connection" handler (`includes/db_connect.php`) that detects the environment it is running in:

1. **MySQL Mode**: Used when hosted on a web server or local XAMPP. It uses the default MySQL port `3307` and standard relational tables.
2. **SQLite Mode**: Used when launched as a Desktop app. It stores everything in a single `.sqlite` file inside the `database/` folder, requiring zero installation from the user.

*Note: The system includes a built-in migration script that automatically cleans up rigid database constraints to ensure bulk uploads never crash the app due to "unknown categories" or statuses.*

## New Format: B-Series Accessioning

As requested, the system has been updated to follow a professional school ledger format:
- Accession numbers are automatically generated as `B001`, `B002`, `B003`, etc.
- When adding multiple copies of the same book, they are automatically sequenced (e.g., if the last book was B050 and you add 3 new copies, they become B051, B052, and B053).

## Installation & Setup

### Web Version (XAMPP)
1. **Clone/Download**: Place the `drumvale-library` folder in your `C:\xampp\htdocs\` directory.
2. **Database**: 
    - Open XAMPP Control Panel and start Apache and MySQL.
    - Go to `phpMyAdmin` and create a database named `drumvale_library`.
    - Import the file `database/schema.sql`.
3. **Ports**: This system is pre-configured to look for MySQL on port **3307** (common in XAMPP setups). If your port is different, change it in `includes/db_connect.php`.
4. **Access**: Open your browser and go to `http://localhost/drumvale-library`.

### Desktop Version (Standalone)
The desktop version is located in the `drumvale-library-desktop/` directory.
1. Simply go into that folder and run **`phpdesktop-chrome.exe`**.
2. The app will open in a dedicated window with its own built-in browser and database.
3. No configuration or server setup is required for this mode.

---

## Bulk Data Import

To import your existing library records:
1. Navigate to **Books > Bulk Upload**.
2. Select your CSV or Excel file.
3. The system will automatically try to match your columns (e.g., "Book Name" -> "Title").
4. Review the mapping and click "Import".
5. The system handles thousands of records efficiently.

## System Requirements

- **PHP**: 8.0 or higher.
- **Web Server**: Apache / Nginx (for web mode).
- **Database**: MySQL 8.0+ or SQLite 3 (built-in).
- **Browser**: Any modern browser (Chrome, Edge, Firefox).

## Directory Structure

- `assets/`: CSS, JavaScript, and UI icons.
- `books/`: Logic for adding, editing, and bulk-uploading books.
- `members/`: Student and Teacher management.
- `transactions/`: Issuing, returning, and tracking books.
- `includes/`: Core database connections, header, and footer.
- `database/`: SQL schemas and the SQLite portable database file.
- `fines/`: Automatic fine calculation and collection logic.

## Developer Notes

- **Initial Login**: `admin` / `admin123`.
- **Security**: The system uses PDO prepared statements for all database queries to prevent SQL injection.
- **Indentation**: Code follows standard PHP/HTML nesting for easy readability.
- **Individuality**: Every physical copy has its own unique record ID to ensure accurate "Available Copy" counts.

---
**Developed for Drumvale Secondary School**  
*Designed for reliability, built for librarians.*
