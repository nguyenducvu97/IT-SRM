<?php
// Debug real accept request flow step by step
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>DEBUG: Real Accept Request Flow</h1>";

// Start session and simulate staff login
session_start();
$_SESSION['user_id'] = 2;
$_SESSION['username'] = 'staff';
$_SESSION['full_name'] = 'Test Staff User';
$_SESSION['role'] = 'staff';

echo "<p>Logged in as: {$_SESSION['full_name']} (Role: {$_SESSION['role']})</p>";

// Step 1: Find an open request
echo "<h2>Step 1: Find Open Request</h2>";
try {
    require_once 'config/database.php';
    $db = (new Database())->getConnection();
    
    $stmt = $db->query("SELECT id, title, user_id, status, assigned_to FROM service_requests WHERE status = 'open' AND (assigned_to IS NULL OR assigned_to = 0) LIMIT 1");
    $openRequest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($openRequest) {
        echo "<p style='color: green;'>Found open request:</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Title</th><th>User ID</th><th>Status</th><th>Assigned To</th></tr>";
        echo "<tr>";
        echo "<td>{$openRequest['id']}</td>";
        echo "<td>" . htmlspecialchars($openRequest['title']) . "</td>";
        echo "<td>{$openRequest['user_id']}</td>";
        echo "<td>{$openRequest['status']}</td>";
        echo "<td>{$openRequest['assigned_to']}</td>";
        echo "</tr>";
        echo "</table>";
        
        $request_id = $openRequest['id'];
        $user_id = $openRequest['user_id'];
        
    } else {
        echo "<p style='color: red;'>No open requests found. Creating one for testing...</p>";
        
        // Create a test request
        $insertStmt = $db->prepare("INSERT INTO service_requests (user_id, title, description, category_id, priority, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $insertResult = $insertStmt->execute([
            1, // user_id (assuming user 1 exists)
            'Test Request for Debug',
            'This is a test request created for debugging notifications',
            1, // category_id
            'medium',
            'open'
        ]);
        
        if ($insertResult) {
            $request_id = $db->lastInsertId();
            $user_id = 1;
            echo "<p style='color: green;'>Created test request #$request_id</p>";
        } else {
            echo "<p style='color: red;'>Failed to create test request</p>";
            exit;
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
    exit;
}

// Step 2: Update request (simulate accept)
echo "<h2>Step 2: Update Request (Accept)</h2>";
try {
    $updateStmt = $db->prepare("UPDATE service_requests SET assigned_to = ?, status = 'in_progress', assigned_at = NOW(), accepted_at = NOW(), updated_at = NOW() WHERE id = ?");
    $updateResult = $updateStmt->execute([$_SESSION['user_id'], $request_id]);
    
    if ($updateResult) {
        echo "<p style='color: green;'>Request updated successfully</p>";
        
        // Verify update
        $verifyStmt = $db->prepare("SELECT * FROM service_requests WHERE id = ?");
        $verifyStmt->execute([$request_id]);
        $updatedRequest = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p>Updated request details:</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        foreach (['id', 'user_id', 'assigned_to', 'status', 'assigned_at', 'accepted_at'] as $field) {
            echo "<tr>";
            echo "<td>$field</td>";
            echo "<td>" . htmlspecialchars($updatedRequest[$field] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: red;'>Failed to update request</p>";
        exit;
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Update error: " . $e->getMessage() . "</p>";
    exit;
}

// Step 3: Get request details for notifications
echo "<h2>Step 3: Get Request Details</h2>";
try {
    $request_query = "SELECT sr.*, u.full_name as requester_name, u.email as requester_email, 
                             staff.full_name as assigned_name, staff.email as assigned_email, c.name as category_name
                      FROM service_requests sr
                      LEFT JOIN users u ON sr.user_id = u.id
                      LEFT JOIN users staff ON sr.assigned_to = staff.id
                      LEFT JOIN categories c ON sr.category_id = c.id
                      WHERE sr.id = ?";
    $request_stmt = $db->prepare($request_query);
    $request_stmt->execute([$request_id]);
    $request_data = $request_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($request_data) {
        echo "<p style='color: green;'>Request details loaded:</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        foreach (['id', 'title', 'requester_name', 'requester_email', 'assigned_name', 'assigned_email', 'category_name'] as $field) {
            echo "<tr>";
            echo "<td>$field</td>";
            echo "<td>" . htmlspecialchars($request_data[$field] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check if user and staff data exists
        if (empty($request_data['requester_email'])) {
            echo "<p style='color: orange;'>Warning: Requester email not found</p>";
        }
        if (empty($request_data['assigned_name'])) {
            echo "<p style='color: orange;'>Warning: Assigned staff name not found</p>";
        }
        
    } else {
        echo "<p style='color: red;'>Failed to load request details</p>";
        exit;
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Request details error: " . $e->getMessage() . "</p>";
    exit;
}

// Step 4: Test User Notification
echo "<h2>Step 4: Test User Notification</h2>";
try {
    require_once 'lib/ServiceRequestNotificationHelper.php';
    $notificationHelper = new ServiceRequestNotificationHelper();
    
    echo "<p>Testing user notification for user_id: {$request_data['user_id']}</p>";
    
    $userNotificationResult = $notificationHelper->notifyUserRequestInProgress(
        $request_id, 
        $request_data['user_id'], 
        $request_data['assigned_name']
    );
    
    echo "<p>User notification result: " . ($userNotificationResult ? "SUCCESS" : "FAILED") . "</p>";
    
    // Check if notification was created
    $checkStmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? AND related_id = ? AND related_type = 'service_request' ORDER BY created_at DESC LIMIT 1");
    $checkStmt->execute([$request_data['user_id'], $request_id]);
    $userNotification = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userNotification) {
        echo "<p style='color: green;'>User notification found in database:</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Title</th><th>Message</th><th>Created</th></tr>";
        echo "<tr>";
        echo "<td>{$userNotification['id']}</td>";
        echo "<td>" . htmlspecialchars($userNotification['title']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($userNotification['message'], 0, 100)) . "...</td>";
        echo "<td>{$userNotification['created_at']}</td>";
        echo "</tr>";
        echo "</table>";
    } else {
        echo "<p style='color: red;'>No user notification found in database</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>User notification error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}

// Step 5: Test Admin Notification
echo "<h2>Step 5: Test Admin Notification</h2>";
try {
    echo "<p>Testing admin notification...</p>";
    
    $adminNotificationResult = $notificationHelper->notifyAdminStatusChange(
        $request_id, 
        'open', 
        'in_progress', 
        $request_data['assigned_name'], 
        $request_data['title']
    );
    
    echo "<p>Admin notification result: " . ($adminNotificationResult ? "SUCCESS" : "FAILED") . "</p>";
    
    // Check admin users
    $adminStmt = $db->query("SELECT id, username, full_name FROM users WHERE role = 'admin'");
    $adminUsers = $adminStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($adminUsers)) {
        echo "<p>Found " . count($adminUsers) . " admin users:</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Notification</th></tr>";
        
        foreach ($adminUsers as $admin) {
            $checkStmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? AND related_id = ? AND related_type = 'service_request' ORDER BY created_at DESC LIMIT 1");
            $checkStmt->execute([$admin['id'], $request_id]);
            $adminNotification = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<tr>";
            echo "<td>{$admin['id']}</td>";
            echo "<td>{$admin['username']}</td>";
            echo "<td>{$admin['full_name']}</td>";
            if ($adminNotification) {
                echo "<td style='color: green;'>FOUND: " . htmlspecialchars(substr($adminNotification['title'], 0, 30)) . "...</td>";
            } else {
                echo "<td style='color: red;'>NOT FOUND</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>No admin users found in database</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Admin notification error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}

// Step 6: Test Email
echo "<h2>Step 6: Test Email</h2>";
try {
    require_once 'lib/EmailHelper.php';
    $emailHelper = new EmailHelper();
    
    echo "<p>Email configuration:</p>";
    $config = $emailHelper->getConfig();
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Setting</th><th>Value</th></tr>";
    foreach (['smtp_server', 'port', 'username', 'from_email'] as $key) {
        echo "<tr>";
        echo "<td>$key</td>";
        echo "<td>" . htmlspecialchars($config[$key] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test email preparation (not actually sending)
    if (!empty($request_data['requester_email'])) {
        echo "<p>Preparing email for: {$request_data['requester_email']}</p>";
        
        $subject = "Yêu câu #{$request_id} - Tràng thái thay thành 'in_progress'";
        $body = "Chào {$request_data['requester_name']},\n\n";
        $body .= "Yêu câu #{$request_id} ('{$request_data['title']}') cua ban da duoc nhan boi nhân viên IT.\n\n";
        $body .= "Nhân viên phu trách: {$request_data['assigned_name']}\n\n";
        $body .= "Trang thái: in_progress\n\n";
        $body .= "Ban có the xem chi tiêt tai: http://localhost/it-service-request/request-detail.html?id={$request_id}\n\n";
        $body .= "Trân tr,\n";
        $body .= "IT Service Request System";
        
        echo "<p>Email prepared:</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>To</td><td>{$request_data['requester_email']}</td></tr>";
        echo "<tr><td>Name</td><td>{$request_data['requester_name']}</td></tr>";
        echo "<tr><td>Subject</td><td>" . htmlspecialchars($subject) . "</td></tr>";
        echo "<tr><td>Body</td><td>" . htmlspecialchars(substr($body, 0, 200)) . "...</td></tr>";
        echo "</table>";
        
        // Uncomment to actually send email for testing
        /*
        $emailResult = $emailHelper->sendEmail(
            $request_data['requester_email'],
            $request_data['requester_name'],
            $subject,
            $body
        );
        echo "<p>Email send result: " . ($emailResult ? "SUCCESS" : "FAILED") . "</p>";
        */
        
        echo "<p style='color: blue;'>Email sending commented out for testing. Uncomment to test actual email.</p>";
        
    } else {
        echo "<p style='color: red;'>Cannot prepare email - requester email not found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Email error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}

// Step 7: Summary
echo "<h2>Step 7: Summary</h2>";
echo "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<h3>Debug Results:</h3>";
echo "<ol>";
echo "<li><strong>Database Update:</strong> " . (isset($updateResult) && $updateResult ? "SUCCESS" : "FAILED") . "</li>";
echo "<li><strong>Request Details:</strong> " . (isset($request_data) && $request_data ? "SUCCESS" : "FAILED") . "</li>";
echo "<li><strong>User Notification:</strong> " . (isset($userNotificationResult) && $userNotificationResult ? "SUCCESS" : "FAILED") . "</li>";
echo "<li><strong>Admin Notification:</strong> " . (isset($adminNotificationResult) && $adminNotificationResult ? "SUCCESS" : "FAILED") . "</li>";
echo "<li><strong>Email Preparation:</strong> " . (isset($emailHelper) ? "SUCCESS" : "FAILED") . "</li>";
echo "</ol>";

echo "<h3>Next Steps:</h3>";
echo "<ul>";
echo "<li>If notifications show SUCCESS but users don't see them, check the frontend notification display</li>";
echo "<li>If notifications show FAILED, check the notification helper implementation</li>";
echo "<li>If email preparation works, uncomment the email send code to test actual email</li>";
echo "<li>Check browser console for JavaScript errors when testing the actual UI</li>";
echo "<li>Monitor server logs for any errors during the actual accept request</li>";
echo "</ul>";
echo "</div>";

echo "<p><a href='index.html'>Back to Main Application</a></p>";
?>
