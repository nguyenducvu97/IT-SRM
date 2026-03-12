<?php
// Test reject requests after syntax fix
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test Reject Requests After Syntax Fix</h2>";

try {
    $conn = new PDO("mysql:host=localhost;dbname=it_service_request", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Database connected</p>";
    
    // Start session as staff
    session_start();
    $_SESSION['user_id'] = 17;
    $_SESSION['username'] = 'nvnam';
    $_SESSION['role'] = 'staff';
    
    echo "<p>Session set: user_id=" . $_SESSION['user_id'] . ", role=" . $_SESSION['role'] . "</p>";
    
    // Test API
    $old_dir = getcwd();
    chdir(__DIR__ . '/api');
    
    $_GET['action'] = 'list';
    $_GET['status'] = 'pending';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    echo "<p>Testing: api/reject_requests.php?action=list&status=pending</p>";
    
    ob_start();
    include 'reject_requests.php';
    $output = ob_get_clean();
    
    chdir($old_dir);
    
    echo "<h3>API Output:</h3>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    
    $response = json_decode($output, true);
    if ($response) {
        echo "<h3>Parsed Response:</h3>";
        echo "<pre>" . print_r($response, true) . "</pre>";
        
        if (isset($response['success'])) {
            if ($response['success']) {
                echo "<p style='color: green;'>✅ API Success - Staff can view reject requests!</p>";
                echo "<p>Found " . count($response['data']) . " reject requests</p>";
                
                if (count($response['data']) === 0) {
                    echo "<p style='color: orange;'>⚠️ No pending reject requests found (this is normal if none exist)</p>";
                    
                    // Create a test reject request
                    echo "<h3>Creating test reject request...</h3>";
                    
                    // Get a service request to create reject request for
                    $stmt = $conn->prepare("SELECT id, user_id FROM service_requests LIMIT 1");
                    $stmt->execute();
                    $service_request = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($service_request) {
                        $stmt = $conn->prepare("
                            INSERT INTO reject_requests (service_request_id, user_id, reject_reason, reject_details, status, created_at)
                            VALUES (?, ?, ?, ?, 'pending', NOW())
                        ");
                        $result = $stmt->execute([
                            $service_request['id'],
                            $service_request['user_id'],
                            'Test reject reason',
                            'Test reject details'
                        ]);
                        
                        if ($result) {
                            echo "<p style='color: green;'>✅ Created test reject request</p>";
                            
                            // Test API again
                            echo "<h3>Testing API again with test data...</h3>";
                            
                            ob_start();
                            include 'reject_requests.php';
                            $output2 = ob_get_clean();
                            
                            echo "<pre>" . htmlspecialchars($output2) . "</pre>";
                            
                            $response2 = json_decode($output2, true);
                            if ($response2 && $response2['success']) {
                                echo "<p style='color: green;'>✅ Now found " . count($response2['data']) . " reject requests!</p>";
                            }
                        }
                    }
                }
            } else {
                echo "<p style='color: red;'>❌ API Failed: " . htmlspecialchars($response['message']) . "</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ No success field in response</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Invalid JSON response</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ Database Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='index.html'>Test in Main Application</a></p>";
?>
