<?php
// Test the fixed API handler
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test Fixed API Handler</h2>";

try {
    require_once __DIR__ . '/config/database.php';
    $db = getDatabaseConnection();
    
    // Create test request
    $insert = $db->prepare("
        INSERT INTO service_requests (title, description, user_id, category_id, status, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $insert->execute(['TEST_FIXED_API', 'Test fixed API handler', 1, 1, 'in_progress']);
    $request_id = $db->lastInsertId();
    
    echo "Created test request ID: {$request_id}<br>";
    
    // Simulate admin session
    session_start();
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['full_name'] = 'System Administrator';
    $_SESSION['role'] = 'admin';
    
    // Test API call
    $test_datetime = date('Y-m-d\TH:i', strtotime('+2 days'));
    $data = [
        'action' => 'update_estimated_completion',
        'id' => $request_id,
        'estimated_completion' => $test_datetime
    ];
    
    echo "Testing API call with data: <pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre><br>";
    
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
        echo "<h3>SUCCESS: API Fixed!</h3>";
        echo "Message: " . $result['message'] . "<br>";
        
        // Verify database update
        $check = $db->prepare("SELECT estimated_completion FROM service_requests WHERE id = ?");
        $check->execute([$request_id]);
        $db_result = $check->fetch(PDO::FETCH_ASSOC);
        
        if ($db_result && $db_result['estimated_completion']) {
            echo "Database updated: " . $db_result['estimated_completion'] . "<br>";
            echo "<h3>Frontend should now work!</h3>";
            echo "No more HTTP 500 errors<br>";
        }
    } else {
        echo "<h3>FAILED: API Still Has Issues</h3>";
        echo "Error: " . ($result['message'] ?? 'Unknown error') . "<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>
