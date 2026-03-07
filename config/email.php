<?php
// Email configuration for IT Service Request Management System

class EmailConfig {
    // SMTP Configuration
    private static $smtpHost = "gw.sgitech.com.vn";
    private static $smtpPort = 25; // Port 25 với STARTTLS
    private static $smtpUsername = "ndvu"; // Hoặc ndvu@sgitech.com.vn tùy thuộc vào cấu hình server
    private static $smtpPassword = "ndvu"; // Thay bằng password thực tế
    private static $smtpEncryption = ""; // Tắt STARTTLS
    private static $fromEmail = "ndvu@sgitech.com.vn";
    private static $fromName = "IT Service Request System";
    
    // Email templates
    private static $templates = [
        'new_request' => [
            'subject' => 'Yêu cầu dịch vụ mới #{request_id}',
            'body' => 'Chào {recipient_name},<br><br>
                     Có một yêu cầu dịch vụ mới đã được tạo:<br><br>
                     <strong>Tiêu đề:</strong> {title}<br>
                     <strong>Người tạo:</strong> {requester_name}<br>
                     <strong>Danh mục:</strong> {category}<br>
                     <strong>Ưu tiên:</strong> {priority}<br>
                     <strong>Mô tả:</strong> {description}<br><br>
                     Vui lòng đăng nhập hệ thống để xem chi tiết và xử lý.<br><br>
                     Trân trọng,<br>
                     IT Service Request System'
        ],
        'status_update' => [
            'subject' => 'Cập nhật trạng thái yêu cầu #{request_id}',
            'body' => 'Chào {recipient_name},<br><br>
                     Yêu cầu của bạn đã được cập nhật trạng thái:<br><br>
                     <strong>Tiêu đề:</strong> {title}<br>
                     <strong>Trạng thái mới:</strong> {status}<br>
                     <strong>Người cập nhật:</strong> {updated_by}<br><br>
                     Đăng nhập hệ thống để xem chi tiết.<br><br>
                     Trân trọng,<br>
                     IT Service Request System'
        ],
        'new_comment' => [
            'subject' => 'Bình luận mới cho yêu cầu #{request_id}',
            'body' => 'Chào {recipient_name},<br><br>
                     Có một bình luận mới cho yêu cầu:<br><br>
                     <strong>Tiêu đề:</strong> {title}<br>
                     <strong>Người bình luận:</strong> {commenter_name}<br>
                     <strong>Nội dung:</strong> {comment}<br><br>
                     Đăng nhập hệ thống để xem chi tiết.<br><br>
                     Trân trọng,<br>
                     IT Service Request System'
        ],
        'request_resolved' => [
            'subject' => 'Yêu cầu #{request_id} đã được giải quyết',
            'body' => 'Chào {recipient_name},<br><br>
                     Yêu cầu của bạn đã được giải quyết:<br><br>
                     <strong>Tiêu đề:</strong> {title}<br>
                     <strong>Người giải quyết:</strong> {resolver_name}<br>
                     <strong>Mô tả lỗi:</strong> {error_description}<br>
                     <strong>Cách khắc phục:</strong> {solution_method}<br><br>
                     Vui lòng kiểm tra và xác nhận nếu vấn đề đã được giải quyết.<br><br>
                     Trân trọng,<br>
                     IT Service Request System'
        ],
        'request_assigned' => [
            'subject' => 'Bạn được giao yêu cầu #{request_id}',
            'body' => 'Chào {recipient_name},<br><br>
                     Bạn đã được giao một yêu cầu mới:<br><br>
                     <strong>Tiêu đề:</strong> {title}<br>
                     <strong>Người tạo:</strong> {requester_name}<br>
                     <strong>Danh mục:</strong> {category}<br>
                     <strong>Ưu tiên:</strong> {priority}<br>
                     <strong>Mô tả:</strong> {description}<br><br>
                     Vui lòng đăng nhập hệ thống để xem chi tiết và xử lý.<br><br>
                     Trân trọng,<br>
                     IT Service Request System'
        ]
    ];
    
    public static function getSmtpConfig() {
        return [
            'host' => self::$smtpHost,
            'port' => self::$smtpPort,
            'username' => self::$smtpUsername,
            'password' => self::$smtpPassword,
            'encryption' => self::$smtpEncryption,
            'from_email' => self::$fromEmail,
            'from_name' => self::$fromName
        ];
    }
    
    public static function getTemplate($templateType) {
        return self::$templates[$templateType] ?? null;
    }
    
    public static function updateSmtpConfig($host, $port, $username, $password, $encryption = 'tls') {
        self::$smtpHost = $host;
        self::$smtpPort = $port;
        self::$smtpUsername = $username;
        self::$smtpPassword = $password;
        self::$smtpEncryption = $encryption;
    }
    
    public static function updateFromEmail($email, $name) {
        self::$fromEmail = $email;
        self::$fromName = $name;
    }
}
?>
