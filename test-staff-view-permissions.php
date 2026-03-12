<?php
// Test staff view permissions after fixes
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test Staff View Permissions After Fixes</h2>";

try {
    $conn = new PDO("mysql:host=localhost;dbname=it_service_request", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Database connected</p>";
    
    // Get staff user
    $stmt = $conn->prepare("SELECT id, username, full_name FROM users WHERE role = 'staff' LIMIT 1");
    $stmt->execute();
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($staff) {
        echo "<h3>Testing as Staff: " . htmlspecialchars($staff['full_name']) . "</h3>";
        
        // Start session as staff
        session_start();
        $_SESSION['user_id'] = $staff['id'];
        $_SESSION['username'] = $staff['username'];
        $_SESSION['role'] = 'staff';
        
        echo "<p>Session role: " . $_SESSION['role'] . "</p>";
        
        // Test API access
        echo "<h4>API Access Tests:</h4>";
        
        // Test support requests GET (should work now)
        echo "<h5>GET support-requests list (should work):</h5>";
        $old_dir = getcwd();
        chdir(__DIR__ . '/api');
        
        $_GET['action'] = 'list';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        ob_start();
        include 'support_requests.php';
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        if ($response && isset($response['success']) && $response['success']) {
            echo "<p style='color: green;'>✅ Can view support requests list</p>";
            echo "<p>Found " . count($response['data']) . " support requests</p>";
        } else {
            echo "<p style='color: red;'>❌ Cannot view support requests list</p>";
            echo "<p>Response: " . htmlspecialchars($output) . "</p>";
        }
        
        // Test reject requests GET (should work now)
        echo "<h5>GET reject-requests list (should work):</h5>";
        $_GET['action'] = 'list';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        ob_start();
        include 'reject_requests.php';
        $reject_output = ob_get_clean();
        
        $reject_response = json_decode($reject_output, true);
        if ($reject_response && isset($reject_response['success']) && $reject_response['success']) {
            echo "<p style='color: green;'>✅ Can view reject requests list</p>";
            echo "<p>Found " . count($reject_response['data']) . " reject requests</p>";
        } else {
            echo "<p style='color: red;'>❌ Cannot view reject requests list</p>";
            echo "<p>Response: " . htmlspecialchars($reject_output) . "</p>";
        }
        
        // Test support requests PUT (should still fail)
        echo "<h5>PUT support-requests decision (should fail):</h5>";
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_GET['action'] = '';
        $input_data = [
            'id' => 1,
            'decision' => 'approved',
            'reason' => 'Test reason'
        ];
        file_put_contents('php://input', json_encode($input_data));
        
        ob_start();
        include 'support_requests.php';
        $put_output = ob_get_clean();
        
        $put_response = json_decode($put_output, true);
        if ($put_response && isset($put_response['success']) && !$put_response['success']) {
            echo "<p style='color: green;'>✅ Cannot process support requests (correct)</p>";
            echo "<p>Message: " . htmlspecialchars($put_response['message']) . "</p>";
        } else {
            echo "<p style='color: red;'>❌ Can process support requests (incorrect)</p>";
        }
        
        // Test reject requests PUT (should still fail)
        echo "<h5>PUT reject-requests decision (should fail):</h5>";
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_GET['action'] = '';
        $reject_input_data = [
            'reject_id' => 1,
            'decision' => 'approved',
            'admin_reason' => 'Test reason'
        ];
        file_put_contents('php://input', json_encode($reject_input_data));
        
        ob_start();
        include 'reject_requests.php';
        $reject_put_output = ob_get_clean();
        
        $reject_put_response = json_decode($reject_put_output, true);
        if ($reject_put_response && isset($reject_put_response['success']) && !$reject_put_response['success']) {
            echo "<p style='color: green;'>✅ Cannot process reject requests (correct)</p>";
            echo "<p>Message: " . htmlspecialchars($reject_put_response['message']) . "</p>";
        } else {
            echo "<p style='color: red;'>❌ Can process reject requests (incorrect)</p>";
        }
        
        chdir($old_dir);
        
    } else {
        echo "<p style='color: orange;'>⚠️ No staff user found. Please create a staff user first.</p>";
    }
    
    echo "<h3>Fixed Permissions Summary:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Action</th><th>Admin</th><th>Staff</th><th>User</th>";
    echo "</tr>";
    
    $permissions = [
        'View support requests list' => ['✅', '✅', '❌'],
        'Process support requests' => ['✅', '❌', '❌'],
        'View reject requests list' => ['✅', '✅', '❌'],
        'Process reject requests' => ['✅', '❌', '❌'],
        'View users' => ['✅', '❌', '❌'],
        'Manage departments' => ['✅', '❌', '❌']
    ];
    
    foreach ($permissions as $action => $roles) {
        echo "<tr>";
        echo "<td><strong>$action</strong></td>";
        foreach ($roles as $role => $access) {
            echo "<td>$access</td>";
        }
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<h3>Frontend Behavior:</h3>";
    echo "<ul>";
    echo "<li>Staff can access support-requests and reject-requests pages</li>";
    echo "<li>Staff can see all requests in the list</li>";
    echo "<li>Staff will NOT see 'Xử lý' buttons (hidden by role check)</li>";
    echo "<li>Staff can view admin decisions and reasons</li>";
    echo "</ul>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='index.html'>Test in Main Application</a></p>";
?>
