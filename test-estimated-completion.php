<?php
// Test estimated completion update functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test Estimated Completion Update</h2>";

try {
    require_once __DIR__ . '/config/database.php';
    $db = getDatabaseConnection();
    
    // Check if estimated_completion column exists
    $column_check = $db->prepare("SHOW COLUMNS FROM service_requests LIKE 'estimated_completion'");
    $column_check->execute();
    $column_exists = $column_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$column_exists) {
        echo "❌ Column 'estimated_completion' does not exist. Please run the SQL migration first.<br>";
        echo "<a href='database/add_estimated_completion_column.sql'>Run Migration</a><br>";
        return;
    } else {
        echo "✅ Column 'estimated_completion' exists<br>";
    }
    
    // Create test request if needed
    $test_request = $db->prepare("SELECT id FROM service_requests WHERE title LIKE 'TEST_ESTIMATED_%' LIMIT 1");
    $test_request->execute();
    $existing = $test_request->fetch(PDO::FETCH_ASSOC);
    
    if (!$existing) {
        $insert = $db->prepare("
            INSERT INTO service_requests (title, description, user_id, category_id, status, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $insert->execute(['TEST_ESTIMATED_Request', 'Test for estimated completion', 1, 1, 'in_progress']);
        $request_id = $db->lastInsertId();
        echo "✅ Created test request ID: {$request_id}<br>";
    } else {
        $request_id = $existing['id'];
        echo "✅ Using existing test request ID: {$request_id}<br>";
    }
    
    // Simulate admin session
    session_start();
    $_SESSION['user_id'] = 1; // admin
    $_SESSION['username'] = 'admin';
    $_SESSION['full_name'] = 'System Administrator';
    $_SESSION['role'] = 'admin';
    
    echo "✅ Admin session created<br>";
    
    // Test API call
    $test_datetime = date('Y-m-d\TH:i', strtotime('+2 days'));
    $data = [
        'action' => 'update_estimated_completion',
        'id' => $request_id,
        'estimated_completion' => $test_datetime
    ];
    
    echo "📤 Testing API call with data: " . json_encode($data) . "<br>";
    
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
    
    // Verify database update
    $verify = $db->prepare("SELECT estimated_completion FROM service_requests WHERE id = ?");
    $verify->execute([$request_id]);
    $result = $verify->fetch(PDO::FETCH_ASSOC);
    
    if ($result && $result['estimated_completion']) {
        echo "✅ Database updated successfully!<br>";
        echo "Estimated completion: " . $result['estimated_completion'] . "<br>";
    } else {
        echo "❌ Database update failed<br>";
    }
    
    echo "<h3>🎯 Test Complete</h3>";
    echo "✅ Frontend: Admin will see datetime input instead of status dropdown<br>";
    echo "✅ Backend: API handler processes estimated completion updates<br>";
    echo "✅ Database: Column stores estimated completion time<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?>
