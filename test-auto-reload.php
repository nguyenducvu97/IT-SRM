<?php
// Test script for auto-reload functionality
// This script simulates real-time data updates for testing the 3-second auto-reload

require_once 'config/database.php';
require_once 'config/session.php';

session_start();

// Mock user session for testing
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['username'] = 'testadmin';
$_SESSION['full_name'] = 'Test Admin';

echo "<h1>Auto-Reload System Test (3-second refresh)</h1>";

echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h2>Test Instructions:</h2>";
echo "<ol>";
echo "<li>Open main application in another tab: <a href='index.html' target='_blank'>Open IT Service Request</a></li>";
echo "<li>Login as any user (admin/staff/user)</li>";
echo "<li>Look for the green 'Auto-refresh: 3s' indicator in top-right corner</li>";
echo "<li>Observe data refreshing every 3 seconds</li>";
echo "<li>Use this page to create test data and see real-time updates</li>";
echo "</ol>";
echo "</div>";

// Test 1: Create sample notification
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>Quick Test Actions:</h3>";

if (isset($_GET['create_notification'])) {
    try {
        $db = getDatabaseConnection();
        
        // Create a test notification
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, related_id, related_type, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            1, // admin user
            "Test Auto-Reload Notification",
            "This is a test notification created at " . date('H:i:s'),
            'info',
            rand(1, 100),
            'service_request'
        ]);
        
        echo "<p style='color: green;'>Test notification created successfully!</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error creating notification: " . $e->getMessage() . "</p>";
    }
}

if (isset($_GET['create_request'])) {
    try {
        $db = getDatabaseConnection();
        
        // Create a test request
        $stmt = $db->prepare("INSERT INTO service_requests (user_id, category_id, title, description, priority, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([
            1, // admin user
            1, // category
            "Auto-Reload Test Request " . rand(1000, 9999),
            "This request was created to test the auto-reload functionality at " . date('H:i:s'),
            'medium',
            'open'
        ]);
        
        echo "<p style='color: green;'>Test request created successfully!</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error creating request: " . $e->getMessage() . "</p>";
    }
}

echo "<a href='?create_notification=1' class='btn' style='background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Create Test Notification</a>";
echo "<a href='?create_request=1' class='btn' style='background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>Create Test Request</a>";
echo "</div>";

// Test 2: Show current data counts
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>Current System Status:</h3>";

try {
    $db = getDatabaseConnection();
    
    // Count notifications
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE is_read = 0");
    $stmt->execute();
    $unread_notifications = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Count requests by status
    $stmt = $db->prepare("SELECT status, COUNT(*) as count FROM service_requests GROUP BY status");
    $stmt->execute();
    $request_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Unread Notifications:</strong> " . $unread_notifications . "</p>";
    echo "<p><strong>Request Statistics:</strong></p>";
    echo "<ul>";
    foreach ($request_stats as $stat) {
        echo "<li>" . ucfirst($stat['status']) . ": " . $stat['count'] . "</li>";
    }
    echo "</ul>";
    
    echo "<p><em>Last updated: " . date('Y-m-d H:i:s') . "</em></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error getting system status: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Test 3: Auto-reload simulation
echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px;'>";
echo "<h3>Auto-Reload Simulation:</h3>";
echo "<p>This page will automatically refresh every 3 seconds to show updated counts.</p>";
echo "<p>In the main application, you should see:</p>";
echo "<ul>";
echo "<li>Green indicator: 'Auto-refresh: 3s' with pulsing dot</li>";
echo "<li>Dashboard data updating every 3 seconds</li>";
echo "<li>Notification count updating in real-time</li>";
echo "<li>Request lists refreshing automatically</li>";
echo "</ul>";
echo "</div>";

// Auto-refresh this test page
echo "<script>";
echo "setTimeout(function() { location.reload(); }, 3000);";
echo "</script>";

?>
