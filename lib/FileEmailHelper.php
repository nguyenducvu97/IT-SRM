<?php
// Tạm thời ghi email ra file để test
class FileEmailHelper {
    private $log_file;
    
    public function __construct() {
        $this->log_file = __DIR__ . "/../logs/email_debug.log";
    }
    
    public function sendEmail($to, $toName, $subject, $body) {
        $log_entry = sprintf(
            "[%s] EMAIL_TO_FILE | To: %s | Subject: %s\n%s\n%s\n",
            date("Y-m-d H:i:s"),
            $to,
            $subject,
            str_repeat("-", 80),
            $body
        );
        
        file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
        return true; // Luôn trả về success
    }
    
    public function sendNewRequestNotification($request_data) {
        $subject = "🔔 Yêu cầu dịch vụ mới #" . $request_data["id"];
        
        $body = "<h2>📋 Yêu cầu dịch vụ mới</h2>
                <p><strong>Mã yêu cầu:</strong> #" . $request_data["id"] . "</p>
                <p><strong>Tiêu đề:</strong> " . htmlspecialchars($request_data["title"]) . "</p>
                <p><strong>Người tạo:</strong> " . htmlspecialchars($request_data["requester_name"]) . "</p>
                <p><strong>Danh mục:</strong> " . htmlspecialchars($request_data["category"]) . "</p>
                <p><strong>Ưu tiên:</strong> " . htmlspecialchars($request_data["priority"]) . "</p>
                <p><strong>Mô tả:</strong> " . nl2br(htmlspecialchars($request_data["description"])) . "</p>
                <hr>
                <p>Vui lòng đăng nhập hệ thống để xem chi tiết và xử lý.</p>
                <p><em>IT Service Request System</em></p>";
        
        return $this->sendEmail(
            "ndvu@sgitech.com.vn",
            "IT Support",
            $subject,
            $body
        );
    }
    
    public function sendStatusUpdateNotification($request_data, $assigned_name = null) {
        $status_text = '';
        switch($request_data['status']) {
            case 'in_progress':
                $status_text = 'Đang xử lý';
                break;
            case 'resolved':
                $status_text = 'Đã giải quyết';
                break;
            case 'closed':
                $status_text = 'Đã đóng';
                break;
            case 'open':
                $status_text = 'Mở';
                break;
            default:
                $status_text = $request_data['status'];
        }
        
        $subject = "📝 Cập nhật trạng thái yêu cầu #" . $request_data["id"];
        
        $body = "<h2>📋 Cập nhật trạng thái yêu cầu</h2>
                <p><strong>Mã yêu cầu:</strong> #" . $request_data["id"] . "</p>
                <p><strong>Tiêu đề:</strong> " . htmlspecialchars($request_data["title"]) . "</p>
                <p><strong>Trạng thái mới:</strong> <span style='color: #007bff; font-weight: bold;'>{$status_text}</span></p>";
        
        if ($assigned_name && $request_data['status'] == 'in_progress') {
            $body .= "<p><strong>Người xử lý:</strong> " . htmlspecialchars($assigned_name) . "</p>";
            $body .= "<p style='color: #28a745;'><strong>✅ Yêu cầu của bạn đã được tiếp nhận và đang được xử lý!</strong></p>";
        }
        
        $body .= "<p><strong>Mô tả:</strong> " . nl2br(htmlspecialchars($request_data["description"])) . "</p>
                 <hr>
                 <p>Vui lòng đăng nhập hệ thống để xem chi tiết và theo dõi tiến độ.</p>
                 <p><em>IT Service Request System</em></p>";
        
        return $this->sendEmail(
            $request_data["requester_email"],
            $request_data["requester_name"],
            $subject,
            $body
        );
    }
}
?>
