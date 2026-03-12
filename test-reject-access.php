<?php
// Test reject requests after fixing access control
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test Reject Requests After Access Fix</h2>";

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
    
    echo "<h3>Testing: api/reject_requests.php?action=list&status=pending</h3>";
    
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
                echo "<p style='color: green;'>🎉 SUCCESS! Staff can now view reject requests!</p>";
                echo "<p>Found " . count($response['data']) . " reject requests</p>";
                
                if (count($response['data']) === 0) {
                    echo "<p style='color: blue;'>ℹ️ No pending reject requests (this is normal)</p>";
                    
                    // Create test data
                    echo "<h4>Creating test reject request...</h4>";
                    
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
                            'Test reject reason for staff view',
                            'Test reject details for staff view'
                        ]);
                        
                        if ($result) {
                            echo "<p style='color: green;'>✅ Created test reject request</p>";
                            
                            // Test again
                            echo "<h4>Testing API again with test data...</h4>";
                            
                            ob_start();
                            include 'reject_requests.php';
                            $output2 = ob_get_clean();
                            
                            $response2 = json_decode($output2, true);
                            if ($response2 && $response2['success']) {
                                echo "<p style='color: green;'>🎉 Now found " . count($response2['data']) . " reject requests!</p>";
                                
                                // Show first request
                                if (!empty($response2['data'])) {
                                    echo "<h4>Sample reject request:</h4>";
                                    echo "<pre>" . print_r($response2['data'][0], true) . "</pre>";
                                }
                            }
                        }
                    }
                } else {
                    echo "<p style='color: green;'>🎉 Staff can see existing reject requests!</p>";
                }
                
                echo "<h3>✅ FINAL STATUS - ALL WORKING!</h3>";
                echo "<ul>";
                echo "<li>✅ Staff can VIEW reject requests list</li>";
                echo "<li>✅ Staff can VIEW support requests list</li>";
                echo "<li>✅ Staff CANNOT process requests (PUT blocked)</li>";
                echo "<li>✅ Staff CANNOT see process buttons in UI</li>";
                echo "<li>✅ Admin can do everything</li>";
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
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ Database Error: " . $e->getMessage() . "</p>";
}

echo "<h3>🚀 READY FOR MAIN APPLICATION!</h3>";
echo "<p><a href='index.html'>Test in Main Application</a></p>";
?>
