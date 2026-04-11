<?php
// Test Duplicate Count Fix
// This script tests the fix for duplicate notification count display

require_once 'config/database.php';
require_once 'config/session.php';

session_start();

// Mock user session for testing
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'user';
$_SESSION['username'] = 'testuser';
$_SESSION['full_name'] = 'Test User';

echo "<h1>Test: Duplicate Count Fix</h1>";

echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h2>Problem Identified:</h2>";
echo "<p><strong>Issue:</strong> Two elements showing same count: #notificationCount and #notificationBadge</p>";
echo "<p><strong>Solution:</strong> Hide #notificationCount with CSS, keep only #notificationBadge</p>";
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
            'title' => 'Test Duplicate Fix',
            'message' => 'This is a test notification for duplicate count fix',
            'type' => 'info',
            'is_read' => 0
        ],
        [
            'title' => 'Another Test',
            'message' => 'Second test notification',
            'type' => 'success',
            'is_read' => 0
        ]
    ];
    
    foreach ($testNotifications as $notif) {
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, is_read, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([1, $notif['title'], $notif['message'], $notif['type'], $notif['is_read']]);
    }
    
    echo "<p style='color: green;'>Created 2 test notifications successfully</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "</div>";

// CSS Structure Check
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>CSS Fix Applied:</h3>";
echo "<p><strong>Before:</strong></p>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 12px;'>";
echo ".notification-count {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #dc3545;
    /* ... other styles ... */
}";
echo "</pre>";

echo "<p><strong>After:</strong></p>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 12px;'>";
echo ".notification-count {
    display: none;  /* Hide this element */
}

.notification-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #dc3545;
    color: white;
    font-size: 10px;
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 10px;
    min-width: 16px;
    text-align: center;
    display: none;  /* Hidden by default, shown when count > 0 */
}";
echo "</pre>";
echo "</div>";

// Expected HTML Structure
echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>Expected HTML Structure:</h3>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 12px;'>";
echo "&lt;button id=\"notificationBtn\" class=\"notification-btn\"&gt;
    &lt;i class=\"fas fa-bell\"&gt;&lt;/i&gt;
    &lt;span id=\"notificationCount\" class=\"notification-count\"&gt;2&lt;/span&gt;  &lt;!-- Hidden by CSS --&gt;
    &lt;span id=\"notificationBadge\" class=\"notification-badge\"&gt;2&lt;/span&gt;  &lt;!-- Visible --&gt;
&lt;/button&gt;";
echo "</pre>";
echo "<p><strong>Key Points:</strong></p>";
echo "<ul>";
echo "<li>Both elements exist in HTML</li>";
echo "<li>CSS hides #notificationCount with display: none</li>";
echo "<li>JavaScript updates both elements</li>";
echo "<li>Only #notificationBadge is visible to user</li>";
echo "</ul>";
echo "</div>";

// Testing Instructions
echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>Testing Instructions:</h3>";
echo "<ol>";
echo "<li><strong>Clear cache:</strong> Ctrl+F5 to load new CSS</li>";
echo "<li><strong>Open main app:</strong> <a href='index.html' target='_blank' class='btn' style='background: #007bff; color: white; padding: 6px 12px; text-decoration: none; border-radius: 3px;'>Open IT Service Request</a></li>";
echo "<li><strong>Login as test user</strong></li>";
echo "<li><strong>Expected Results:</strong></li>";
echo "<ul>";
echo "<li>Only ONE red badge showing count '2'</li>";
echo "<li>No duplicate count display</li>";
echo "<li>Clean, single badge appearance</li>";
echo "<li>Badge positioned correctly at top-right of bell</li>";
echo "</ul>";
echo "</ol>";
echo "</div>";

// Debug Console Commands
echo "<div style='background: #28a745; color: white; padding: 20px; border-radius: 8px;'>";
echo "<h2>Debug Commands:</h2>";
echo "<p>Open browser console (F12) and run:</p>";
echo "<pre style='background: rgba(255,255,255,0.1); padding: 10px; border-radius: 4px; font-size: 12px;'>";
echo "// Check both elements exist
console.log('Count element:', document.getElementById('notificationCount'));
console.log('Badge element:', document.getElementById('notificationBadge'));

// Check computed styles
const count = document.getElementById('notificationCount');
const badge = document.getElementById('notificationBadge');

if (count) {
    console.log('Count element styles:', {
        display: window.getComputedStyle(count).display,
        visibility: window.getComputedStyle(count).visibility
    });
}

if (badge) {
    console.log('Badge element styles:', {
        display: window.getComputedStyle(badge).display,
        visibility: window.getComputedStyle(badge).visibility,
        content: badge.textContent
    });
}

// Test manual update
if (window.app && window.app.updateNotificationCountDisplay) {
    window.app.updateNotificationCountDisplay(5);
    console.log('Manual update applied');
}
";
echo "</pre>";
echo "</div>";

echo "<div style='background: #6f42c1; color: white; padding: 20px; border-radius: 8px;'>";
echo "<h2>Final Fix Summary:</h2>";
echo "<p>Duplicate count issue resolved by CSS:</p>";
echo "<ul>";
echo "<li>Hide #notificationCount with display: none</li>";
echo "<li>Keep #notificationBadge visible</li>";
echo "<li>JavaScript continues to update both elements</li>";
echo "<li>Only badge is visible to user</li>";
echo "<li>Clean, single count display</li>";
echo "</ul>";
echo "<p><strong>Expected Result:</strong></p>";
echo "<ul>";
echo "<li>Only ONE red badge with count '2'</li>";
echo "<li>No duplicate numbers</li>";
echo "<li>Clean visual appearance</li>";
echo "<li>Proper positioning</li>";
echo "</ul>";
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
