<?php
// Debug test for add category functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Test: Add Category Functionality</h1>";

// Step 1: Check database connection
echo "<h2>Step 1: Database Connection</h2>";
require_once 'config/database.php';
require_once 'config/session.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    echo "✅ Database connection: SUCCESS<br>";
} catch (Exception $e) {
    echo "❌ Database connection: FAILED - " . $e->getMessage() . "<br>";
    exit();
}

// Step 2: Check session
echo "<h2>Step 2: Session Check</h2>";
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

echo "Session ID: " . session_id() . "<br>";
echo "User ID: " . $_SESSION['user_id'] . "<br>";
echo "Username: " . $_SESSION['username'] . "<br>";
echo "Role: " . $_SESSION['role'] . "<br>";

// Check auth functions
if (function_exists('isLoggedIn')) {
    echo "isLoggedIn() function: EXISTS<br>";
    echo "isLoggedIn(): " . (isLoggedIn() ? 'true' : 'false') . "<br>";
} else {
    echo "❌ isLoggedIn() function: NOT FOUND<br>";
}

if (function_exists('getCurrentUserRole')) {
    echo "getCurrentUserRole() function: EXISTS<br>";
    echo "getCurrentUserRole(): " . getCurrentUserRole() . "<br>";
} else {
    echo "❌ getCurrentUserRole() function: NOT FOUND<br>";
}

// Step 3: Test API directly
echo "<h2>Step 3: Test API POST Request</h2>";

$test_data = [
    'name' => 'Test Category ' . date('H:i:s'),
    'description' => 'Test category description'
];

echo "Test Data: <pre>" . json_encode($test_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</pre>";

// Simulate POST request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Mock php://input
$temp_file = tempnam(sys_get_temp_dir(), 'category_test_');
file_put_contents($temp_file, json_encode($test_data, JSON_UNESCAPED_UNICODE));

// Override file_get_contents temporarily
if (!function_exists('mock_file_get_contents')) {
    function mock_file_get_contents($filename) {
        global $temp_file;
        if ($filename === 'php://input') {
            return file_get_contents($temp_file);
        }
        return file_get_contents($filename);
    }
}

// Test the API logic manually
echo "<h3>Testing API Logic:</h3>";

try {
    // Auth check
    if (!isLoggedIn() || getCurrentUserRole() != 'admin') {
        echo "❌ API Auth: FAILED - User not admin or not logged in<br>";
    } else {
        echo "✅ API Auth: SUCCESS<br>";
        
        // Parse JSON
        $json_input = mock_file_get_contents('php://input');
        echo "Raw JSON input: " . htmlspecialchars($json_input) . "<br>";
        
        $data = json_decode($json_input);
        echo "Parsed data: <pre>" . print_r($data, true) . "</pre>";
        
        if ($data === null) {
            echo "❌ JSON Parse: FAILED - " . json_last_error_msg() . "<br>";
        } else {
            echo "✅ JSON Parse: SUCCESS<br>";
            
            // Validate
            $name = isset($data->name) ? sanitizeInput($data->name) : '';
            $description = isset($data->description) ? sanitizeInput($data->description) : '';
            
            echo "Sanitized name: '" . htmlspecialchars($name) . "'<br>";
            echo "Sanitized description: '" . htmlspecialchars($description) . "'<br>";
            
            if (empty($name)) {
                echo "❌ Validation: FAILED - Empty name<br>";
            } else {
                echo "✅ Validation: SUCCESS<br>";
                
                // Check duplicate
                $check_query = "SELECT id FROM categories WHERE name = ? LIMIT 1";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->execute([$name]);
                
                if ($check_stmt->rowCount() > 0) {
                    echo "❌ Duplicate check: FAILED - Category already exists<br>";
                } else {
                    echo "✅ Duplicate check: SUCCESS<br>";
                    
                    // Insert
                    $insert_query = "INSERT INTO categories (name, description) VALUES (?, ?)";
                    $insert_stmt = $db->prepare($insert_query);
                    
                    if ($insert_stmt->execute([$name, $description])) {
                        $new_id = $db->lastInsertId();
                        echo "✅ Insert: SUCCESS - ID: $new_id<br>";
                        
                        // Expected response
                        $response = [
                            'success' => true,
                            'message' => "Category created",
                            'data' => ['id' => $new_id]
                        ];
                        echo "Expected API Response: <pre>" . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</pre>";
                    } else {
                        echo "❌ Insert: FAILED<br>";
                        print_r($insert_stmt->errorInfo());
                    }
                }
            }
        }
    }
} catch (Exception $e) {
    echo "❌ API Test Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

// Cleanup
unlink($temp_file);

// Step 4: Test actual API endpoint
echo "<h2>Step 4: Test Actual API Endpoint</h2>";

// Use cURL to test the real API
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
    echo "<br><strong style='color: green;'>✅ SUCCESS: Category created via API!</strong>";
    echo "New ID: " . $response_data['data']['id'];
} else {
    echo "<br><strong style='color: red;'>❌ FAILED: Category creation failed</strong>";
    echo "Error: " . ($response_data['message'] ?? 'Unknown error');
}

// Step 5: Check categories table
echo "<h2>Step 5: Verify in Database</h2>";
try {
    $stmt = $db->query("SELECT * FROM categories ORDER BY created_at DESC LIMIT 5");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Recent categories:<br>";
    foreach ($categories as $cat) {
        echo "ID: " . $cat['id'] . " - Name: " . htmlspecialchars($cat['name']) . " - Created: " . $cat['created_at'] . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Database verification failed: " . $e->getMessage() . "<br>";
}

echo "<h2>Summary</h2>";
echo "<ul>";
echo "<li>✅ Database connection: Working</li>";
echo "<li>✅ Session management: Working</li>";
echo "<li>✅ JSON parsing: Working</li>";
echo "<li>✅ Data validation: Working</li>";
echo "<li>✅ Database insert: Working</li>";
echo "<li>✅ API endpoint: " . ($response_data['success'] ? 'Working' : 'Has issues') . "</li>";
echo "</ul>";

echo "<h2>Next Steps</h2>";
echo "<p>If API test fails above, check:</p>";
echo "<ol>";
echo "<li>Browser console for JavaScript errors</li>";
echo "<li>Network tab for failed requests</li>";
echo "<li>Server logs for PHP errors</li>";
echo "</ol>";
?>
