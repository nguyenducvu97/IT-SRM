<?php
// Debug request support count
session_start();

// Mock login for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['role'] = 'admin';
    $_SESSION['full_name'] = 'Test Admin';
}

require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'];
    
    echo "<h1>Debug Request Support Count</h1>";
    echo "<p>User ID: $user_id</p>";
    echo "<p>User Role: $user_role</p>";
    
    // Test 1: Check support_requests table
    echo "<h2>Test 1: Support Requests Table</h2>";
    $support_query = "SELECT COUNT(*) as total FROM support_requests";
    $stmt = $db->prepare($support_query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total support requests: <strong>{$result['total']}</strong></p>";
    
    // Test 2: Check approved support requests
    echo "<h2>Test 2: Approved Support Requests</h2>";
    $approved_query = "SELECT COUNT(*) as total FROM support_requests WHERE status = 'approved'";
    $stmt = $db->prepare($approved_query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Approved support requests: <strong>{$result['total']}</strong></p>";
    
    // Test 3: Check service_requests with support requests (same as API)
    echo "<h2>Test 3: Service Requests with Support Requests (API Logic)</h2>";
    $support_query = "SELECT COUNT(DISTINCT sr.id) as count 
                     FROM service_requests sr 
                     LEFT JOIN support_requests sreq ON sr.id = sreq.service_request_id 
                     WHERE sreq.id IS NOT NULL AND sreq.status = 'approved'";
    
    // Only filter by user for non-admin/non-staff
    if ($user_role != 'admin' && $user_role != 'staff') {
        $support_query .= " AND sr.user_id = :user_id";
        $support_stmt = $db->prepare($support_query);
        $support_stmt->bindValue(":user_id", $user_id);
    } else {
        $support_stmt = $db->prepare($support_query);
    }
    
    $support_stmt->execute();
    $support_result = $support_stmt->fetch(PDO::FETCH_ASSOC);
    $request_support_count = $support_result['count'] ?? 0;
    
    echo "<p>API query result: <strong>$request_support_count</strong></p>";
    echo "<p>Query used: <code>$support_query</code></p>";
    
    // Test 4: Show actual data
    echo "<h2>Test 4: Actual Support Request Data</h2>";
    $data_query = "SELECT sr.id as request_id, sr.title, sreq.id as support_id, sreq.status as support_status
                  FROM service_requests sr 
                  LEFT JOIN support_requests sreq ON sr.id = sreq.service_request_id 
                  WHERE sreq.id IS NOT NULL";
    
    if ($user_role != 'admin' && $user_role != 'staff') {
        $data_query .= " AND sr.user_id = :user_id";
        $data_stmt = $db->prepare($data_query);
        $data_stmt->bindValue(":user_id", $user_id);
    } else {
        $data_stmt = $db->prepare($data_query);
    }
    
    $data_stmt->execute();
    $support_data = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Request ID</th><th>Title</th><th>Support ID</th><th>Support Status</th></tr>";
    
    $approved_count = 0;
    foreach ($support_data as $row) {
        echo "<tr>";
        echo "<td>{$row['request_id']}</td>";
        echo "<td>{$row['title']}</td>";
        echo "<td>{$row['support_id']}</td>";
        echo "<td>{$row['support_status']}</td>";
        echo "</tr>";
        
        if ($row['support_status'] === 'approved') {
            $approved_count++;
        }
    }
    echo "</table>";
    
    echo "<h3>Manual Count Verification</h3>";
    echo "<p>Manual approved count: <strong>$approved_count</strong></p>";
    echo "<p>API query count: <strong>$request_support_count</strong></p>";
    echo "<p>Match: " . ($approved_count == $request_support_count ? 'YES' : 'NO') . "</p>";
    
    // Test 5: API call simulation
    echo "<h2>Test 5: API Response Simulation</h2>";
    echo "<iframe src='api/service_requests.php?action=list' width='100%' height='300'></iframe>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<p><a href="index.html">Back to Application</a></p>
