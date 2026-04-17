<?php
// Test estimated completion display functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test Estimated Completion Display</h2>";

try {
    require_once __DIR__ . '/config/database.php';
    $db = getDatabaseConnection();
    
    // Create test request with estimated completion
    $insert = $db->prepare("
        INSERT INTO service_requests (title, description, user_id, category_id, status, estimated_completion, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $test_datetime = date('Y-m-d H:i:s', strtotime('+2 days'));
    $insert->execute(['TEST_ESTIMATED_DISPLAY', 'Test display of estimated completion', 1, 1, 'in_progress', $test_datetime]);
    $request_id = $db->lastInsertId();
    
    echo "Created test request ID: {$request_id} with estimated completion: {$test_datetime}<br>";
    
    // Simulate API call to get request details
    $ch = curl_init('http://localhost/it-service-request/api/service_requests.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/it-service-request/api/service_requests.php?id=' . $request_id);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "API Response: <pre>{$response}</pre><br>";
    
    // Parse response to check if estimated_completion is included
    $result = json_decode($response, true);
    
    if ($result && isset($result['data']['estimated_completion'])) {
        echo "SUCCESS: estimated_completion is included in API response<br>";
        echo "Value: " . $result['data']['estimated_completion'] . "<br>";
    } else {
        echo "FAILED: estimated_completion not found in API response<br>";
    }
    
    echo "<h3>Expected UI Display:</h3>";
    echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0;'>";
    echo "<div class='meta-item'><strong><i class='fas fa-clock text-primary'></i> Th?i gian d? ki?n hoàn thành:</strong> ";
    echo "<span class='text-primary fw-bold'>" . date('d/m/Y H:i', strtotime($test_datetime)) . "</span></div>";
    echo "</div>";
    
    echo "<h3>Next Steps:</h3>";
    echo "1. Check if API returns estimated_completion field<br>";
    echo "2. Verify frontend displays it in request details<br>";
    echo "3. Test with different user roles (admin, staff, user)<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>
