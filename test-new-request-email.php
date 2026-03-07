<?php
// Test email notification for new requests
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'lib/PHPMailerEmailHelper.php';

// Start session and set test user
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'testuser';
$_SESSION['role'] = 'user';

// Create test request data
$test_request_data = [
    'id' => 'TEST-' . time(),
    'title' => 'Yêu cầu test thông báo email',
    'requester_name' => 'Người Test',
    'category' => 'Hardware',
    'priority' => 'high',
    'description' => 'Đây là yêu cầu test để kiểm tra chức năng gửi email thông báo đến admin và staff khi có yêu cầu mới.'
];

echo "<h2>🧪 Test Email Notification</h2>";
echo "<p><strong>Request ID:</strong> " . $test_request_data['id'] . "</p>";
echo "<p><strong>Title:</strong> " . $test_request_data['title'] . "</p>";
echo "<p><strong>Requester:</strong> " . $test_request_data['requester_name'] . "</p>";
echo "<p><strong>Category:</strong> " . $test_request_data['category'] . "</p>";
echo "<p><strong>Priority:</strong> " . $test_request_data['priority'] . "</p>";
echo "<hr>";

try {
    $emailHelper = new PHPMailerEmailHelper();
    $result = $emailHelper->sendNewRequestNotification($test_request_data);
    
    if ($result) {
        echo "<p style='color: green; font-weight: bold;'>✅ Email notification sent successfully!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Failed to send email notification</p>";
    }
    
    echo "<h3>📋 Admin/Staff Users in Database:</h3>";
    
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->prepare("SELECT id, username, full_name, email, role FROM users WHERE role IN ('admin', 'staff')");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($users)) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Role</th></tr>";
        
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['full_name']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['role']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "<p><strong>Total recipients:</strong> " . count($users) . "</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ No admin or staff users found in database!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>📝 Email Content Preview:</h3>";
echo "<div style='border: 1px solid #ccc; padding: 10px; background-color: #f9f9f9;'>";
echo "<h2>📋 Yêu cầu dịch vụ mới</h2>";
echo "<p><strong>Mã yêu cầu:</strong> #" . $test_request_data['id'] . "</p>";
echo "<p><strong>Tiêu đề:</strong> " . htmlspecialchars($test_request_data['title']) . "</p>";
echo "<p><strong>Người tạo:</strong> " . htmlspecialchars($test_request_data['requester_name']) . "</p>";
echo "<p><strong>Danh mục:</strong> " . htmlspecialchars($test_request_data['category']) . "</p>";
echo "<p><strong>Ưu tiên:</strong> " . htmlspecialchars($test_request_data['priority']) . "</p>";
echo "<p><strong>Mô tả:</strong> " . nl2br(htmlspecialchars($test_request_data['description'])) . "</p>";
echo "<hr>";
echo "<p>Vui lòng đăng nhập hệ thống để xem chi tiết và xử lý: <a href='http://localhost/it-service-request/'>http://localhost/it-service-request/</a></p>";
echo "<p><em>IT Service Request System</em></p>";
echo "</div>";

echo "<hr>";
echo "<p><a href='javascript:history.back()'>← Back</a></p>";
?>
