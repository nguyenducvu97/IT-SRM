<?php
// MySQL Service Fix Script
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>MySQL Service Fix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
        .warning { color: orange; font-weight: bold; }
        .test-section { border: 1px solid #ddd; padding: 20px; margin: 20px 0; border-radius: 5px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h2>🔧 MySQL Service Fix</h2>
    
    <div class="test-section">
        <h3>📋 Current MySQL Status</h3>
        
        <?php
        // Check if MySQL process is running
        $process_check = shell_exec('tasklist | findstr mysql');
        if ($process_check) {
            echo "<p class='success'>✅ MySQL process is running:</p>";
            echo "<pre>" . htmlspecialchars($process_check) . "</pre>";
        } else {
            echo "<p class='error'>❌ MySQL process is NOT running</p>";
        }
        
        // Check common MySQL ports
        $ports = [3306, 3307, 3308];
        echo "<h4>Port Status Check:</h4>";
        foreach ($ports as $port) {
            $connection = @fsockopen('localhost', $port, $errno, $errstr, 2);
            if ($connection) {
                echo "<p class='success'>✅ Port $port: OPEN</p>";
                fclose($connection);
            } else {
                echo "<p class='error'>❌ Port $port: CLOSED ($errno - $errstr)</p>";
            }
        }
        ?>
    </div>
    
    <div class="test-section">
        <h3>🔧 XAMPP MySQL Service Status</h3>
        
        <?php
        // Check XAMPP MySQL service
        $service_check = shell_exec('sc query mysql 2>nul');
        if ($service_check && strpos($service_check, 'FAILED') === false) {
            echo "<p class='success'>✅ MySQL service found:</p>";
            echo "<pre>" . htmlspecialchars($service_check) . "</pre>";
        } else {
            echo "<p class='warning'>⚠️ MySQL service not found or not installed</p>";
        }
        
        // Check if XAMPP is installed
        $xampp_paths = [
            'C:\xampp',
            'C:\xampp2',
            'D:\xampp'
        ];
        
        echo "<h4>XAMPP Installation Check:</h4>";
        foreach ($xampp_paths as $path) {
            if (file_exists($path)) {
                echo "<p class='success'>✅ XAMPP found at: $path</p>";
                if (file_exists($path . '\mysql\bin\mysqld.exe')) {
                    echo "<p class='success'>✅ MySQL binary found at: $path\\mysql\\bin\\mysqld.exe</p>";
                }
            }
        }
        ?>
    </div>
    
    <div class="test-section">
        <h3>🛠️ Manual Fix Instructions</h3>
        
        <div class="warning">
            <h4>⚠️ IMPORTANT: Follow these steps to fix MySQL:</h4>
            
            <ol>
                <li><strong>Open XAMPP Control Panel</strong>
                    <ul>
                        <li>Navigate to C:\xampp</li>
                        <li>Double-click <code>xampp-control.exe</code></li>
                        <li>Or search for "XAMPP Control Panel" in Start Menu</li>
                    </ul>
                </li>
                
                <li><strong>Start MySQL Service</strong>
                    <ul>
                        <li>In XAMPP Control Panel, find MySQL row</li>
                        <li>Click the "Start" button next to MySQL</li>
                        <li>Wait for status to change to "Running"</li>
                        <li>Check that the port number shows (usually 3306 or 3308)</li>
                    </ul>
                </li>
                
                <li><strong>Verify Connection</strong>
                    <ul>
                        <li>MySQL should show green "Running" status</li>
                        <li>Port should be accessible (check the port number displayed)</li>
                        <li>Test again with this page</li>
                    </ul>
                </li>
                
                <li><strong>If MySQL won't start:</strong>
                    <ul>
                        <li>Check for other MySQL services running</li>
                        <li>Stop other MySQL instances</li>
                        <li>Check if port 3306/3308 is blocked</li>
                        <li>Restart XAMPP Control Panel</li>
                        <li>Run as Administrator</li>
                    </ul>
                </li>
            </ol>
        </div>
        
        <h4>🔄 Alternative: Command Line Start</h4>
        <pre>
# Open Command Prompt as Administrator
cd C:\xampp\mysql\bin
mysqld --console

# Or using XAMPP batch files
cd C:\xampp
mysql_start.bat
        </pre>
        
        <h4>🔍 Check MySQL Configuration</h4>
        <pre>
# Check my.ini file
C:\xampp\mysql\bin\my.ini

# Look for port setting:
port = 3306  # or 3308
        </pre>
    </div>
    
    <div class="test-section">
        <h3>🧪 Test Database Connection After Fix</h3>
        
        <p><a href="test-database-simple.php" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
            🔄 Test Database Connection Again
        </a></p>
        
        <p><a href="test-session.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
            🔄 Test Session Functionality
        </a></p>
    </div>
    
    <div class="test-section">
        <h3>📝 Common Issues & Solutions</h3>
        
        <div class="info">
            <h4>Issue: "Port already in use"</h4>
            <p>Solution: Another MySQL instance is running. Stop it first.</p>
            
            <h4>Issue: "Access denied"</h4>
            <p>Solution: Run XAMPP Control Panel as Administrator.</p>
            
            <h4>Issue: "Service cannot start"</h4>
            <p>Solution: Check my.ini configuration or corrupted MySQL files.</p>
            
            <h4>Issue: "Database connection failed"</h4>
            <p>Solution: Update port number in config/database.php to match XAMPP MySQL port.</p>
        </div>
    </div>
</body>
</html>
