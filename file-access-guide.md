# 🗂️ File Access Guide for Network Deployment

## 📋 How Files Work in Network Environment

### **Current System Architecture**
```
Server (192.168.1.100)
├── C:\xampp\htdocs\it-service-request\
│   ├── uploads\                    ← All files stored here
│   │   ├── requests\               ← User uploaded files
│   │   ├── attachments\            ← Staff attachments
│   │   └── completed\              ← Completed request files
│   ├── api\
│   │   ├── attachment.php          ← Original file handler
│   │   └── attachment-network.php ← Network-optimized handler
│   └── database                    ← File metadata
```

### **File Flow Process**

#### 1. **User Uploads File**
```
User Machine → Server
├── User selects file in browser
├── Upload to: http://192.168.1.100/api/service_requests.php
├── File saved to: C:\xampp\htdocs\it-service-request\uploads\requests\2026\05\
├── Database record: filename, path, service_request_id
└── File accessible from ANY machine in network
```

#### 2. **Staff Downloads File**
```
Staff Machine → Server
├── Staff clicks "Download" button
├── Request: http://192.168.1.100/api/attachment.php?file=abc123.pdf
├── Server validates permissions
├── Server streams file content
└── Staff receives file on their machine
```

## 🔧 Network Configuration Updates

### **1. Update CORS Headers**
```php
// In attachment-network.php
header("Access-Control-Allow-Origin: *"); // Allow all network IPs
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
```

### **2. Update File URLs in JavaScript**
```javascript
// Update API base URL for network
const API_BASE_URL = 'http://192.168.1.100/it-service-request/api/';

// File download URLs
const fileUrl = `${API_BASE_URL}attachment.php?file=${fileName}&action=download`;
const viewUrl = `${API_BASE_URL}attachment.php?file=${fileName}&action=view`;
```

### **3. Apache Configuration for Files**
```apache
# In httpd-vhosts.conf
<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs/it-service-request"
    ServerName 192.168.1.100
    
    # Enable file access with proper headers
    <Directory "C:/xampp/htdocs/it-service-request/uploads">
        Options -Indexes +FollowSymLinks
        AllowOverride None
        Require all granted
        
        # Set proper MIME types
        <FilesMatch "\.(pdf|doc|docx|xls|xlsx)$">
            Header set Content-Disposition attachment
        </FilesMatch>
        
        <FilesMatch "\.(jpg|jpeg|png|gif)$">
            Header set Content-Disposition inline
        </FilesMatch>
    </Directory>
</VirtualHost>
```

## 🌐 Access Methods

### **Method 1: Direct Browser Access**
```
User can access files directly:
http://192.168.1.100/it-service-request/api/attachment.php?file=abc123.pdf

This will:
✅ Check authentication
✅ Validate permissions  
✅ Stream file content
✅ Work from any machine in network
```

### **Method 2: Through Application Interface**
```
Staff navigates to request detail:
http://192.168.1.100/it-service-request/request-detail.html?id=123

Click "Download" button → App calls API → Downloads file
```

### **Method 3: Network Share (Alternative)**
```bash
# Create network share for uploads folder
# Right-click C:\xampp\htdocs\it-service-request\uploads
# Properties → Sharing → Advanced Sharing
# Share name: ITRequestFiles
# Permissions: Read for Staff, Full for Admin

# Access from client machines:
\\192.168.1.100\ITRequestFiles
```

## 🔒 Security Considerations

### **1. File Access Permissions**
```php
// Current permission system:
✅ Admin: Can access ALL files
✅ Staff: Can access all request files + their own attachments
✅ User: Can only access their own uploaded files
✅ Resolution attachments: Admin + Staff only
```

### **2. Path Security**
```php
// Prevents directory traversal attacks
$dangerousPatterns = [
    '/\.\.[\/\\\\]/',     // No ../ or ..\
    '/^[\/\\\\]/',        // No starting with /
    '/[A-Za-z]:[\/\\\\]/', // No drive letters
];
```

### **3. File Type Validation**
```php
// Validates MIME types match extensions
if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif']) && strpos($mimeType, 'image/') !== 0) {
    // Reject corrupted image files
}
```

## 📊 Performance Optimization

### **1. File Caching**
```apache
# Enable browser caching for files
<FilesMatch "\.(pdf|doc|docx|xls|xlsx|jpg|jpeg|png|gif)$">
    ExpiresActive On
    ExpiresDefault "access plus 1 week"
    Header set Cache-Control "public"
</FilesMatch>
```

### **2. Compression**
```apache
# Enable gzip compression for text files
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>
```

### **3. Large File Handling**
```php
// Increase limits for large files
ini_set('upload_max_filesize', '50M');
ini_set('post_max_size', '50M');
ini_set('max_execution_time', '300');
ini_set('memory_limit', '256M');
```

## 🧪 Testing File Access

### **1. Test Upload**
```bash
# From any client machine:
curl -X POST -F "file=@test.pdf" -F "action=create_request" \
     http://192.168.1.100/it-service-request/api/service_requests.php
```

### **2. Test Download**
```bash
# From any client machine:
curl -O http://192.168.1.100/it-service-request/api/attachment.php?file=test.pdf
```

### **3. Test Permissions**
```bash
# Test different user roles:
# 1. Admin user - Should access all files
# 2. Staff user - Should access request files
# 3. Regular user - Should access own files only
```

## 🚀 Deployment Steps

### **1. Update Configuration**
```bash
# Update attachment.php for network access
# Copy attachment-network.php over attachment.php
# Or update CORS headers in existing file
```

### **2. Update JavaScript**
```javascript
// In app.js and request-detail.js
// Update API URLs to use server IP
const BASE_URL = 'http://192.168.1.100/it-service-request';
```

### **3. Test File Access**
```bash
# From different client machines:
# 1. Upload file as user
# 2. Access file as staff
# 3. Download file as admin
# 4. Verify permissions work correctly
```

## 📱 User Experience

### **For End Users:**
1. **Upload files normally** - No change in experience
2. **Files stored centrally** - On server, not local machine
3. **Access from anywhere** - Any computer in network
4. **Consistent experience** - Same interface for everyone

### **For Staff:**
1. **View attachments instantly** - No download delays
2. **Download when needed** - Click to download locally
3. **Access all request files** - From any computer
4. **Mobile access** - Works on tablets/laptops

### **For Admin:**
1. **Central file management** - All files in one location
2. **Backup simplified** - Backup server uploads folder
3. **Security control** - Manage permissions centrally
4. **Storage monitoring** - Track disk usage

## 🔧 Troubleshooting

### **Common Issues:**

#### 1. "File Not Found"
```
Cause: File path incorrect or file deleted
Solution: 
- Check file exists in uploads folder
- Verify database record
- Check file permissions
```

#### 2. "Access Denied"
```
Cause: User doesn't have permission
Solution:
- Check user role in database
- Verify file ownership
- Update permission logic
```

#### 3. "Slow Download"
```
Cause: Network bandwidth or server load
Solution:
- Enable file compression
- Optimize file sizes
- Check network speed
```

#### 4. "Corrupted Files"
```
Cause: Upload interrupted or storage issue
Solution:
- Re-upload file
- Check disk space
- Verify file integrity
```

## 📊 Storage Management

### **1. Monitor Disk Usage**
```bash
# Check uploads folder size
du -sh C:\xampp\htdocs\it-service-request\uploads

# Find large files
find C:\xampp\htdocs\it-service-request\uploads -type f -size +10M
```

### **2. Cleanup Old Files**
```bash
# Delete files older than 1 year
forfiles /p C:\xampp\htdocs\it-service-request\uploads /m *.* /d -365 /c "cmd /c del @path"
```

### **3. Backup Strategy**
```bash
# Daily backup of uploads
robocopy C:\xampp\htdocs\it-service-request\uploads \\backup-server\ITRequestFiles /E /MIR
```

## 🎯 Summary

### **How Files Work in Network:**
1. **Central Storage** - All files on server
2. **Database Metadata** - File info in database
3. **Permission Control** - Role-based access
4. **Network Access** - HTTP-based file serving
5. **Security** - Path validation and authentication

### **Benefits:**
- ✅ **Single Source of Truth** - All files in one place
- ✅ **Easy Backup** - Backup server only
- ✅ **Consistent Access** - Same experience everywhere
- ✅ **Security Control** - Centralized permissions
- ✅ **Scalability** - Support many users

**🎉 Staff có thể truy cập và download files từ bất kỳ máy tính nào trong mạng chỉ cần truy cập vào ứng dụng web!**
