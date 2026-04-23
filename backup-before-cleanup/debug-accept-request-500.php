<?php
// Enable all error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>🔍 Debug Accept Request 500 Error</h1>";

// Simulate the exact same request as the frontend
session_start();

// Check if user is logged in
echo "<h2>🔐 Session Check</h2>";
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'>❌ Not logged in. Simulating staff login...</p>";
    $_SESSION['user_id'] = 2;
    $_SESSION['username'] = 'staff';
    $_SESSION['full_name'] = 'Test Staff User';
    $_SESSION['role'] = 'staff';
    echo "<p style='color: green;'>✅ Simulated staff login</p>";
} else {
    echo "<p style='color: green;'>✅ Already logged in: {$_SESSION['full_name']} (Role: {$_SESSION['role']})</p>";
}

echo "<h2>📡 Simulating API Request</h2>";

// Simulate PUT request data
$_PUT = [
    'action' => 'accept_request',
    'request_id' => 1 // Change this to a valid open request ID
];

echo "<p><strong>Request Data:</strong></p>";
echo "<pre>" . json_encode($_PUT, JSON_PRETTY_PRINT) . "</pre>";

// Simulate the exact API logic
try {
    require_once 'config/database.php';
    require_once 'config/session.php';
    
    $db = (new Database())->getConnection();
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'];
    
    echo "<p style='color: green;'>✅ Database connected</p>";
    echo "<p><strong>User ID:</strong> $user_id</p>";
    echo "<p><strong>User Role:</strong> $user_role</p>";
    
    // Test input validation
    $request_id = isset($_PUT['request_id']) ? (int)$_PUT['request_id'] : 0;
    $action = isset($_PUT['action']) ? $_PUT['action'] : '';
    
    echo "<h3>✅ Input Validation</h3>";
    echo "<p><strong>Action:</strong> $action</p>";
    echo "<p><strong>Request ID:</strong> $request_id</p>";
    
    if ($request_id <= 0) {
        throw new Exception("Request ID is required");
    }
    
    if ($user_role != 'staff' && $user_role != 'admin') {
        throw new Exception("Access denied - Only staff and admin can accept requests");
    }
    
    if (!$user_id) {
        throw new Exception("Session expired");
    }
    
    echo "<p style='color: green;'>✅ All validations passed</p>";
    
    // Test request availability check
    echo "<h3>🔍 Request Availability Check</h3>";
    $check_query = "SELECT id, assigned_to, status FROM service_requests 
                   WHERE id = :request_id AND (status = 'open' OR status = 'request_support') 
                   AND (assigned_to IS NULL OR assigned_to = 0)";
    
    echo "<p><strong>Query:</strong> " . htmlspecialchars($check_query) . "</p>";
    
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":request_id", $request_id);
    $check_stmt->execute();
    
    echo "<p><strong>Available requests found:</strong> " . $check_stmt->rowCount() . "</p>";
    
    if ($check_stmt->rowCount() == 0) {
        // Get detailed info for debugging
        $debug_query = "SELECT id, assigned_to, status FROM service_requests WHERE id = :request_id";
        $debug_stmt = $db->prepare($debug_query);
        $debug_stmt->bindParam(":request_id", $request_id);
        $debug_stmt->execute();
        $debug_info = $debug_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($debug_info) {
            $status = $debug_info['status'];
            $assigned = $debug_info['assigned_to'];
            echo "<p style='color: orange;'>⚠️ Request exists but not available:</p>";
            echo "<p><strong>Status:</strong> $status (should be 'open' or 'request_support')</p>";
            echo "<p><strong>Assigned:</strong> " . ($assigned ? $assigned : 'None') . " (should be None or 0)</p>";
        } else {
            echo "<p style='color: red;'>❌ Request not found with ID: $request_id</p>";
        }
        throw new Exception("Request not available for assignment");
    }
    
    echo "<p style='color: green;'>✅ Request is available for assignment</p>";
    
    // Test the update query
    echo "<h3>💾 Database Update Test</h3>";
    $update_query = "UPDATE service_requests 
                     SET assigned_to = :user_id, status = 'in_progress', 
                         assigned_at = NOW(), accepted_at = NOW(), updated_at = NOW() 
                     WHERE id = :request_id";
    
    echo "<p><strong>Update Query:</strong> " . htmlspecialchars($update_query) . "</p>";
    
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(":request_id", $request_id);
    $update_stmt->bindParam(":user_id", $user_id);
    
    if ($update_stmt->execute()) {
        echo "<p style='color: green;'>✅ Database update successful</p>";
        echo "<p><strong>Affected rows:</strong> " . $update_stmt->rowCount() . "</p>";
        
        // Get updated request details
        $request_query = "SELECT sr.*, u.full_name as requester_name, u.email as requester_email, 
                         staff.full_name as assigned_name, staff.email as assigned_email, c.name as category_name
                         FROM service_requests sr
                         LEFT JOIN users u ON sr.user_id = u.id
                         LEFT JOIN users staff ON sr.assigned_to = staff.id
                         LEFT JOIN categories c ON sr.category_id = c.id
                         WHERE sr.id = :request_id";
        $request_stmt = $db->prepare($request_query);
        $request_stmt->bindParam(":request_id", $request_id);
        $request_stmt->execute();
        $request_data = $request_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($request_data) {
            echo "<h4>📋 Updated Request Details:</h4>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            foreach (['id', 'title', 'status', 'assigned_name', 'accepted_at'] as $field) {
                echo "<tr>";
                echo "<td><strong>$field</strong></td>";
                echo "<td>" . htmlspecialchars($request_data[$field] ?? 'N/A') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Test notification system
        echo "<h3>📧 Notification System Test</h3>";
        try {
            require_once 'lib/ServiceRequestNotificationHelper.php';
            $notificationHelper = new ServiceRequestNotificationHelper();
            echo "<p style='color: green;'>✅ ServiceRequestNotificationHelper loaded</p>";
            
            // Test user notification
            echo "<p>Testing user notification...</p>";
            $userNotifResult = $notificationHelper->notifyUserRequestInProgress(
                $request_id, 
                $request_data['user_id'], 
                $request_data['assigned_name']
            );
            echo "<p style='color: " . ($userNotifResult ? "green" : "red") . ";'>User notification: " . ($userNotifResult ? "✅ Sent" : "❌ Failed") . "</p>";
            
            // Test admin notification
            echo "<p>Testing admin notification...</p>";
            $adminNotifResult = $notificationHelper->notifyAdminStatusChange(
                $request_id, 
                'open', 
                'in_progress', 
                $request_data['assigned_name'], 
                $request_data['title']
            );
            echo "<p style='color: " . ($adminNotifResult ? "green" : "red") . ";'>Admin notification: " . ($adminNotifResult ? "✅ Sent" : "❌ Failed") . "</p>";
            
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ Notification test failed: " . $e->getMessage() . "</p>";
            echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
            echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
        }
        
        // Test email system
        echo "<h3>📧 Email System Test</h3>";
        try {
            require_once 'lib/EmailHelper.php';
            $emailHelper = new PHPMailerEmailHelper();
            echo "<p style='color: green;'>✅ EmailHelper loaded</p>";
            
            // Note: We won't actually send email in debug
            echo "<p>✅ Email system ready (not sending in debug mode)</p>";
            
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ Email system test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "<h2>🎯 Expected API Response</h2>";
        echo "<div style='background-color: #d4edda; padding: 10px; border-radius: 5px;'>";
        echo "<pre>{
    &quot;success&quot;: true,
    &quot;message&quot;: &quot;Request accepted successfully&quot;
}</pre>";
        echo "</div>";
        
    } else {
        echo "<p style='color: red;'>❌ Database update failed</p>";
        echo "<p><strong>Error Info:</strong></p>";
        echo "<pre>";
        print_r($update_stmt->errorInfo());
        echo "</pre>";
        throw new Exception("Failed to accept request");
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Exception Caught</h2>";
    echo "<div style='background-color: #f8d7da; padding: 10px; border-radius: 5px; border-left: 4px solid #dc3545;'>";
    echo "<p><strong>Error Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<p><strong>Trace:</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
    
    echo "<h2>🎯 Expected Error Response</h2>";
    echo "<div style='background-color: #f8d7da; padding: 10px; border-radius: 5px;'>";
    echo "<pre>{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;" . $e->getMessage() . "&quot;
}</pre>";
    echo "</div>";
}

echo "<hr>";
echo "<h2>🔧 Next Steps</h2>";
echo "<ol>";
echo "<li>If you see '✅ Database update successful', the API logic is working</li>";
echo "<li>If you see errors above, fix them before testing the actual API</li>";
echo "<li>Test the actual API by clicking the 'Nhận yêu cầu' button</li>";
echo "<li>Check browser network tab for the actual request/response</li>";
echo "<li>Check PHP error logs for additional details</li>";
echo "</ol>";

echo "<p><a href='index.html'>← Back to Main Application</a></p>";
echo "<p><a href='test-complete-accept-flow.php'>← Complete Accept Flow Test</a></p>";
?>
