<?php
// Test API endpoints directly
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>API Endpoints Test</h1>";

// Test 1: Auth API
echo "<h2>1. Testing Auth API</h2>";
try {
    $ch = curl_init('http://localhost/it-service-request/api/auth.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'action' => 'login',
        'username' => 'admin',
        'password' => 'admin'
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Status: $http_code<br>";
    echo "Response: <pre>" . htmlspecialchars($response) . "</pre>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

// Test 2: Notifications API (should fail without session)
echo "<h2>2. Testing Notifications API (no session)</h2>";
try {
    $ch = curl_init('http://localhost/it-service-request/api/notifications.php?action=list');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Status: $http_code<br>";
    echo "Response: <pre>" . htmlspecialchars($response) . "</pre>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

// Test 3: Comments API (should fail without session)
echo "<h2>3. Testing Comments API (no session)</h2>";
try {
    $ch = curl_init('http://localhost/it-service-request/api/comments.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'service_request_id' => 31,
        'comment' => 'Test comment'
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Status: $http_code<br>";
    echo "Response: <pre>" . htmlspecialchars($response) . "</pre>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

// Test 4: Database connection
echo "<h2>4. Testing Database Connection</h2>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "✅ Database connected<br>";
    echo "Users count: " . $count['count'] . "<br>";
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 5: Session functions
echo "<h2>5. Testing Session Functions</h2>";
try {
    require_once 'config/session.php';
    
    startSession();
    $_SESSION['test'] = 'Hello';
    
    echo "✅ Session functions working<br>";
    echo "Session data: " . json_encode($_SESSION) . "<br>";
    
} catch (Exception $e) {
    echo "❌ Session error: " . $e->getMessage() . "<br>";
}
?>
