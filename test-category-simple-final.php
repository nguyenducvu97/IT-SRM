<?php
// Simple test for add category
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Simple Test: Add Category</h1>";

// Start session
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

echo "Session started for admin user<br>";

// Test data
$test_name = "Danh mục Test " . date('H:i:s');
$test_desc = "Mô tả danh mục test";

echo "Test category name: " . htmlspecialchars($test_name) . "<br>";
echo "Test description: " . htmlspecialchars($test_desc) . "<br>";

// Test direct database insert
echo "<h2>Direct Database Test</h2>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO categories (name, description) VALUES (?, ?)";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$test_name, $test_desc])) {
        $id = $db->lastInsertId();
        echo "✅ Direct insert SUCCESS - ID: $id<br>";
        
        // Clean up
        $delete_query = "DELETE FROM categories WHERE id = ?";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->execute([$id]);
        echo "✅ Test data cleaned up<br>";
    } else {
        echo "❌ Direct insert FAILED<br>";
        print_r($stmt->errorInfo());
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test API call
echo "<h2>API Test</h2>";
$post_data = [
    'name' => $test_name,
    'description' => $test_desc
];

$json_data = json_encode($post_data, JSON_UNESCAPED_UNICODE);
echo "JSON data: " . htmlspecialchars($json_data) . "<br>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/it-service-request/api/categories.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json; charset=UTF-8',
    'Content-Length: ' . strlen($json_data)
]);
curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code<br>";
echo "Raw Response: " . htmlspecialchars($response) . "<br>";

$response_data = json_decode($response, true);
if ($response_data) {
    echo "Parsed Response: <pre>" . print_r($response_data, true) . "</pre>";
    
    if ($response_data['success']) {
        echo "<strong style='color: green;'>✅ API SUCCESS - Category ID: " . $response_data['data']['id'] . "</strong><br>";
    } else {
        echo "<strong style='color: red;'>❌ API FAILED - " . $response_data['message'] . "</strong><br>";
    }
} else {
    echo "<strong style='color: red;'>❌ Invalid JSON response</strong><br>";
}

echo "<h2>Check Browser Console</h2>";
echo "<p>Open the main application and check browser console for JavaScript errors when adding category.</p>";
echo "<p>Check Network tab for failed API requests.</p>";
?>
