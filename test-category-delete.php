<?php
// Test category deletion with requests
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Category Deletion with Requests</h1>";

// Step 1: Create test category with requests
echo "<h2>Step 1: Create Test Category with Requests</h2>";
require_once 'config/database.php';
require_once 'config/session.php';

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Create test category
    $test_category_name = "Test Delete Category " . date('H:i:s');
    $test_category_desc = "Category for testing delete with requests";
    
    $query = "INSERT INTO categories (name, description) VALUES (?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$test_category_name, $test_category_desc]);
    
    $category_id = $db->lastInsertId();
    echo "Created test category ID: $category_id - Name: $test_category_name<br>";
    
    // Create test requests in this category
    $test_requests = [
        [
            'title' => 'Test Request 1 for Delete',
            'description' => 'Test request for deletion testing',
            'category_id' => $category_id,
            'user_id' => 1,
            'status' => 'open'
        ],
        [
            'title' => 'Test Request 2 for Delete', 
            'description' => 'Another test request for deletion testing',
            'category_id' => $category_id,
            'user_id' => 1,
            'status' => 'in_progress'
        ]
    ];
    
    foreach ($test_requests as $request) {
        $query = "INSERT INTO service_requests (title, description, category_id, user_id, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $db->prepare($query);
        $stmt->execute([
            $request['title'],
            $request['description'], 
            $request['category_id'],
            $request['user_id'],
            $request['status']
        ]);
    }
    
    echo "Created 2 test requests in category<br>";
    
    // Verify requests exist
    $check_query = "SELECT COUNT(*) as count FROM service_requests WHERE category_id = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$category_id]);
    $count = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "Verified: $count requests in category<br>";
    
} catch (Exception $e) {
    echo "Setup error: " . $e->getMessage() . "<br>";
    exit();
}

// Step 2: Test deletion API
echo "<h2>Step 2: Test Category Deletion API</h2>";

// Use cURL to test DELETE request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/it-service-request/api/categories.php?id=$category_id");
curl_setopt($ch, CURLOPT_DELETE, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, "PHPSESSID=" . session_id());
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code<br>";
echo "Response: <pre>" . htmlspecialchars($response) . "</pre>";

$response_data = json_decode($response, true);
if ($response_data) {
    if ($response_data['success']) {
        echo "<br><strong style='color: red;'>UNEXPECTED: Category deletion succeeded when it should have failed!</strong>";
    } else {
        echo "<br><strong style='color: green;'>EXPECTED: Category deletion failed as expected</strong><br>";
        echo "Error message: " . $response_data['message'] . "<br>";
        
        if ($response_data['error_type'] === 'category_has_requests') {
            echo "Error type: " . $response_data['error_type'] . "<br>";
            echo "Request count: " . $response_data['request_count'] . "<br>";
            echo "Category name: " . $response_data['category_name'] . "<br>";
            
            echo "<br><strong style='color: blue;'>SUCCESS: API correctly prevents deletion of category with requests!</strong>";
        }
    }
}

// Step 3: Test deletion of empty category
echo "<h2>Step 3: Test Deletion of Empty Category</h2>";

// Create empty category
$empty_category_name = "Empty Test Category " . date('H:i:s');
$query = "INSERT INTO categories (name, description) VALUES (?, ?)";
$stmt = $db->prepare($query);
$stmt->execute([$empty_category_name, "Empty category for testing"]);

$empty_category_id = $db->lastInsertId();
echo "Created empty category ID: $empty_category_id<br>";

// Test deletion of empty category
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/it-service-request/api/categories.php?id=$empty_category_id");
curl_setopt($ch, CURLOPT_DELETE, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, "PHPSESSID=" . session_id());

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code<br>";
echo "Response: <pre>" . htmlspecialchars($response) . "</pre>";

$response_data = json_decode($response, true);
if ($response_data && $response_data['success']) {
    echo "<br><strong style='color: green;'>SUCCESS: Empty category deletion succeeded!</strong>";
} else {
    echo "<br><strong style='color: red;'>UNEXPECTED: Empty category deletion failed!</strong>";
}

// Step 4: Cleanup
echo "<h2>Step 4: Cleanup</h2>";
try {
    // Delete test requests
    $query = "DELETE FROM service_requests WHERE category_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$category_id]);
    echo "Deleted test requests from category $category_id<br>";
    
    // Delete test category
    $query = "DELETE FROM categories WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$category_id]);
    echo "Deleted test category $category_id<br>";
    
} catch (Exception $e) {
    echo "Cleanup error: " . $e->getMessage() . "<br>";
}

echo "<h2>Test Summary</h2>";
echo "<ul>";
echo "<li>Category with requests: Should NOT be deletable</li>";
echo "<li>Empty category: Should be deletable</li>";
echo "<li>Error messages should be clear and informative</li>";
echo "<li>JavaScript should handle errors gracefully</li>";
echo "</ul>";

echo "<h2>JavaScript Test Instructions</h2>";
echo "<p>To test the JavaScript functionality:</p>";
echo "<ol>";
echo "<li>Open the main application and login as admin</li>";
echo "<li>Create a new category</li>";
echo "<li>Create some requests in that category</li>";
echo "<li>Try to delete the category</li>";
echo "<li>Should see detailed error message with option to view requests</li>";
echo "</ol>";
?>
