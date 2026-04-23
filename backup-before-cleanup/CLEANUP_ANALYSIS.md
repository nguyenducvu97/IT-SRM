# Phân tích File Có Thể Xóa - IT Service Request Project

## 📊 Tổng quan
- Tổng số file ở root: 71 files
- File cần giữ: ~20 files
- File có thể xóa: ~51 files (test, debug, temporary)

---

## ✅ FILES CẦN GIỮ (Production Files)

### Core Application Files
1. **index.html** - Trang chủ chính
2. **request-detail.html** - Trang chi tiết yêu cầu
3. **profile.php** - Trang profile
4. **.htaccess** - Apache configuration
5. **sw.js** - Service Worker

### API & Configuration
6. **api/** - Thư mục API endpoints (30 files)
7. **config/** - Thư mục config (5 files)
8. **lib/** - Thư mục libraries (4 files)
9. **database/** - Thư mục database schemas (21 files)
10. **assets/** - Thư mục assets (CSS, JS, images)
11. **scripts/** - Thư mục scripts (4 files)
12. **vendor/** - Thư mục vendor dependencies (4 files)
13. **uploads/** - Thư mục uploads (272 items)
14. **logs/** - Thư mục logs (50 items)
15. **cron/** - Thư mục cron jobs
16. **docs/** - Thư mục documentation

### Helper Files
17. **autoloader.php** - Autoloader class

### Administrative
18. **admin-kpi-config.html** - Cấu hình KPI admin
19. **kpi-export-form.html** - Form export KPI
20. **restart-apache.bat** - Script restart Apache (dev tool)

---

## ❌ FILES CÓ THỂ XÓA (Test/Debug/Temporary Files)

### Test Files (22 files)
1. **test-accept-javascript.html** - Test JavaScript accept
2. **test-all-notifications-complete.php** - Test notifications hoàn chỉnh
3. **test-comprehensive-email-fix.php** - Test fix email
4. **test-create-notification.php** - Test create notification
5. **test-email-no-login.php** - Test email không login
6. **test-formdata-email-fix.php** - Test FormData email fix
7. **test-full-notification-flow.php** - Test full notification flow
8. **test-notification-helper-instance.php** - Test notification helper
9. **test-notification-logger.php** - Test notification logger
10. **test-notifications-database-only.php** - Test database notifications
11. **test-notifications-final-verification.php** - Test verification cuối
12. **test-notifications-simple.php** - Test notifications đơn giản
13. **test-single-method.php** - Test single method
14. **test-staff-accept.php** - Test staff accept
15. **test-staff-notification-fix.php** - Test staff notification fix
16. **test-staff-notification-new-request.php** - Test staff notification
17. **test-standard-email-template.php** - Test email template
18. **test_k4_values.php** - Test K4 values
19. **test_processing_results.php** - Test processing results
20. **test_would_recommend.php** - Test would recommend
21. **create-and-test.php** - Create và test
22. **create-test-request.php** - Create test request

### Debug Files (7 files)
23. **debug-accept-186.php** - Debug accept request 186
24. **debug-accept-real.php** - Debug accept real
25. **debug-accept-request-500.php** - Debug accept request 500
26. **debug-create-request.php** - Debug create request
27. **debug-failed-methods.php** - Debug failed methods
28. **debug-notification-failures.php** - Debug notification failures
29. **debug-staff-notification-issue.php** - Debug staff notification

### Check Files (6 files)
30. **check-categories.php** - Check categories
31. **check-notifications.php** - Check notifications
32. **check-open-requests.php** - Check open requests
33. **check-request-152.php** - Check request 152
34. **check-table-structure.php** - Check table structure
35. **check-users-table.php** - Check users table

### Fix Files (3 files)
36. **fix-all-email-issues.php** - Fix all email issues
37. **fix-email-accept-request.php** - Fix email accept request
38. **quick-smtp-fix.php** - Quick SMTP fix

### Install/Setup Files (3 files)
39. **install-phpmailer.php** - Install PHPMailer
40. **manual-install-phpmailer.php** - Manual install PHPMailer
41. **add-status-column.php** - Add status column (migration script)

### Temporary/Control Files (3 files)
42. **disable_email_queue.php** - Disable email queue
43. **email_control.php** - Email control
44. **find-open-request.php** - Find open request

### Documentation/Analysis Files (6 files)
45. **STAFF_ACCEPT_REQUEST_TEST_SUMMARY.md** - Test summary
46. **cleanup-test-files-analysis.md** - Cleanup analysis
47. **notification-requirements-analysis.md** - Notification analysis
48. **notification-system-analysis.md** - System analysis
49. **notification-system-status.md** - System status (CÓ THỂ GIỮ)
50. **explain-background-processing.php** - Explain background processing

### Archive/Unused Files (2 files)
51. **phpmailer.zip** - PHPMailer archive (đã cài xong)
52. **pop3-email-helper.php** - Unused POP3 helper
53. **cookies.txt** - Temporary cookies file

---

## 🔍 KIỂM TRA SỬ DỤNG

### Files được require/include:
- ✅ Không có file test/debug/check/fix nào được require
- ✅ Không có file pop3-email-helper.php được sử dụng
- ✅ Không có file phpmailer.zip được sử dụng

### Files có thể cần giữ lại:
- **notification-system-status.md** - Documentation về status hiện tại
- **restart-apache.bat** - Dev tool useful
- **admin-kpi-config.html** - Production feature
- **kpi-export-form.html** - Production feature

---

## 📋 DANH SÁCH XÓA ĐƯỢC (50 files)

### Test Files (22 files)
```
test-accept-javascript.html
test-all-notifications-complete.php
test-comprehensive-email-fix.php
test-create-notification.php
test-email-no-login.php
test-formdata-email-fix.php
test-full-notification-flow.php
test-notification-helper-instance.php
test-notification-logger.php
test-notifications-database-only.php
test-notifications-final-verification.php
test-notifications-simple.php
test-single-method.php
test-staff-accept.php
test-staff-notification-fix.php
test-staff-notification-new-request.php
test-standard-email-template.php
test_k4_values.php
test_processing_results.php
test_would_recommend.php
create-and-test.php
create-test-request.php
```

### Debug Files (7 files)
```
debug-accept-186.php
debug-accept-real.php
debug-accept-request-500.php
debug-create-request.php
debug-failed-methods.php
debug-notification-failures.php
debug-staff-notification-issue.php
```

### Check Files (6 files)
```
check-categories.php
check-notifications.php
check-open-requests.php
check-request-152.php
check-table-structure.php
check-users-table.php
```

### Fix Files (3 files)
```
fix-all-email-issues.php
fix-email-accept-request.php
quick-smtp-fix.php
```

### Install/Setup Files (3 files)
```
install-phpmailer.php
manual-install-phpmailer.php
add-status-column.php
```

### Temporary/Control Files (3 files)
```
disable_email_queue.php
email_control.php
find-open-request.php
```

### Documentation Files (5 files)
```
STAFF_ACCEPT_REQUEST_TEST_SUMMARY.md
cleanup-test-files-analysis.md
notification-requirements-analysis.md
notification-system-analysis.md
explain-background-processing.php
```

### Archive/Unused Files (2 files)
```
phpmailer.zip
pop3-email-helper.php
cookies.txt
```

---

## ⚠️ LƯU Ý

### Files CẦN XÁC NHẬN TRƯỚC KHI XÓA:
1. **notification-system-status.md** - Documentation useful, nên giữ
2. **restart-apache.bat** - Dev tool, có thể giữ
3. **admin-kpi-config.html** - Production feature, KEEP
4. **kpi-export-form.html** - Production feature, KEEP

### Files KHÔNG ĐƯỢC XÓA:
- Tất cả file trong thư mục: api/, config/, lib/, database/, assets/, scripts/, vendor/
- index.html, request-detail.html, profile.php
- .htaccess, sw.js, autoloader.php

---

## 🎯 KHUYẾN NGHỊ

### Option 1: Xóa tất cả test/debug files (50 files)
- Tối ưu nhất cho production
- Giảm 50 files ở root
- Không ảnh hưởng chức năng

### Option 2: Giữ lại documentation (1 file)
- Giữ notification-system-status.md
- Xóa 49 files còn lại
- Có reference khi cần

### Option 3: Tạo thư mục archive cho test files
- Tạo folder: /archive/test-files/
- Di chuyển tất cả test files vào đó
- Giữ nguyên nếu cần test lại sau này

---

## ✅ KẾT LUẬN

**Có thể xóa an toàn:** 50 files
**Nên giữ documentation:** notification-system-status.md
**Số files sau khi xóa:** ~21 files ở root (giảm từ 71)

**Không ảnh hưởng đến dự án đang hoạt động tốt.**
