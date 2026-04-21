<?php
// Check Users Table Structure
// Kiểm tra cấu trúc table users để fix lỗi column not found

require_once __DIR__ . '/config/database.php';

echo "<h1>🔍 Users Table Structure Check</h1>";

try {
    $db = getDatabaseConnection();
    
    echo "<h2>Method 1: DESCRIBE users</h2>";
    
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td><strong>{$column['Field']}</strong></td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<hr>";
    
    echo "<h2>Method 2: Check for 'status' column</h2>";
    
    $has_status = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'status') {
            $has_status = true;
            break;
        }
    }
    
    if ($has_status) {
        echo "<p style='color: green;'>✅ <strong>Status column EXISTS</strong></p>";
    } else {
        echo "<p style='color: red;'>❌ <strong>Status column NOT FOUND</strong></p>";
        
        echo "<h3>Solution:</h3>";
        echo "<p>Add status column to users table:</p>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
        echo "ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'active' AFTER email;";
        echo "</pre>";
    }
    
    echo "<hr>";
    
    echo "<h2>Method 3: Show Sample Data</h2>";
    
    $stmt = $db->query("SELECT id, username, full_name, email, role FROM users LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Role</th>";
    if ($has_status) {
        echo "<th>Status</th>";
    }
    echo "</tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['full_name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['role']}</td>";
        if ($has_status) {
            echo "<td>" . ($user['status'] ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}
?>
