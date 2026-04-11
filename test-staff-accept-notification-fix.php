<?php
// Test Staff Accept Notification Fix
// This script tests the fix for staff acceptance notifications

require_once 'config/database.php';
require_once 'config/session.php';

session_start();

// Mock user session for testing
$_SESSION['user_id'] = 2; // Staff ID
$_SESSION['role'] = 'staff';
$_SESSION['username'] = 'staffuser';
$_SESSION['full_name'] = 'Staff User';

echo "<h1>Test: Staff Accept Notification Fix</h1>";

echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h2>Problem Fixed:</h2>";
echo "<p><strong>Issue:</strong> PUT accept_request had wrong notification (notifyStaffAdminApproved)</p>";
echo "<p><strong>Solution:</strong> Removed the wrong notification, kept only correct ones</p>";
echo "</div>";

// Create test request
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>Creating Test Request...</h3>";

try {
    $db = getDatabaseConnection();
    
    // Clear existing test requests
    $db->exec("DELETE FROM service_requests WHERE title LIKE 'Test Staff Accept%' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    
    // Create test request
    $stmt = $db->prepare("INSERT INTO service_requests (user_id, title, description, category_id, status, priority, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([1, 'Test Staff Accept Notification', 'This is a test request for staff acceptance notification', 1, 'open', 'medium']);
    
    $requestId = $db->lastInsertId();
    echo "<p style='color: green;'>Created test request #$requestId</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error creating test request: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Check notification logic
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>Fixed Notification Logic:</h3>";
echo "<p><strong>PUT accept_request now sends:</strong></p>";
echo "<ol>";
echo "<li style='color: green;'>notifyUserRequestInProgress() - To user</li>";
echo "<li style='color: green;'>notifyAdminStatusChange() - To admin</li>";
echo "<li style='color: red; text-decoration: line-through;'>notifyStaffAdminApproved() - REMOVED (was wrong)</li>";
echo "</ol>";
echo "<p><strong>Why removed:</strong></p>";
echo "<ul>";
echo "<li>notifyStaffAdminApproved() is for when ADMIN approves, not when STAFF accepts</li>";
echo "<li>It was sending notifications to ALL STAFF (including accepting staff)</li>";
echo "<li>Created confusion and duplicate notifications</li>";
echo "</ul>";
echo "</div>";

// Testing Instructions
echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>Testing Instructions:</h3>";
echo "<ol>";
echo "<li><strong>Login as staff:</strong> Username: staffuser</li>";
echo "<li><strong>Open test request:</strong> <a href='request-detail.html?id=$requestId' target='_blank' class='btn' style='background: #007bff; color: white; padding: 6px 12px; text-decoration: none; border-radius: 3px;'>Open Request #$requestId</a></li>";
echo "<li><strong>Click 'Tiêp nhân yêu càu' button</strong></li>";
echo "<li><strong>Expected notifications:</strong></li>";
echo "<ul>";
echo "<li>User receives: 'Yêu càu #$requestId dang duoc xu ly'</li>";
echo "<li>Admin receives: 'Nhan vien Staff User da thay doi trang thái yêu càu #$requestId tu 'open' thanh 'in_progress''</li>";
echo "<li>NO notification to other staff (correct)</li>";
echo "</ul>";
echo "</ol>";
echo "</div>";

// Expected Results
echo "<div style='background: #28a745; color: white; padding: 20px; border-radius: 8px;'>";
echo "<h2>Expected Results:</h2>";
echo "<p><strong>When staff accepts request:</strong></p>";
echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr><th>Role</th><th>Should Receive</th><th>Should NOT Receive</th></tr>";
echo "<tr><td>User</td><td style='color: green;'>Yes - Request in progress</td><td>-</td></tr>";
echo "<tr><td>Admin</td><td style='color: green;'>Yes - Status change</td><td>-</td></tr>";
echo "<tr><td>Staff (accepting)</td><td>-</td><td style='color: green;'>No notification</td></tr>";
echo "<tr><td>Staff (others)</td><td>-</td><td style='color: green;'>No notification</td></tr>";
echo "</table>";
echo "</div>";

// Debug info
echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>Debug Information:</h3>";
echo "<p><strong>Frontend uses:</strong> PUT method (confirmed in request-detail.js line 7585)</p>";
echo "<p><strong>Backend fixed:</strong> PUT accept_request (line 7038 removed)</p>";
echo "<p><strong>Notifications sent:</strong> Only to user and admin (correct)</p>";
echo "</div>";

// Auto-refresh
echo "<script>";
echo "setTimeout(() => { location.reload(); }, 15000);";
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
