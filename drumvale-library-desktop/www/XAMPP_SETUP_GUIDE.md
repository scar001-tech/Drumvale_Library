# 🚀 Complete xampp-new Setup Guide for Drumvale Library

## Step 1: Start xampp-new Control Panel

1. **Open xampp-new Control Panel**:
   - Go to your xampp-new installation directory (usually `C:\xampp-new`)
   - Double-click `xampp-new-control.exe` to open the control panel
   - If you can't find it, search for "xampp-new Control Panel" in Windows Start menu

2. **Start Required Services**:
   - Click **"Start"** next to **Apache** (web server)
   - Click **"Start"** next to **MySQL** (database server)
   - Both should show green "Running" status

## Step 2: Copy Project to xampp-new Directory

1. **Navigate to xampp-new htdocs folder**:
   - Open File Explorer
   - Go to `C:\xampp-new\htdocs\`

2. **Create project folder**:
   - Create a new folder called `drumvale-library`
   - Full path should be: `C:\xampp-new\htdocs\drumvale-library\`

3. **Copy all project files**:
   - Copy ALL files and folders from your current project location
   - From: `C:\Users\PC\OneDrive\Desktop\Drumvale Library\`
   - To: `C:\xampp-new\htdocs\drumvale-library\`

## Step 3: Create Database

### Option A: Using phpMyAdmin (Recommended)

1. **Open phpMyAdmin**:
   - Open your web browser
   - Go to: `http://localhost/phpmyadmin`

2. **Create Database**:
   - Click "New" in the left sidebar
   - Database name: `drumvale_library`
   - Collation: `utf8mb4_general_ci`
   - Click "Create"

3. **Import Schema**:
   - Select the `drumvale_library` database
   - Click "Import" tab
   - Click "Choose File"
   - Select `database/schema.sql` from your project
   - Click "Go" to import

### Option B: Using Command Line

1. **Open Command Prompt as Administrator**
2. **Navigate to xampp-new MySQL bin**:
   ```cmd
   cd C:\xampp-new\mysql\bin
   ```

3. **Create Database**:
   ```cmd
   mysql -u root -p -e "CREATE DATABASE drumvale_library;"
   ```

4. **Import Schema**:
   ```cmd
   mysql -u root -p drumvale_library < C:\xampp-new\htdocs\drumvale-library\database\schema.sql
   ```

## Step 4: Configure Database Connection

1. **Open the database config file**:
   - File: `C:\xampp-new\htdocs\drumvale-library\includes\db_connect.php`

2. **Update credentials if needed** (usually default xampp-new settings work):
   ```php
   $db_config = [
       'host' => 'localhost',
       'dbname' => 'drumvale_library',
       'username' => 'root',
       'password' => '',  // Usually empty for xampp-new
       'charset' => 'utf8mb4'
   ];
   ```

## Step 5: Test the System

1. **Open your browser**
2. **Go to**: `http://localhost/drumvale-library/`
3. **You should see the login page**

4. **Run System Check**:
   - Go to: `http://localhost/drumvale-library/system_check.php?check=system`
   - This will verify all components are working

5. **Login with default credentials**:
   - Username: `admin`
   - Password: `admin123`

## 🔧 Troubleshooting

### Apache Won't Start
- **Port 80 conflict**: Another service is using port 80
- **Solution**: In xampp-new Control Panel, click "Config" next to Apache → "httpd.conf"
- Change `Listen 80` to `Listen 8080`
- Access via: `http://localhost:8080/drumvale-library/`

### MySQL Won't Start
- **Port 3306 conflict**: Another MySQL service is running
- **Solution**: Stop other MySQL services or change xampp-new MySQL port

### Database Connection Failed
- Check MySQL is running in xampp-new Control Panel
- Verify database name is exactly `drumvale_library`
- Check username/password in `db_connect.php`

### Page Not Found
- Ensure project is in `C:\xampp-new\htdocs\drumvale-library\`
- Check Apache is running
- Try: `http://localhost/drumvale-library/index.php`

## 📁 Final Directory Structure

```
C:\xampp-new\htdocs\drumvale-library\
├── assets/
│   ├── css/main.css
│   └── js/main.js
├── books/
├── database/
│   └── schema.sql
├── fines/
├── includes/
│   ├── db_connect.php
│   ├── header.php
│   └── footer.php
├── members/
├── reports/
├── settings/
├── transactions/
├── index.php
├── login.php
├── logout.php
├── system_check.php
└── README.md
```

## ✅ Success Checklist

- [ ] xampp-new Control Panel shows Apache and MySQL as "Running"
- [ ] Database `drumvale_library` created successfully
- [ ] Schema imported without errors
- [ ] Project files copied to `C:\xampp-new\htdocs\drumvale-library\`
- [ ] Can access `http://localhost/drumvale-library/`
- [ ] System check passes all tests
- [ ] Can login with admin/admin123

## 🎉 You're Ready!

Once all steps are complete, your library management system will be fully operational!

**Next Steps After Setup**:
1. Change the default admin password
2. Add your books and members
3. Start managing your library efficiently!