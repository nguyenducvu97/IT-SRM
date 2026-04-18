<?php
// Test notifications after fixing constructor issue
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test Notifications Fixed</h2>";

try {
    require_once __DIR__ . '/config/database.php';
    $db = getDatabaseConnection();
    
    // Create test request with assigned staff
    $insert = $db->prepare("
        INSERT INTO service_requests (title, description, user_id, category_id, status, assigned_to, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $insert->execute(['TEST_NOTIFICATIONS_FIXED', 'Test notifications after fix', 1, 1, 'in_progress', 2]);
    $request_id = $db->lastInsertId();
    
    echo "Created test request ID: {$request_id}<br>";
    echo "User ID: 1, Assigned Staff ID: 2<br>";
    
    // Simulate admin session
    session_start();
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['full_name'] = 'System Administrator';
    $_SESSION['role'] = 'admin';
    
    // Test API call with notifications enabled
    $test_datetime = date('Y-m-d\TH:i', strtotime('+2 days'));
    $data = [
        'action' => 'update_estimated_completion',
        'id' => $request_id,
        'estimated_completion' => $test_datetime
    ];
    
    echo "Testing API call with notifications enabled...<br>";
    
    $ch = curl_init('http://localhost/it-service-request/api/service_requests.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Cookie: ' . session_name() . '=' . session_id()
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    echo "HTTP Status: {$http_code}<br>";
    if ($curl_error) {
        echo "CURL Error: {$curl_error}<br>";
    }
    
    echo "API Response: <pre>{$response}</pre><br>";
    
    // Parse response
    $result = json_decode($response, true);
    
    if ($result && $result['success']) {
        echo "<h3>SUCCESS: API + Notifications Working!</h3>";
        echo "Message: " . $result['message'] . "<br>";
        
        // Check notifications in database
        $notification_query = "SELECT n.*, u.username, u.role 
                             FROM notifications n 
                             LEFT JOIN users u ON n.user_id = u.id 
                             WHERE n.related_id = ? AND n.related_type = 'service_request' 
                             ORDER BY n.created_at DESC";
        $notification_stmt = $db->prepare($notification_query);
        $notification_stmt->execute([$request_id]);
        $notifications = $notification_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>Notifications Created (" . count($notifications) . " total):</h4>";
        foreach ($notifications as $notif) {
            echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 5px 0;'>";
            echo "<strong>User:</strong> {$notif['username']} ({$notif['role']})<br>";
            echo "<strong>Title:</strong> {$notif['title']}<br>";
            echo "<strong>Message:</strong> {$notif['message']}<br>";
            echo "<strong>Type:</strong> {$notif['type']}<br>";
            echo "<strong>Created:</strong> {$notif['created_at']}<br>";
            echo "</div>";
        }
        
        echo "<h3>Expected Notifications:</h3>";
        echo "<ul>";
        echo "<li>1. User (ID 1) - Request creator</li>";
        echo "<li>2. Assigned Staff (ID 2) - Staff handling request</li>";
        echo "<li>3. All other Staff - General notification</li>";
        echo "</ul>";
        
        // Verify database update
        $check = $db->prepare("SELECT estimated_completion FROM service_requests WHERE id = ?");
        $check->execute([$request_id]);
        $db_result = $check->fetch(PDO::FETCH_ASSOC);
        
        if ($db_result && $db_result['estimated_completion']) {
            echo "<h4>Database Updated:</h4> " . $db_result['estimated_completion'] . "<br>";
        }
        
    } else {
        echo "<h3>FAILED: API Error</h3>";
        echo "Error: " . ($result['message'] ?? 'Unknown error') . "<br>";
        echo "Check if ServiceRequestNotificationHelper constructor is correct<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>
