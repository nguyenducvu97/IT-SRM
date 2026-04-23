<?php
// Add Status Column to Users Table
// Thêm cột status vào table users

require_once __DIR__ . '/config/database.php';

echo "<h1>🔧 Add Status Column to Users Table</h1>";

try {
    $db = getDatabaseConnection();
    
    echo "<h2>Step 1: Check Current Table Structure</h2>";
    
    // Check if status column already exists
    $stmt = $db->prepare("DESCRIBE users");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $status_exists = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'status') {
            $status_exists = true;
            break;
        }
    }
    
    if ($status_exists) {
        echo "<p style='color: green;'>✅ <strong>Status column already exists!</strong></p>";
    } else {
        echo "<p style='color: orange;'>⚠️ <strong>Status column not found - adding it...</strong></p>";
        
        echo "<h2>Step 2: Add Status Column</h2>";
        
        // Add the status column
        $alter_sql = "ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'active' AFTER email";
        
        echo "<p><strong>SQL:</strong> <code style='background: #f8f9fa; padding: 5px; border-radius: 3px;'>{$alter_sql}</code></p>";
        
        $result = $db->exec($alter_sql);
        
        if ($result) {
            echo "<p style='color: green;'>✅ <strong>Status column added successfully!</strong></p>";
        } else {
            echo "<p style='color: red;'>❌ <strong>Failed to add status column!</strong></p>";
            echo "<p><strong>Error:</strong> " . print_r($db->errorInfo()) . "</p>";
        }
    }
    
    echo "<hr>";
    
    echo "<h2>Step 3: Verify New Structure</h2>";
    
    // Check the new structure
    $stmt = $db->prepare("DESCRIBE users");
    $stmt->execute();
    $new_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    
    foreach ($new_columns as $column) {
        $highlight = $column['Field'] === 'status' ? 'style="background: #d4edda;"' : '';
        echo "<tr {$highlight}>";
        echo "<td><strong>{$column['Field']}</strong></td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<hr>";
    
    echo "<h2>Step 4: Update Existing Users</h2>";
    
    // Set default status for existing users
    $update_sql = "UPDATE users SET status = 'active' WHERE status IS NULL";
    $update_result = $db->exec($update_sql);
    
    $affected_rows = $update_result ? $db->rowCount() : 0;
    
    echo "<p><strong>SQL:</strong> <code style='background: #f8f9fa; padding: 5px; border-radius: 3px;'>{$update_sql}</code></p>";
    echo "<p><strong>Updated rows:</strong> {$affected_rows}</p>";
    
    echo "<hr>";
    
    echo "<h2>Step 5: Test Sample Data</h2>";
    
    // Test with a sample user
    $test_stmt = $db->prepare("SELECT id, username, full_name, email, role, status FROM users LIMIT 3");
    $test_stmt->execute();
    $test_users = $test_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Role</th><th>Status</th></tr>";
    
    foreach ($test_users as $user) {
        $status_color = $user['status'] === 'active' ? 'green' : 'red';
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['full_name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "<td><strong style='color: {$status_color}'>" . ($user['status'] ?? 'NULL') . "</strong></td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<hr>";
    echo "<h2>✅ Summary</h2>";
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
    echo "<p><strong>Status Column:</strong> " . ($status_exists ? 'Already existed' : 'Successfully added') . "</p>";
    echo "<p><strong>Users Updated:</strong> {$affected_rows} users now have status = 'active'</p>";
    echo "<p><strong>Database:</strong> Ready for email notifications!</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ <strong>Error:</strong> " . $e->getMessage() . "</p>";
}
?>
