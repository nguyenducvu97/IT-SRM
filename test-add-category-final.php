<?php
// Final test for add category functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Final Test: Add Category Functionality</h1>";

// Step 1: Check database connection
echo "<h2>Step 1: Database Connection</h2>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "Database connection: SUCCESS<br>";
} catch (Exception $e) {
    echo "Database connection: FAILED - " . $e->getMessage() . "<br>";
    exit();
}

// Step 2: Check session and auth
echo "<h2>Step 2: Session & Authentication</h2>";
session_start();

// Set admin session for testing
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

echo "Session ID: " . session_id() . "<br>";
echo "User role: " . $_SESSION['role'] . "<br>";

// Check auth functions
require_once 'config/session.php';
echo "isLoggedIn(): " . (isLoggedIn() ? 'true' : 'false') . "<br>";
echo "getCurrentUserRole(): " . getCurrentUserRole() . "<br>";

// Step 3: Test direct database insert
echo "<h2>Step 3: Direct Database Insert Test</h2>";
$test_name = "Test Category " . date('H:i:s');
$test_desc = "Test description for category";

try {
    $query = "INSERT INTO categories (name, description) VALUES (?, ?)";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$test_name, $test_desc])) {
        $id = $db->lastInsertId();
        echo "Direct insert: SUCCESS - ID: $id<br>";
        
        // Clean up
        $delete_query = "DELETE FROM categories WHERE id = ?";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->execute([$id]);
        echo "Test data cleaned up<br>";
    } else {
        echo "Direct insert: FAILED<br>";
        print_r($stmt->errorInfo());
    }
} catch (Exception $e) {
    echo "Direct insert error: " . $e->getMessage() . "<br>";
}

// Step 4: Test API endpoint with curl
echo "<h2>Step 4: API Endpoint Test</h2>";
$api_data = [
    'name' => "API Test Category " . date('H:i:s'),
    'description' => 'API test description'
];

$json_data = json_encode($api_data, JSON_UNESCAPED_UNICODE);
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
$curl_error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $http_code<br>";
echo "CURL Error: " . ($curl_error ?: 'None') . "<br>";
echo "Raw Response: " . htmlspecialchars($response) . "<br>";

$response_data = json_decode($response, true);
if ($response_data) {
    echo "Parsed Response: <pre>" . print_r($response_data, true) . "</pre>";
    
    if ($response_data['success']) {
        echo "<strong style='color: green;'>API SUCCESS - Category ID: " . $response_data['data']['id'] . "</strong><br>";
    } else {
        echo "<strong style='color: red;'>API FAILED - " . $response_data['message'] . "</strong><br>";
    }
} else {
    echo "<strong style='color: red;'>Invalid JSON response</strong><br>";
}

// Step 5: Check API file syntax
echo "<h2>Step 5: API File Syntax Check</h2>";
$api_file = 'api/categories.php';
if (file_exists($api_file)) {
    echo "API file exists: YES<br>";
    echo "File size: " . filesize($api_file) . " bytes<br>";
    
    // Check for common syntax errors
    $content = file_get_contents($api_file);
    $errors = [];
    
    // Check for balanced braces
    $open_braces = substr_count($content, '{');
    $close_braces = substr_count($content, '}');
    echo "Open braces: $open_braces, Close braces: $close_braces<br>";
    if ($open_braces != $close_braces) {
        $errors[] = "Unbalanced braces";
    }
    
    // Check for PHP tags
    if (strpos($content, '<?php') === false) {
        $errors[] = "Missing PHP opening tag";
    }
    
    if (empty($errors)) {
        echo "Syntax check: PASSED<br>";
    } else {
        echo "Syntax check: FAILED - " . implode(', ', $errors) . "<br>";
    }
} else {
    echo "API file exists: NO<br>";
}

// Step 6: Check JavaScript
echo "<h2>Step 6: JavaScript Check</h2>";
$js_file = 'assets/js/app.js';
if (file_exists($js_file)) {
    echo "JavaScript file exists: YES<br>";
    echo "File size: " . filesize($js_file) . " bytes<br>";
    
    $js_content = file_get_contents($js_file);
    
    // Check for key functions
    $functions_to_check = [
        'handleCategorySubmit',
        'showCategoryModal',
        'apiCall',
        'showNotification',
        'loadCategories'
    ];
    
    foreach ($functions_to_check as $func) {
        if (strpos($js_content, $func) !== false) {
            echo "$func: FOUND<br>";
        } else {
            echo "$func: NOT FOUND<br>";
        }
    }
} else {
    echo "JavaScript file exists: NO<br>";
}

echo "<h2>Summary & Next Steps</h2>";
echo "<ol>";
echo "<li><strong>Clear browser cache</strong> (Ctrl+F5)</li>";
echo "<li><strong>Open browser console</strong> (F12) and check for JavaScript errors</li>";
echo "<li><strong>Test add category</strong> in main application</li>";
echo "<li><strong>Check Network tab</strong> for failed API requests</li>";
echo "<li><strong>Check Server logs</strong> for PHP errors</li>";
echo "</ol>";

echo "<h2>Debugging Tips</h2>";
echo "<ul>";
echo "<li>If API test fails above, check XAMPP services</li>";
echo "<li>If JavaScript functions not found, check file loading</li>";
echo "<li>If database insert fails, check table structure</li>";
echo "<li>If auth fails, check session configuration</li>";
echo "</ul>";
?>
