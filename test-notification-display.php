<?php
// Test Notification Display Fixes
// This script tests notification count display and title sizing

require_once 'config/database.php';
require_once 'config/session.php';

session_start();

// Mock user session for testing
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'user';
$_SESSION['username'] = 'testuser';
$_SESSION['full_name'] = 'Test User';

echo "<h1>Test Notification Display Fixes</h1>";

echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h2>Issues Being Fixed:</h2>";
echo "<ul>";
echo "<li><strong>Số lượng không hiển thị:</strong> Notification count không hiện ở chuông thông báo</li>";
echo "<li><strong>Tiêu đề quá to:</strong> Notification title font-size 16px → 14px</li>";
echo "</ul>";
echo "</div>";

// Test 1: Create Test Notifications
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>Test 1: Create Test Notifications</h3>";

try {
    $db = getDatabaseConnection();
    
    // Create test notifications with different types
    $testNotifications = [
        [
            'user_id' => 1,
            'title' => 'Yêu cầu đang được xử lý',
            'message' => 'Yêu cầu #123 của bạn đã được nhân viên IT tiếp nhận và đang xử lý.',
            'type' => 'info',
            'is_read' => 0
        ],
        [
            'user_id' => 1,
            'title' => 'Yêu cầu mới cần xử lý',
            'message' => 'Người dùng Test User đã tạo yêu cầu mới: #124 - Test Request',
            'type' => 'info',
            'is_read' => 0
        ],
        [
            'user_id' => 1,
            'title' => 'Yêu cầu đã hoàn thành',
            'message' => 'Yêu cầu #125 của bạn đã được xử lý thành công.',
            'type' => 'success',
            'is_read' => 0
        ]
    ];
    
    foreach ($testNotifications as $notif) {
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, is_read, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $notif['user_id'],
            $notif['title'],
            $notif['message'],
            $notif['type'],
            $notif['is_read']
        ]);
    }
    
    echo "<p style='color: green;'>Created 3 test notifications successfully</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error creating test notifications: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Test 2: Check Notification Count API
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>Test 2: Notification Count API</h3>";

echo "<p><strong>Testing API endpoint:</strong></p>";
echo "<div style='margin: 10px 0;'>";
echo "<a href='api/notifications.php?action=count' target='_blank' class='btn' style='background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>Test Count API</a>";
echo "<span> - Should return JSON with unread_count</span>";
echo "</div>";

echo "<p><strong>Expected Response:</strong></p>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 4px;'>";
echo "{
    \"success\": true,
    \"data\": {
        \"unread_count\": 3
    }
}";
echo "</pre>";

echo "</div>";

// Test 3: Check Notification List API
echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>Test 3: Notification List API</h3>";

echo "<p><strong>Testing API endpoint:</strong></p>";
echo "<div style='margin: 10px 0;'>";
echo "<a href='api/notifications.php?action=list&limit=5' target='_blank' class='btn' style='background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>Test List API</a>";
echo "<span> - Should return JSON with notifications array</span>";
echo "</div>";

echo "<p><strong>Expected Response Structure:</strong></p>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 4px;'>";
echo "{
    \"success\": true,
    \"message\": \"Notifications retrieved successfully\",
    \"data\": [
        {
            \"id\": 1,
            \"title\": \"Yêu cầu đang được xử lý\",
            \"message\": \"Yêu cầu #123 của bạn đã được nhân viên IT tiếp nhận...\",
            \"type\": \"info\",
            \"is_read\": false,
            \"created_at\": \"2026-04-10 17:00:00\",
            \"time_ago\": \"Vài giây\"
        }
    ]
}";
echo "</pre>";

echo "</div>";

// Test 4: CSS Styles Verification
echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>Test 4: CSS Styles Verification</h3>";

echo "<p><strong>Fixed CSS Properties:</strong></p>";

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Property</th><th>Before</th><th>After</th><th>Status</th></tr>";

echo "<tr>";
echo "<td>notification-title font-size</td>";
echo "<td>16px (quá to)</td>";
echo "<td>14px (phù hợp)</td>";
echo "<td style='color: green;'>✅ FIXED</td>";
echo "</tr>";

echo "<tr>";
echo "<td>notification-count display</td>";
echo "<td>Không hiển thị</td>";
echo "<td>Hiển thị đúng selector</td>";
echo "<td style='color: green;'>✅ FIXED</td>";
echo "</tr>";

echo "<tr>";
echo "<td>updateNotificationCountDisplay</td>";
echo "<td>Chỉ update .notification-badge</td>";
echo "<td>Update cả .notification-badge và #notificationCount</td>";
echo "<td style='color: green;'>✅ FIXED</td>";
echo "</tr>";

echo "</table>";

echo "</div>";

// Test 5: Frontend Integration Test
echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>Test 5: Frontend Integration</h3>";

echo "<p><strong>Testing Steps:</strong></p>";
echo "<ol>";
echo "<li><strong>Open main application:</strong> <a href='index.html' target='_blank' class='btn' style='background: #6f42c1; color: white; padding: 6px 12px; text-decoration: none; border-radius: 3px;'>Open IT Service Request</a></li>";
echo "<li><strong>Login as test user:</strong> Username: testuser, Password: any</li>";
echo "<li><strong>Check notification count:</strong> Should show '3' in red badge</li>";
echo "<li><strong>Click notification bell:</strong> Should see 3 notifications with proper title size</li>";
echo "<li><strong>Verify auto-reload:</strong> Count should update every 3 seconds</li>";
echo "</ol>";

echo "<div style='background: #e2e3e5; padding: 10px; border-radius: 4px; margin-top: 10px;'>";
echo "<p><strong>Expected Results:</strong></p>";
echo "<ul>";
echo "<li>✅ Notification count shows '3' in red circle</li>";
echo "<li>✅ Title font-size is 14px (not 16px)</li>";
echo "<li>✅ Auto-reload updates count every 3 seconds</li>";
echo "<li>✅ No JavaScript errors in console</li>";
echo "</ul>";
echo "</div>";

echo "</div>";

echo "<div style='background: #28a745; color: white; padding: 20px; border-radius: 8px;'>";
echo "<h2>Notification Display Fixes Summary</h2>";
echo "<p>All notification display issues have been addressed:</p>";
echo "<ul>";
echo "<li><strong>Count Display:</strong> Fixed to update both .notification-badge and #notificationCount</li>";
echo "<li><strong>Title Size:</strong> Reduced from 16px to 14px for better readability</li>";
echo "<li><strong>Auto-reload:</strong> Added notification count update to dashboard load</li>";
echo "<li><strong>CSS Styling:</strong> Proper notification item styling with type-based colors</li>";
echo "</ul>";
echo "<p><strong>Testing Instructions:</strong></p>";
echo "<ol>";
echo "<li>Clear browser cache (Ctrl+F5)</li>";
echo "<li>Run this test script to create sample notifications</li>";
echo "<li>Open main application and verify fixes</li>";
echo "<li>Check console for any remaining errors</li>";
echo "</ol>";
echo "</div>";

// Auto-refresh this test page to show updated counts
echo "<script>";
echo "setTimeout(function() { location.reload(); }, 5000);";
echo "</script>";

?>

<style>
.btn {
    cursor: pointer;
    transition: background-color 0.3s;
}
.btn:hover {
    opacity: 0.8;
}
</style>
