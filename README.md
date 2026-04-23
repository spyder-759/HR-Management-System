# HR Management System - Setup Guide

A comprehensive HR Management System built with PHP, MySQL, HTML, CSS, and JavaScript. This system provides modern HR management capabilities including recruitment, employee management, leave tracking, attendance, and performance appraisal.

## 🚀 Features

### 🔐 Authentication System
- Secure admin login/logout with session management
- Password hashing using PHP's built-in functions
- Form validation (frontend + backend)
- Session timeout handling

### 📊 Admin Dashboard
- Real-time statistics and metrics
- Interactive charts using Chart.js
- Recent activity feeds
- Responsive sidebar navigation
- Modern card-based layout

### 👨‍💼 Recruitment Module
- Job posting management
- Candidate application tracking
- Resume upload (PDF, DOC, DOCX)
- Candidate status management (Applied → Shortlisted → Interviewed → Selected/Rejected)
- Advanced search and filtering

### 🧑‍💻 Employee Management
- Employee onboarding from selected candidates
- Automatic employee ID generation
- Department and role management
- Employee profile management
- Search and pagination

### 📅 Leave & Attendance Module
- Daily attendance marking
- Leave request system
- Admin approval workflow
- Multiple leave types
- Attendance calendar view
- Monthly attendance summaries

### 📈 Performance Appraisal
- Performance review system
- Multi-criteria rating (Technical, Communication, Teamwork, Punctuality)
- Review history tracking
- Goal setting and feedback
- Performance analytics

## 🛠️ Technical Requirements

### Server Requirements
- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher / MariaDB 10.2 or higher
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP Extensions**: 
  - PDO
  - PDO_MySQL
  - mbstring
  - fileinfo
  - json

### Client Requirements
- Modern web browser (Chrome 90+, Firefox 88+, Safari 14+, Edge 90+)
- JavaScript enabled
- Minimum screen resolution: 1024x768

## 📋 Installation Guide

### Step 1: Database Setup

1. **Create MySQL Database**
   ```sql
   CREATE DATABASE hr_management_system;
   ```

2. **Import Database Schema**
   - Open `database.sql` in MySQL client or phpMyAdmin
   - Execute the SQL file to create all tables and initial data

3. **Verify Database Creation**
   ```sql
   USE hr_management_system;
   SHOW TABLES;
   ```

### Step 2: Configure Application

1. **Extract Files**
   - Extract all files to your web server directory (e.g., `/var/www/html/hrms/`)

2. **Update Database Configuration**
   - Open `includes/config.php`
   - Update database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'hr_management_system');
   define('DB_USER', 'your_mysql_username');
   define('DB_PASS', 'your_mysql_password');
   ```

3. **Set File Permissions**
   ```bash
   chmod 755 /path/to/hrms/
   chmod 777 /path/to/hrms/uploads/
   chmod 644 /path/to/hrms/includes/config.php
   ```

### Step 3: Web Server Configuration

#### Apache Configuration
Create `.htaccess` file in the root directory:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L]

# Security headers
Header always set X-Frame-Options DENY
Header always set X-Content-Type-Options nosniff
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"

# PHP settings
php_flag display_errors Off
php_value upload_max_filesize 5M
php_value post_max_size 6M
php_value memory_limit 256M
```

#### Nginx Configuration
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/html/hrms;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location /uploads/ {
        location ~* \.(php|phtml|php3|php4|php5|pl|py|cgi|sh|exe)$ {
            deny all;
        }
    }
}
```

### Step 4: Access the Application

1. **Open Browser**
   - Navigate to `http://your-domain.com/hrms/` or `http://localhost/hrms/`

2. **Default Login**
   - Username: `admin`
   - Password: `admin123`

3. **Change Default Password**
   - Immediately change the default admin password for security

## 🔒 Security Configuration

### 1. Database Security
```sql
-- Create dedicated database user
CREATE USER 'hrms_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON hr_management_system.* TO 'hrms_user'@'localhost';
FLUSH PRIVILEGES;
```

### 2. File Security
- Move `includes/config.php` outside public web directory if possible
- Set proper file permissions (755 for directories, 644 for files)
- Disable directory listing

### 3. PHP Security Settings
Update `php.ini`:
```ini
; Hide PHP version
expose_php = Off

; Error reporting for production
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log

; File upload security
file_uploads = On
upload_max_filesize = 5M
max_file_uploads = 20

; Session security
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
```

## 📁 Project Structure

```
HR Management System/
├── assets/
│   ├── css/
│   │   ├── style.css          # Main stylesheet
│   │   └── responsive.css     # Responsive styles
│   ├── js/
│   │   └── app.js            # Main JavaScript file
│   └── images/                # Image assets
├── includes/
│   └── config.php             # Database configuration
├── modules/
│   ├── recruitment/
│   │   ├── jobs.php           # Job management
│   │   └── candidates.php     # Candidate management
│   ├── employees/
│   │   └── employees.php      # Employee management
│   ├── leaves/
│   │   ├── leaves.php         # Leave management
│   │   └── attendance.php     # Attendance management
│   └── performance/
│       └── performance.php    # Performance reviews
├── uploads/                   # File upload directory
├── database.sql               # Database schema
├── login.php                  # Login page
├── dashboard.php              # Admin dashboard
├── logout.php                 # Logout handler
└── README.md                  # This file
```

## 🎨 Customization

### 1. Branding
- Update company logo in `assets/images/`
- Modify colors in `assets/css/style.css`:
```css
:root {
    --primary-color: #4f46e5;    /* Change to your brand color */
    --secondary-color: #6366f1;   /* Change to your secondary color */
}
```

### 2. Email Configuration
Add email settings in `includes/config.php`:
```php
// Email configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('FROM_EMAIL', 'hr@yourcompany.com');
define('FROM_NAME', 'HR Management System');
```

### 3. Leave Types
Modify leave types in the database:
```sql
INSERT INTO leave_types (name, description, max_days_per_year) VALUES 
('Custom Leave', 'Description of custom leave', 10);
```

## 🔧 Maintenance

### 1. Regular Backups
```bash
# Database backup
mysqldump -u username -p hr_management_system > backup_$(date +%Y%m%d).sql

# Files backup
tar -czf hrms_backup_$(date +%Y%m%d).tar.gz /path/to/hrms/
```

### 2. Log Monitoring
Monitor these log files:
- Apache/Nginx access and error logs
- PHP error log
- Application logs (if implemented)

### 3. Database Maintenance
```sql
-- Optimize tables
OPTIMIZE TABLE employees, candidates, leaves, attendance, performance_reviews;

-- Clean old sessions (if using file sessions)
DELETE FROM sessions WHERE expiry < NOW();
```

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `includes/config.php`
   - Verify MySQL server is running
   - Ensure database exists and user has permissions

2. **File Upload Issues**
   - Check `uploads/` directory permissions
   - Verify PHP upload settings in `php.ini`
   - Check file size limits

3. **Session Issues**
   - Verify session save path is writable
   - Check session cookie settings
   - Ensure session timeout is appropriate

4. **Blank Pages**
   - Enable PHP error reporting temporarily
   - Check syntax errors in PHP files
   - Verify file permissions

### Error Codes Reference

| Error | Description | Solution |
|-------|-------------|----------|
| 404 | Page not found | Check file exists and URL is correct |
| 500 | Server error | Check PHP error logs |
| 403 | Access denied | Check file permissions |
| 401 | Unauthorized | Check login credentials |

## 📞 Support

For technical support:
1. Check this documentation first
2. Review error logs
3. Test with minimal configuration
4. Report issues with detailed information

## 🔄 Updates

### Version History
- **v1.0.0** - Initial release with core HR management features

### Future Updates
- Employee self-service portal
- Payroll management
- Advanced reporting
- Mobile app
- API integration

## 📄 License

This project is licensed under the MIT License. See LICENSE file for details.

---

**Installation Complete!** 🎉

Your HR Management System is now ready to use. Remember to:
1. Change the default admin password
2. Configure backup procedures
3. Monitor system performance
4. Keep software updated

For additional help, refer to the troubleshooting section or contact support.
