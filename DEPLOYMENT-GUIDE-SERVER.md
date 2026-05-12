# 🚀 Hướng dẫn Deploy IT Service Request lên Server

## 📋 Mục tiêu
Đưa ứng dụng IT Service Request lên server để các máy tính trong mạng có thể truy cập và sử dụng.

## 🎯 Tổng quan workflow

```
Server Setup → Network Configuration → Client Access → Testing → Go Live
```

## 🖥️ PHASE 1: Server Setup

### 1.1 Yêu cầu hệ thống server
- **OS:** Windows Server 2016/2019/2022 hoặc Windows 10/11 Pro
- **RAM:** Tối thiểu 8GB, khuyến nghị 16GB
- **Storage:** Tối thiểu 50GB free space
- **Network:** Static IP address
- **Software:** XAMPP (Apache, MySQL, PHP)

### 1.2 Cài đặt XAMPP trên Server
```bash
1. Download XAMPP từ: https://www.apachefriends.org/
2. Run installer với Administrator privileges
3. Install path: C:\xampp
4. Components: Apache, MySQL, PHP, phpMyAdmin
5. Bỏ qua Bitnami modules
6. Complete installation
```

### 1.3 Deploy Web Application
```bash
1. Copy toàn bộ project folder đến server
2. Destination: C:\xampp\htdocs\it-service-request\
3. Đảm bảo các folder quan trọng:
   - api/
   - assets/
   - config/
   - lib/
   - uploads/
   - database/
```

### 1.4 Database Setup
```bash
1. Start MySQL service từ XAMPP Control Panel
2. Mở phpMyAdmin: http://localhost/phpmyadmin
3. Create database: it_service_request
4. Import database từ file: database/it_service_request.sql
5. Verify tables created successfully
```

## 🔧 PHASE 2: Network Configuration

### 2.1 Lấy Server IP Address
```bash
1. Mở Command Prompt với Administrator
2. Chạy: ipconfig
3. Tìm IPv4 Address (ví dụ: 192.168.1.100)
4. Ghi lại IP address này
```

### 2.2 Configure Apache cho Network Access
```bash
# Method 1: Tự động với script
.\server-setup.bat

# Method 2: Manual
1. Mở file: C:\xampp\apache\conf\httpd.conf
2. Tìm dòng: Listen 127.0.0.1:80
3. Thay đổi thành: Listen 0.0.0.0:80
4. Save và restart Apache
```

### 2.3 Configure MySQL cho Remote Access
```bash
# Method 1: Tự động với script
.\server-setup.bat

# Method 2: Manual
1. Mở file: C:\xampp\mysql\bin\my.ini
2. Tìm dòng: bind-address = 127.0.0.1
3. Thay đổi thành: bind-address = 0.0.0.0
4. Save và restart MySQL
```

### 2.4 Configure Windows Firewall
```bash
# Method 1: Tự động với script
.\server-setup.bat

# Method 2: Manual
1. Mở Windows Defender Firewall
2. Advanced Settings → Inbound Rules
3. Add Rule cho Apache:
   - Port: 80
   - Protocol: TCP
   - Action: Allow
   - Name: Apache HTTP Server
4. Add Rule cho MySQL:
   - Port: 3306
   - Protocol: TCP
   - Action: Allow
   - Name: MySQL Server
```

### 2.5 Create Database User cho Network
```sql
-- Connect to MySQL với root user
CREATE USER IF NOT EXISTS 'it_service_user'@'%' IDENTIFIED BY 'ServerAccess123!';
GRANT ALL PRIVILEGES ON it_service_request.* TO 'it_service_user'@'%';
FLUSH PRIVILEGES;
```

## 🌐 PHASE 3: Application Configuration

### 3.1 Update Database Configuration
```php
// File: config/database.php
// Thay đổi localhost thành server IP
$host = '192.168.1.100'; // Update với server IP của bạn
```

### 3.2 Update JavaScript URLs
```javascript
// Files: assets/js/app.js, assets/js/request-detail.js
// Thay đổi localhost thành server IP
const BASE_URL = 'http://192.168.1.100/it-service-request';
```

### 3.3 Update File Access Configuration
```php
// File: api/attachment.php
// Update CORS headers cho network access
header("Access-Control-Allow-Origin: *");
```

### 3.4 Create Network Configuration File
```php
// File: config/network.php
<?php
define('SERVER_IP', '192.168.1.100');
define('BASE_URL', 'http://192.168.1.100/it-service-request');
define('API_URL', 'http://192.168.1.100/it-service-request/api/');
define('ALLOWED_ORIGINS', ['http://192.168.1.100', 'http://localhost']);
?>
```

## 🖥️ PHASE 4: Client Access Methods

### Method 1: Browser Access (Khuyên dùng cho quick start)
```bash
# User chỉ cần:
1. Mở browser (Chrome, Firefox, Edge)
2. Gõ URL: http://192.168.1.100/it-service-request
3. Login với tài khoản
4. Sử dụng ứng dụng

# ✅ Ưu điểm:
- Không cần cài đặt gì trên client
- Hoạt động trên bất kỳ máy nào có browser
- Dễ maintenance
```

### Method 2: Desktop Application (Professional)
```bash
# Trên server:
1. Cập nhật server IP trong main.js
2. Build desktop app: npm run build-win
3. Tạo installer: IT Service Request Setup 1.0.0.exe
4. Copy installer đến client machines

# Trên client:
1. Double-click installer
2. Install → Finish
3. Desktop shortcut created
4. Double-click icon để mở app

# ✅ Ưu điểm:
- Professional desktop experience
- Native window
- Auto-connect to server
- Error handling built-in
```

### Method 3: Browser Shortcut (Nhanh nhất)
```bash
# Tạo shortcut trên desktop:
1. Right-click desktop → New → Shortcut
2. Location: http://192.168.1.100/it-service-request
3. Name: IT Service Request
4. Finish

# User double-click shortcut → Mở browser → Vào app
```

## 🧪 PHASE 5: Testing

### 5.1 Server Side Testing
```bash
1. Test từ server machine:
   - Mở browser: http://localhost/it-service-request
   - Verify app loads correctly
   - Test login functionality
   - Test file upload/download

2. Test services:
   - Apache: http://localhost
   - MySQL: phpMyAdmin access
   - File permissions: uploads folder
```

### 5.2 Client Side Testing
```bash
1. Network connectivity:
   - Ping server: ping 192.168.1.100
   - Telnet Apache: telnet 192.168.1.100 80
   - Telnet MySQL: telnet 192.168.1.100 3306

2. Application access:
   - Browser: http://192.168.1.100/it-service-request
   - Login với different user roles
   - Test all features
   - Verify file access

3. Desktop app (if used):
   - Install on client machine
   - Test auto-connection
   - Test server error handling
```

### 5.3 File Access Testing
```bash
1. User upload file từ máy A
2. Staff access file từ máy B
3. Verify download works
4. Test permissions (admin, staff, user)
```

## 📋 PHASE 6: Deployment Checklist

### Pre-Deployment Checklist
- [ ] XAMPP installed on server
- [ ] Static IP configured
- [ ] Project files copied
- [ ] Database imported
- [ ] Apache configured for network
- [ ] MySQL configured for network
- [ ] Firewall rules created
- [ ] Database user created
- [ ] Application URLs updated
- [ ] File permissions set

### Post-Deployment Checklist
- [ ] Server accessible via IP
- [ ] Web app loads correctly
- [ ] Login functionality works
- [ ] File upload works
- [ ] File download works
- [ ] All user roles functional
- [ ] Desktop app installs (if used)
- [ ] Error handling works
- [ ] Performance acceptable

## 🔧 Troubleshooting

### Common Issues & Solutions

#### 1. "Connection Refused"
```
Cause: Apache not running or blocked
Solution:
- Check Apache service in XAMPP Control Panel
- Verify firewall rules
- Check network connectivity
```

#### 2. "Database Connection Failed"
```
Cause: MySQL not accessible or wrong credentials
Solution:
- Check MySQL service
- Verify database user permissions
- Check bind-address configuration
```

#### 3. "File Not Found"
```
Cause: File permissions or wrong paths
Solution:
- Check uploads folder permissions
- Verify file exists on server
- Check attachment.php configuration
```

#### 4. "Slow Loading"
```
Cause: Network bandwidth or server load
Solution:
- Check network speed
- Optimize images
- Enable caching
- Check server resources
```

#### 5. "Desktop App Won't Connect"
```
Cause: Wrong server IP or server offline
Solution:
- Verify server IP in main.js
- Check server status
- Test browser access first
```

### Debug Commands
```bash
# Check server status
netstat -an | findstr :80
netstat -an | findstr :3306

# Test connectivity
ping 192.168.1.100
telnet 192.168.1.100 80

# Check logs
C:\xampp\apache\logs\access.log
C:\xampp\apache\logs\error.log
C:\xampp\mysql\data\mysql.err
```

## 📊 Performance Optimization

### Server Optimization
```apache
# Enable compression in httpd.conf
LoadModule deflate_module modules/mod_deflate.so
LoadModule expires_module modules/mod_expires.so

# Enable caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresDefault "access plus 1 week"
</IfModule>
```

### Database Optimization
```ini
# In my.ini
innodb_buffer_pool_size = 256M
max_connections = 100
query_cache_size = 64M
```

### Application Optimization
```php
// Enable PHP caching
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=4000
```

## 🔒 Security Considerations

### Network Security
- [ ] Firewall configured properly
- [ ] Only necessary ports open
- [ ] VPN access for remote users
- [ ] Regular security updates

### Application Security
- [ ] Strong passwords for database
- [ ] HTTPS enabled (optional)
- [ ] File upload restrictions
- [ ] Input validation
- [ ] Regular backups

### User Security
- [ ] Strong password policy
- [ ] Role-based access control
- [ ] Session timeout
- [ ] Audit logging

## 📞 Support & Maintenance

### Support Contacts
```
IT Support: ext. 1234
Server Admin: ext. 9999
Emergency: ext. 8888
Email: it-support@company.com
```

### Maintenance Schedule
```
Daily:
- Check server status
- Monitor error logs
- Verify backup completion

Weekly:
- Update security patches
- Check disk space
- Performance monitoring

Monthly:
- Database maintenance
- Log cleanup
- Security audit
```

### Backup Strategy
```
Database:
- Daily backup to network share
- Weekly backup to external drive
- Monthly off-site backup

Files:
- Weekly backup of uploads folder
- Version control for code
- Disaster recovery plan
```

## 🎯 Go Live Checklist

### Final Verification
- [ ] All tests passed
- [ ] Documentation complete
- [ ] User training done
- [ ] Support team ready
- [ ] Backup system active
- [ ] Monitoring in place

### User Communication
- [ ] Announce deployment
- [ ] Provide access instructions
- [ ] Share support contacts
- [ ] Training schedule
- [ ] Feedback collection

### Post-Launch
- [ ] Monitor system performance
- [ ] Collect user feedback
- [ ] Address issues promptly
- [ ] Plan improvements
- [ ] Regular maintenance

## 📚 Quick Reference

### URLs
```
Server IP: 192.168.1.100 (update với IP của bạn)
Web App: http://192.168.1.100/it-service-request
phpMyAdmin: http://192.168.1.100/phpmyadmin
```

### Commands
```bash
# Start services
net start Apache2.4
net start mysql

# Restart services
net stop Apache2.4 && net start Apache2.4
net stop mysql && net start mysql

# Test connectivity
ping 192.168.1.100
curl http://192.168.1.100/it-service-request
```

### File Locations
```
Web App: C:\xampp\htdocs\it-service-request\
Apache Config: C:\xampp\apache\conf\httpd.conf
MySQL Config: C:\xampp\mysql\bin\my.ini
Logs: C:\xampp\apache\logs\
Uploads: C:\xampp\htdocs\it-service-request\uploads\
```

## 🎉 Success Criteria

Deployment thành công khi:
- ✅ Server accessible từ tất cả máy trong mạng
- ✅ User có thể truy cập qua browser
- ✅ Tất cả tính năng hoạt động đúng
- ✅ File upload/download working
- ✅ Desktop app installs và hoạt động (nếu dùng)
- ✅ Performance acceptable
- ✅ Security measures in place
- ✅ Support system ready

---

**🚊 Sau khi hoàn thành các bước trong guide này, hệ thống IT Service Request của bạn sẽ sẵn sàng phục vụ tất cả users trong network!**
