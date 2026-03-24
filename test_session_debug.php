<?php
// Simple test for session and database
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Session & Database Test</h2>";

try {
    require_once __DIR__ . '/../config/session.php';
    require_once __DIR__ . '/../config/database.php';
    
    echo "✅ Files loaded<br>";
    
    // Test database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db === null) {
        echo "❌ Database connection failed<br>";
        exit;
    }
    
    echo "✅ Database connected<br>";
    
    // Test session start
    echo "Starting session...<br>";
    startSession();
    echo "✅ Session started<br>";
    
    // Test session functions
    $user_id = getCurrentUserId();
    $user_role = getCurrentUserRole();
    
    echo "User ID: " . ($user_id ?? 'null') . "<br>";
    echo "User Role: " . ($user_role ?? 'null') . "<br>";
    echo "Is logged in: " . (isLoggedIn() ? 'YES' : 'NO') . "<br>";
    
    // Test simple query
    echo "<h3>Testing simple query...</h3>";
    $query = "SELECT COUNT(*) as total FROM service_requests";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute()) {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Simple query executed<br>";
        echo "Total requests: " . $result['total'] . "<br>";
    } else {
        echo "❌ Simple query failed<br>";
        echo "Error: " . print_r($stmt->errorInfo(), true) . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

?>
