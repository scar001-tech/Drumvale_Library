# 🚀 Quick Installation Guide

## Required Software Installation

### 1. Install XAMPP (Recommended for Windows)

XAMPP includes PHP, MySQL, and Apache web server in one package:

1. **Download XAMPP**: Visit https://www.apachefriends.org/download.html
2. **Download the latest version** (PHP 8.0+)
3. **Run the installer** and install to `C:\xampp`
4. **Start XAMPP Control Panel**
5. **Start Apache and MySQL** services

### 2. Alternative: Install Separately

If you prefer individual installations:

#### PHP Installation
1. Download PHP 8.0+ from https://windows.php.net/download/
2. Extract to `C:\php`
3. Add `C:\php` to your system PATH
4. Copy `php.ini-development` to `php.ini`

#### MySQL Installation
1. Download MySQL 8.0+ from https://dev.mysql.com/downloads/mysql/
2. Run the installer
3. Set root password during installation
4. Start MySQL service

## 🗄️ Database Setup

### Using XAMPP phpMyAdmin
1. Open http://localhost/phpmyadmin
2. Click "New" to create database
3. Name it `drumvale_library`
4. Go to "Import" tab
5. Choose `database/schema.sql` file
6. Click "Go"

### Using MySQL Command Line
```bash
# Create database
mysql -u root -p -e "CREATE DATABASE drumvale_library;"

# Import schema
mysql -u root -p drumvale_library < database/schema.sql
```

## ⚙️ Configuration

1. **Update database credentials** in `includes/db_connect.php`:
   - Host: `localhost`
   - Database: `drumvale_library`
   - Username: `root` (or your MySQL username)
   - Password: (your MySQL password)

2. **Place project files** in web server directory:
   - XAMPP: `C:\xampp\htdocs\drumvale-library\`
   - Other servers: Your web root directory

## 🌐 Access the System

1. **Start your web server** (Apache in XAMPP)
2. **Open browser** and go to: `http://localhost/drumvale-library/`
3. **Login with default credentials**:
   - Username: `admin`
   - Password: `admin123`

## ✅ Verification Checklist

- [ ] PHP 8.0+ installed and working
- [ ] MySQL 8.0+ installed and running
- [ ] Apache web server running
- [ ] Database `drumvale_library` created
- [ ] Schema imported successfully
- [ ] Project files in web directory
- [ ] Can access login page
- [ ] Can login with admin credentials

## 🆘 Troubleshooting

### Common Issues:

1. **"PHP not found"**
   - Ensure PHP is in system PATH
   - Restart command prompt/terminal

2. **"Database connection failed"**
   - Check MySQL service is running
   - Verify credentials in `db_connect.php`
   - Ensure database exists

3. **"Page not found"**
   - Check web server is running
   - Verify project is in correct directory
   - Check file permissions

### Getting Help:
- Check Apache error logs
- Enable PHP error reporting
- Verify all services are running in XAMPP Control Panel

---

**Next Steps**: Once everything is installed and working, you can start using the library management system!