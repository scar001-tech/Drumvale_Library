# 📚 Drumvale Secondary School Library Management System

A modern, admin-only library management system designed specifically for physical book tracking in Kenyan secondary schools. Built with PHP, MySQL, HTML5, CSS3, and JavaScript.

## 🎯 System Philosophy

- **Single Point of Control**: Only admin/librarian operates the system
- **Physical Books Only**: No e-books, no downloads - pure physical inventory tracking
- **Complete Accountability**: Every book is always traceable to a shelf, borrower, or date
- **No Student/Teacher Access**: Members exist only as records for tracking purposes

## ✨ Key Features

### 📊 Admin Dashboard
- Real-time statistics and metrics
- Quick action buttons for common tasks
- Recent activity monitoring
- Modern, responsive design

### 📚 Book Management
- Complete book inventory tracking
- Accession number system
- KCSE subject categorization
- Shelf location management
- Bulk upload support
- Advanced search and filtering

### 👥 Member Management
- Student and teacher registration
- No login credentials required
- Class/department organization
- Contact information tracking
- Status management (Active/Left School)

### 🔄 Transaction System
- Issue and return book workflow
- Automatic due date calculation
- Overdue tracking
- Renewal management
- Complete audit trail

### 💰 Fine Management
- Automatic fine calculation
- Multiple payment tracking
- Waiver system
- Fine reports and analytics

### 📈 Comprehensive Reports
- Book inventory reports
- Member activity reports
- Transaction history
- Overdue books tracking
- Fine collection reports
- Export to PDF/Excel

### ⚙️ System Settings
- Configurable borrow limits
- Loan duration settings
- Fine rate management
- Academic year controls

## 🛠️ Technology Stack

- **Backend**: PHP 8.0+
- **Database**: MySQL 8.0+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Icons**: Font Awesome 6
- **Fonts**: Google Fonts (Inter)
- **Security**: PDO prepared statements, password hashing, session management

## 📋 System Requirements

- PHP 8.0 or higher
- MySQL 8.0 or higher
- Apache/Nginx web server
- Modern web browser (Chrome, Firefox, Safari, Edge)

## 🚀 Installation Guide

### 1. Database Setup

```sql
-- Create database
CREATE DATABASE drumvale_library;

-- Import the schema
mysql -u root -p drumvale_library < database/schema.sql
```

### 2. Configuration

1. Update database credentials in `includes/db_connect.php`:
```php
$db_config = [
    'host' => 'localhost',
    'dbname' => 'drumvale_library',
    'username' => 'your_username',
    'password' => 'your_password',
    'charset' => 'utf8mb4'
];
```

2. Set proper file permissions:
```bash
chmod 755 /path/to/library/system
chmod 644 /path/to/library/system/*.php
```

### 3. Default Login

- **Username**: `admin`
- **Password**: `admin123`

**⚠️ Important**: Change the default password immediately after first login!

## 📁 Project Structure

```
drumvale-library/
├── assets/
│   ├── css/
│   │   └── main.css          # Main stylesheet
│   ├── js/
│   │   └── main.js           # Main JavaScript
│   └── images/               # System images
├── books/
│   ├── index.php             # Book listing
│   ├── add.php               # Add new book
│   ├── edit.php              # Edit book
│   └── bulk_upload.php       # Bulk book upload
├── members/
│   ├── index.php             # Member listing
│   ├── add.php               # Register member
│   ├── edit.php              # Edit member
│   ├── students.php          # Students only
│   └── teachers.php          # Teachers only
├── transactions/
│   ├── index.php             # Transaction history
│   ├── issue.php             # Issue book
│   ├── return.php            # Return book
│   └── overdue.php           # Overdue books
├── fines/
│   ├── index.php             # Fine management
│   ├── collect.php           # Collect payment
│   └── waive.php             # Waive fine
├── reports/
│   ├── index.php             # Reports dashboard
│   ├── books.php             # Book reports
│   ├── members.php           # Member reports
│   ├── transactions.php      # Transaction reports
│   └── inventory.php         # Inventory report
├── settings/
│   ├── index.php             # System settings
│   ├── backup.php            # Database backup
│   └── restore.php           # Database restore
├── includes/
│   ├── db_connect.php        # Database connection
│   ├── header.php            # Common header
│   └── footer.php            # Common footer
├── database/
│   └── schema.sql            # Database schema
├── index.php                 # Dashboard
├── login.php                 # Admin login
├── logout.php                # Logout handler
└── README.md                 # This file
```

## 🗄️ Database Schema

### Core Tables

1. **admins** - System administrators
2. **books** - Physical book inventory
3. **members** - Students and teachers (no login access)
4. **transactions** - Borrow/return history
5. **fines** - Fine management
6. **system_settings** - Configurable settings
7. **activity_log** - Audit trail

### Key Relationships

- Books ↔ Transactions (One-to-Many)
- Members ↔ Transactions (One-to-Many)
- Transactions ↔ Fines (One-to-One)
- Admins ↔ Activity Log (One-to-Many)

## 🔒 Security Features

- Password hashing with PHP's `password_hash()`
- PDO prepared statements prevent SQL injection
- Session-based authentication
- Activity logging for audit trails
- Input validation and sanitization
- CSRF protection on forms

## 📱 Responsive Design

The system is fully responsive and works on:
- Desktop computers
- Tablets
- Mobile phones
- All modern browsers

## 🎨 Design Principles

- **Clean & Modern**: Contemporary web design
- **User-Friendly**: Intuitive navigation and workflows
- **Professional**: Suitable for educational institutions
- **Accessible**: WCAG 2.1 compliant
- **Fast**: Optimized for performance

## 🔧 Customization

### Colors & Branding

Update CSS variables in `assets/css/main.css`:

```css
:root {
    --primary-color: #2563eb;    /* Main brand color */
    --secondary-color: #64748b;  /* Secondary color */
    /* ... other variables */
}
```

### System Settings

Configure via the admin panel:
- Borrow limits per member type
- Loan duration defaults
- Fine rates
- Academic year settings

## 📊 Sample Data

The system includes sample data for testing:
- 5 sample books across different subjects
- 5 sample members (students and teachers)
- Sample transactions and fines

## 🚀 Deployment

### Production Checklist

1. ✅ Change default admin password
2. ✅ Update database credentials
3. ✅ Set proper file permissions
4. ✅ Enable HTTPS
5. ✅ Configure backup schedule
6. ✅ Test all functionality
7. ✅ Train library staff

### Recommended Hosting

- **Shared Hosting**: Any PHP/MySQL hosting
- **VPS**: Ubuntu/CentOS with LAMP stack
- **Cloud**: AWS, DigitalOcean, Linode

## 🔄 Backup & Maintenance

### Regular Backups

```bash
# Database backup
mysqldump -u username -p drumvale_library > backup_$(date +%Y%m%d).sql

# File backup
tar -czf files_backup_$(date +%Y%m%d).tar.gz /path/to/library/system
```

### Maintenance Tasks

- Weekly database backups
- Monthly system updates
- Quarterly security reviews
- Annual data archiving

## 🆘 Support & Troubleshooting

### Common Issues

1. **Login Problems**
   - Check database connection
   - Verify admin credentials
   - Clear browser cache

2. **Database Errors**
   - Check MySQL service status
   - Verify database permissions
   - Review error logs

3. **Performance Issues**
   - Optimize database indexes
   - Enable PHP OPcache
   - Compress static assets

### Getting Help

- Check the error logs in `/var/log/apache2/error.log`
- Enable PHP error reporting for debugging
- Review database slow query log

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📞 Contact

For support or questions:
- **Email**: library@drumvale.edu
- **Phone**: +254 XXX XXX XXX
- **Address**: Drumvale Secondary School, Kenya

---

**Built with ❤️ for Drumvale Secondary School**

*Empowering education through efficient library management*