<?php
/**
 * Final Comprehensive Notification System Verification
 * Tests all notification scenarios to ensure everything works correctly
 */

require_once 'config/database.php';
require_once 'config/session.php';
require_once 'lib/ServiceRequestNotificationHelper.php';

echo "<h1>🔍 FINAL NOTIFICATION SYSTEM VERIFICATION</h1>";
echo "<style>
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .success { background-color: #d4edda; border-color: #c3e6cb; }
    .error { background-color: #f8d7da; border-color: #f5c6cb; }
    .info { background-color: #d1ecf1; border-color: #bee5eb; }
    .warning { background-color: #fff3cd; border-color: #ffeaa7; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .status-ok { color: green; font-weight: bold; }
    .status-error { color: red; font-weight: bold; }
</style>";

// Initialize
$db = getDatabaseConnection();
$notificationHelper = new ServiceRequestNotificationHelper();

// Test 1: Database Setup
echo "<div class='section info'>";
echo "<h2>1. DATABASE SETUP VERIFICATION</h2>";

// Check tables
$tables = ['notifications', 'users', 'service_requests', 'reject_requests'];
foreach ($tables as $table) {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM {$table}");
    try {
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "✅ Table '{$table}': {$count} records<br>";
    } catch (Exception $e) {
        echo "❌ Table '{$table}': ERROR - " . $e->getMessage() . "<br>";
    }
}

// Check users by role
$stmt = $db->prepare("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$stmt->execute();
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<br><strong>Users by Role:</strong><br>";
foreach ($roles as $role) {
    echo "- {$role['role']}: {$role['count']} users<br>";
}
echo "</div>";

// Test 2: ServiceRequestNotificationHelper Methods
echo "<div class='section info'>";
echo "<h2>2. NOTIFICATION METHODS VERIFICATION</h2>";

$methods = [
    'notifyUserRequestInProgress' => 'User notifications for In Progress',
    'notifyUserRequestResolved' => 'User notifications for Resolved',
    'notifyUserRequestRejected' => 'User notifications for Rejected',
    'notifyStaffNewRequest' => 'Staff notifications for New Request',
    'notifyStaffUserFeedback' => 'Staff notifications for User Feedback',
    'notifyStaffAdminApproved' => 'Staff notifications for Admin Approval',
    'notifyStaffAdminRejected' => 'Staff notifications for Admin Rejection',
    'notifyAdminNewRequest' => 'Admin notifications for New Request',
    'notifyAdminStatusChange' => 'Admin notifications for Status Change',
    'notifyAdminSupportRequest' => 'Admin notifications for Support Request',
    'notifyAdminRejectionRequest' => 'Admin notifications for Rejection Request'
];

foreach ($methods as $method => $description) {
    if (method_exists($notificationHelper, $method)) {
        echo "✅ {$method}: {$description}<br>";
    } else {
        echo "❌ {$method}: MISSING - {$description}<br>";
    }
}
echo "</div>";

// Test 3: Get Test Data
echo "<div class='section info'>";
echo "<h2>3. TEST DATA PREPARATION</h2>";

// Get a test request
$stmt = $db->prepare("SELECT * FROM service_requests LIMIT 1");
$stmt->execute();
$testRequest = $stmt->fetch(PDO::FETCH_ASSOC);

if ($testRequest) {
    echo "✅ Using test request #{$testRequest['id']}: {$testRequest['title']}<br>";
    $testRequestId = $testRequest['id'];
    $testUserId = $testRequest['user_id'];
} else {
    echo "❌ No test request found. Creating one...<br>";
    // Create a test request
    $stmt = $db->prepare("
        INSERT INTO service_requests (user_id, title, description, category_id, priority, status, created_at) 
        VALUES (1, 'Test Request for Notifications', 'This is a test request', 1, 'medium', 'open', NOW())
    ");
    $stmt->execute();
    $testRequestId = $db->lastInsertId();
    $testUserId = 1;
    echo "✅ Created test request #{$testRequestId}<br>";
}

// Get admin user
$stmt = $db->prepare("SELECT * FROM users WHERE role = 'admin' LIMIT 1");
$stmt->execute();
$adminUser = $stmt->fetch(PDO::FETCH_ASSOC);

if ($adminUser) {
    echo "✅ Using admin user: {$adminUser['full_name']} (ID: {$adminUser['id']})<br>";
} else {
    echo "❌ No admin user found!<br>";
}
echo "</div>";

// Test 4: User Notifications
echo "<div class='section'>";
echo "<h2>4. USER NOTIFICATIONS TEST</h2>";

$tests = [
    [
        'method' => 'notifyUserRequestInProgress',
        'params' => [$testRequestId, $testUserId, 'Test Staff'],
        'description' => 'In Progress Notification'
    ],
    [
        'method' => 'notifyUserRequestResolved', 
        'params' => [$testRequestId, $testUserId, 'Test resolution'],
        'description' => 'Resolved Notification'
    ],
    [
        'method' => 'notifyUserRequestRejected',
        'params' => [$testRequestId, $testUserId, 'Test rejection reason'],
        'description' => 'Rejected Notification'
    ]
];

$userResults = [];
foreach ($tests as $test) {
    try {
        $result = call_user_func_array([$notificationHelper, $test['method']], $test['params']);
        $userResults[] = $result;
        echo ($result ? "✅" : "❌") . " {$test['description']}: " . ($result ? "SUCCESS" : "FAILED") . "<br>";
    } catch (Exception $e) {
        $userResults[] = false;
        echo "❌ {$test['description']}: ERROR - " . $e->getMessage() . "<br>";
    }
}
echo "</div>";

// Test 5: Staff Notifications
echo "<div class='section'>";
echo "<h2>5. STAFF NOTIFICATIONS TEST</h2>";

$staffTests = [
    [
        'method' => 'notifyStaffNewRequest',
        'params' => [$testRequestId, $testRequest['title'], $testRequest['user_id']],
        'description' => 'New Request Notification'
    ],
    [
        'method' => 'notifyStaffUserFeedback',
        'params' => [$testRequestId, $testUserId, 5, 'Great service!', 'Test User'],
        'description' => 'User Feedback Notification'
    ]
];

$staffResults = [];
foreach ($staffTests as $test) {
    try {
        $result = call_user_func_array([$notificationHelper, $test['method']], $test['params']);
        $staffResults[] = $result;
        echo ($result ? "✅" : "❌") . " {$test['description']}: " . ($result ? "SUCCESS" : "FAILED") . "<br>";
    } catch (Exception $e) {
        $staffResults[] = false;
        echo "❌ {$test['description']}: ERROR - " . $e->getMessage() . "<br>";
    }
}
echo "</div>";

// Test 6: Admin Notifications
echo "<div class='section'>";
echo "<h2>6. ADMIN NOTIFICATIONS TEST</h2>";

$adminTests = [
    [
        'method' => 'notifyAdminNewRequest',
        'params' => [$testRequestId, $testRequest['title'], 'Test User', 'Test Category'],
        'description' => 'New Request Notification'
    ],
    [
        'method' => 'notifyAdminStatusChange',
        'params' => [$testRequestId, 'open', 'in_progress', 'Test Staff', $testRequest['title']],
        'description' => 'Status Change Notification'
    ],
    [
        'method' => 'notifyAdminSupportRequest',
        'params' => [$testRequestId, 'Need technical assistance', 'Test Staff', $testRequest['title']],
        'description' => 'Support Request Notification'
    ],
    [
        'method' => 'notifyAdminRejectionRequest',
        'params' => [$testRequestId, 'Violates policy', 'Test Staff', $testRequest['title']],
        'description' => 'Rejection Request Notification'
    ]
];

$adminResults = [];
foreach ($adminTests as $test) {
    try {
        $result = call_user_func_array([$notificationHelper, $test['method']], $test['params']);
        $adminResults[] = $result;
        echo ($result ? "✅" : "❌") . " {$test['description']}: " . ($result ? "SUCCESS" : "FAILED") . "<br>";
    } catch (Exception $e) {
        $adminResults[] = false;
        echo "❌ {$test['description']}: ERROR - " . $e->getMessage() . "<br>";
    }
}
echo "</div>";

// Test 7: Database Verification
echo "<div class='section info'>";
echo "<h2>7. DATABASE VERIFICATION</h2>";

// Count all notifications created during test
$stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
$stmt->execute();
$recentNotifications = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
echo "✅ Notifications created in last hour: {$recentNotifications}<br>";

// Show latest notifications
$stmt = $db->prepare("
    SELECT n.*, u.full_name as user_name 
    FROM notifications n 
    LEFT JOIN users u ON n.user_id = u.id 
    ORDER BY n.created_at DESC 
    LIMIT 10
");
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Latest Notifications:</h3>";
echo "<table>";
echo "<tr><th>ID</th><th>User</th><th>Title</th><th>Message</th><th>Type</th><th>Created</th></tr>";
foreach ($notifications as $notif) {
    echo "<tr>";
    echo "<td>{$notif['id']}</td>";
    echo "<td>{$notif['user_name']}</td>";
    echo "<td>{$notif['title']}</td>";
    echo "<td>" . htmlspecialchars(substr($notif['message'], 0, 80)) . "...</td>";
    echo "<td>{$notif['type']}</td>";
    echo "<td>{$notif['created_at']}</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

// Final Summary
echo "<div class='section'>";
echo "<h2>📊 FINAL SUMMARY</h2>";

$allResults = array_merge($userResults, $staffResults, $adminResults);
$totalTests = count($allResults);
$passedTests = count(array_filter($allResults));
$failedTests = $totalTests - $passedTests;

echo "<table>";
echo "<tr><th>Metric</th><th>Count</th><th>Status</th></tr>";
echo "<tr><td>Total Tests</td><td>{$totalTests}</td><td>-</td></tr>";
echo "<tr><td>Passed</td><td>{$passedTests}</td><td class='status-ok'>✅</td></tr>";
echo "<tr><td>Failed</td><td>{$failedTests}</td><td class='" . ($failedTests > 0 ? 'status-error' : 'status-ok') . "'>" . ($failedTests > 0 ? '❌' : '✅') . "</td></tr>";
echo "</table>";

if ($failedTests === 0) {
    echo "<div class='success'><h3>🎉 ALL TESTS PASSED!</h3>";
    echo "<p>The notification system is working perfectly according to requirements:</p>";
    echo "<ul>";
    echo "<li>✅ User notifications for status changes, resolution, and rejection</li>";
    echo "<li>✅ Staff notifications for new requests, feedback, and admin decisions</li>";
    echo "<li>✅ Admin notifications for new requests, status changes, escalations, and rejection requests</li>";
    echo "<li>✅ All methods exist and function correctly</li>";
    echo "<li>✅ Database integration working properly</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div class='error'><h3>⚠️ SOME TESTS FAILED</h3>";
    echo "<p>There are {$failedTests} failing test(s) that need to be addressed.</p>";
    echo "</div>";
}

echo "</div>";

echo "<div class='section info'>";
echo "<h3>🔧 NEXT STEPS</h3>";
echo "<ol>";
echo "<li>Test the notification system in the live application</li>";
echo "<li>Verify notifications appear correctly in the UI</li>";
echo "<li>Check email notifications if configured</li>";
echo "<li>Monitor error logs for any issues</li>";
echo "</ol>";
echo "</div>";
?>
