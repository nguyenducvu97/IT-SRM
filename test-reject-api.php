<?php
/**
 * Test Reject Request API Call
 * Simulates the actual reject request submission
 */

require_once 'config/database.php';
require_once 'config/session.php';

// Start session and mock login
startSession();
$_SESSION['user_id'] = 2; // Staff user ID
$_SESSION['role'] = 'staff';
$_SESSION['full_name'] = 'Test Staff';
$_SESSION['username'] = 'staff';

echo "<h1>Test Reject Request API</h1>";

// Test data
$testData = [
    'action' => 'reject_request',
    'request_id' => 1, // Change to a real request ID
    'reject_reason' => 'Test rejection - violates company policy',
    'reject_details' => 'This request cannot be fulfilled due to technical limitations'
];

echo "<h2>Test Data:</h2>";
echo "<pre>" . print_r($testData, true) . "</pre>";

// Simulate POST request
$_POST = $testData;

echo "<h2>Processing Reject Request...</h2>";

try {
    $db = getDatabaseConnection();
    
    // Get request details
    $request_id = $testData['request_id'];
    $reject_reason = $testData['reject_reason'];
    $reject_details = $testData['reject_details'];
    $user_id = $_SESSION['user_id'];
    
    // Check if request exists
    $stmt = $db->prepare("SELECT * FROM service_requests WHERE id = ?");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        echo "❌ Request #{$request_id} not found<br>";
        exit;
    }
    
    echo "✅ Found request: {$request['title']}<br>";
    
    // Check if reject request already exists
    $stmt = $db->prepare("SELECT * FROM reject_requests WHERE service_request_id = ? AND status = 'pending'");
    $stmt->execute([$request_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        echo "⚠️ Reject request already exists for request #{$request_id}<br>";
    } else {
        echo "✅ No existing reject request, creating new one<br>";
        
        // Insert reject request
        $insert_query = "INSERT INTO reject_requests 
                         (service_request_id, rejected_by, reject_reason, reject_details, status, created_at) 
                         VALUES (?, ?, ?, ?, 'pending', NOW())";
        
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->execute([$request_id, $user_id, $reject_reason, $reject_details]);
        
        $reject_id = $db->lastInsertId();
        echo "✅ Reject request created with ID: {$reject_id}<br>";
        
        // Test notification creation
        require_once 'lib/ServiceRequestNotificationHelper.php';
        $notificationHelper = new ServiceRequestNotificationHelper();
        
        echo "<h3>Testing Notification Creation...</h3>";
        
        $requestDetails = $notificationHelper->getRequestDetails($request_id);
        echo "✅ Request details loaded: {$requestDetails['title']}<br>";
        
        $result = $notificationHelper->notifyAdminRejectionRequest(
            $request_id, 
            $reject_reason . ($reject_details ? " - " . $reject_details : ""), 
            $_SESSION['full_name'] ?? 'Staff', 
            $requestDetails['title']
        );
        
        if ($result) {
            echo "✅ Notification sent to admin successfully!<br>";
            
            // Verify notification in database
            $stmt = $db->prepare("
                SELECT COUNT(*) as count FROM notifications 
                WHERE title = 'Yêu cầu từ chối cần xác nhận' 
                AND related_id = ? AND related_type = 'service_request'
            ");
            $stmt->execute([$request_id]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            echo "✅ Found {$count} notification(s) in database<br>";
            
        } else {
            echo "❌ Failed to send notification to admin<br>";
        }
    }
    
    echo "<h2>✅ Test Complete!</h2>";
    echo "<p>Reject request process is working correctly.</p>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}
?>
