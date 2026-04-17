<?php
// Test file for category API functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Category API Test</h1>";

// Test 1: Check if API file exists and is syntactically correct
echo "<h2>1. Testing API File Syntax</h2>";
$api_file = 'api/categories.php';
if (file_exists($api_file)) {
    echo "File exists: $api_file<br>";
    
    // Check syntax
    $output = shell_exec('php -l ' . $api_file . ' 2>&1');
    echo "Syntax check: " . $output . "<br>";
} else {
    echo "ERROR: File not found: $api_file<br>";
}

// Test 2: Test database connection
echo "<h2>2. Testing Database Connection</h2>";
require_once 'config/database.php';
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "Database connection: SUCCESS<br>";
    
    // Check if categories table exists
    $stmt = $db->query("SHOW TABLES LIKE 'categories'");
    if ($stmt->rowCount() > 0) {
        echo "Categories table: EXISTS<br>";
        
        // Show table structure
        $stmt = $db->query("DESCRIBE categories");
        echo "<pre>Table structure:";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            print_r($row);
        }
        echo "</pre>";
    } else {
        echo "ERROR: Categories table not found<br>";
    }
} catch (Exception $e) {
    echo "Database connection ERROR: " . $e->getMessage() . "<br>";
}

// Test 3: Test session and authentication
echo "<h2>3. Testing Session & Authentication</h2>";
require_once 'config/session.php';
session_start();

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    echo "User logged in: ID=" . $_SESSION['user_id'] . ", Role=" . ($_SESSION['role'] ?? 'unknown') . "<br>";
} else {
    echo "No user session found<br>";
    
    // Try to simulate admin login for testing
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['role'] = 'admin';
    echo "Simulated admin session created<br>";
}

// Test 4: Test API endpoints directly
echo "<h2>4. Testing API Endpoints</h2>";

// Test GET endpoint (list categories)
echo "<h3>GET /api/categories.php (List Categories)</h3>";
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'list';

// Capture output
ob_start();
include 'api/categories.php';
$get_response = ob_get_clean();
echo "GET Response: " . $get_response . "<br>";

// Test POST endpoint (create category)
echo "<h3>POST /api/categories.php (Create Category)</h3>";
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = []; // Clear POST
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Simulate JSON input
$json_data = json_encode([
    'name' => 'Test Category ' . date('Y-m-d H:i:s'),
    'description' => 'Test category description'
]);

// Simulate php://input
file_put_contents('php://temp/test_category_input.json', $json_data);
$_SERVER['REQUEST_METHOD'] = 'POST';

// Override file_get_contents for testing
function mock_file_get_contents($filename) {
    if ($filename === 'php://input') {
        return file_get_contents('php://temp/test_category_input.json');
    }
    return file_get_contents($filename);
}

// Capture output
ob_start();
// We need to mock the file_get_contents call in the API
// For now, let's just show what would be sent
echo "Would send JSON data: " . $json_data . "<br>";
$post_response = ob_get_clean();
echo "POST Response simulation completed<br>";

// Test 5: Check helper functions
echo "<h2>5. Testing Helper Functions</h2>";
if (function_exists('sanitizeInput')) {
    echo "sanitizeInput function: EXISTS<br>";
    $test_input = "Test <script>alert('xss')</script>";
    echo "Input: " . $test_input . "<br>";
    echo "Sanitized: " . sanitizeInput($test_input) . "<br>";
} else {
    echo "sanitizeInput function: NOT FOUND<br>";
}

if (function_exists('isLoggedIn')) {
    echo "isLoggedIn function: EXISTS<br>";
    echo "Current login status: " . (isLoggedIn() ? "Logged in" : "Not logged in") . "<br>";
} else {
    echo "isLoggedIn function: NOT FOUND<br>";
}

if (function_exists('getCurrentUserRole')) {
    echo "getCurrentUserRole function: EXISTS<br>";
    echo "Current user role: " . getCurrentUserRole() . "<br>";
} else {
    echo "getCurrentUserRole function: NOT FOUND<br>";
}

// Cleanup
unlink('php://temp/test_category_input.json');

echo "<h2>Test Complete</h2>";
echo "<p>Check the results above to identify any issues with the category management functionality.</p>";
?>
