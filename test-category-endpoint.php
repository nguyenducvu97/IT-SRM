<?php
// Test the actual category API endpoint
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Category API Endpoint Test</h1>";

// Test the API directly using cURL
function testCategoryAPI($method, $data = null) {
    $url = 'http://localhost/it-service-request/api/categories.php';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    
    // Set cookies for session
    curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
    
    if ($method == 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    
    curl_close($ch);
    
    return [
        'http_code' => $http_code,
        'headers' => $headers,
        'body' => $body
    ];
}

// Start session and login as admin
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

echo "<h2>Session Setup</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "User: " . $_SESSION['username'] . " (" . $_SESSION['role'] . ")<br>";

// Test 1: GET categories list
echo "<h2>Test 1: GET Categories List</h2>";
$result = testCategoryAPI('GET');
echo "HTTP Code: " . $result['http_code'] . "<br>";
echo "Response Body:<br><pre>" . htmlspecialchars($result['body']) . "</pre><br>";

// Test 2: POST create category
echo "<h2>Test 2: POST Create Category</h2>";
$test_data = [
    'name' => 'API Test Category ' . date('H:i:s'),
    'description' => 'Created via API test'
];

echo "POST Data: <pre>" . json_encode($test_data, JSON_PRETTY_PRINT) . "</pre>";

$result = testCategoryAPI('POST', $test_data);
echo "HTTP Code: " . $result['http_code'] . "<br>";
echo "Response Headers:<br><pre>" . htmlspecialchars($result['headers']) . "</pre>";
echo "Response Body:<br><pre>" . htmlspecialchars($result['body']) . "</pre>";

// Parse response
$response_data = json_decode($result['body'], true);
if ($response_data) {
    echo "Parsed Response:<br><pre>" . json_encode($response_data, JSON_PRETTY_PRINT) . "</pre>";
    
    if ($response_data['success']) {
        echo "<br>Category creation appears successful!";
    } else {
        echo "<br>Category creation failed. Error: " . $response_data['message'];
    }
} else {
    echo "<br>Failed to parse JSON response";
}

// Test 3: Check if category was actually created
echo "<h2>Test 3: Verify Category in Database</h2>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->prepare("SELECT * FROM categories WHERE name LIKE ? ORDER BY id DESC LIMIT 5");
    $stmt->execute(['API Test Category%']);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Recent test categories:<br>";
    foreach ($categories as $cat) {
        echo "ID: " . $cat['id'] . " - Name: " . $cat['name'] . "<br>";
    }
} catch (Exception $e) {
    echo "Database verification failed: " . $e->getMessage() . "<br>";
}

echo "<h2>Test Complete</h2>";
?>
