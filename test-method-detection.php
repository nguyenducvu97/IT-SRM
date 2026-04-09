<?php
session_start();

// Set user session
$_SESSION['user_id'] = 4;
$_SESSION['username'] = 'ndvu';
$_SESSION['full_name'] = 'Nguyênx Duc Vu';
$_SESSION['role'] = 'user';

echo "<h2>Test Method Detection</h2>";

echo "<h3>Server Method Detection:</h3>";
echo "<p>SERVER['REQUEST_METHOD']: " . $_SERVER['REQUEST_METHOD'] . "</p>";
echo "<p>SERVER['CONTENT_TYPE']: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set') . "</p>";

// Test different ways to simulate POST
echo "<h3>Test 1: Using POST simulation</h3>";

// Simulate POST request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['action'] = 'create';
$_POST['title'] = 'Test Method Detection';
$_POST['description'] = 'Test description';
$_POST['category_id'] = '1';
$_POST['priority'] = 'medium';

echo "<p>Simulated method: " . $_SERVER['REQUEST_METHOD'] . "</p>";
echo "<p>POST action: " . $_POST['action'] . "</p>";

// Test method detection logic
$method = $_SERVER['REQUEST_METHOD'];
echo "<p>Detected method: $method</p>";

if ($method == 'POST') {
    echo "<p style='color: green;'>POST method detected correctly</p>";
    
    // Check content type
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    echo "<p>Content-Type: $content_type</p>";
    
    // Get action
    if (strpos($content_type, 'multipart/form-data') !== false) {
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        echo "<p>Action from FormData: $action</p>";
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = isset($input['action']) ? $input['action'] : '';
        echo "<p>Action from JSON: $action</p>";
    }
    
    if ($action === 'create') {
        echo "<p style='color: green;'>Create action detected correctly</p>";
    } else {
        echo "<p style='color: red;'>Create action not detected. Found: '$action'</p>";
    }
    
} else {
    echo "<p style='color: red;'>POST method not detected. Found: $method</p>";
}

echo "<h3>Test 2: Testing with actual POST data</h3>";

// Test with actual POST data
$raw_input = file_get_contents('php://input');
echo "<p>Raw input: '$raw_input'</p>";

if (!empty($raw_input)) {
    $input = json_decode($raw_input, true);
    if ($input) {
        echo "<p>JSON decoded successfully</p>";
        echo "<p>JSON action: " . ($input['action'] ?? 'not set') . "</p>";
    } else {
        echo "<p>JSON decode failed</p>";
    }
} else {
    echo "<p>No raw input found</p>";
}

echo "<h3>Test 3: Check API method handling</h3>";

// Try to include the API and see what happens
try {
    echo "<p>Attempting to include API...</p>";
    
    // Temporarily override method
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    ob_start();
    include 'api/service_requests.php';
    $output = ob_get_clean();
    
    echo "<p>API Output:</p>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
    echo htmlspecialchars($output);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
}

echo "<h3>Conclusion:</h3>";
echo "<p>If API returns 'Method not allowed', the issue is likely:</p>";
echo "<ul>";
echo "<li>1. Method detection logic in API</li>";
echo "<li>2. Content-Type handling</li>";
echo "<li>3. Action parameter extraction</li>";
echo "<li>4. Session authentication</li>";
echo "</ul>";
?>
