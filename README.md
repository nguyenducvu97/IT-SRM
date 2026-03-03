# IT Service Request Management System

Hệ thống quản lý yêu cầu dịch vụ CNTT được phát triển với PHP, MySQL và HTML/CSS/JavaScript, tương thích với XAMPP.

## Tính năng

- **Quản lý người dùng**: Đăng nhập, đăng ký, phân quyền (admin, staff, user)
- **Quản lý yêu cầu**: Tạo, xem, cập nhật trạng thái yêu cầu dịch vụ
- **Phân loại yêu cầu**: Theo danh mục, mức độ ưu tiên, trạng thái
- **Bình luận**: Thêm bình luận cho các yêu cầu
- **Dashboard**: Thống kê và theo dõi yêu cầu gần đây
- **Responsive Design**: Tương thích với máy tính và thiết bị di động

## Yêu cầu hệ thống

- XAMPP (Apache + MySQL + PHP)
- PHP 7.4 hoặc cao hơn
- MySQL 5.7 hoặc cao hơn
- Trình duyệt web hiện đại

## Cài đặt

### 1. Cài đặt XAMPP

1. Tải và cài đặt XAMPP từ [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Khởi động Apache và MySQL từ XAMPP Control Panel

### 2. Tạo database

1. Mở trình duyệt, truy cập http://localhost/phpmyadmin
2. Tạo database mới tên là `it_service_request`
3. Import file SQL từ `database/create_database.sql`

Hoặc chạy lệnh SQL thủ công:

```sql
-- Copy nội dung từ file database/create_database.sql
```

### 3. Cấu hình project

1. Copy thư mục `it-service-request` vào `C:/xampp/htdocs/`
2. Đảm bảo cấu trúc thư mục như sau:
```
htdocs/
└── it-service-request/
    ├── api/
    │   ├── auth.php
    │   ├── categories.php
    │   ├── comments.php
    │   └── service_requests.php
    ├── assets/
    │   ├── css/
    │   │   └── style.css
    │   └── js/
    │       └── app.js
    ├── config/
    │   └── database.php
    ├── database/
    │   └── create_database.sql
    └── index.html
```

### 4. Kiểm tra cấu hình

Mở file `config/database.php` và đảm bảo các thông số sau:

```php
private $host = "localhost";
private $db_name = "it_service_request";
private $username = "root";
private $password = ""; // Mật khẩu MySQL của bạn
```

## Sử dụng

### 1. Khởi động hệ thống

1. Mở XAMPP Control Panel
2. Start Apache và MySQL
3. Mở trình duyệt và truy cập: `http://localhost/it-service-request/`

### 2. Đăng nhập lần đầu

Sử dụng tài khoản admin mặc định:
- Username: `admin`
- Password: `admin123`

### 3. Tạo người dùng mới

1. Đăng nhập với tài khoản admin
2. Đăng ký tài khoản mới qua form đăng ký
3. Admin có thể phân quyền người dùng trong database

## Cấu trúc Database

### Tables

- **users**: Thông tin người dùng
- **categories**: Danh mục dịch vụ
- **service_requests**: Yêu cầu dịch vụ
- **comments**: Bình luận cho yêu cầu
- **attachments**: File đính kèm (chưa triển khai)

### Mối quan hệ

- users có thể tạo nhiều service_requests
- service_requests thuộc về một category
- service_requests có thể có nhiều comments
- service_requests có thể được gán cho một user (staff)

## API Endpoints

### Authentication
- `POST /api/auth.php` - Login, Register, Logout

### Service Requests
- `GET /api/service_requests.php?action=list` - Danh sách yêu cầu
- `GET /api/service_requests.php?action=get&id={id}` - Chi tiết yêu cầu
- `POST /api/service_requests.php` - Tạo yêu cầu mới
- `PUT /api/service_requests.php` - Cập nhật yêu cầu
- `DELETE /api/service_requests.php?id={id}` - Xóa yêu cầu (admin only)

### Categories
- `GET /api/categories.php` - Danh sách danh mục
- `POST /api/categories.php` - Tạo danh mục (admin only)
- `PUT /api/categories.php` - Cập nhật danh mục (admin only)
- `DELETE /api/categories.php?id={id}` - Xóa danh mục (admin only)

### Comments
- `POST /api/comments.php` - Thêm bình luận
- `DELETE /api/comments.php?id={id}` - Xóa bình luận

## Phân quyền

### Admin
- Quản lý tất cả yêu cầu
- Quản lý danh mục
- Quản lý người dùng (chưa triển khai đầy đủ)
- Xóa yêu cầu

### Staff
- Xem tất cả yêu cầu
- Cập nhật trạng thái yêu cầu
- Gán yêu cầu cho bản thân
- Thêm bình luận

### User
- Tạo yêu cầu mới
- Xem yêu cầu của mình
- Thêm bình luận cho yêu cầu của mình

## Tùy chỉnh

### Thay đổi thông tin database

Sửa file `config/database.php`:

```php
private $host = "localhost"; // Server MySQL
private $db_name = "it_service_request"; // Tên database
private $username = "root"; // Username MySQL
private $password = ""; // Password MySQL
```

### Thay đổi giao diện

- CSS: `assets/css/style.css`
- HTML: `index.html`
- JavaScript: `assets/js/app.js`

### Thêm tính năng mới

1. Tạo API endpoint trong thư mục `api/`
2. Thêm logic xử lý trong JavaScript
3. Cập nhật giao diện nếu cần

## Khắc phục sự cố

### 1. Lỗi kết nối database

Kiểm tra:
- MySQL đã chạy chưa
- Tên database đúng chưa
- Username/password MySQL đúng chưa

### 2. Lỗi 404

Kiểm tra:
- Apache đã chạy chưa
- Project đã đặt trong `htdocs/` chưa
- URL đúng chưa

### 3. Lỗi PHP

Kiểm tra:
- Phiên bản PHP có tương thích không
- Extension PDO đã bật chưa
- Error reporting trong file PHP

### 4. Lỗi CORS

Thêm header vào file PHP:
```php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
```

## Bảo mật

- Sử dụng prepared statements để tránh SQL injection
- Hash password với password_hash()
- Validate input data
- Session-based authentication
- CORS headers cho API

## Tính năng sẽ triển khai

- File upload/attachment
- Email notifications
- Advanced reporting
- User management interface
- API documentation
- Multi-language support

## Hỗ trợ

Nếu gặp vấn đề, kiểm tra:
1. Log lỗi Apache: `C:/xampp/apache/logs/error.log`
2. Log lỗi PHP: `C:/xampp/php/logs/php_error_log`
3. Console log trình duyệt (F12)

## License

MIT License - Có thể sử dụng và chỉnh sửa tự do.
