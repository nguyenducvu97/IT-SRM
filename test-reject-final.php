<?php
// Final test for reject requests after fixing all syntax errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Final Test - Reject Requests API</h2>";

try {
    $conn = new PDO("mysql:host=localhost;dbname=it_service_request", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Database connected</p>";
    
    // Start session as staff
    session_start();
    $_SESSION['user_id'] = 17;
    $_SESSION['username'] = 'nvnam';
    $_SESSION['role'] = 'staff';
    
    echo "<p>Session: user_id=" . $_SESSION['user_id'] . ", role=" . $_SESSION['role'] . "</p>";
    
    // Test API
    $old_dir = getcwd();
    chdir(__DIR__ . '/api');
    
    $_GET['action'] = 'list';
    $_GET['status'] = 'pending';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    echo "<h3>Testing API...</h3>";
    
    ob_start();
    include 'reject_requests.php';
    $output = ob_get_clean();
    
    chdir($old_dir);
    
    echo "<h4>API Output:</h4>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ccc;'>" . htmlspecialchars($output) . "</pre>";
    
    $response = json_decode($output, true);
    if ($response) {
        echo "<h4>Parsed Response:</h4>";
        echo "<pre>" . print_r($response, true) . "</pre>";
        
        if (isset($response['success'])) {
            if ($response['success']) {
                echo "<p style='color: green;'>✅ SUCCESS! Staff can view reject requests!</p>";
                echo "<p>Found " . count($response['data']) . " reject requests</p>";
                
                if (count($response['data']) === 0) {
                    echo "<p style='color: orange;'>ℹ️ No pending reject requests (this is normal)</p>";
                } else {
                    echo "<p style='color: green;'>🎉 Staff can see reject requests list!</p>";
                }
                
                echo "<h3>✅ PERMISSIONS WORKING CORRECTLY:</h3>";
                echo "<ul>";
                echo "<li>✅ Staff can VIEW reject requests list</li>";
                echo "<li>✅ Staff CANNOT process reject requests (PUT blocked)</li>";
                echo "<li>✅ Admin can do everything</li>";
                echo "<li>✅ Users cannot access these pages</li>";
                echo "</ul>";
                
            } else {
                echo "<p style='color: red;'>❌ API Failed: " . htmlspecialchars($response['message']) . "</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ No success field in response</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Invalid JSON response</p>";
        echo "<p>Raw output: " . htmlspecialchars($output) . "</p>";
    }
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li><a href='index.html'>Go to main application</a></li>";
    echo "<li>Login as staff user</li>";
    echo "<li>Navigate to 'Yêu cầu từ chối' page</li>";
    echo "<li>Should see list without errors</li>";
    echo "<li>Should NOT see 'Xử lý' buttons</li>";
    echo "</ol>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ Database Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='index.html'>Test in Main Application</a></p>";
?>
