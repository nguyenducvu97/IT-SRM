<?php
// Simple auth debug test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Auth Debug Test</h2>";

// Test loading auth.php
try {
    require_once 'config/session.php';
    require_once 'config/database.php';
    
    echo "✅ Config files loaded successfully<br>";
    
    // Start session
    startSession();
    echo "✅ Session started successfully<br>";
    
    // Get database connection
    $db = getDatabaseConnection();
    echo "✅ Database connection established<br>";
    
    // Test auth.php inclusion
    include 'api/auth.php';
    echo "✅ Auth.php included successfully<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} catch (Error $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Test session check endpoint
echo "<h3>Test Session Check:</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/it-service-request/api/auth.php?action=check_session');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
curl_setopt($ch, CURLOPT_HEADER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode<br>";
echo "Response: <pre>" . htmlspecialchars($response) . "</pre>";

// Test login endpoint
echo "<h3>Test Login:</h3>";
$loginData = json_encode(['action' => 'login', 'username' => 'admin', 'password' => 'admin123']);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/it-service-request/api/auth.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_HEADER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode<br>";
echo "Response: <pre>" . htmlspecialchars($response) . "</pre>";
?>
