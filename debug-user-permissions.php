<?php
// Debug user permissions issue
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug User Permissions</h2>";

try {
    $conn = new PDO("mysql:host=localhost;dbname=it_service_request", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Database connected</p>";
    
    // Check all users and their roles
    echo "<h3>All Users:</h3>";
    $stmt = $conn->prepare("SELECT id, username, full_name, role, department FROM users ORDER BY id");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Department</th>";
    echo "</tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . htmlspecialchars($user['username']) . "</td>";
        echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($user['role']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($user['department'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check role distribution
    echo "<h3>Role Distribution:</h3>";
    $stmt = $conn->prepare("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $stmt->execute();
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($roles as $role) {
        echo "<p><strong>" . htmlspecialchars($role['role']) . ":</strong> " . $role['count'] . " users</p>";
    }
    
    // Check if there are staff users
    $stmt = $conn->prepare("SELECT id, username, full_name FROM users WHERE role = 'staff'");
    $stmt->execute();
    $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Staff Users:</h3>";
    if (count($staff) > 0) {
        foreach ($staff as $staff_user) {
            echo "<p>ID: " . $staff_user['id'] . " - " . htmlspecialchars($staff_user['username']) . " (" . htmlspecialchars($staff_user['full_name']) . ")</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ No staff users found!</p>";
    }
    
    // Check if there are admin users
    $stmt = $conn->prepare("SELECT id, username, full_name FROM users WHERE role = 'admin'");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Admin Users:</h3>";
    if (count($admins) > 0) {
        foreach ($admins as $admin_user) {
            echo "<p>ID: " . $admin_user['id'] . " - " . htmlspecialchars($admin_user['username']) . " (" . htmlspecialchars($admin_user['full_name']) . ")</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ No admin users found!</p>";
    }
    
    // Check session logic
    echo "<h3>Session Test:</h3>";
    session_start();
    
    // Simulate different user logins
    $test_users = [
        ['id' => 1, 'username' => 'admin', 'role' => 'admin'],
        ['id' => 2, 'username' => 'staff', 'role' => 'staff'],
        ['id' => 3, 'username' => 'user', 'role' => 'user']
    ];
    
    foreach ($test_users as $test_user) {
        $_SESSION['user_id'] = $test_user['id'];
        $_SESSION['username'] = $test_user['username'];
        $_SESSION['role'] = $test_user['role'];
        
        echo "<h4>Testing as " . $test_user['role'] . " (ID: " . $test_user['id'] . ")</h4>";
        echo "<p>Session user_id: " . $_SESSION['user_id'] . "</p>";
        echo "<p>Session role: " . $_SESSION['role'] . "</p>";
        
        // Test common permission checks
        echo "<p>isAdmin(): " . (function_exists('isAdmin') ? (isAdmin() ? 'true' : 'false') : 'function not found') . "</p>";
        echo "<p>isStaff(): " . (function_exists('isStaff') ? (isStaff() ? 'true' : 'false') : 'function not found') . "</p>";
        echo "<p>isLoggedIn(): " . (function_exists('isLoggedIn') ? (isLoggedIn() ? 'true' : 'false') : 'function not found') . "</p>";
    }
    
    echo "<h3>Recommendations:</h3>";
    if (count($staff) === 0) {
        echo "<p style='color: orange;'>⚠️ Consider creating at least one staff user for proper testing</p>";
    }
    if (count($admins) === 0) {
        echo "<p style='color: red;'>❌ No admin users found! System needs at least one admin.</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
}
?>
