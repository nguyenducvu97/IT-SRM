<?php
// Direct database table creation
echo "<h2>Creating Notifications Table Directly</h2>";

try {
    // Database connection
    $host = "localhost";
    $db_name = "it_service_request";
    $username = "root";
    $password = "";
    
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("set names utf8");
    
    echo "<p style='color: green;'>✅ Connected to database</p>";
    
    // Create notifications table
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
        related_id INT NULL,
        related_type ENUM('request', 'comment', 'assignment', 'resolution') NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        read_at TIMESTAMP NULL,
        INDEX idx_user_unread (user_id, is_read),
        INDEX idx_created_at (created_at)
    )";
    
    $conn->exec($sql);
    echo "<p style='color: green;'>✅ Notifications table created successfully</p>";
    
    // Verify table
    $stmt = $conn->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Table verification successful</p>";
        
        // Show structure
        $stmt = $conn->query("DESCRIBE notifications");
        echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Create a test notification for each user
        $stmt = $conn->query("SELECT id, full_name FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Creating Test Notifications:</h3>";
        foreach ($users as $user) {
            $title = "Chào mừng đến hệ thống thông báo!";
            $message = "Xin chào " . $user['full_name'] . ", hệ thống thông báo đã được kích hoạt thành công.";
            
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user['id'], $title, $message, 'success']);
            
            echo "<p style='color: green;'>✅ Test notification created for user: " . htmlspecialchars($user['full_name']) . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Table creation failed</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='index.html'>Go to IT Service Request System</a> | <a href='test-notifications.php'>Test Notifications</a></p>";
?>
