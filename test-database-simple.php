<?php
// Simple database connection test
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Connection Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
        .test-section { border: 1px solid #ddd; padding: 20px; margin: 20px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <h2>🔧 Database Connection Test</h2>
    
    <div class="test-section">
        <h3>📋 Testing Database Connection</h3>
        
        <?php
        echo "<p class='info'>Testing connection to MySQL...</p>";
        
        // Test different ports
        $ports = [3306, 3307, 3308];
        $workingPort = null;
        
        foreach ($ports as $port) {
            echo "<p class='info'>Testing port $port...</p>";
            
            try {
                $conn = new PDO("mysql:host=localhost;port=$port;dbname=it_service_request;charset=utf8mb4", "root", "", [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 5
                ]);
                
                echo "<p class='success'>✅ Connected successfully on port $port</p>";
                $workingPort = $port;
                
                // Test a simple query
                $stmt = $conn->query("SELECT COUNT(*) as count FROM users LIMIT 1");
                $result = $stmt->fetch();
                echo "<p class='success'>✅ Query successful: {$result['count']} users found</p>";
                
                $conn = null;
                break;
                
            } catch (PDOException $e) {
                echo "<p class='error'>❌ Port $port failed: " . $e->getMessage() . "</p>";
            }
        }
        
        if ($workingPort) {
            echo "<h3 class='success'>✅ Working Port: $workingPort</h3>";
            echo "<p class='info'>Update config/database.php to use port $workingPort</p>";
        } else {
            echo "<h3 class='error'>❌ No working port found</h3>";
            echo "<p class='error'>Check if MySQL is running and database exists</p>";
        }
        ?>
        
        <h3>🔧 Recommended Fix:</h3>
        <?php if ($workingPort): ?>
        <p>Update <code>config/database.php</code> line 10:</p>
        <pre><code>private $port = <?php echo $workingPort; ?>;</code></pre>
        <?php else: ?>
        <p>1. Check if MySQL/XAMPP is running</p>
        <p>2. Check if database 'it_service_request' exists</p>
        <p>3. Check MySQL credentials</p>
        <?php endif; ?>
    </div>
    
    <div class="test-section">
        <h3>🗄️ Database Info</h3>
        <?php
        if ($workingPort) {
            try {
                $conn = new PDO("mysql:host=localhost;port=$workingPort;charset=utf8mb4", "root", "", [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                
                // Show databases
                $stmt = $conn->query("SHOW DATABASES");
                $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                echo "<h4>Available Databases:</h4>";
                echo "<ul>";
                foreach ($databases as $db) {
                    if ($db === 'it_service_request') {
                        echo "<li><strong>$db</strong> ✅</li>";
                    } else {
                        echo "<li>$db</li>";
                    }
                }
                echo "</ul>";
                
                // Check if target database exists and show tables
                if (in_array('it_service_request', $databases)) {
                    $conn->exec("USE it_service_request");
                    $stmt = $conn->query("SHOW TABLES");
                    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    echo "<h4>Tables in it_service_request:</h4>";
                    echo "<ul>";
                    foreach ($tables as $table) {
                        echo "<li>$table</li>";
                    }
                    echo "</ul>";
                }
                
                $conn = null;
                
            } catch (PDOException $e) {
                echo "<p class='error'>Error getting database info: " . $e->getMessage() . "</p>";
            }
        }
        ?>
    </div>
</body>
</html>
