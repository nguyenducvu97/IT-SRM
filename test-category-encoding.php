<?php
// Test category encoding
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Category Encoding</h1>";

// Test 1: Create category with Vietnamese characters
echo "<h2>Test 1: Create Category with Vietnamese Characters</h2>";

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

$test_data = [
    'name' => 'Danh mục Phần cứng',
    'description' => 'Quản lý các yêu cầu về phần cứng máy tính'
];

echo "Test Data: <pre>" . json_encode($test_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</pre>";

// Use cURL to test POST request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/it-service-request/api/categories.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_data, JSON_UNESCAPED_UNICODE));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=UTF-8']);
curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code<br>";
echo "Response: <pre>" . htmlspecialchars($response) . "</pre>";

$response_data = json_decode($response, true);
if ($response_data && $response_data['success']) {
    $category_id = $response_data['data']['id'];
    echo "<br><strong style='color: green;'>SUCCESS: Category created with ID: $category_id</strong>";
    
    // Test 2: Try to delete category with requests
    echo "<h2>Test 2: Try to Delete Category with Requests</h2>";
    
    // Create a request first
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO service_requests (title, description, category_id, user_id, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $db->prepare($query);
    $stmt->execute(['Yêu cầu test xóa', 'Yêu cầu để test lỗi font khi xóa', $category_id, 1, 'open']);
    
    echo "Created test request in category<br>";
    
    // Now try to delete
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost/it-service-request/api/categories.php?id=$category_id");
    curl_setopt($ch, CURLOPT_DELETE, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=UTF-8']);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "HTTP Code: $http_code<br>";
    echo "Response: <pre>" . htmlspecialchars($response) . "</pre>";

    $response_data = json_decode($response, true);
    if ($response_data && !$response_data['success']) {
        echo "<br><strong style='color: green;'>EXPECTED: Deletion failed</strong><br>";
        echo "Error message: " . $response_data['message'] . "<br>";
        
        // Check if Vietnamese characters are displayed correctly
        if (strpos($response_data['message'], 'Phần cứng') !== false) {
            echo "<br><strong style='color: blue;'>SUCCESS: Vietnamese characters displayed correctly!</strong>";
        } else {
            echo "<br><strong style='color: red;'>ISSUE: Vietnamese characters not displayed correctly</strong>";
        }
    }
    
} else {
    echo "<br><strong style='color: red;'>FAILED: Category creation failed</strong>";
    echo "Error: " . ($response_data['message'] ?? 'Unknown error') . "<br>";
}

echo "<h2>Character Encoding Check</h2>";
$test_strings = [
    'Không thể xóa danh mục',
    'vì còn yêu cầu', 
    'Bạn phải xóa tất cả',
    'Phần cứng',
    'Phần mềm',
    'Máy tính',
    'Màn hình',
    'Bàn phím'
];

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Expected</th><th>JSON Encoded</th><th>JSON Decoded</th></tr>";

foreach ($test_strings as $string) {
    $encoded = json_encode($string, JSON_UNESCAPED_UNICODE);
    $decoded = json_decode($encoded);
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($string) . "</td>";
    echo "<td>" . htmlspecialchars($encoded) . "</td>";
    echo "<td>" . htmlspecialchars($decoded) . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>Test Instructions</h2>";
echo "<ol>";
echo "<li>Check if Vietnamese characters display correctly in the table above</li>";
echo "<li>Test the actual application by creating a category with Vietnamese name</li>";
echo "<li>Try to delete it and check the error message</li>";
echo "<li>All Vietnamese characters should display correctly without garbled text</li>";
echo "</ol>";
?>
