<?php
// Test notifications for estimated completion update
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test Estimated Completion Notifications</h2>";

try {
    require_once __DIR__ . '/config/database.php';
    $db = getDatabaseConnection();
    
    // Create test request with assigned staff
    $insert = $db->prepare("
        INSERT INTO service_requests (title, description, user_id, category_id, status, assigned_to, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $insert->execute(['TEST_NOTIFICATIONS', 'Test notification system', 1, 1, 'in_progress', 2]); // Assuming user_id=1, assigned_to=2 (staff)
    $request_id = $db->lastInsertId();
    
    echo "Created test request ID: {$request_id}<br>";
    echo "Assigned to staff ID: 2<br>";
    
    // Simulate admin session
    session_start();
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['full_name'] = 'System Administrator';
    $_SESSION['role'] = 'admin';
    
    // Test API call with notification
    $test_datetime = date('Y-m-d\TH:i', strtotime('+2 days'));
    $data = [
        'action' => 'update_estimated_completion',
        'id' => $request_id,
        'estimated_completion' => $test_datetime
    ];
    
    echo "Testing API call with notifications...<br>";
    
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
    curl_close($ch);
    
    echo "API Response: <pre>{$response}</pre><br>";
    
    // Parse response
    $result = json_decode($response, true);
    
    if ($result && $result['success']) {
        echo "<h3>SUCCESS: Notifications Sent!</h3>";
        
        // Check notifications in database
        $notification_query = "SELECT * FROM notifications WHERE related_id = ? AND related_type = 'service_request' ORDER BY created_at DESC";
        $notification_stmt = $db->prepare($notification_query);
        $notification_stmt->execute([$request_id]);
        $notifications = $notification_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>Notifications Created:</h4>";
        foreach ($notifications as $notif) {
            echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 5px 0;'>";
            echo "<strong>User ID:</strong> {$notif['user_id']}<br>";
            echo "<strong>Title:</strong> {$notif['title']}<br>";
            echo "<strong>Message:</strong> {$notif['message']}<br>";
            echo "<strong>Type:</strong> {$notif['type']}<br>";
            echo "<strong>Created:</strong> {$notif['created_at']}<br>";
            echo "</div>";
        }
        
        echo "<h3>Expected Notifications:</h3>";
        echo "<ul>";
        echo "<li><strong>User (ID 1):</strong> Nh?n thông báo c? nh?t th?i gian d? ki?n</li>";
        echo "<li><strong>Assigned Staff (ID 2):</strong> Nh?n thông báo c? nh?t th?i gian d? ki?n</li>";
        echo "<li><strong>All Staff:</strong> Nh?n thông báo admin dã c?p nh?t th?i gian</li>";
        echo "</ul>";
        
    } else {
        echo "<h3>FAILED: API Error</h3>";
        echo "Error: " . ($result['message'] ?? 'Unknown error') . "<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>
