# 🔧 Configuration Guide for Port 3005

## 📋 Server Information
- **IP Address:** `192.168.220.25`
- **Port:** `3005`
- **Full URL:** `http://192.168.220.25:3005/it-service-request`

## 🚀 Quick Setup Commands

### **Method 1: Automatic Configuration**
```bash
# Run this script on server
.\configure-custom-port.bat
```

### **Method 2: Manual Configuration**
```bash
# Follow the manual steps below
```

## 🔧 Manual Configuration Steps

### **1. Apache Port Configuration**
```apache
# File: C:\xampp\apache\conf\httpd.conf
# Find and replace:
Listen 80
# With:
Listen 3005

# Also replace:
Listen 127.0.0.1:80
# With:
Listen 127.0.0.1:3005
```

### **2. VirtualHost Configuration**
```apache
# File: C:\xampp\apache\conf\extra\httpd-vhosts.conf
# Add or update:
<VirtualHost *:3005>
    DocumentRoot "C:/xampp/htdocs/it-service-request"
    ServerName 192.168.220.25
    ServerAlias it-service.local
</VirtualHost>
```

### **3. Database Configuration**
```php
// File: config/database.php
// Update host:
$host = '192.168.220.25';
```

### **4. Network Configuration**
```php
// File: config/network.php
<?php
define('SERVER_IP', '192.168.220.25');
define('SERVER_PORT', '3005');
define('BASE_URL', 'http://192.168.220.25:3005/it-service-request');
define('API_URL', 'http://192.168.220.25:3005/it-service-request/api/');
define('ALLOWED_ORIGINS', ['http://192.168.220.25:3005']);
?>
```

### **5. JavaScript Files**
```javascript
// Files: assets/js/app.js, assets/js/request-detail.js
// Update URLs:
const BASE_URL = 'http://192.168.220.25:3005/it-service-request';
const API_BASE_URL = 'http://192.168.220.25:3005/it-service-request/api/';
```

### **6. Desktop App Configuration**
```javascript
// File: main.js
// Update server URL:
const SERVER_IP = '192.168.220.25';
const SERVER_URL = 'http://192.168.220.25:3005/it-service-request';
```

### **7. Firewall Configuration**
```bash
# Remove old rule and add new one
netsh advfirewall firewall delete rule name="Apache HTTP Server"
netsh advfirewall firewall add rule name="Apache HTTP Server" dir=in action=allow protocol=TCP localport=3005
```

## 🌐 Access URLs

### **Web Application**
```
Main URL: http://192.168.220.25:3005/it-service-request
API URL:  http://192.168.220.25:3005/it-service-request/api/
```

### **File Access**
```
Download: http://192.168.220.25:3005/it-service-request/api/attachment.php?file=filename.pdf&action=download
View:     http://192.168.220.25:3005/it-service-request/api/attachment.php?file=filename.jpg&action=view
```

### **Desktop App**
```
Server URL: http://192.168.220.25:3005/it-service-request
```

## 🧪 Testing Commands

### **Server Side Testing**
```bash
# Test Apache on new port
curl http://192.168.220.25:3005/it-service-request

# Test port connectivity
telnet 192.168.220.25 3005

# Check Apache listening
netstat -an | findstr :3005
```

### **Client Side Testing**
```bash
# Test network connectivity
ping 192.168.220.25

# Test web access
# Open browser: http://192.168.220.25:3005/it-service-request

# Test port
telnet 192.168.220.25 3005
```

## 📱 Client Access Methods

### **Method 1: Browser Access**
```
User opens browser → Types: http://192.168.220.25:3005/it-service-request → Login → Use
```

### **Method 2: Desktop Shortcut**
```
Create shortcut with target: http://192.168.220.25:3005/it-service-request
```

### **Method 3: Desktop App**
```
Build with: npm run build-win
Install on client machines
```

## 🔍 Troubleshooting

### **Common Issues**

#### 1. "Port 3005 not accessible"
```
Cause: Apache not listening on port 3005
Solution:
- Check httpd.conf for Listen 3005
- Restart Apache service
- Check firewall rules
```

#### 2. "Connection refused"
```
Cause: Apache not running or blocked
Solution:
- Start Apache service
- Check firewall
- Verify port configuration
```

#### 3. "404 Not Found"
```
Cause: Wrong DocumentRoot or VirtualHost
Solution:
- Check VirtualHost configuration
- Verify file paths
- Restart Apache
```

#### 4. "Database connection failed"
```
Cause: Database host not updated
Solution:
- Update config/database.php
- Check MySQL service
- Verify network access
```

### **Debug Commands**
```bash
# Check Apache status
net start Apache2.4

# Check listening ports
netstat -an | findstr :3005

# Check Apache logs
type "C:\xampp\apache\logs\error.log"

# Test web server
curl -v http://192.168.220.25:3005/
```

## 🚀 Deployment Checklist

### **Pre-Deployment**
- [ ] Apache configured for port 3005
- [ ] VirtualHost configured
- [ ] Database host updated
- [ ] JavaScript URLs updated
- [ ] Desktop app configured
- [ ] Firewall rules updated
- [ ] Network configuration created

### **Post-Deployment**
- [ ] Apache restarted successfully
- [ ] Server accessible via port 3005
- [ ] Web application loads correctly
- [ ] Database connection works
- [ ] File upload/download works
- [ ] Client machines can access
- [ ] Desktop app connects (if used)

## 📊 Port Information

### **Why Port 3005?**
- Custom port for dedicated application
- Avoids conflicts with default ports
- Easy to remember and configure
- Works well with firewall rules

### **Port Usage**
```
Port 80:   Default HTTP (avoiding conflicts)
Port 443:  Default HTTPS (avoiding conflicts)
Port 3005: Custom application port (using this)
Port 3306: MySQL (unchanged)
```

## 🔒 Security Considerations

### **Firewall Rules**
```bash
# Allow only specific IPs if needed
netsh advfirewall firewall add rule name="Apache HTTP Server" dir=in action=allow protocol=TCP localport=3005 remoteip=192.168.220.0/24
```

### **Apache Security**
```apache
# In VirtualHost configuration
<Directory "C:/xampp/htdocs/it-service-request">
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
    
    # Security headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
</Directory>
```

## 📞 Support Information

### **Quick Commands for Support**
```bash
# Check server status
curl http://192.168.220.25:3005/it-service-request

# Check port connectivity
telnet 192.168.220.25 3005

# Restart services
net stop Apache2.4 && net start Apache2.4

# Check logs
type "C:\xampp\apache\logs\error.log" | more
```

### **Common Support Questions**
```
Q: Why port 3005 instead of 80?
A: Avoids conflicts with other web services

Q: Can I use a different port?
A: Yes, update all configurations consistently

Q: How to test if port is open?
A: Use telnet 192.168.220.25 3005

Q: Firewall blocking access?
A: Add rule for port 3005 in Windows Firewall
```

## 🎯 Success Criteria

Deployment thành công khi:
- ✅ Apache running on port 3005
- ✅ Application accessible at http://192.168.220.25:3005/it-service-request
- ✅ All features working correctly
- ✅ Client machines can access
- ✅ File upload/download working
- ✅ Desktop app connects (if used)

---

**🚊 Sau khi cấu hình xong, ứng dụng sẽ hoạt động tại: http://192.168.220.25:3005/it-service-request**
