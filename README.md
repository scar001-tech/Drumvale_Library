# 🤖 Drumvale Library Management System (DLMS) v2.0

![Platform](https://img.shields.io/badge/Platform-Web%20%7C%20Desktop-blue)
![Language](https://img.shields.io/badge/Back--end-PHP-777bb4)
![Database](https://img.shields.io/badge/Database-MySQL%20%2F%20SQLite-orange)
![License](https://img.shields.io/badge/License-MIT-green)

> **"Empowering schools with AI-powered data management and seamless integration."**

Welcome to the **Drumvale Library Management System**, an advanced, high-performance solution engineered to streamline the management of physical library assets. Designed with a dual-redundancy architecture, DLMS operates flawlessly in both standard web environments (XAMPP/MySQL) and as a standalone, zero-config desktop application.

---

## 🚀 Vision & Innovation

DLMS isn't just a database; it's a productivity multiplier. By automating the tedious aspects of school library management, we allow educators to focus on what matters most: **The Students.**

### 💎 Core Philosophy
- **Zero-Barrier Entry**: Optimized for non-technical administrators.
- **Hybrid Infrastructure**: Seamlessly switch between Web and Desktop modes.
- **Intelligent Automation**: Auto-generating accession numbers, dynamic fine calculation, and bulk data processing.

---

## ✨ Cutting-Edge Features

### 📊 Intelligent Dashboard
Experience a 360-degree view of your library's health. Monitor total inventory, issued assets, and overdue trends through a high-fidelity administrative interface.

### 📚 Advanced Book Serialization
- **Custom Accessioning**: Supports the professional `B001` ledger format with automated sequencing.
- **Multi-Copy Cloning**: Add dozens of physical copies in a single transaction with unique copy tracking.
- **Subject Intelligence**: Pre-configured for Kenyan National Curriculum (KCSE) subjects.

### 🔄 Transaction Engine 2.0
A state-of-the-art borrowing and return engine that handles mathematical fine calculations, loan durations, and renewal logic with 100% precision.

### 📥 Zero-Click Bulk Import
Transform messy Excel/CSV files into professional library records instantly. Our flexible mapping system understands your data, even if your column names don't match the system.

---

## 🛠️ Technological Stack

| Component | Technology | Role |
| :--- | :--- | :--- |
| **Kernel** | PHP 8.0+ | Server-side logic & core processing |
| **Storage (Web)** | MySQL | High-performance relational data management |
| **Storage (Edge)** | SQLite | Zero-config, single-file database for Desktop |
| **Interface** | HTML5 / CSS3 | Responsive, glassmorphic UI design |
| **Logic** | Vanilla JavaScript | Real-time validation & interactive elements |
| **Security** | PDO & BCrypt | Enterprise-grade encryption & SQL protection |

---

## 💻 Standalone Desktop Mode

The project features a full **PHP Desktop integration**, allowing the entire system to run as a `.exe` on any Windows computer without needing a server, internet, or local XAMPP installation. 

- **Embedded Browser**: Chrome-based high-speed rendering.
- **Portable DB**: Uses an internal SQLite engine for maximum portability.
*Perfect for schools with limited internet or server infrastructure.*

---

## 📥 Deployment Guide

### Web Server (XAMPP/LAMP)
1. Clone the repository to your `htdocs` or `/var/www/` folder.
2. Import `database/schema.sql` into your MySQL server.
3. Configure `includes/db_connect.php` with your database credentials.
4. Launch via `localhost/drumvale-library`.

### Desktop Environment
1. Navigate to the `drumvale-library-desktop` folder.
2. Launch `phpdesktop-chrome.exe`.
3. The system will automatically detect the SQLite environment and initialize itself.

---

## 🛡️ Security & Integrity

- **Prepared Statements**: Complete immunity to SQL Injection attacks.
- **Session Isolation**: Secure admin-only access with automatic timeout logic.
- **Audit Logging**: Every create, update, and delete action is tracked with an IP and timestamp.

---

## 📞 Future Integrations
- [ ] Barcode/QR Code Scanner integration.
- [ ] Student ID Card synchronization.
- [ ] Automated SMS notifications for overdue books.
- [ ] AI-driven book recommendation engine.

---

### **Built with ❤️ by Antigravity AI & Drumvale Tech**
*Optimizing the world's knowledge, one book at a time.*