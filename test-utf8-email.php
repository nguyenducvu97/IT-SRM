<?php
// Test email with UTF-8 encoding fix
require_once 'lib/ImprovedEmailHelper.php';

echo "=== Testing Email with UTF-8 Fix ===\n\n";

$emailHelper = new ImprovedEmailHelper();

// Test with Vietnamese characters
$test_request = [
    'id' => 1001,
    'title' => 'Yêu cầu sửa máy tính - Lỗi font tiếng Việt',
    'requester_name' => 'Nguyễn Văn A',
    'category' => 'Phần cứng',
    'priority' => 'Cao',
    'description' => 'Máy tính không khởi động được, màn hình hiện thông báo lỗi. Cần hỗ trợ kỹ thuật khẩn cấp.'
];

$result = $emailHelper->sendNewRequestNotification($test_request);

echo "Email result: " . ($result ? "✅ SUCCESS" : "❌ FAILED") . "\n\n";

// Check email log
echo "Latest email activity:\n";
$log_file = __DIR__ . '/logs/email_activity.log';
if (file_exists($log_file)) {
    $logs = file_get_contents($log_file);
    $lines = explode("\n", trim($logs));
    $last_line = end($lines);
    echo $last_line . "\n";
}

echo "\n=== Kiểm tra email của bạn ===\n";
echo "Email đã được gửi với:\n";
echo "- Tiêu đề: 🔔 Yêu cầu dịch vụ mới #1001\n";
echo "- Nội dung có chứa tiếng Việt: Nguyễn Văn A, Phần cứng, Cao, khẩn cấp\n";
echo "- Encoding: UTF-8 với Base64 encoding\n\n";
echo "Kiểm tra inbox để xem font tiếng Việt đã hiển thị đúng chưa!\n";
?>
