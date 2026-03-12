<?php
// Detailed debug for reject requests API
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Detailed Debug - Reject Requests API</h2>";

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
        
        // Test API step by step
        $old_dir = getcwd();
        chdir(__DIR__ . '/api');
        
        echo "<h4>Step 1: Check session variables in API</h4>";
        
        // Simulate the session check from the API
        if (!isset($_SESSION['user_id'])) {
            echo "<p style='color: red;'>❌ Session user_id not set</p>";
        } else {
            echo "<p style='color: green;'>✅ Session user_id: " . $_SESSION['user_id'] . "</p>";
        }
        
        if (!isset($_SESSION['role'])) {
            echo "<p style='color: red;'>❌ Session role not set</p>";
        } else {
            echo "<p style='color: green;'>✅ Session role: " . $_SESSION['role'] . "</p>";
        }
        
        echo "<h4>Step 2: Test API call with GET parameters</h4>";
        
        // Set GET parameters exactly as the app would
        $_GET['action'] = 'list';
        $_GET['status'] = 'pending';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        echo "<p>GET parameters: action=" . $_GET['action'] . ", status=" . $_GET['status'] . "</p>";
        echo "<p>Request method: " . $_SERVER['REQUEST_METHOD'] . "</p>";
        
        // Capture all output and errors
        ob_start();
        
        // Include the API file
        try {
            include 'reject_requests.php';
            $api_output = ob_get_contents();
        } catch (Exception $e) {
            $api_output = "Exception: " . $e->getMessage();
        } catch (Error $e) {
            $api_output = "Fatal Error: " . $e->getMessage();
        }
        
        ob_end_clean();
        
        chdir($old_dir);
        
        echo "<h5>API Output:</h5>";
        echo "<pre>" . htmlspecialchars($api_output) . "</pre>";
        
        // Try to parse as JSON
        $response = json_decode($api_output, true);
        if ($response) {
            echo "<h5>Parsed Response:</h5>";
            echo "<pre>" . print_r($response, true) . "</pre>";
            
            if (isset($response['success'])) {
                if ($response['success']) {
                    echo "<p style='color: green;'>✅ API Success</p>";
                    if (isset($response['data'])) {
                        echo "<p>Found " . count($response['data']) . " reject requests</p>";
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
        
        echo "<h4>Step 3: Check if there are any reject requests in database</h4>";
        
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM reject_requests WHERE status = 'pending'");
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p>Total pending reject requests: " . $count['total'] . "</p>";
        
        if ($count['total'] > 0) {
            $stmt = $conn->prepare("SELECT id, service_request_id, status FROM reject_requests WHERE status = 'pending' LIMIT 3");
            $stmt->execute();
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p>Sample requests:</p>";
            echo "<pre>" . print_r($requests, true) . "</pre>";
        }
        
    } else {
        echo "<p style='color: orange;'>⚠️ No staff user found</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ Database Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='index.html'>Test in Main Application</a></p>";
?>
