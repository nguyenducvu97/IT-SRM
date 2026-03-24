<?php
// Minimal test to isolate the error
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Minimal Service Requests Test</h2>";

try {
    echo "1. Loading session.php...<br>";
    require_once __DIR__ . '/config/session.php';
    echo "✅ session.php loaded<br>";
    
    echo "2. Loading database.php...<br>";
    require_once __DIR__ . '/config/database.php';
    echo "✅ database.php loaded<br>";
    
    echo "3. Starting session...<br>";
    startSession();
    echo "✅ Session started<br>";
    
    echo "4. Creating database connection...<br>";
    $database = new Database();
    $db = $database->getConnection();
    if ($db === null) {
        echo "❌ Database connection failed<br>";
        exit;
    }
    echo "✅ Database connected<br>";
    
    echo "5. Testing user functions...<br>";
    $user_id = getCurrentUserId();
    $user_role = getCurrentUserRole();
    echo "User ID: " . ($user_id ?? 'null') . "<br>";
    echo "User Role: " . ($user_role ?? 'null') . "<br>";
    
    echo "6. Testing simple query...<br>";
    $query = "SELECT COUNT(*) as total FROM service_requests LIMIT 1";
    $stmt = $db->prepare($query);
    if ($stmt->execute()) {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Query executed: " . $result['total'] . " requests<br>";
    } else {
        echo "❌ Query failed<br>";
    }
    
    echo "7. Testing service_requests.php include...<br>";
    
    // Set up environment
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET['action'] = 'list';
    
    // Capture output
    ob_start();
    include __DIR__ . '/api/service_requests.php';
    $output = ob_get_clean();
    
    echo "✅ service_requests.php included<br>";
    echo "Output length: " . strlen($output) . " characters<br>";
    if (strlen($output) > 0) {
        echo "<h3>Output:</h3>";
        echo "<pre>" . htmlspecialchars(substr($output, 0, 1000)) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} catch (Error $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

?>
