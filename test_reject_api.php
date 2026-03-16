<?php
require_once 'config/database.php';
require_once 'config/session.php';

// Start session and login as admin
startSession();

// Auto-login as admin for testing
if (!isLoggedIn()) {
    $db = getDatabaseConnection();
    $stmt = $db->prepare("SELECT id, username, full_name, password_hash, role FROM users WHERE username = 'admin'");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify('admin123', $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
    }
}

header("Content-Type: application/json; charset=UTF-8");

// Test reject requests API
echo "<h2>Testing Reject Requests API</h2>";

// Test 1: Check if user is logged in
echo "<h3>1. Authentication Check:</h3>";
if (isLoggedIn()) {
    $user = getCurrentUser();
    echo "<p>✅ Logged in as: " . htmlspecialchars($user['username']) . " (" . htmlspecialchars($user['role']) . ")</p>";
} else {
    echo "<p>❌ Not logged in</p>";
}

// Test 2: Check if reject_requests table exists and has data
echo "<h3>2. Database Check:</h3>";
try {
    $db = getDatabaseConnection();
    
    // Check if table exists
    $stmt = $db->prepare("SHOW TABLES LIKE 'reject_requests'");
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ reject_requests table exists</p>";
        
        // Count records
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM reject_requests");
        $stmt->execute();
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "<p>Total reject requests: $total</p>";
        
        // Show sample records
        $stmt = $db->prepare("
            SELECT rr.*, 
                   u.full_name as requester_name,
                   sr.title as request_title
            FROM reject_requests rr
            JOIN users u ON rr.rejected_by = u.id
            JOIN service_requests sr ON rr.service_request_id = sr.id
            ORDER BY rr.created_at DESC
            LIMIT 3
        ");
        $stmt->execute();
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($requests)) {
            echo "<h4>Sample reject requests:</h4>";
            foreach ($requests as $req) {
                echo "<p>- ID {$req['id']}: {$req['request_title']} ({$req['status']}) by {$req['requester_name']}</p>";
            }
        } else {
            echo "<p>No reject requests found in database</p>";
        }
    } else {
        echo "<p>❌ reject_requests table doesn't exist</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 3: Test API endpoint directly
echo "<h3>3. API Endpoint Test:</h3>";
try {
    // Simulate API call
    $db = getDatabaseConnection();
    $status = 'pending';
    $limit = 20;
    $offset = 0;
    
    $stmt = $db->prepare("
        SELECT rr.*, 
               u.full_name as requester_name,
               sr.title as request_title
        FROM reject_requests rr
        JOIN users u ON rr.rejected_by = u.id
        JOIN service_requests sr ON rr.service_request_id = sr.id
        WHERE rr.status = ?
        ORDER BY rr.created_at DESC
        LIMIT $limit OFFSET $offset
    ");
    $stmt->execute([$status]);
    $reject_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>✅ API query executed successfully</p>";
    echo "<p>Found " . count($reject_requests) . " pending reject requests</p>";
    
    if (!empty($reject_requests)) {
        echo "<h4>API Response Sample:</h4>";
        echo "<pre>" . json_encode($reject_requests[0], JSON_PRETTY_PRINT) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ API test error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 4: Create sample reject request if none exist
echo "<h3>4. Create Sample Data (if needed):</h3>";
try {
    $db = getDatabaseConnection();
    
    // Check if we have any sample data
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM reject_requests");
    $stmt->execute();
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($total == 0) {
        // Create a sample reject request
        $stmt = $db->prepare("
            INSERT INTO reject_requests (service_request_id, rejected_by, reject_reason, reject_details, status, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        // Get a sample service request
        $sr_stmt = $db->prepare("SELECT id, title FROM service_requests LIMIT 1");
        $sr_stmt->execute();
        $service_request = $sr_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($service_request) {
            $result = $stmt->execute([
                $service_request['id'],
                $_SESSION['user_id'],
                'Test reject reason',
                'Test reject details',
                'pending'
            ]);
            
            if ($result) {
                echo "<p>✅ Created sample reject request for service request #{$service_request['id']}</p>";
            } else {
                echo "<p>❌ Failed to create sample reject request</p>";
            }
        } else {
            echo "<p>❌ No service requests found to create reject request for</p>";
        }
    } else {
        echo "<p>ℹ️ Sample data already exists ($total records)</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Sample data creation error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
