<?php
// Test accept request with FIXED direct processing
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test FIXED Accept Request (Direct Processing)</h2>";

try {
    require_once __DIR__ . '/config/database.php';
    $db = getDatabaseConnection();
    
    // Clean up old test requests
    $db->exec("DELETE FROM service_requests WHERE title LIKE 'TEST_FIXED_%'");
    
    // Create new test request
    $insert = $db->prepare("
        INSERT INTO service_requests (title, description, user_id, category_id, status, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $insert->execute(['TEST_FIXED_Request', 'Test description for fixed accept', 1, 1, 'open']);
    $request_id = $db->lastInsertId();
    
    echo "✅ Created test request ID: {$request_id}<br>";
    
    // Simulate staff session
    session_start();
    $_SESSION['user_id'] = 2; // staff1
    $_SESSION['username'] = 'staff1';
    $_SESSION['full_name'] = 'John Smith';
    $_SESSION['role'] = 'staff';
    
    echo "✅ Session created: " . json_encode($_SESSION) . "<br>";
    
    // Make PUT request like frontend
    $data = [
        'action' => 'accept_request',
        'request_id' => $request_id
    ];
    
    echo "📤 Sending PUT request with FIXED direct processing...<br>";
    
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
    
    // Check results immediately (no need to wait for background)
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
    
    echo "🎉 Notifications created: " . count($notifications) . "<br>";
    foreach ($notifications as $notif) {
        $time = new DateTime($notif['created_at']);
        echo "- {$time->format('H:i:s')}: User {$notif['user_id']} - {$notif['title']} ({$notif['type']})<br>";
    }
    
    // Check logs for debug messages
    echo "📋 Checking recent debug logs...<br>";
    $log_file = __DIR__ . '/logs/api_errors.log';
    if (file_exists($log_file)) {
        $logs = file_get_contents($log_file);
        $recent_logs = substr($logs, -3000); // Last 3000 characters
        
        // Extract debug messages for our request
        $debug_lines = explode("\n", $recent_logs);
        $our_debug = [];
        foreach ($debug_lines as $line) {
            if (strpos($line, "request_id={$request_id}") !== false || 
                strpos($line, "Notifications sent for request #{$request_id}") !== false) {
                $our_debug[] = $line;
            }
        }
        
        if (!empty($our_debug)) {
            echo "Debug messages for request #{$request_id}:<br>";
            foreach ($our_debug as $debug) {
                echo "- " . htmlspecialchars($debug) . "<br>";
            }
        } else {
            echo "No debug messages found for request #{$request_id}<br>";
        }
    }
    
    echo "<h2>🎯 RESULT</h2>";
    if ($http_code == 200 && count($notifications) >= 2) {
        echo "✅ SUCCESS: Request accepted AND notifications sent!<br>";
    } else {
        echo "❌ ISSUE: Something still not working<br>";
        echo "- HTTP Code: {$http_code} (expected 200)<br>";
        echo "- Notifications: " . count($notifications) . " (expected 2+)<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?>
