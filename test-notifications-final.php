<?php
// Test the fixed notifications API
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Fixed notifications.php</h2>";

// Start session and set user
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

echo "<p>Session set with user_id: " . $_SESSION['user_id'] . "</p>";

// Test API by including it
try {
    echo "<h3>Testing notifications.php...</h3>";
    
    // Change to api directory
    $old_dir = getcwd();
    chdir(__DIR__ . '/api');
    
    // Set GET parameters
    $_GET['action'] = 'list';
    
    // Capture output
    ob_start();
    include 'notifications.php';
    $output = ob_get_clean();
    
    chdir($old_dir);
    
    echo "<h4>API Output:</h4>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    
    // Test count endpoint
    $_GET['action'] = 'count';
    ob_start();
    include 'notifications.php';
    $countOutput = ob_get_clean();
    
    echo "<h4>Count Output:</h4>";
    echo "<pre>" . htmlspecialchars($countOutput) . "</pre>";
    
    echo "<h3 style='color: green;'>✅ API Test Completed!</h3>";
    echo "<p><a href='index.html'>Test in Main Application</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
}
?>
