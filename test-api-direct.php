<?php
// Test API directly with exact same environment
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Set up exact same session as real app
$_SESSION['user_id'] = 2;
$_SESSION['username'] = 'staff1';
$_SESSION['role'] = 'staff';
$_SESSION['full_name'] = 'John Smith';

// Set up exact same GET parameters
$_GET['action'] = 'list';
$_GET['page'] = '1';
$_GET['search'] = 'test';

// Set up exact same server variables
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/it-service-request/api/service_requests.php?action=list&page=1&search=test';
$_SERVER['QUERY_STRING'] = 'action=list&page=1&search=test';
$_SERVER['SCRIPT_NAME'] = '/it-service-request/api/service_requests.php';

echo "<h2>Direct API Test</h2>";
echo "<p>Testing API with exact same environment...</p>";

try {
    // Include all required files first
    echo "<p>1. Including required files...</p>";
    
    // Check if files exist
    $required_files = [
        'config/database.php',
        'lib/functions.php',
        'api/service_requests.php'
    ];
    
    foreach ($required_files as $file) {
        if (file_exists($file)) {
            echo "<p>File exists: $file</p>";
        } else {
            echo "<p style='color: red;'>File missing: $file</p>";
        }
    }
    
    // Test database connection
    echo "<p>2. Testing database connection...</p>";
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db === null) {
        throw new Exception("Database connection failed");
    }
    echo "<p>Database connection: SUCCESS</p>";
    
    // Test functions
    echo "<p>3. Testing required functions...</p>";
    
    if (function_exists('isLoggedIn')) {
        echo "<p>isLoggedIn function: EXISTS</p>";
    } else {
        echo "<p style='color: red;'>isLoggedIn function: MISSING</p>";
    }
    
    if (function_exists('getCurrentUserId')) {
        echo "<p>getCurrentUserId function: EXISTS</p>";
    } else {
        echo "<p style='color: red;'>getCurrentUserId function: MISSING</p>";
    }
    
    if (function_exists('getCurrentUserRole')) {
        echo "<p>getCurrentUserRole function: EXISTS</p>";
    } else {
        echo "<p style='color: red;'>getCurrentUserRole function: MISSING</p>";
    }
    
    if (function_exists('serviceJsonResponse')) {
        echo "<p>serviceJsonResponse function: EXISTS</p>";
    } else {
        echo "<p style='color: red;'>serviceJsonResponse function: MISSING</p>";
    }
    
    // Test session functions
    echo "<p>4. Testing session functions...</p>";
    echo "<p>Session ID: " . session_id() . "</p>";
    echo "<p>Session data: " . json_encode($_SESSION) . "</p>";
    
    if (function_exists('getCurrentUserId')) {
        echo "<p>Current user ID: " . getCurrentUserId() . "</p>";
    }
    
    if (function_exists('getCurrentUserRole')) {
        echo "<p>Current user role: " . getCurrentUserRole() . "</p>";
    }
    
    // Now test API inclusion
    echo "<p>5. Including API file...</p>";
    
    // Capture output
    ob_start();
    include 'api/service_requests.php';
    $output = ob_get_clean();
    
    echo "<h3>API Output:</h3>";
    echo "<pre style='background-color: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
    echo htmlspecialchars($output);
    echo "</pre>";
    
    // Try to parse JSON if possible
    $json_data = json_decode($output, true);
    if ($json_data !== null) {
        echo "<h3>Parsed JSON Response:</h3>";
        echo "<pre>" . json_encode($json_data, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p style='color: orange;'>Output is not valid JSON</p>";
    }
    
} catch (Exception $e) {
    echo "<h3>Exception:</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
} catch (Error $e) {
    echo "<h3>Fatal Error:</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

?>
