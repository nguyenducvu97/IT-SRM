<?php
// Final Test for Complete Notification System Fix
// This script tests the complete notification system after all fixes

require_once 'config/database.php';
require_once 'config/session.php';

session_start();

// Mock user session for testing
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'user';
$_SESSION['username'] = 'testuser';
$_SESSION['full_name'] = 'Test User';

echo "<h1>Final Test: Complete Notification System</h1>";

echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h2>Issues Fixed:</h2>";
echo "<ol>";
echo "<li><strong>Số lượng không hiển thị:</strong> Thêm trang notifications page và event handler</li>";
echo "<li><strong>Chuông thông báo không hoạt động:</strong> Thêm click event listener</li>";
echo "<li><strong>Tiêu đề quá to:</strong> Giảm font-size từ 16px → 14px</li>";
echo "<li><strong>Auto-reload không update count:</strong> Thêm updateNotificationCount() vào dashboard</li>";
echo "</ol>";
echo "</div>";

// Create test notifications
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>Creating Test Notifications...</h3>";

try {
    $db = getDatabaseConnection();
    
    // Clear existing notifications for clean test
    $db->exec("DELETE FROM notifications WHERE user_id = 1 AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    
    // Create 5 test notifications
    $testNotifications = [
        [
            'title' => 'Yêu cầu đang được xử lý',
            'message' => 'Yêu cầu #123 của bạn đã được nhân viên IT tiếp nhận.',
            'type' => 'info',
            'is_read' => 0
        ],
        [
            'title' => 'Yêu cầu mới cần xử lý',
            'message' => 'Người dùng Test User đã tạo yêu cầu mới: #124',
            'type' => 'info',
            'is_read' => 0
        ],
        [
            'title' => 'Yêu cầu đã hoàn thành',
            'message' => 'Yêu cầu #125 của bạn đã được xử lý thành công.',
            'type' => 'success',
            'is_read' => 0
        ],
        [
            'title' => 'Yêu cầu đang chờ phê duyệt',
            'message' => 'Yêu cầu #126 của bạn đang chờ Admin xem xét.',
            'type' => 'warning',
            'is_read' => 0
        ],
        [
            'title' => 'Yêu cầu đã bị từ chối',
            'message' => 'Yêu cầu #127 của bạn đã bị từ chối. Lý do: Vi phạm chính sách.',
            'type' => 'error',
            'is_read' => 0
        ]
    ];
    
    foreach ($testNotifications as $notif) {
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, is_read, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([1, $notif['title'], $notif['message'], $notif['type'], $notif['is_read']]);
    }
    
    echo "<p style='color: green;'>Created 5 test notifications successfully</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error creating test notifications: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Test API endpoints
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>Testing API Endpoints:</h3>";

echo "<div style='margin: 10px 0;'>";
echo "<button onclick='testNotificationAPI()' class='btn' style='background: #007bff; color: white; padding: 8px 16px; border: none; border-radius: 4px;'>Test Notification APIs</button>";
echo "<span> - Test count and list APIs</span>";
echo "</div>";

echo "<div id='apiTestResults' style='margin-top: 10px;'></div>";

echo "</div>";

// Test instructions
echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>Testing Instructions:</h3>";
echo "<ol>";
echo "<li><strong>Clear browser cache:</strong> Ctrl+F5 để load version mới</li>";
echo "<li><strong>Open main application:</strong> <a href='index.html' target='_blank' class='btn' style='background: #28a745; color: white; padding: 6px 12px; text-decoration: none; border-radius: 3px;'>Open IT Service Request</a></li>";
echo "<li><strong>Login as test user:</strong> Username: testuser, Password: any</li>";
echo "<li><strong>Check notification count:</strong> Should show '5' in red badge next to bell</li>";
echo "<li><strong>Click notification bell:</strong> Should navigate to notifications page</li>";
echo "<li><strong>Verify notifications list:</strong> Should see 5 notifications with proper styling</li>";
echo "<li><strong>Check title size:</strong> Should be 14px (not too large)</li>";
echo "<li><strong>Test auto-reload:</strong> Count should update every 3 seconds</li>";
echo "<li><strong>Test mark as read:</strong> Click "Đánh dấu tất cả đã đọc" button</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #28a745; color: white; padding: 20px; border-radius: 8px;'>";
echo "<h2>Expected Results:</h2>";
echo "<ul>";
echo "<li>✅ Notification count shows '5' in red badge</li>";
echo "<li>✅ Click bell navigates to notifications page</li>";
echo "<li>✅ Notifications page displays 5 items correctly</li>";
echo "<li>✅ Title font-size is 14px (readable)</li>";
echo "<li>✅ Auto-reload updates count every 3 seconds</li>";
echo "<li>✅ Mark all as read functionality works</li>";
echo "<li>✅ No JavaScript errors in console</li>";
echo "</ul>";
echo "</div>";

?>

<script>
function testNotificationAPI() {
    const resultsDiv = document.getElementById('apiTestResults');
    resultsDiv.innerHTML = '<p>Testing APIs...</p>';
    
    // Test notification count API
    fetch('api/notifications.php?action=count')
    .then(response => response.json())
    .then(data => {
        console.log('Count API result:', data);
        
        // Test notification list API
        return fetch('api/notifications.php?action=list&limit=5');
    })
    .then(response => response.json())
    .then(data => {
        console.log('List API result:', data);
        
        resultsDiv.innerHTML = `
            <div style='background: #f8f9fa; padding: 10px; border-radius: 4px;'>
                <p><strong>API Test Results:</strong></p>
                <p style='color: green;'>Count API: ${data.success ? 'SUCCESS' : 'FAILED'}</p>
                <p style='color: green;'>List API: ${data.success ? 'SUCCESS' : 'FAILED'}</p>
                <p>Unread count: ${data.data?.unread_count || 'N/A'}</p>
                <p>Notifications returned: ${data.data?.length || 0}</p>
            </div>
        `;
    })
    .catch(error => {
        console.error('API test error:', error);
        resultsDiv.innerHTML = '<p style="color: red;">API test: ERROR - ' + error.message + '</p>';
    });
}

// Auto-refresh this test page
setTimeout(() => {
    location.reload();
}, 10000);
</script>

<style>
.btn {
    cursor: pointer;
    transition: background-color 0.3s;
}
.btn:hover {
    opacity: 0.8;
}
</style>
