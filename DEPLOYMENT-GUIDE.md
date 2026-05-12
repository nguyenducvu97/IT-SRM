# Hướng dẫn triển khai IT Service Request Desktop App

## Yêu cầu hệ thống trên máy tính mới

### 1. Phần mềm bắt buộc cài đặt

#### a) XAMPP (hoặc Apache + MySQL + PHP)
```
Download: https://www.apachefriends.org/download.html
- Phiên bản XAMPP 7.4+ hoặc 8.0+
- Bao gồm: Apache, MySQL, PHP
- Cài đặt vào C:\xampp (không thay đổi đường dẫn)
```

#### b) Node.js (chỉ cho development, không cần cho end user)
```
Download: https://nodejs.org/
- Phiên bản LTS (Long Term Support)
- Chỉ cần nếu muốn build lại ứng dụng
```

### 2. Cấu hình Web Server

#### a) Database Setup
```sql
-- Import database schema
-- File: database/*.sql
-- Import vào phpMyAdmin hoặc MySQL command line
```

#### b) Web Files
```
Copy toàn bộ thư mục web vào:
C:\xampp\htdocs\it-service-request\
```

### 3. Cấu hình Apache

#### a) httpd.conf (nếu cần)
```apache
# Đảm bảo các module sau được enable
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule php_module modules/php8_module

# DocumentRoot
DocumentRoot "C:/xampp/htdocs"
<Directory "C:/xampp/htdocs">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

#### b) Virtual Host (tùy chọn)
```apache
# Thêm vào httpd-vhosts.conf
<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs\it-service-request"
    ServerName it-service.local
    ServerAlias www.it-service.local
    <Directory "C:/xampp\htdocs\it-service-request">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 4. PHP Configuration

#### a) php.ini
```ini
; Enable extensions
extension=mysqli
extension=pdo_mysql
extension=fileinfo
extension=gd
extension=curl

; Upload settings
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300

; Session settings
session.save_handler = files
session.save_path = "C:\xampp\tmp"
```

### 5. Database Configuration

#### a) MySQL User
```sql
-- Tạo database và user
CREATE DATABASE it_service_request;
CREATE USER 'ituser'@'localhost' IDENTIFIED BY 'password123';
GRANT ALL PRIVILEGES ON it_service_request.* TO 'ituser'@'localhost';
FLUSH PRIVILEGES;
```

#### b) Import Database
```bash
# Import schema
mysql -u root -p it_service_request < database/schema.sql
mysql -u root -p it_service_request < database/data.sql
```

### 6. File Permissions

#### a) Windows Permissions
```
C:\xampp\htdocs\it-service-request\uploads\ - Full Control
C:\xampp\htdocs\it-service-request\logs\ - Full Control
C:\xampp\htdocs\it-service-request\background_jobs\ - Full Control
```

### 7. Test Web Server

#### a) Kiểm tra Apache
```
http://localhost/dashboard
```

#### b) Kiểm tra PHP
```
http://localhost/it-service-request/test.php
```

#### c) Kiểm tra Database Connection
```
http://localhost/it-service-request/test-db.php
```

## Cài đặt Desktop Application

### 1. Chạy Installer
```
Double-click: IT Service Request Setup 1.0.0.exe
Follow installation wizard
```

### 2. Kiểm tra Installation
- Desktop shortcut được tạo
- Start menu entry được thêm
- Application có thể khởi động

### 3. Cấu hình Application (nếu cần)

#### a) Update URL trong main.js
```javascript
// Nếu localhost khác port hoặc domain
mainWindow.loadURL('http://localhost:8080/it-service-request');
// hoặc
mainWindow.loadURL('http://it-service.local');
```

#### b) Rebuild Application (nếu thay đổi config)
```bash
npm run build-win
```

## Troubleshooting

### 1. Common Issues

#### a) "Cannot connect to database"
```
- Kiểm tra MySQL service đang chạy
- Kiểm tra database credentials
- Kiểm tra file config/database.php
```

#### b) "404 Not Found"
```
- Kiểm tra Apache đang chạy
- Kiểm tra file permissions
- Kiểm tra .htaccess file
```

#### c) "White screen"
```
- Kiểm tra PHP error log
- Bật error reporting trong php.ini
- Kiểm tra memory limit
```

#### d) "Desktop app không load"
```
- Kiểm tra web app hoạt động trong browser
- Kiểm tra URL trong main.js
- Kiểm tra firewall/antivirus
```

### 2. Debug Tools

#### a) Browser DevTools
```javascript
// Trong Electron app
// Ctrl+Shift+I để mở DevTools
```

#### b) Log Files
```
C:\xampp\apache\logs\error.log
C:\xampp\mysql\data\mysql.err
C:\xampp\htdocs\it-service-request\logs\
```

### 3. Performance Optimization

#### a) Apache Configuration
```apache
# Enable compression
LoadModule deflate_module modules/mod_deflate.so

# Enable caching
LoadModule expires_module modules/mod_expires.so
```

#### b) MySQL Optimization
```ini
# my.ini
[mysqld]
innodb_buffer_pool_size = 256M
innodb_log_file_size = 64M
max_connections = 100
```

## Security Considerations

### 1. Production Security
```php
// Disable error display
ini_set('display_errors', 0);

// Secure session
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
```

### 2. File Security
```
- Chmod 755 cho thư mục
- Chmod 644 cho file
- Protect config files
```

### 3. Database Security
```sql
-- Remove test database
DROP DATABASE IF EXISTS test;

-- Remove anonymous users
DELETE FROM mysql.user WHERE User='';

-- Set strong passwords
```

## Maintenance

### 1. Backup Strategy
```bash
# Database backup
mysqldump -u root -p it_service_request > backup.sql

# Files backup
xcopy "C:\xampp\htdocs\it-service-request" "backup\" /E /I /H
```

### 2. Updates
```
- Update XAMPP components
- Update database schema
- Rebuild desktop app
- Deploy new version
```

### 3. Monitoring
```
- Check disk space
- Monitor error logs
- Performance metrics
- User feedback
```

## Checklist Deployment

### Pre-Deployment Checklist
- [ ] XAMPP installed and configured
- [ ] Database created and imported
- [ ] Web files copied to htdocs
- [ ] File permissions set correctly
- [ ] Apache and MySQL services running
- [ ] Web app accessible via browser
- [ ] Desktop app installer tested

### Post-Deployment Checklist
- [ ] Desktop app launches successfully
- [ ] All features working correctly
- [ ] User accounts created
- [ ] Performance acceptable
- [ ] Security measures in place
- [ ] Backup system configured
- [ ] Documentation provided to users

## Support Contacts

### Technical Support
- Email: support@company.com
- Phone: ext. 1234
- Wiki: internal.wiki.com/it-service

### Emergency Contacts
- System Administrator: ext. 9999
- Database Admin: ext. 8888
- Network Admin: ext. 7777
