<?php
// Fix category add functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Fix Category Add Functionality</h1>";

// Step 1: Test direct database insert
echo "<h2>Step 1: Test Direct Database Insert</h2>";
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    echo "Database connection: SUCCESS<br>";
    
    // Test insert
    $test_name = "Direct Test " . date('H:i:s');
    $test_desc = "Direct insert test";
    
    $query = "INSERT INTO categories (name, description) VALUES (?, ?)";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$test_name, $test_desc])) {
        $id = $db->lastInsertId();
        echo "Direct insert: SUCCESS - ID: $id<br>";
    } else {
        echo "Direct insert: FAILED<br>";
        print_r($stmt->errorInfo());
    }
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}

// Step 2: Test API with simulation
echo "<h2>Step 2: Test API Simulation</h2>";

// Simulate the exact API flow
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Create test JSON
$test_json = json_encode([
    'name' => 'API Sim Test ' . date('H:i:s'),
    'description' => 'API simulation test'
]);

echo "JSON payload: $test_json<br>";

// Mock file_get_contents for php://input
$temp_file = tempnam(sys_get_temp_dir(), 'category_test_');
file_put_contents($temp_file, $test_json);

// Override file_get_contents temporarily
if (!function_exists('original_file_get_contents')) {
    function original_file_get_contents($filename) {
        global $temp_file;
        if ($filename === 'php://input') {
            return file_get_contents($temp_file);
        }
        return file_get_contents($filename);
    }
}

// Start session for auth
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

echo "Session: Admin user logged in<br>";

// Test the API logic manually
try {
    // Auth check
    if (!isLoggedIn() || getCurrentUserRole() != 'admin') {
        echo "API Auth: FAILED<br>";
    } else {
        echo "API Auth: SUCCESS<br>";
        
        // Parse JSON
        $data = json_decode($test_json);
        
        if ($data === null) {
            echo "JSON Parse: FAILED - " . json_last_error_msg() . "<br>";
        } else {
            echo "JSON Parse: SUCCESS<br>";
            
            // Validate
            $name = sanitizeInput($data->name);
            $description = sanitizeInput($data->description);
            
            echo "Sanitized name: '$name'<br>";
            echo "Sanitized description: '$description'<br>";
            
            if (empty($name)) {
                echo "Validation: FAILED - Empty name<br>";
            } else {
                echo "Validation: SUCCESS<br>";
                
                // Check duplicate
                $check_query = "SELECT id FROM categories WHERE name = ? LIMIT 1";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->execute([$name]);
                
                if ($check_stmt->rowCount() > 0) {
                    echo "Duplicate check: FAILED - Category exists<br>";
                } else {
                    echo "Duplicate check: SUCCESS<br>";
                    
                    // Insert
                    $insert_query = "INSERT INTO categories (name, description) VALUES (?, ?)";
                    $insert_stmt = $db->prepare($insert_query);
                    
                    if ($insert_stmt->execute([$name, $description])) {
                        $new_id = $db->lastInsertId();
                        echo "API Insert: SUCCESS - ID: $new_id<br>";
                        
                        // Expected response
                        $response = [
                            'success' => true,
                            'message' => "Category created",
                            'data' => ['id' => $new_id]
                        ];
                        echo "Expected API Response: <pre>" . json_encode($response, JSON_PRETTY_PRINT) . "</pre>";
                    } else {
                        echo "API Insert: FAILED<br>";
                        print_r($insert_stmt->errorInfo());
                    }
                }
            }
        }
    }
} catch (Exception $e) {
    echo "API Test Error: " . $e->getMessage() . "<br>";
}

// Cleanup
unlink($temp_file);

// Step 3: Check JavaScript issues
echo "<h2>Step 3: Check JavaScript Issues</h2>";
$js_file = 'assets/js/app.js';

if (file_exists($js_file)) {
    $js_content = file_get_contents($js_file);
    
    // Find the handleCategorySubmit function
    $start_pos = strpos($js_content, 'async handleCategorySubmit(e)');
    if ($start_pos !== false) {
        $end_pos = strpos($js_content, '}', $start_pos);
        if ($end_pos !== false) {
            $function_code = substr($js_content, $start_pos, $end_pos - $start_pos + 1);
            echo "handleCategorySubmit function found:<br>";
            echo "<pre>" . htmlspecialchars(substr($function_code, 0, 1000)) . "...</pre><br>";
        }
    }
    
    // Check for common issues
    if (strpos($js_content, 'e.preventDefault()') === false) {
        echo "ISSUE: e.preventDefault() might be missing<br>";
    }
    
    if (strpos($js_content, 'api/categories.php') === false) {
        echo "ISSUE: API endpoint might be incorrect<br>";
    }
    
    if (strpos($js_content, 'JSON.stringify') === false) {
        echo "ISSUE: JSON.stringify might be missing<br>";
    }
} else {
    echo "JavaScript file not found<br>";
}

// Step 4: Create a fixed version of the API
echo "<h2>Step 4: Create Fixed API Version</h2>";

$fixed_api_code = '<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

require_once "../config/database.php";
require_once "../config/session.php";

if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
    http_response_code(200);
    exit();
}

// Start session for POST/PUT/DELETE requests
if ($_SERVER["REQUEST_METHOD"] != "GET") {
    session_start();
    
    if (!isLoggedIn() || getCurrentUserRole() != "admin") {
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "message" => "Only administrators can manage categories"
        ]);
        exit();
    }
}

$database = new Database();
$db = $database->getConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get JSON input
    $json_input = file_get_contents("php://input");
    $data = json_decode($json_input);
    
    // Debug logging
    error_log("Category API - JSON input: " . $json_input);
    error_log("Category API - Parsed data: " . print_r($data, true));
    
    if (!$data) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid JSON data"
        ]);
        exit();
    }
    
    $name = isset($data->name) ? sanitizeInput($data->name) : "";
    $description = isset($data->description) ? sanitizeInput($data->description) : "";
    
    if (empty($name)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Category name is required"
        ]);
        exit();
    }
    
    // Check for duplicate
    $check_query = "SELECT id FROM categories WHERE name = ? LIMIT 1";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$name]);
    
    if ($check_stmt->rowCount() > 0) {
        http_response_code(409);
        echo json_encode([
            "success" => false,
            "message" => "Category already exists"
        ]);
        exit();
    }
    
    // Insert category
    $query = "INSERT INTO categories (name, description) VALUES (?, ?)";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$name, $description])) {
        $new_id = $db->lastInsertId();
        
        error_log("Category API - Successfully created category ID: " . $new_id);
        
        echo json_encode([
            "success" => true,
            "message" => "Category created successfully",
            "data" => ["id" => $new_id]
        ]);
    } else {
        error_log("Category API - Insert failed: " . print_r($stmt->errorInfo(), true));
        
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to create category"
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed"
    ]);
}
?>';

file_put_contents('api/categories_fixed.php', $fixed_api_code);
echo "Fixed API created: api/categories_fixed.php<br>";

// Step 5: Test the fixed API
echo "<h2>Step 5: Test Fixed API</h2>";
echo "Test the fixed API at: <a href='test-fixed-category.php'>test-fixed-category.php</a><br>";

// Create test file for fixed API
$test_fixed_code = '<?php
// Test the fixed category API
error_reporting(E_ALL);
ini_set("display_errors", 1);

echo "<h1>Test Fixed Category API</h1>";

// Start session and login as admin
session_start();
$_SESSION["user_id"] = 1;
$_SESSION["username"] = "admin";
$_SESSION["role"] = "admin";

echo "Session: Admin user logged in<br>";

// Test data
$test_data = [
    "name" => "Fixed API Test " . date("H:i:s"),
    "description" => "Testing fixed API"
];

echo "Test data: <pre>" . json_encode($test_data, JSON_PRETTY_PRINT) . "</pre>";

// Use cURL to test the API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/it-service-request/api/categories_fixed.php");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_COOKIE, "PHPSESSID=" . session_id());

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code<br>";
echo "Response: <pre>" . htmlspecialchars($response) . "</pre>";

$response_data = json_decode($response, true);
if ($response_data && $response_data["success"]) {
    echo "<br><strong style=\"color: green;\">SUCCESS: Category created with ID: " . $response_data["data"]["id"] . "</strong>";
} else {
    echo "<br><strong style=\"color: red;\">FAILED: " . ($response_data["message"] ?? "Unknown error") . "</strong>";
}
?>';

file_put_contents('test-fixed-category.php', $test_fixed_code);
echo "Test file created: test-fixed-category.php<br>";

echo "<h2>Summary</h2>";
echo "1. Check the test results above<br>";
echo "2. Open <a href='test-fixed-category.php'>test-fixed-category.php</a> to test the fixed API<br>";
echo "3. If the fixed API works, replace api/categories.php with api/categories_fixed.php<br>";
?>
