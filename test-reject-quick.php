<?php
// Quick test for reject requests API
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Quick Test - Reject Requests API</h2>";

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
        
        echo "<p>Session: user_id=" . $_SESSION['user_id'] . ", role=" . $_SESSION['role'] . "</p>";
        
        // Test API
        $old_dir = getcwd();
        chdir(__DIR__ . '/api');
        
        echo "<h4>Testing GET reject_requests.php?action=list&status=pending</h4>";
        
        $_GET['action'] = 'list';
        $_GET['status'] = 'pending';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        ob_start();
        include 'reject_requests.php';
        $output = ob_get_clean();
        
        chdir($old_dir);
        
        echo "<h5>Raw Output:</h5>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
        
        $response = json_decode($output, true);
        if ($response && isset($response['success'])) {
            if ($response['success']) {
                echo "<p style='color: green;'>✅ API Success - Can view reject requests</p>";
                echo "<p>Found " . count($response['data']) . " reject requests</p>";
            } else {
                echo "<p style='color: red;'>❌ API Failed: " . htmlspecialchars($response['message']) . "</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Invalid JSON response</p>";
        }
        
    } else {
        echo "<p style='color: orange;'>⚠️ No staff user found</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='index.html'>Test in Main Application</a></p>";
?>
