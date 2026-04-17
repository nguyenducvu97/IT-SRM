<?php
// Debug category creation functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Category Creation Debug</h1>";

// Step 1: Check database connection
echo "<h2>Step 1: Database Connection</h2>";
require_once 'config/database.php';
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "Database connection: SUCCESS<br>";
    
    // Test basic query
    $stmt = $db->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "Basic query test: " . ($result['test'] == 1 ? "SUCCESS" : "FAILED") . "<br>";
} catch (Exception $e) {
    echo "Database connection FAILED: " . $e->getMessage() . "<br>";
    exit();
}

// Step 2: Check session and authentication
echo "<h2>Step 2: Authentication</h2>";
require_once 'config/session.php';
session_start();

if (!isLoggedIn()) {
    echo "User not logged in. Creating test admin session...<br>";
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['full_name'] = 'System Administrator';
    $_SESSION['role'] = 'admin';
}

echo "User ID: " . $_SESSION['user_id'] . "<br>";
echo "Username: " . $_SESSION['username'] . "<br>";
echo "Role: " . $_SESSION['role'] . "<br>";
echo "Is logged in: " . (isLoggedIn() ? "YES" : "NO") . "<br>";
echo "Is admin: " . (getCurrentUserRole() == 'admin' ? "YES" : "NO") . "<br>";

// Step 3: Test category creation directly
echo "<h2>Step 3: Direct Category Creation Test</h2>";

$test_name = "Debug Test Category " . date('Y-m-d H:i:s');
$test_description = "This is a test category created for debugging";

echo "Test category name: " . $test_name . "<br>";
echo "Test description: " . $test_description . "<br>";

try {
    // Check if category already exists
    $check_query = "SELECT id FROM categories WHERE name = :name LIMIT 1";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":name", $test_name);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        echo "Category already exists, skipping creation<br>";
    } else {
        // Insert new category
        $query = "INSERT INTO categories (name, description) VALUES (:name, :description)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":name", $test_name);
        $stmt->bindParam(":description", $test_description);
        
        if ($stmt->execute()) {
            $new_id = $db->lastInsertId();
            echo "Category creation: SUCCESS<br>";
            echo "New category ID: " . $new_id . "<br>";
            
            // Verify the category was created
            $verify_query = "SELECT * FROM categories WHERE id = :id";
            $verify_stmt = $db->prepare($verify_query);
            $verify_stmt->bindParam(":id", $new_id);
            $verify_stmt->execute();
            
            $category = $verify_stmt->fetch(PDO::FETCH_ASSOC);
            if ($category) {
                echo "Category verification: SUCCESS<br>";
                echo "Created category: <pre>" . print_r($category, true) . "</pre>";
            } else {
                echo "Category verification: FAILED<br>";
            }
        } else {
            echo "Category creation: FAILED<br>";
            echo "Error info: <pre>" . print_r($stmt->errorInfo(), true) . "</pre>";
        }
    }
} catch (Exception $e) {
    echo "Category creation ERROR: " . $e->getMessage() . "<br>";
}

// Step 4: Test API simulation
echo "<h2>Step 4: API Simulation</h2>";

// Simulate POST request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Create test JSON data
$api_test_name = "API Test Category " . date('Y-m-d H:i:s');
$api_test_data = [
    'name' => $api_test_name,
    'description' => 'API test category description'
];

$json_payload = json_encode($api_test_data);
echo "API JSON payload: " . $json_payload . "<br>";

// Simulate the API logic
try {
    // Authentication check
    if (!isLoggedIn() || getCurrentUserRole() != 'admin') {
        echo "API Auth check: FAILED - Not admin<br>";
    } else {
        echo "API Auth check: SUCCESS - Admin user<br>";
        
        // Parse JSON data
        $data = json_decode($json_payload);
        $name = sanitizeInput($data->name);
        $description = sanitizeInput($data->description);
        
        echo "Parsed name: " . $name . "<br>";
        echo "Parsed description: " . $description . "<br>";
        
        // Validation
        if (empty($name)) {
            echo "API Validation: FAILED - Empty name<br>";
        } else {
            echo "API Validation: SUCCESS - Name provided<br>";
            
            // Check for duplicate
            $check_query = "SELECT id FROM categories WHERE name = :name LIMIT 1";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(":name", $name);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                echo "API Duplicate check: FAILED - Category exists<br>";
            } else {
                echo "API Duplicate check: SUCCESS - Unique name<br>";
                
                // Insert category
                $query = "INSERT INTO categories (name, description) VALUES (:name, :description)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":name", $name);
                $stmt->bindParam(":description", $description);
                
                if ($stmt->execute()) {
                    $api_new_id = $db->lastInsertId();
                    echo "API Category creation: SUCCESS<br>";
                    echo "API New category ID: " . $api_new_id . "<br>";
                    
                    // Expected API response
                    $expected_response = [
                        'success' => true,
                        'message' => "Category created",
                        'data' => ['id' => $api_new_id]
                    ];
                    echo "Expected API response: <pre>" . json_encode($expected_response, JSON_PRETTY_PRINT) . "</pre>";
                } else {
                    echo "API Category creation: FAILED<br>";
                    echo "API Error info: <pre>" . print_r($stmt->errorInfo(), true) . "</pre>";
                }
            }
        }
    }
} catch (Exception $e) {
    echo "API Simulation ERROR: " . $e->getMessage() . "<br>";
}

// Step 5: Check JavaScript functionality
echo "<h2>Step 5: JavaScript Check</h2>";
$js_file = 'assets/js/app.js';
if (file_exists($js_file)) {
    echo "JavaScript file exists: $js_file<br>";
    
    // Check for category functions
    $js_content = file_get_contents($js_file);
    
    if (strpos($js_content, 'showCategoryModal') !== false) {
        echo "showCategoryModal function: FOUND<br>";
    } else {
        echo "showCategoryModal function: NOT FOUND<br>";
    }
    
    if (strpos($js_content, 'handleCategorySubmit') !== false) {
        echo "handleCategorySubmit function: FOUND<br>";
    } else {
        echo "handleCategorySubmit function: NOT FOUND<br>";
    }
    
    if (strpos($js_content, 'loadCategories') !== false) {
        echo "loadCategories function: FOUND<br>";
    } else {
        echo "loadCategories function: NOT FOUND<br>";
    }
} else {
    echo "JavaScript file NOT found: $js_file<br>";
}

// Step 6: Check HTML modal
echo "<h2>Step 6: HTML Modal Check</h2>";
$html_file = 'index.html';
if (file_exists($html_file)) {
    echo "HTML file exists: $html_file<br>";
    
    $html_content = file_get_contents($html_file);
    
    if (strpos($html_content, 'categoryModal') !== false) {
        echo "categoryModal: FOUND<br>";
    } else {
        echo "categoryModal: NOT FOUND<br>";
    }
    
    if (strpos($html_content, 'categoryForm') !== false) {
        echo "categoryForm: FOUND<br>";
    } else {
        echo "categoryForm: NOT FOUND<br>";
    }
    
    if (strpos($html_content, 'addCategoryBtn') !== false) {
        echo "addCategoryBtn: FOUND<br>";
    } else {
        echo "addCategoryBtn: NOT FOUND<br>";
    }
} else {
    echo "HTML file NOT found: $html_file<br>";
}

echo "<h2>Debug Complete</h2>";
echo "<p>Review the results above to identify any issues with category creation.</p>";
?>
