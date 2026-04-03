<?php
// Simple session debug test
require_once 'config/session.php';
require_once 'config/database.php';

// Start session
startSession();

// Get database connection
$db = getDatabaseConnection();

echo "<h2>Session Debug Test</h2>";

echo "<h3>Session Data:</h3>";
echo "<pre>";
var_dump($_SESSION);
echo "</pre>";

echo "<h3>Cookie Data:</h3>";
echo "<pre>";
var_dump($_COOKIE);
echo "</pre>";

if (isset($_SESSION['user_id'])) {
    echo "<h3>User Found in Session</h3>";
    
    // Get user details from database
    $query = "SELECT id, username, full_name, role FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<pre>";
        var_dump($user);
        echo "</pre>";
        
        echo "<h3>Access Check:</h3>";
        echo "User Role: " . $user['role'] . "<br>";
        echo "Can access reject requests: " . (in_array($user['role'], ['admin', 'staff']) ? 'YES' : 'NO') . "<br>";
    } else {
        echo "User not found in database!";
    }
} else {
    echo "<h3>No User in Session</h3>";
    echo "Please <a href='index.html'>login</a> first.";
}

// Test API endpoint
echo "<h3>API Test:</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/it-service-request/api/auth.php?action=check_session');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
curl_close($ch);

echo "<pre>";
echo $response;
echo "</pre>";
?>
