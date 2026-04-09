<?php
session_start();

// Set user session (regular user)
$_SESSION['user_id'] = 4;
$_SESSION['username'] = 'ndvu';
$_SESSION['full_name'] = 'Nguyênx Duc Vu';
$_SESSION['role'] = 'user';

echo "<h2>Test User Create Request</h2>";

// Simulate POST request
$_POST['action'] = 'create';
$_POST['title'] = 'Test User Request ' . date('H:i:s');
$_POST['description'] = 'Test description from user at ' . date('Y-m-d H:i:s');
$_POST['category_id'] = '1';
$_POST['priority'] = 'medium';

echo "<h3>Request Data:</h3>";
echo "<p>action: " . $_POST['action'] . "</p>";
echo "<p>title: " . $_POST['title'] . "</p>";
echo "<p>description: " . $_POST['description'] . "</p>";
echo "<p>category_id: " . $_POST['category_id'] . "</p>";
echo "<p>priority: " . $_POST['priority'] . "</p>";
echo "<p>user_id: " . $_SESSION['user_id'] . "</p>";
echo "<p>user_role: " . $_SESSION['role'] . "</p>";

// Start timer
$start_time = microtime(true);

try {
    // Include the API
    require_once 'api/service_requests.php';
    
    $end_time = microtime(true);
    $execution_time = ($end_time - $start_time) * 1000; // Convert to milliseconds
    
    echo "<h3>Performance:</h3>";
    echo "<p>Execution time: " . number_format($execution_time, 2) . " ms</p>";
    
    if ($execution_time > 1000) {
        echo "<p style='color: red;'>SLOW: Request took more than 1 second!</p>";
    } elseif ($execution_time > 500) {
        echo "<p style='color: orange;'>MODERATE: Request took more than 500ms</p>";
    } else {
        echo "<p style='color: green;'>FAST: Request completed in acceptable time</p>";
    }
    
} catch (Exception $e) {
    echo "<h3>Error:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
}

echo "<h3>Database Check:</h3>";

// Check if request was created
try {
    require_once 'config/database.php';
    $db = getDatabaseConnection();
    
    // Get latest request
    $query = "SELECT id, title, created_at FROM service_requests 
             WHERE user_id = :user_id 
             ORDER BY created_at DESC 
             LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $_SESSION['user_id']);
    $stmt->execute();
    
    $latest_request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($latest_request) {
        echo "<p style='color: green;'>Latest request found: ID {$latest_request['id']} - '{$latest_request['title']}'</p>";
        echo "<p>Created at: {$latest_request['created_at']}</p>";
        
        // Check if it's our test request
        if (strpos($latest_request['title'], 'Test User Request') !== false) {
            echo "<p style='color: green;'>SUCCESS: Test request was created!</p>";
        }
    } else {
        echo "<p style='color: orange;'>No requests found for this user</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Database check failed: " . $e->getMessage() . "</p>";
}
?>
