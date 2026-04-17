<?php
// Simple test for category creation
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Simple Category Creation Test</h1>";

// Load required files
require_once 'config/database.php';
require_once 'config/session.php';

// Start session and create admin session
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

echo "<h2>Session Setup</h2>";
echo "User ID: " . $_SESSION['user_id'] . "<br>";
echo "Username: " . $_SESSION['username'] . "<br>";
echo "Role: " . $_SESSION['role'] . "<br>";
echo "Is logged in: " . (isLoggedIn() ? "YES" : "NO") . "<br>";
echo "Is admin: " . (getCurrentUserRole() == 'admin' ? "YES" : "NO") . "<br>";

// Test database connection
echo "<h2>Database Connection</h2>";
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "Database connection: SUCCESS<br>";
} catch (Exception $e) {
    echo "Database connection FAILED: " . $e->getMessage() . "<br>";
    exit();
}

// Test category creation
echo "<h2>Category Creation Test</h2>";

$test_name = "Simple Test " . date('H:i:s');
$test_description = "Test description";

echo "Creating category: '$test_name'<br>";

try {
    // Check for existing category
    $check_query = "SELECT id FROM categories WHERE name = ? LIMIT 1";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$test_name]);
    
    if ($check_stmt->rowCount() > 0) {
        echo "Category already exists<br>";
    } else {
        // Create category
        $query = "INSERT INTO categories (name, description) VALUES (?, ?)";
        $stmt = $db->prepare($query);
        $result = $stmt->execute([$test_name, $test_description]);
        
        if ($result) {
            $new_id = $db->lastInsertId();
            echo "Category created successfully!<br>";
            echo "New ID: $new_id<br>";
            
            // Verify
            $verify = $db->prepare("SELECT * FROM categories WHERE id = ?");
            $verify->execute([$new_id]);
            $category = $verify->fetch();
            
            if ($category) {
                echo "Verification successful:<br>";
                echo "Name: " . $category['name'] . "<br>";
                echo "Description: " . $category['description'] . "<br>";
            }
        } else {
            echo "Category creation failed!<br>";
            print_r($stmt->errorInfo());
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

// Test API response format
echo "<h2>API Response Format Test</h2>";
$api_response = [
    'success' => true,
    'message' => "Category created",
    'data' => ['id' => 123]
];

echo "Expected API response:<br>";
echo "<pre>" . json_encode($api_response, JSON_PRETTY_PRINT) . "</pre>";

echo "<h2>Test Complete</h2>";
?>
