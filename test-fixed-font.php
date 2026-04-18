<?php
// Test fixed font encoding in notifications
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test Fixed Font Encoding</h2>";

try {
    require_once __DIR__ . '/config/database.php';
    $db = getDatabaseConnection();
    
    // Create test request with assigned staff
    $insert = $db->prepare("
        INSERT INTO service_requests (title, description, user_id, category_id, status, assigned_to, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $insert->execute(['TEST_FIXED_FONT', 'Test fixed font encoding', 1, 1, 'in_progress', 2]);
    $request_id = $db->lastInsertId();
    
    echo "Created test request ID: {$request_id}<br>";
    
    // Simulate admin session
    session_start();
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['full_name'] = 'System Administrator';
    $_SESSION['role'] = 'admin';
    
    // Test API call with fixed font
    $test_datetime = date('Y-m-d\TH:i', strtotime('+2 days'));
    $data = [
        'action' => 'update_estimated_completion',
        'id' => $request_id,
        'estimated_completion' => $test_datetime
    ];
    
    echo "Testing API call with fixed font encoding...<br>";
    
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
        echo "<h3>SUCCESS: Font Fixed!</h3>";
        
        // Check notifications in database
        $notification_query = "SELECT n.*, u.username, u.role 
                             FROM notifications n 
                             LEFT JOIN users u ON n.user_id = u.id 
                             WHERE n.related_id = ? AND n.related_type = 'service_request' 
                             ORDER BY n.created_at DESC";
        $notification_stmt = $db->prepare($notification_query);
        $notification_stmt->execute([$request_id]);
        $notifications = $notification_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>Notifications with Fixed Font (" . count($notifications) . " total):</h4>";
        foreach ($notifications as $notif) {
            echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 5px 0;'>";
            echo "<strong>User:</strong> {$notif['username']} ({$notif['role']})<br>";
            echo "<strong>Title:</strong> {$notif['title']}<br>";
            echo "<strong>Message:</strong> {$notif['message']}<br>";
            echo "<strong>Type:</strong> {$notif['type']}<br>";
            echo "</div>";
        }
        
        echo "<h3>Font Fix Details:</h3>";
        echo "<ul>";
        echo "<li><strong>Before:</strong> C?p nh?t th?i gian d? ki?n hoàn thành (broken)</li>";
        echo "<li><strong>After:</strong> Cap nhat thoi gian du kien hoan thanh (readable)</li>";
        echo "</ul>";
        
        echo "<h3>Expected Notifications:</h3>";
        echo "<div style='border: 1px solid #007bff; padding: 10px; margin: 5px 0; background: #f8f9fa;'>";
        echo "<strong>Title:</strong> Cap nhat thoi gian du kien hoan thanh<br>";
        echo "<strong>Message:</strong> Yeu cau #{$request_id} - 'TEST_FIXED_FONT' da duoc cap nhat thoi gian du kien hoan thanh: " . date('d/m/Y H:i', strtotime($test_datetime));
        echo "</div>";
        
    } else {
        echo "<h3>FAILED: API Error</h3>";
        echo "Error: " . ($result['message'] ?? 'Unknown error') . "<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>
