<?php
// Test complete implementation of estimated completion display
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test Complete Implementation</h2>";

try {
    require_once __DIR__ . '/config/database.php';
    $db = getDatabaseConnection();
    
    // Create test request with estimated completion
    $insert = $db->prepare("
        INSERT INTO service_requests (title, description, user_id, category_id, status, estimated_completion, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $test_datetime = date('Y-m-d H:i:s', strtotime('+3 days'));
    $insert->execute(['TEST_COMPLETE_Display', 'Test complete implementation', 1, 1, 'in_progress', $test_datetime]);
    $request_id = $db->lastInsertId();
    
    echo "Created test request ID: {$request_id}<br>";
    echo "Estimated completion: {$test_datetime}<br>";
    
    // Simulate API call to get request details
    $ch = curl_init('http://localhost/it-service-request/api/service_requests.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/it-service-request/api/service_requests.php?id=' . $request_id);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<h3>API Response:</h3>";
    echo "<pre>{$response}</pre>";
    
    // Parse response
    $result = json_decode($response, true);
    
    if ($result && isset($result['data']['estimated_completion'])) {
        echo "<h3>SUCCESS: Backend Implementation</h3>";
        echo "Backend returns estimated_completion: " . $result['data']['estimated_completion'] . "<br>";
        
        $formatted_date = date('d/m/Y H:i', strtotime($result['data']['estimated_completion']));
        echo "Formatted date: {$formatted_date}<br>";
        
        echo "<h3>Frontend Implementation:</h3>";
        echo "Code added to displayRequestDetail() at line 8571-8579<br>";
        echo "Display will show for all users (Admin, Staff, User)<br>";
        
        echo "<h3>Expected UI:</h3>";
        echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0;'>";
        echo "<div class='meta-item'>";
        echo "<strong><i class='fas fa-clock text-primary'></i> Th?i gian d? ki?n hoàn thành:</strong> ";
        echo "<span class='text-primary fw-bold'>{$formatted_date}</span>";
        echo "</div>";
        echo "</div>";
        
        echo "<h3>Testing Steps:</h3>";
        echo "1. Admin sets estimated completion time<br>";
        echo "2. Check if Staff can see it in request details<br>";
        echo "3. Check if User can see it in request details<br>";
        
    } else {
        echo "<h3>FAILED: Backend Issue</h3>";
        echo "Backend doesn't return estimated_completion field<br>";
        echo "Check API query in service_requests.php<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>
