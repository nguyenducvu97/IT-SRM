<?php
// Check session configuration
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Checking session configuration...\n";

// Test database connection
require_once 'config/database.php';
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "✅ Database connection successful\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit;
}

// Test session table
try {
    $stmt = $db->query("SHOW TABLES LIKE 'sessions'");
    $table_exists = $stmt->rowCount() > 0;
    
    if ($table_exists) {
        echo "✅ Sessions table exists\n";
    } else {
        echo "❌ Sessions table missing\n";
        // Create sessions table
        $sql = "CREATE TABLE sessions (
            id VARCHAR(128) PRIMARY KEY,
            data TEXT NOT NULL,
            timestamp INT NOT NULL,
            INDEX (timestamp)
        )";
        $db->exec($sql);
        echo "✅ Sessions table created\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking sessions table: " . $e->getMessage() . "\n";
}

// Test session start
try {
    require_once 'config/session.php';
    startSession();
    echo "✅ Session started successfully\n";
    
    // Set test data
    $_SESSION['test'] = 'working';
    $_SESSION['user_id'] = 4;
    $_SESSION['role'] = 'user';
    
    echo "✅ Session data set\n";
    echo "Session ID: " . session_id() . "\n";
    
} catch (Exception $e) {
    echo "❌ Session start failed: " . $e->getMessage() . "\n";
}

echo "Session check completed.\n";
?>
