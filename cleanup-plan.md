# 🧹 DỰ ÁN CLEANUP PLAN - IT Service Request Management

## 📊 Phân tích hiện tại

### 📁 Tổng quan file/folder:
- **Total files:** ~350+ files
- **Total size:** ~50MB+ (chủ yếu logs)
- **Core files:** ~50 files
- **Debug/Test files:** ~20+ files
- **Log files:** ~50+ files (19MB+)

## 🗑️ Files đề xuất XÓA (Safe to remove)

### 1. **TEST FILES** (11 files - ~50KB)
```
test-date-filter-simple.php
test-date-filter.php
test-kpi-fix.php
test-kpi-modal-fix.php
test-login-simple.php
test-search-auth.php
test-search-authenticated.php
test-search-debug.php
test-search-functionality.php
test-search-simple.php
test-search-with-session.php
```
**Lý do:** Debug files tạm thời, không cần thiết cho production

### 2. **DEBUG FILES** (2 files - ~35KB)
```
debug-modal-css.php
debug-modal-issue.php
```
**Lý do:** Debug tools cho modal issue, đã fix xong

### 3. **TEMPORARY HTML FILES** (3 files - ~30KB)
```
check-event-conflicts.html
admin-kpi-config-new.html
admin-kpi-config.html
kpi-export-form.html
quick-test.php
test-kpi-modal.html
test-search-debug.html
test-search-simple.html
```
**Lý do:** Test UI files, không phải production code

### 4. **OLD LOG FILES** (40+ files - ~18MB)
```
email_2026-04-21_*.eml (40+ files)
notification_debug.log
```
**Lý do:** Old email logs từ tháng 4, debug logs không cần thiết

### 5. **DOCUMENTATION TEMP** (1 file - ~6KB)
```
notification-system-status.md
```
**Lý do:** Temporary documentation, có thể xóa

## ✅ Files GIỮ LẠI (Essential)

### **Core Application:**
- `index.html` - Main application (103KB)
- `request-detail.html` - Request detail page (61KB)
- `profile.php` - User profile (16KB)
- `sw.js` - Service worker (10KB)

### **API Endpoints:**
- `api/` folder (33 files) - All API endpoints

### **Configuration:**
- `config/` folder (5 files) - Database, email, session configs
- `autoloader.php` - Autoloader
- `.htaccess` - Apache config

### **Database:**
- `database/` folder (21 files) - SQL schemas and migrations

### **Libraries:**
- `lib/` folder (4 files) - Helper classes
- `vendor/` folder (4 files) - External libraries

### **Assets:**
- `assets/` folder (26 files) - CSS, JS, images, sounds

### **User Data:**
- `uploads/` folder (297 files) - User uploaded files (IMPORTANT!)

### **Essential Logs:**
- `logs/api_errors.log` - Error tracking (19MB)
- `logs/email_activity.log` - Email activity (113KB)
- `logs/email_queue.json` - Email queue (30KB)

## 🎯 KẾT QUẢ CLEANUP

### **Sau khi cleanup:**
- **Files removed:** ~60 files
- **Space saved:** ~18MB+
- **Files remaining:** ~290 files
- **Core functionality:** 100% intact
- **User data:** 100% preserved

### **Lợi ích:**
- ✅ **Dễ maintain hơn** - Ít file rác
- ✅ **Deploy nhanh hơn** - Ít file hơn
- ✅ **Backup nhỏ hơn** - Giảm storage
- ✅ **Code cleaner** - Chỉ giữ essential files

## 🚀 Commands to Execute

### **Windows (PowerShell):**
```powershell
# Remove test files
Remove-Item test-*.php, debug-*.php, check-*.html, admin-kpi-config*.html, kpi-export-form.html, quick-test.php, test-*.html

# Remove old email logs (keep recent ones)
Remove-Item logs/email_2026-04-*.eml

# Remove debug log
Remove-Item logs/notification_debug.log

# Remove temp documentation
Remove-Item notification-system-status.md
```

### **Unix/Linux:**
```bash
# Remove test files
rm test-*.php debug-*.php check-*.html admin-kpi-config*.html kpi-export-form.html quick-test.php test-*.html

# Remove old email logs
rm logs/email_2026-04-*.eml

# Remove debug log
rm logs/notification_debug.log

# Remove temp documentation
rm notification-system-status.md
```

## ⚠️ CẢNH BÁO

### **KHÔNG XÓA:**
- ❌ `uploads/` folder - User data!
- ❌ `logs/api_errors.log` - Error tracking
- ❌ `logs/email_activity.log` - Email monitoring
- ❌ `database/` folder - Schema files
- ❌ `config/` folder - Configuration files
- ❌ Core application files

### **BACKUP TRƯỚC KHI CLEANUP:**
1. Export database
2. Zip uploads folder
3. Commit current state to Git
4. Test after cleanup

## 📋 Check List

- [ ] Backup database
- [ ] Backup uploads folder
- [ ] Git commit current state
- [ ] Remove test files
- [ ] Remove debug files
- [ ] Remove old logs
- [ ] Test core functionality
- [ ] Test KPI modal
- [ ] Test file uploads
- [ ] Test API endpoints
- [ ] Final backup

## 🎉 Expected Result

Sau cleanup, dự án sẽ:
- **Gọn gàng hơn** - Chỉ giữ essential files
- **Fast hơn** - Ít files để scan
- **Clean hơn** - Không còn debug rác
- **Maintainable** - Dễ dàng maintain và develop

**🚀 Ready for production deployment!**
