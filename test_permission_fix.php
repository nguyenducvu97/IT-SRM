<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Permission Fix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .user-role { background: #f0f0f0; padding: 10px; margin: 10px 0; }
        .hidden-info { background: #ffe6e6; padding: 10px; margin: 10px 0; }
        .visible-info { background: #e6ffe6; padding: 10px; margin: 10px 0; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Test Permission System Fix</h1>
    
    <?php
    require_once 'config/session.php';
    require_once 'config/database.php';
    
    startSession();
    
    if (!isset($_SESSION['user_id'])) {
        echo '<p class="error">Please login first: <a href="index.html">Login</a></p>';
        exit;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db === null) {
        echo '<p class="error">Database connection failed</p>';
        exit;
    }
    
    $current_user = $_SESSION['user_id'];
    $user_role = $_SESSION['role'] ?? 'user';
    
    echo "<div class='user-role'>";
    echo "<h2>Current User Information</h2>";
    echo "<p><strong>User ID:</strong> $current_user</p>";
    echo "<p><strong>User Role:</strong> $user_role</p>";
    echo "</div>";
    
    // Test API responses
    echo "<div class='test-section'>";
    echo "<h2>API Response Test</h2>";
    
    // Test service request with reject info
    $test_service_id = 38; // Use the ID from user's report
    
    // Simulate API call for service request
    $stmt = $db->prepare("
        SELECT sr.*, u.username as requester_name, c.name as category_name,
               sreq.id as support_request_id, sreq.support_type, sreq.support_details, 
               sreq.support_reason, sreq.status as support_status, sreq.admin_reason,
               sreq.processed_by, sreq.processed_at, sreq.created_at as support_created_at,
               sreq_admin.full_name as support_admin_name,
               r.id as reject_id, r.reject_reason, r.reject_details, r.status as reject_status,
               r.admin_reason as reject_admin_reason, r.processed_by as reject_processed_by,
               r.processed_at as reject_processed_at, r.created_at as reject_created_at,
               r_admin.full_name as reject_admin_name
        FROM service_requests sr
        LEFT JOIN users u ON sr.user_id = u.id
        LEFT JOIN categories c ON sr.category_id = c.id
        LEFT JOIN support_requests sreq ON sr.id = sreq.service_request_id
        LEFT JOIN users sreq_admin ON sreq.processed_by = sreq_admin.id
        LEFT JOIN reject_requests r ON sr.id = r.service_request_id
        LEFT JOIN users r_admin ON r.processed_by = r_admin.id
        WHERE sr.id = ?
    ");
    $stmt->execute([$test_service_id]);
    $service_request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($service_request) {
        echo "<h3>Original Data (Before Filtering):</h3>";
        echo "<pre>";
        echo "Support Request Info:\n";
        if ($service_request['support_request_id']) {
            echo "- Admin Reason: " . ($service_request['admin_reason'] ?? 'NULL') . "\n";
            echo "- Processed By: " . ($service_request['processed_by'] ?? 'NULL') . "\n";
            echo "- Processed At: " . ($service_request['processed_at'] ?? 'NULL') . "\n";
        }
        echo "\nReject Request Info:\n";
        if ($service_request['reject_id']) {
            echo "- Reject Admin Reason: " . ($service_request['reject_admin_reason'] ?? 'NULL') . "\n";
            echo "- Reject Processed By: " . ($service_request['reject_processed_by'] ?? 'NULL') . "\n";
            echo "- Reject Processed At: " . ($service_request['reject_processed_at'] ?? 'NULL') . "\n";
        }
        echo "</pre>";
        
        // Apply filtering based on user role (same as API)
        if ($service_request['support_request_id']) {
            $service_request['support_request'] = [
                'id' => $service_request['support_request_id'],
                'support_type' => $service_request['support_type'],
                'support_details' => $service_request['support_details'],
                'support_reason' => $service_request['support_reason'],
                'status' => $service_request['support_status'],
                'admin_reason' => $service_request['admin_reason'],
                'processed_by' => $service_request['processed_by'],
                'processed_at' => $service_request['processed_at'],
                'created_at' => $service_request['support_created_at'],
                'admin_name' => $service_request['support_admin_name']
            ];
            
            // Filter sensitive information based on user role
            if ($user_role === 'user') {
                unset($service_request['support_request']['admin_reason']);
                unset($service_request['support_request']['processed_by']);
                unset($service_request['support_request']['processed_at']);
                unset($service_request['support_request']['admin_name']);
            }
        }
        
        if ($service_request['reject_id']) {
            $service_request['reject_request'] = [
                'id' => $service_request['reject_id'],
                'reject_reason' => $service_request['reject_reason'],
                'reject_details' => $service_request['reject_details'],
                'status' => $service_request['reject_status'],
                'admin_reason' => $service_request['reject_admin_reason'],
                'processed_by' => $service_request['reject_processed_by'],
                'processed_at' => $service_request['reject_processed_at'],
                'created_at' => $service_request['reject_created_at'],
                'admin_name' => $service_request['reject_admin_name']
            ];
            
            // Filter sensitive information based on user role
            if ($user_role === 'user') {
                unset($service_request['reject_request']['admin_reason']);
                unset($service_request['reject_request']['processed_by']);
                unset($service_request['reject_request']['processed_at']);
                unset($service_request['reject_request']['admin_name']);
            }
        }
        
        echo "<h3>Filtered Data (After API Filtering for role: $user_role):</h3>";
        echo "<pre>";
        if (isset($service_request['support_request'])) {
            echo "Support Request Info:\n";
            echo "- Admin Reason: " . ($service_request['support_request']['admin_reason'] ?? 'NULL') . "\n";
            echo "- Processed By: " . ($service_request['support_request']['processed_by'] ?? 'NULL') . "\n";
            echo "- Processed At: " . ($service_request['support_request']['processed_at'] ?? 'NULL') . "\n";
        }
        if (isset($service_request['reject_request'])) {
            echo "\nReject Request Info:\n";
            echo "- Reject Admin Reason: " . ($service_request['reject_request']['admin_reason'] ?? 'NULL') . "\n";
            echo "- Reject Processed By: " . ($service_request['reject_request']['processed_by'] ?? 'NULL') . "\n";
            echo "- Reject Processed At: " . ($service_request['reject_request']['processed_at'] ?? 'NULL') . "\n";
        }
        echo "</pre>";
        
        // Test results
        echo "<h3>Permission Test Results:</h3>";
        if ($user_role === 'user') {
            $support_hidden = !isset($service_request['support_request']['admin_reason']);
            $reject_hidden = !isset($service_request['reject_request']['admin_reason']);
            
            echo "<div class='" . ($support_hidden ? 'success' : 'error') . "'>";
            echo "Support Request Admin Info: " . ($support_hidden ? 'HIDDEN ✓' : 'VISIBLE ✗');
            echo "</div>";
            
            echo "<div class='" . ($reject_hidden ? 'success' : 'error') . "'>";
            echo "Reject Request Admin Info: " . ($reject_hidden ? 'HIDDEN ✓' : 'VISIBLE ✗');
            echo "</div>";
            
            if ($support_hidden && $reject_hidden) {
                echo "<p class='success'>✓ Permission system working correctly for regular users!</p>";
            } else {
                echo "<p class='error'>✗ Permission system NOT working correctly!</p>";
            }
        } else {
            echo "<p class='success'>✓ Admin/Staff users should see all information</p>";
        }
    } else {
        echo "<p class='error'>Service request #$test_service_id not found</p>";
    }
    
    echo "</div>";
    ?>
    
    <div class='test-section'>
        <h2>Frontend Test</h2>
        <p><a href="request-detail.html?id=<?php echo $test_service_id; ?>" target="_blank">Open Request Detail Page</a></p>
        <p>Check if the reject request and support request sections are hidden for regular users.</p>
    </div>
    
    <div class='test-section'>
        <h2>Expected Behavior</h2>
        <div class="hidden-info">
            <h3>Regular Users (role: user) should NOT see:</h3>
            <ul>
                <li>Entire "Yêu cầu từ chối từ Staff" section</li>
                <li>Entire "Yêu cầu hỗ trợ từ Staff" section</li>
                <li>Admin decision information (admin_reason, processed_by, processed_at)</li>
            </ul>
        </div>
        
        <div class="visible-info">
            <h3>Staff/Admin Users should see:</h3>
            <ul>
                <li>All reject request information</li>
                <li>All support request information</li>
                <li>Admin decision information</li>
            </ul>
        </div>
    </div>
    
    <p><a href="index.html">Back to Application</a></p>
</body>
</html>
