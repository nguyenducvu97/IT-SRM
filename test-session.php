<?php
// Test session functionality
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
        .test-section { border: 1px solid #ddd; padding: 20px; margin: 20px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <h2>🔧 Session Test</h2>
    
    <div class="test-section">
        <h3>📋 Testing Session Handler</h3>
        
        <?php
        echo "<p class='info'>Testing session start...</p>";
        
        try {
            require_once 'config/session.php';
            startSession();
            echo "<p class='success'>✅ Session started successfully</p>";
            echo "<p class='info'>Session ID: " . session_id() . "</p>";
            echo "<p class='info'>Session status: " . session_status() . "</p>";
            
            // Test session data
            $_SESSION['test'] = 'Session working!';
            echo "<p class='success'>✅ Session data written: " . $_SESSION['test'] . "</p>";
            
            // Show session contents
            echo "<h4>Session Contents:</h4>";
            echo "<pre>" . print_r($_SESSION, true) . "</pre>";
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Session error: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <div class="test-section">
        <h3>🗄️ Database Connection Test</h3>
        
        <?php
        try {
            require_once 'config/database.php';
            $database = new Database();
            $conn = $database->getConnection();
            
            if ($conn) {
                echo "<p class='success'>✅ Database connected successfully</p>";
                
                // Test query
                $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
                $result = $stmt->fetch();
                echo "<p class='success'>✅ Users table: {$result['count']} records</p>";
                
                // Test session handler
                $handler = new DatabaseSessionHandler();
                if ($handler->db) {
                    echo "<p class='success'>✅ Session handler database connection working</p>";
                } else {
                    echo "<p class='error'>❌ Session handler database connection failed</p>";
                }
            } else {
                echo "<p class='error'>❌ Database connection failed</p>";
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Database error: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <div class="test-section">
        <h3>🔧 Test API Authentication</h3>
        
        <?php
        // Test auth API
        $auth_url = 'http://localhost/it-service-request/api/auth.php?action=check_session';
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Cookie: ' . $_SERVER['HTTP_COOKIE'] ?? ''
            ]
        ]);
        
        $response = file_get_contents($auth_url, false, $context);
        
        if ($response) {
            echo "<p class='success'>✅ Auth API responded</p>";
            echo "<h4>Response:</h4>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
        } else {
            echo "<p class='error'>❌ Auth API failed to respond</p>";
        }
        ?>
    </div>
</body>
</html>
