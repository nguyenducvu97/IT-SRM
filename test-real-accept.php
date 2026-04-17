<?php
// Test real accept request with proper session and logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test Real Accept Request</h2>";

// Step 1: Create a test request first
try {
    require_once __DIR__ . '/config/database.php';
    $db = getDatabaseConnection();
    
    // Clean up old test requests
    $db->exec("DELETE FROM service_requests WHERE title LIKE 'TEST_ACCEPT_%'");
    
    // Create new test request
    $insert = $db->prepare("
        INSERT INTO service_requests (title, description, user_id, category_id, status, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $insert->execute(['TEST_ACCEPT_Request', 'Test description for accept', 1, 1, 'open']);
    $request_id = $db->lastInsertId();
    
    echo "✅ Created test request ID: {$request_id}<br>";
    
    // Step 2: Simulate staff session
    session_start();
    $_SESSION['user_id'] = 2; // staff1
    $_SESSION['username'] = 'staff1';
    $_SESSION['full_name'] = 'John Smith';
    $_SESSION['role'] = 'staff';
    
    echo "✅ Session created: " . json_encode($_SESSION) . "<br>";
    
    // Step 3: Make PUT request like frontend
    $data = [
        'action' => 'accept_request',
        'request_id' => $request_id
    ];
    
    echo "📤 Sending PUT request...<br>";
    
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
    
    echo "📥 API Response:<br>";
    echo "HTTP Code: {$http_code}<br>";
    echo "Response: <pre>{$response}</pre>";
    
    // Step 4: Wait a bit for background processing
    echo "⏳ Waiting 3 seconds for background processing...<br>";
    sleep(3);
    
    // Step 5: Check results
    echo "🔍 Checking results...<br>";
    
    // Check request status
    $req_check = $db->prepare("SELECT status, assigned_to FROM service_requests WHERE id = ?");
    $req_check->execute([$request_id]);
    $req_status = $req_check->fetch(PDO::FETCH_ASSOC);
    
    echo "Request status: " . json_encode($req_status) . "<br>";
    
    // Check notifications
    $notif_check = $db->prepare("
        SELECT * FROM notifications 
        WHERE related_id = ? AND related_type = 'service_request' 
        ORDER BY created_at DESC 
    ");
    $notif_check->execute([$request_id]);
    $notifications = $notif_check->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Notifications created: " . count($notifications) . "<br>";
    foreach ($notifications as $notif) {
        $time = new DateTime($notif['created_at']);
        echo "- {$time->format('H:i:s')}: User {$notif['user_id']} - {$notif['title']}<br>";
    }
    
    // Step 6: Check logs
    echo "📋 Checking recent logs...<br>";
    $log_file = __DIR__ . '/logs/api_errors.log';
    if (file_exists($log_file)) {
        $logs = file_get_contents($log_file);
        $recent_logs = substr($logs, -2000); // Last 2000 characters
        echo "Recent logs (last 2000 chars):<pre>{$recent_logs}</pre>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?>
