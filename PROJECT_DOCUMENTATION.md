# IT Service Request Management System - Tài Liệu Dự Án Chi Tiết

## 📋 Tổng Quan Dự Án

**IT Service Request Management System** là một hệ thống quản lý yêu cầu dịch vụ CNTT hoàn chỉnh, được phát triển bằng PHP, MySQL, HTML/CSS/JavaScript. Hệ thống hỗ trợ đa ngôn ngữ (Việt Nam, Anh, Hàn Quốc), quản lý người dùng, yêu cầu dịch vụ, file đính kèm, thông báo và nhiều tính năng khác.

---

## 🗂️ Cấu Trúc Thư Mục và Chức Năng Chi Tiết

### 📄 **File Chính (Root Level)**

| File | Chức Năng | Mục Đích | Nhiệm Vụ |
|------|-----------|----------|-----------|
| `index.html` | Giao diện chính của ứng dụng | Trang chủ hiển thị dashboard, danh sách yêu cầu | Hiển thị giao diện người dùng chính, quản lý yêu cầu, thống kê |
| `request-detail.html` | Trang chi tiết yêu cầu | Hiển thị thông tin chi tiết của một yêu cầu | Xem chi tiết, bình luận, cập nhật trạng thái yêu cầu |
| `profile.php` | Quản lý hồ sơ người dùng | Trang quản lý thông tin cá nhân | Cập nhật thông tin, đổi mật khẩu, xem lịch sử |
| `README.md` | Tài liệu dự án | Hướng dẫn cài đặt và sử dụng | Cung cấp thông tin cho người phát triển |
| `PROJECT_STRUCTURE.md` | Tài liệu cấu trúc | Mô tả cấu trúc dự án | Giúp hiểu về tổ chức file và thư mục |
| `.htaccess` | Cấu hình Apache | Bảo mật và tối ưu hóa server | Cấu hình security headers, caching, compression |

---

### 🔧 **API Endpoints (`/api/`)**

| File | Chức Năng | Mục Đích | Nhiệm Vụ |
|------|-----------|----------|-----------|
| `auth.php` | Xác thực người dùng | Đăng nhập, đăng ký, đăng xuất | Quản lý session, kiểm tra quyền truy cập |
| `service_requests.php` | Quản lý yêu cầu dịch vụ | CRUD operations cho yêu cầu | Tạo, đọc, cập nhật, xóa yêu cầu dịch vụ |
| `categories.php` | Quản lý danh mục | Quản lý loại dịch vụ | Thêm, sửa, xóa danh mục yêu cầu |
| `comments.php` | Quản lý bình luận | Bình luận cho yêu cầu | Thêm, xóa bình luận trên yêu cầu |
| `departments.php` | Quản lý phòng ban | Quản lý bộ phận tổ chức | CRUD cho phòng ban, phân công nhân sự |
| `users.php` | Quản lý người dùng | Quản lý tài khoản | CRUD cho user, phân quyền |
| `notifications.php` | Hệ thống thông báo | Gửi thông báo real-time | Quản lý thông báo cho người dùng |
| `profile.php` | API hồ sơ người dùng | Xử lý dữ liệu profile | Cập nhật thông tin cá nhân qua API |
| `attachments.php` | Quản lý file đính kèm | Upload/download files | Xử lý file đính kèm cho yêu cầu |
| `reject_requests.php` | Quản lý yêu cầu từ chối | Xử lý yêu cầu bị từ chối | CRUD cho yêu cầu bị từ chối |
| `support_requests.php` | Quản lý yêu cầu hỗ trợ | Yêu cầu hỗ trợ kỹ thuật | CRUD cho ticket hỗ trợ |
| `software_updates.php` | Quản lý cập nhật phần mềm | Update management | Quản lý phiên bản và cập nhật |
| `evaluations.php` | Đánh giá yêu cầu | Evaluation system | Đánh giá chất lượng dịch vụ |
| `download.php` | Tải file | File download handler | Xử lý download file an toàn |
| `reject_request_attachment.php` | File đính kèm yêu cầu từ chối | Attachments cho reject | Quản lý file của yêu cầu từ chối |
| `support_request_attachment.php` | File đính kèm yêu cầu hỗ trợ | Attachments cho support | Quản lý file của yêu cầu hỗ trợ |
| `download_complete_attachment.php` | Tải file hoàn chỉnh | Complete file download | Download file đã xử lý xong |
| `uploads/` | Thư mục upload tạm | Temporary upload storage | Lưu trữ file tạm thời khi upload |

---

### 🎨 **Assets (`/assets/`)**

#### CSS Stylesheets
| File | Chức Năng | Mục Đích | Nhiệm Vụ |
|------|-----------|----------|-----------|
| `css/style.css` | Style chính của ứng dụng | Giao diện chung | Định dạng toàn bộ UI, responsive design |
| `css/profile.css` | Style cho trang profile | Giao diện hồ sơ | Định dạng trang quản lý thông tin cá nhân |

#### JavaScript Files
| File | Chức Năng | Mục Đích | Nhiệm Vụ |
|------|-----------|----------|-----------|
| `app.js` | Logic chính của ứng dụng | Core functionality | Quản lý dashboard, yêu cầu, tương tác người dùng |
| `request-detail.js` | Chi tiết yêu cầu | Request detail functionality | Xử lý trang chi tiết, bình luận, cập nhật |
| `profile.js` | Quản lý hồ sơ | Profile management | Xử lý form cập nhật thông tin cá nhân |
| `notifications.js` | Hệ thống thông báo | Notification system | Hiển thị thông báo real-time |
| `translation.js` | Đa ngôn ngữ | Multilingual support | Chuyển đổi ngôn ngữ, tải file ngôn ngữ |
| `toast-notifications.js` | Toast notification | Small notifications | Hiển thị thông báo nhỏ, success/error messages |
| `advanced-notifications.js` | Thông báo nâng cao | Advanced notifications | Hệ thống thông báo phức tạp hơn |
| `departments-manager.js` | Quản lý phòng ban | Department management | Quản lý CRUD phòng ban qua UI |
| `department-helper.js` | Helper cho phòng ban | Department utilities | Hàm hỗ trợ xử lý phòng ban |
| `support-reject-manager.js` | Quản lý hỗ trợ/từ chối | Support/reject management | Xử lý workflow hỗ trợ và từ chối |

#### Language Files (`/assets/js/languages/`)
| File | Chức Năng | Mục Đích | Nhiệm Vụ |
|------|-----------|----------|-----------|
| `vi.js` | Ngôn ngữ Việt Nam | Tiếng Việt (default) | Bản dịch tiếng Việt cho toàn bộ UI |
| `en.js` | Ngôn ngữ Anh | English translation | Bản dịch tiếng Anh |
| `ko.js` | Ngôn ngữ Hàn | Korean translation | Bản dịch tiếng Hàn |

#### Images & Sounds
| Thư Mục | Chức Năng | Mục Đích | Nhiệm Vụ |
|---------|-----------|----------|-----------|
| `images/` | Hình ảnh ứng dụng | UI images | Logo, icons, hình ảnh giao diện |
| `sounds/` | Âm thanh thông báo | Notification sounds | File âm thanh cho thông báo |

---

### ⚙️ **Configuration (`/config/`)**

| File | Chức Năng | Mục Đích | Nhiệm Vụ |
|------|-----------|----------|-----------|
| `database.php` | Cấu hình database | Database connection | Kết nối MySQL, helper functions |
| `email.php` | Cấu hình email | Email settings | Cấu hình SMTP, email settings |
| `session.php` | Cấu hình session | Session management | Bảo mật session, timeout, encryption |

---

### 🗄️ **Database Schema (`/database/`)**

| File | Chức Năng | Mục Đích | Nhiệm Vụ |
|------|-----------|----------|-----------|
| `create_database.sql` | Tạo database chính | Main database schema | Tạo tất cả tables cơ bản |
| `create_all_attachment_tables.sql` | Tables file đính kèm | Attachment tables | Tạo tables lưu trữ file |
| `create_departments_table.sql` | Table phòng ban | Departments table | Tạo table quản lý phòng ban |
| `create_notifications_table.sql` | Table thông báo | Notifications table | Tạo table lưu thông báo |
| `create_reject_requests_table.sql` | Table yêu cầu từ chối | Reject requests table | Tạo table yêu cầu bị từ chối |
| `create_support_requests_table.sql` | Table yêu cầu hỗ trợ | Support requests table | Tạo table ticket hỗ trợ |
| `create_support_request_attachments_table.sql` | File đính kèm hỗ trợ | Support attachments | Table file cho yêu cầu hỗ trợ |
| `create_enhanced_evaluation_schema.sql` | Schema đánh giá nâng cao | Evaluation schema | Table đánh giá chất lượng |
| `create_request_feedback_table.sql` | Table feedback | Feedback table | Lưu phản hồi từ người dùng |
| `create_resolutions_table.sql` | Table giải pháp | Resolutions table | Lưu giải pháp cho yêu cầu |
| `add_accepted_at_column.sql` | Thêm cột accepted | Add accepted timestamp | Thêm thời gian chấp nhận |
| `add_resolution_columns.sql` | Thêm cột giải pháp | Add resolution data | Thêm thông tin giải pháp |
| `add_software_feedback.sql` | Feedback phần mềm | Software feedback | Thêm feedback cho software |
| `update_feedback_table.sql` | Cập nhật feedback | Update feedback schema | Cập nhật cấu trúc feedback |
| `update_request_support_status.sql` | Cập nhật trạng thái | Update status logic | Cập nhật logic trạng thái |
| `create_request_rejections_table.sql` | Table từ chối | Rejections table | Lưu lý do từ chối |

---

### 📚 **Libraries (`/lib/`)**

| File | Chức Năng | Mục Đích | Nhiệm Vụ |
|------|-----------|----------|-----------|
| `EmailHelper.php` | Helper email cơ bản | Basic email functionality | Gửi email thông thường |
| `FileEmailHelper.php` | Email với file | File attachment email | Gửi email có đính kèm file |
| `GmailEmailHelper.php` | Gmail integration | Gmail API integration | Gửi email qua Gmail |
| `ImprovedEmailHelper.php` | Email nâng cao | Enhanced email features | Email với template, formatting |
| `NotificationHelper.php` | Helper thông báo | Notification helper | Xử lý các loại thông báo |
| `PHPMailerEmailHelper.php` | PHPMailer wrapper | PHPMailer integration | Sử dụng PHPMailer library |

---

### 📁 **Uploads (`/uploads/`)**

| Thư Mục/File | Chức Năng | Mục Đích | Nhiệm Vụ |
|--------------|-----------|----------|-----------|
| `.htaccess` | Bảo mật uploads | Upload security | Chặn truy cập trực tiếp, chỉ cho phép download |
| `requests/` | File yêu cầu dịch vụ | Service request files | Lưu file đính kèm của yêu cầu dịch vụ |
| `support_requests/` | File hỗ trợ | Support request files | Lưu file của ticket hỗ trợ |
| `reject_requests/` | File từ chối | Reject request files | Lưu file của yêu cầu bị từ chối |
| `complete_requests/` | File hoàn tất | Completed request files | Lưu file của yêu cầu đã xử lý xong |

---

### 📝 **Logs (`/logs/`)**

| File | Chức Năng | Mục Đích | Nhiệm Vụ |
|------|-----------|----------|-----------|
| `api_errors.log` | Log lỗi API | API error tracking | Ghi lại lỗi từ các API endpoints |
| `email_activity.log` | Log email | Email activity monitoring | Ghi lại hoạt động gửi email |

---

### 🔌 **Vendor (`/vendor/`)**

| File/Thư Mục | Chức Năng | Mục Đích | Nhiệm Vụ |
|--------------|-----------|----------|-----------|
| `phpmailer/` | PHPMailer library | Email sending library | Thư viện gửi email chuyên nghiệp |
| `autoload.php` | Autoloader | Class autoloading | Tự động tải các class PHP |

---

### 🔒 **Git (`.git/`)**

| Thư Mục | Chức Năng | Mục Đích | Nhiệm Vụ |
|---------|-----------|----------|-----------|
| `.git/` | Version control | Git repository | Quản lý phiên bản, lịch sử thay đổi |

---

## 🔄 **Luồng Hoạt Động Chính**

### 1. **Luồng Đăng Nhập**
```
index.html → auth.php → session.php → dashboard
```

### 2. **Luồng Tạo Yêu Cầu**
```
index.html → service_requests.php → database → email notification
```

### 3. **Luồng Xử Lý Yêu Cầu**
```
request-detail.html → service_requests.php → notifications → email
```

### 4. **Luồng Upload File**
```
form → attachment.php → uploads/ → database record
```

---

## 🛡️ **Bảo Mật**

### 1. **Database Security**
- Sử dụng prepared statements
- Hash password với PASSWORD_DEFAULT
- Input sanitization

### 2. **File Security**
- .htaccess protection cho uploads
- File type validation
- Path traversal prevention

### 3. **Session Security**
- Secure session configuration
- Session timeout
- CSRF protection

### 4. **API Security**
- CORS configuration
- Input validation
- Error handling

---

## 🌐 **Đa Ngôn Ngữ**

### Hỗ trợ 3 ngôn ngữ:
- **Tiếng Việt** (vi.js) - Ngôn ngữ mặc định
- **Tiếng Anh** (en.js) - English translation
- **Tiếng Hàn** (ko.js) - Korean translation

### Tính năng:
- Auto-detect browser language
- Persistent language preference
- Real-time language switching
- Fallback mechanism

---

## 📊 **Database Schema**

### Tables chính:
1. **users** - Quản lý người dùng
2. **service_requests** - Yêu cầu dịch vụ
3. **categories** - Danh mục dịch vụ
4. **comments** - Bình luận
5. **departments** - Phòng ban
6. **notifications** - Thông báo
7. **attachments** - File đính kèm
8. **support_requests** - Yêu cầu hỗ trợ
9. **reject_requests** - Yêu cầu từ chối
10. **evaluations** - Đánh giá

---

## 🚀 **Tính Năng Nổi Bật**

### 1. **Core Features**
- ✅ User authentication & authorization
- ✅ Service request management
- ✅ File upload & attachment handling
- ✅ Email notifications
- ✅ Real-time notifications
- ✅ Multilingual support
- ✅ Responsive design

### 2. **Advanced Features**
- ✅ Department management
- ✅ Support request system
- ✅ Reject request workflow
- ✅ Evaluation system
- ✅ Advanced notifications
- ✅ User profile management

---

## 📈 **Performance & Optimization**

### 1. **Frontend Optimization**
- CSS/JS minification
- Image optimization
- Caching headers
- Lazy loading

### 2. **Backend Optimization**
- Database indexing
- Query optimization
- Connection pooling
- Error logging

### 3. **Security Optimization**
- Input validation
- SQL injection prevention
- XSS protection
- CSRF protection

---

## 🔧 **Maintenance & Monitoring**

### 1. **Regular Tasks**
- Monitor error logs
- Clean up old uploads
- Update language files
- Database optimization

### 2. **Security Monitoring**
- Review access logs
- Update dependencies
- Security audits
- Backup procedures

---

## 📝 **Ghi Chú Phát Triển**

### Coding Standards:
- PHP PSR-12
- JavaScript ES6+
- CSS BEM methodology
- RESTful API design

### Best Practices:
- Error handling
- Logging
- Documentation
- Testing

---

**Tài liệu này được tạo ngày: 2026-03-19**  
**Phiên bản dự án: 1.0.0**  
**Trạng thái: Production Ready**
