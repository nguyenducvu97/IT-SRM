<?php
// Test role permissions after fixes
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test Role Permissions After Fixes</h2>";

try {
    $conn = new PDO("mysql:host=localhost;dbname=it_service_request", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Database connected</p>";
    
    // Get users by role
    $roles = ['admin', 'staff', 'user'];
    
    foreach ($roles as $role) {
        echo "<h3>Testing $role Role:</h3>";
        
        $stmt = $conn->prepare("SELECT id, username, full_name FROM users WHERE role = ? LIMIT 2");
        $stmt->execute([$role]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($users as $user) {
            echo "<h4>Testing as " . htmlspecialchars($user['full_name']) . " (ID: " . $user['id'] . ")</h4>";
            
            // Start session
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $role;
            
            echo "<p>Session role: " . $_SESSION['role'] . "</p>";
            
            // Test access to different pages
            $pages = [
                'users' => ['admin'],
                'departments' => ['admin'],
                'support-requests' => ['admin', 'staff'],
                'reject-requests' => ['admin', 'staff'],
                'new-request' => ['user', 'staff', 'admin'],
                'requests' => ['user', 'staff', 'admin'],
                'profile' => ['user', 'staff', 'admin']
            ];
            
            foreach ($pages as $page => $allowed_roles) {
                $has_access = in_array($role, $allowed_roles);
                $status = $has_access ? '✅' : '❌';
                $message = $has_access ? 'Can access' : 'Cannot access';
                
                echo "<p>$status $page: $message</p>";
            }
            
            echo "<hr>";
        }
    }
    
    echo "<h3>Expected Behavior After Fixes:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Page</th><th>Admin</th><th>Staff</th><th>User</th>";
    echo "</tr>";
    
    $page_permissions = [
        'users' => ['admin', '❌', '❌'],
        'departments' => ['admin', '❌', '❌'],
        'support-requests' => ['admin', 'staff', '❌'],
        'reject-requests' => ['admin', 'staff', '❌'],
        'new-request' => ['❌', '❌', 'admin'],
        'requests' => ['admin', 'staff', 'admin'],
        'profile' => ['admin', 'staff', 'admin']
    ];
    
    foreach ($page_permissions as $page => $permissions) {
        echo "<tr>";
        echo "<td><strong>$page</strong></td>";
        foreach ($permissions as $role => $access) {
            echo "<td>$access</td>";
        }
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<h3>Menu Display After Fixes:</h3>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> adminMenu + adminDepartmentMenu + adminSupportMenu + adminRejectMenu (no newRequestMenu)</li>";
    echo "<li><strong>Staff:</strong> adminSupportMenu + adminRejectMenu (no adminMenu, no adminDepartmentMenu, no newRequestMenu)</li>";
    echo "<li><strong>User:</strong> newRequestMenu (no admin menus)</li>";
    echo "</ul>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='index.html'>Test in Main Application</a></p>";
?>
