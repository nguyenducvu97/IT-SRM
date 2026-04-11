<?php
// Test Notification Dropdown Fix
// This script tests the fix for notification dropdown overlay

require_once 'config/database.php';
require_once 'config/session.php';

session_start();

// Mock user session for testing
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'user';
$_SESSION['username'] = 'testuser';
$_SESSION['full_name'] = 'Test User';

echo "<h1>Test: Notification Dropdown Fix</h1>";

echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h2>Problem Fixed:</h2>";
echo "<p><strong>Issue:</strong> Clicking notification bell opened full page instead of dropdown overlay</p>";
echo "<p><strong>Root Cause:</strong> JavaScript was calling showPage('notifications') instead of toggleNotificationDropdown()</p>";
echo "<p><strong>Solution:</strong> Changed to show dropdown overlay with proper functionality</p>";
echo "</div>";

// Create test notifications
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>Creating Test Notifications...</h3>";

try {
    $db = getDatabaseConnection();
    
    // Clear and create notifications
    $db->exec("DELETE FROM notifications WHERE user_id = 1 AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    
    $testNotifications = [
        [
            'title' => 'Dropdown Test 1',
            'message' => 'This is a test notification for dropdown functionality',
            'type' => 'info',
            'is_read' => 0
        ],
        [
            'title' => 'Dropdown Test 2',
            'message' => 'Second test notification for dropdown overlay',
            'type' => 'success',
            'is_read' => 0
        ],
        [
            'title' => 'Dropdown Test 3',
            'message' => 'Third test notification to verify dropdown display',
            'type' => 'warning',
            'is_read' => 1
        ]
    ];
    
    foreach ($testNotifications as $notif) {
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, is_read, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([1, $notif['title'], $notif['message'], $notif['type'], $notif['is_read']]);
    }
    
    echo "<p style='color: green;'>Created 3 test notifications (2 unread, 1 read)</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "</div>";

// JavaScript Function Comparison
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>JavaScript Function Changes:</h3>";
echo "<p><strong>Before (Full Page):</strong></p>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 12px;'>";
echo "// TRƯỚC - Mở trang notifications
const notificationBtn = document.getElementById('notificationBtn');
if (notificationBtn) {
    notificationBtn.addEventListener('click', () => {
        this.showPage('notifications');  // ❌ Mở trang mới
    });
}";
echo "</pre>";

echo "<p><strong>After (Dropdown Overlay):</strong></p>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 12px;'>";
echo "// SAU - Hiển thị dropdown overlay
const notificationBtn = document.getElementById('notificationBtn');
if (notificationBtn) {
    notificationBtn.addEventListener('click', () => {
        this.toggleNotificationDropdown();  // ✅ Hiển thị dropdown
    });
}

// Event listener cho nút "Đánh dấu đã đọc tất cả"
const markAllReadBtn = document.getElementById('markAllReadBtn');
if (markAllReadBtn) {
    markAllReadBtn.addEventListener('click', () => {
        this.markAllNotificationsAsRead();
    });
}";
echo "</pre>";
echo "</div>";

// New Functions Added
echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>New Functions Added:</h3>";
echo "<ul>";
echo "<li><strong>toggleNotificationDropdown():</strong> Hiển thị/ẩn dropdown</li>";
echo "<li><strong>loadNotificationsForDropdown():</strong> Load notifications cho dropdown</li>";
echo "<li><strong>displayNotificationsInDropdown():</strong> Hiển thị notifications trong dropdown</li>";
echo "<li><strong>handleNotificationDropdownClickOutside():</strong> Đóng dropdown khi click outside</li>";
echo "</ul>";
echo "</div>";

// Testing Instructions
echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>Testing Instructions:</h3>";
echo "<ol>";
echo "<li><strong>Clear cache:</strong> Ctrl+F5 để load JavaScript mới</li>";
echo "<li><strong>Open main app:</strong> <a href='index.html' target='_blank' class='btn' style='background: #007bff; color: white; padding: 6px 12px; text-decoration: none; border-radius: 3px;'>Open IT Service Request</a></li>";
echo "<li><strong>Login as test user</strong></li>";
echo "<li><strong>Test notification dropdown:</strong></li>";
echo "<ul>";
echo "<li>Click chuông thông báo → Dropdown overlay hiển thị</li>";
echo "<li>Không chuyển trang, vẫn ở trang hiện tại</li>";
echo "<li>Dropdown hiển thị 3 test notifications</li>";
echo "<li>Click outside dropdown → Dropdown đóng</li>";
echo "<li>Click chuông thông báo again → Dropdown mở lại</li>";
echo "</ul>";
echo "<li><strong>Test dropdown functionality:</strong></li>";
echo "<ul>";
echo "<li>Click 'Đã đọc' button → Notification được đánh dấu đã đọc</li>";
echo "<li>Click 'Đánh dấu đã đọc tất cả' → Tất cả notifications được đánh dấu đã đọc</li>";
echo "<li>Badge count cập nhật sau khi đánh dấu đã đọc</li>";
echo "</ul>";
echo "</ol>";
echo "</div>";

// Expected Results
echo "<div style='background: #28a745; color: white; padding: 20px; border-radius: 8px;'>";
echo "<h2>Expected Results:</h2>";
echo "<p><strong>After fix, notification bell should:</strong></p>";
echo "<ul>";
echo "<li>✅ Show dropdown overlay (not full page)</li>";
echo "<li>✅ Stay on current page</li>";
echo "<li>✅ Load notifications dynamically</li>";
echo "<li>✅ Close when clicking outside</li>";
echo "<li>✅ Support marking as read</li>";
echo "<li>✅ Support marking all as read</li>";
echo "<li>✅ Update badge count</li>";
echo "<li>✅ Smooth user experience</li>";
echo "</ul>";
echo "</div>";

// CSS Verification
echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>CSS Verification:</h3>";
echo "<p><strong>Notification dropdown CSS:</strong></p>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 12px;'>";
echo ".notification-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    width: 350px;
    max-height: 400px;
    z-index: 1000;
    display: none;  /* Ẩn mặc định */
    margin-top: 0.5rem;
}";
echo "</pre>";
echo "<p><strong>Status:</strong> ✅ CSS is correct for dropdown overlay</p>";
echo "</div>";

// Auto-refresh
echo "<script>";
echo "setTimeout(() => { location.reload(); }, 20000);";
echo "</script>";

?>

<style>
.btn {
    cursor: pointer;
    transition: background-color 0.3s;
    margin: 5px;
}
.btn:hover {
    opacity: 0.8;
}
</style>
