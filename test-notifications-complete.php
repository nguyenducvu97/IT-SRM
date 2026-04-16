<?php
/**
 * Comprehensive Notification System Test
 * Tests all notification scenarios according to requirements
 */

require_once 'config/database.php';
require_once 'config/session.php';
require_once 'lib/ServiceRequestNotificationHelper.php';

// Start session for testing
startSession();

// Mock user session for testing
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['full_name'] = 'Test Admin';
$_SESSION['username'] = 'admin';

// Initialize notification helper
$notificationHelper = new ServiceRequestNotificationHelper();

echo "<h1>Comprehensive Notification System Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .success { background-color: #d4edda; border-color: #c3e6cb; }
    .error { background-color: #f8d7da; border-color: #f5c6cb; }
    .info { background-color: #d1ecf1; border-color: #bee5eb; }
    ul { margin: 10px 0; }
    li { margin: 5px 0; }
</style>";

// Get test request details
$testRequestId = 1;
$requestDetails = $notificationHelper->getRequestDetails($testRequestId);

if (!$requestDetails) {
    echo "<div class='error'>Test request #{$testRequestId} not found. Please create a test request first.</div>";
    exit;
}

echo "<div class='info'>Using test request #{$testRequestId}: {$requestDetails['title']}</div>";

// Test 1: User Notifications
echo "<div class='test-section'>";
echo "<h2>1. USER NOTIFICATIONS TEST</h2>";

// Test 1.1: Open -> In Progress
echo "<h3>1.1 Open -> In Progress Notification</h3>";
$result1 = $notificationHelper->notifyUserRequestInProgress(
    $testRequestId, 
    $requestDetails['user_id'], 
    'Test Staff'
);
echo "<div class='" . ($result1 ? 'success' : 'error') . "'>" . 
     ($result1 ? 'SUCCESS' : 'FAILED') . ": User notified about In Progress status</div>";

// Test 1.2: Request Resolved
echo "<h3>1.2 Request Resolved Notification</h3>";
$result2 = $notificationHelper->notifyUserRequestResolved(
    $testRequestId, 
    $requestDetails['user_id'], 
    'Test resolution details'
);
echo "<div class='" . ($result2 ? 'success' : 'error') . "'>" . 
     ($result2 ? 'SUCCESS' : 'FAILED') . ": User notified about resolved request</div>";

// Test 1.3: Request Rejected
echo "<h3>1.3 Request Rejected Notification</h3>";
$result3 = $notificationHelper->notifyUserRequestRejected(
    $testRequestId, 
    $requestDetails['user_id'], 
    'Test rejection reason'
);
echo "<div class='" . ($result3 ? 'success' : 'error') . "'>" . 
     ($result3 ? 'SUCCESS' : 'FAILED') . ": User notified about rejected request</div>";

echo "</div>";

// Test 2: Staff Notifications
echo "<div class='test-section'>";
echo "<h2>2. STAFF NOTIFICATIONS TEST</h2>";

// Test 2.1: New Request
echo "<h3>2.1 New Request Notification</h3>";
$result4 = $notificationHelper->notifyStaffNewRequest(
    $testRequestId, 
    $requestDetails['title'], 
    $requestDetails['requester_name'], 
    'Test Category'
);
echo "<div class='" . ($result4 ? 'success' : 'error') . "'>" . 
     ($result4 ? 'SUCCESS' : 'FAILED') . ": Staff notified about new request</div>";

// Test 2.2: User Feedback
echo "<h3>2.2 User Feedback Notification</h3>";
$result5 = $notificationHelper->notifyStaffUserFeedback(
    $testRequestId, 
    $requestDetails['user_id'], 
    5, 
    'Great service!', 
    $requestDetails['requester_name']
);
echo "<div class='" . ($result5 ? 'success' : 'error') . "'>" . 
     ($result5 ? 'SUCCESS' : 'FAILED') . ": Staff notified about user feedback</div>";

// Test 2.3: Admin Approved Support Request
echo "<h3>2.3 Admin Approved Support Request</h3>";
$result6 = $notificationHelper->notifyStaffAdminApproved(
    $testRequestId, 
    $requestDetails['title'], 
    'Test Admin'
);
echo "<div class='" . ($result6 ? 'success' : 'error') . "'>" . 
     ($result6 ? 'SUCCESS' : 'FAILED') . ": Staff notified about admin approval</div>";

// Test 2.4: Admin Rejected Support Request
echo "<h3>2.4 Admin Rejected Support Request</h3>";
$result7 = $notificationHelper->notifyStaffAdminRejected(
    $testRequestId, 
    $requestDetails['title'], 
    'Test Admin', 
    'Not feasible'
);
echo "<div class='" . ($result7 ? 'success' : 'error') . "'>" . 
     ($result7 ? 'SUCCESS' : 'FAILED') . ": Staff notified about admin rejection</div>";

echo "</div>";

// Test 3: Admin Notifications
echo "<div class='test-section'>";
echo "<h2>3. ADMIN NOTIFICATIONS TEST</h2>";

// Test 3.1: New Request
echo "<h3>3.1 New Request Notification</h3>";
$result8 = $notificationHelper->notifyAdminNewRequest(
    $testRequestId, 
    $requestDetails['title'], 
    $requestDetails['requester_name'], 
    'Test Category'
);
echo "<div class='" . ($result8 ? 'success' : 'error') . "'>" . 
     ($result8 ? 'SUCCESS' : 'FAILED') . ": Admin notified about new request</div>";

// Test 3.2: Status Change
echo "<h3>3.2 Status Change Notification</h3>";
$result9 = $notificationHelper->notifyAdminStatusChange(
    $testRequestId, 
    'open', 
    'in_progress', 
    'Test Staff', 
    $requestDetails['title']
);
echo "<div class='" . ($result9 ? 'success' : 'error') . "'>" . 
     ($result9 ? 'SUCCESS' : 'FAILED') . ": Admin notified about status change</div>";

// Test 3.3: Support Request (Escalation)
echo "<h3>3.3 Support Request (Escalation)</h3>";
$result10 = $notificationHelper->notifyAdminSupportRequest(
    $testRequestId, 
    'Need technical assistance with complex issue', 
    'Test Staff', 
    $requestDetails['title']
);
echo "<div class='" . ($result10 ? 'success' : 'error') . "'>" . 
     ($result10 ? 'SUCCESS' : 'FAILED') . ": Admin notified about support request</div>";

// Test 3.4: Rejection Request
echo "<h3>3.4 Rejection Request</h3>";
$result11 = $notificationHelper->notifyAdminRejectionRequest(
    $testRequestId, 
    'Request violates company policy', 
    'Test Staff', 
    $requestDetails['title']
);
echo "<div class='" . ($result11 ? 'success' : 'error') . "'>" . 
     ($result11 ? 'SUCCESS' : 'FAILED') . ": Admin notified about rejection request</div>";

echo "</div>";

// Summary
echo "<div class='test-section'>";
echo "<h2>4. TEST SUMMARY</h2>";

$allTests = [$result1, $result2, $result3, $result4, $result5, $result6, $result7, $result8, $result9, $result10, $result11];
$passedTests = array_filter($allTests);
$failedTests = count($allTests) - count($passedTests);

echo "<div class='info'>";
echo "<h3>Results:</h3>";
echo "<ul>";
echo "<li><strong>Total Tests:</strong> " . count($allTests) . "</li>";
echo "<li><strong>Passed:</strong> " . count($passedTests) . "</li>";
echo "<li><strong>Failed:</strong> {$failedTests}</li>";
echo "</ul>";

if ($failedTests === 0) {
    echo "<div class='success'><h3>ALL TESTS PASSED! Notification system is working correctly.</h3></div>";
} else {
    echo "<div class='error'><h3>{$failedTests} test(s) failed. Please check the notification system.</h3></div>";
}

echo "</div>";

// Check notifications in database
echo "<div class='test-section'>";
echo "<h2>5. DATABASE VERIFICATION</h2>";

$db = getDatabaseConnection();
$stmt = $db->prepare("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10");
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Recent Notifications (Last 10):</h3>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID</th><th>User ID</th><th>Title</th><th>Message</th><th>Type</th><th>Created</th></tr>";

foreach ($notifications as $notif) {
    echo "<tr>";
    echo "<td>{$notif['id']}</td>";
    echo "<td>{$notif['user_id']}</td>";
    echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
    echo "<td>" . htmlspecialchars(substr($notif['message'], 0, 100)) . "...</td>";
    echo "<td>{$notif['type']}</td>";
    echo "<td>{$notif['created_at']}</td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";

echo "<div class='info'>";
echo "<h3>Test Complete!</h3>";
echo "<p>All notification scenarios have been tested according to the requirements:</p>";
echo "<ul>";
echo "<li>1. User notifications for status changes, resolution, and rejection</li>";
echo "<li>2. Staff notifications for new requests, feedback, and admin decisions</li>";
echo "<li>3. Admin notifications for new requests, status changes, escalations, and rejection requests</li>";
echo "</ul>";
echo "</div>";
?>
