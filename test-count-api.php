<?php
// Test count API specifically
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Count API</h2>";

// Start session and set user
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

echo "<p>Session set with user_id: " . $_SESSION['user_id'] . "</p>";

// Test database connection and count
try {
    $pdo = new PDO("mysql:host=localhost;dbname=it_service_request", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Database connected</p>";
    
    // Check total notifications
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ?");
    $stmt->execute([1]);
    $total = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total notifications: " . $total['total'] . "</p>";
    
    // Check unread notifications
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
    $stmt->execute([1]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Unread notifications: " . $count['count'] . "</p>";
    
    // Test API count endpoint
    echo "<h3>Testing API count endpoint:</h3>";
    
    // Change to api directory
    $old_dir = getcwd();
    chdir(__DIR__ . '/api');
    
    // Set GET parameters
    $_GET['action'] = 'count';
    
    // Capture output
    ob_start();
    include 'notifications.php';
    $output = ob_get_clean();
    
    chdir($old_dir);
    
    echo "<h4>API Response:</h4>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    
    // Parse JSON to verify format
    $response = json_decode($output, true);
    if ($response) {
        echo "<h4>Parsed Response:</h4>";
        echo "<pre>" . print_r($response, true) . "</pre>";
        
        if (isset($response['count'])) {
            echo "<p style='color: green;'>✅ Count field exists: " . $response['count'] . "</p>";
        } else {
            echo "<p style='color: red;'>❌ Count field missing!</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Invalid JSON response!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='index.html'>Test in Main Application</a></p>";
?>
