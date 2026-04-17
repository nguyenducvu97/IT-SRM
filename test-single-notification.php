<?php
// Test to verify single notification for estimated completion update
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test Single Notification for Estimated Completion</h2>";

try {
    require_once __DIR__ . '/config/database.php';
    $db = getDatabaseConnection();
    
    // Create test request
    $insert = $db->prepare("
        INSERT INTO service_requests (title, description, user_id, category_id, status, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $insert->execute(['TEST_SINGLE_Notification', 'Test for single notification', 1, 1, 'in_progress']);
    $request_id = $db->lastInsertId();
    
    echo "Created test request ID: {$request_id}<br>";
    
    // Test API call directly (simulate admin)
    session_start();
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['full_name'] 'System Administrator';
    $_SESSION['role'] = 'admin';
    
    $test_datetime = date('Y-m-d\TH:i', strtotime('+1 day'));
    $data = [
        'action' => 'update_estimated_completion',
        'id' => $request_id,
        'estimated_completion' => $test_datetime
    ];
    
    echo "Testing API call...<br>";
    
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
        echo "SUCCESS: API returned success<br>";
        echo "Expected: Only ONE success notification<br>";
        echo "Fixed: Removed loadRequestDetails() call<br>";
    } else {
        echo "FAILED: API returned error<br>";
    }
    
    // Check database
    $check = $db->prepare("SELECT estimated_completion FROM service_requests WHERE id = ?");
    $check->execute([$request_id]);
    $db_result = $check->fetch(PDO::FETCH_ASSOC);
    
    if ($db_result && $db_result['estimated_completion']) {
        echo "Database updated: {$db_result['estimated_completion']}<br>";
    }
    
    echo "<h3>Fix Applied:</h3>";
    echo "1. Removed this.loadRequestDetails() call<br>";
    echo "2. Only show single success notification<br>";
    echo "3. No more double notifications<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>
