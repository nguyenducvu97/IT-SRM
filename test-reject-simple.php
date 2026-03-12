<?php
// Simple test for reject requests API
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Simple Test - Reject Requests API</h2>";

try {
    $conn = new PDO("mysql:host=localhost;dbname=it_service_request", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Database connected</p>";
    
    // Start session as staff
    session_start();
    $_SESSION['user_id'] = 17; // Staff ID from logs
    $_SESSION['username'] = 'nvnam';
    $_SESSION['role'] = 'staff';
    
    echo "<p>Session set: user_id=" . $_SESSION['user_id'] . ", role=" . $_SESSION['role'] . "</p>";
    
    // Test API directly
    echo "<h3>Testing API directly:</h3>";
    
    // Change to api directory
    $old_dir = getcwd();
    chdir(__DIR__ . '/api');
    
    // Set GET parameters exactly as JavaScript would
    $_GET['action'] = 'list';
    $_GET['status'] = 'pending';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    echo "<p>GET params: " . json_encode($_GET) . "</p>";
    
    // Capture all output including errors
    ob_start();
    
    try {
        include 'reject_requests.php';
        $output = ob_get_contents();
    } catch (Exception $e) {
        $output = "Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString();
    } catch (Error $e) {
        $output = "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString();
    }
    
    ob_end_clean();
    
    chdir($old_dir);
    
    echo "<h4>Raw API Output:</h4>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ccc;'>" . htmlspecialchars($output) . "</pre>";
    
    // Try to parse as JSON
    $json_start = strpos($output, '{');
    $json_end = strrpos($output, '}');
    
    if ($json_start !== false && $json_end !== false) {
        $json_string = substr($output, $json_start, $json_end - $json_start + 1);
        $response = json_decode($json_string, true);
        
        if ($response) {
            echo "<h4>Parsed JSON Response:</h4>";
            echo "<pre>" . print_r($response, true) . "</pre>";
            
            if (isset($response['success'])) {
                if ($response['success']) {
                    echo "<p style='color: green;'>✅ API Success!</p>";
                    if (isset($response['data'])) {
                        echo "<p>Found " . count($response['data']) . " reject requests</p>";
                    }
                } else {
                    echo "<p style='color: red;'>❌ API Failed: " . htmlspecialchars($response['message']) . "</p>";
                }
            }
        } else {
            echo "<p style='color: red;'>❌ Could not parse JSON</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ No JSON found in output</p>";
    }
    
    echo "<h3>Database Check:</h3>";
    
    // Check if reject_requests table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'reject_requests'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ reject_requests table exists</p>";
        
        // Check pending requests
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM reject_requests WHERE status = 'pending'");
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Pending requests: " . $count['total'] . "</p>";
        
        // Show sample data
        if ($count['total'] > 0) {
            $stmt = $conn->prepare("SELECT id, service_request_id, status, created_at FROM reject_requests WHERE status = 'pending' LIMIT 2");
            $stmt->execute();
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<p>Sample data:</p>";
            echo "<pre>" . print_r($requests, true) . "</pre>";
        }
    } else {
        echo "<p style='color: red;'>❌ reject_requests table does not exist</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ Database Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='index.html'>Test in Main Application</a></p>";
?>
