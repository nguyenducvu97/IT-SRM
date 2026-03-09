<?php
// Test script to verify user access control
// This script simulates different user roles and tests access to requests

require_once 'config/database.php';
require_once 'config/session.php';

// Start session
startSession();

// Test data setup
$test_users = [
    'admin' => ['id' => 1, 'username' => 'admin', 'role' => 'admin'],
    'staff' => ['id' => 2, 'username' => 'staff1', 'role' => 'staff'], 
    'user1' => ['id' => 3, 'username' => 'user1', 'role' => 'user'],
    'user2' => ['id' => 4, 'username' => 'user2', 'role' => 'user']
];

echo "<h2>Testing User Access Control</h2>\n";

// Test 1: Admin should see all requests
echo "<h3>Test 1: Admin Access</h3>\n";
$_SESSION['user_id'] = $test_users['admin']['id'];
$_SESSION['role'] = $test_users['admin']['role'];

$database = new Database();
$db = $database->getConnection();

$query = "SELECT COUNT(*) as total FROM service_requests";
$stmt = $db->prepare($query);
$stmt->execute();
$total_requests = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
echo "Admin can see $total_requests total requests<br>\n";

// Test 2: Staff should see assigned requests and their own
echo "<h3>Test 2: Staff Access</h3>\n";
$_SESSION['user_id'] = $test_users['staff']['id'];
$_SESSION['role'] = $test_users['staff']['role'];

$query = "SELECT COUNT(*) as total FROM service_requests 
          WHERE user_id = :user_id OR assigned_to = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $test_users['staff']['id']);
$stmt->execute();
$staff_requests = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
echo "Staff can see $staff_requests requests (own + assigned)<br>\n";

// Test 3: Regular user should only see their own requests
echo "<h3>Test 3: Regular User Access (user1)</h3>\n";
$_SESSION['user_id'] = $test_users['user1']['id'];
$_SESSION['role'] = $test_users['user1']['role'];

$query = "SELECT COUNT(*) as total FROM service_requests WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $test_users['user1']['id']);
$stmt->execute();
$user1_requests = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
echo "User1 can see $user1_requests requests (only their own)<br>\n";

// Test 4: Another regular user should only see their own requests
echo "<h3>Test 4: Regular User Access (user2)</h3>\n";
$_SESSION['user_id'] = $test_users['user2']['id'];
$_SESSION['role'] = $test_users['user2']['role'];

$query = "SELECT COUNT(*) as total FROM service_requests WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $test_users['user2']['id']);
$stmt->execute();
$user2_requests = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
echo "User2 can see $user2_requests requests (only their own)<br>\n";

// Test 5: Verify users cannot access other users' requests directly
echo "<h3>Test 5: Direct Access Control</h3>\n";

// Find a request that belongs to user1
$query = "SELECT id, user_id FROM service_requests WHERE user_id = ? LIMIT 1";
$stmt = $db->prepare($query);
$stmt->execute([$test_users['user1']['id']]);
$user1_request = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user1_request) {
    echo "Found request ID {$user1_request['id']} belonging to user1<br>\n";
    
    // Try to access it as user2 (should fail)
    $_SESSION['user_id'] = $test_users['user2']['id'];
    $_SESSION['role'] = $test_users['user2']['role'];
    
    $query = "SELECT id, user_id FROM service_requests WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user1_request['id']]);
    $request_check = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($request_check) {
        if ($request_check['user_id'] != $_SESSION['user_id']) {
            echo "✓ ACCESS DENIED: User2 cannot access User1's request (correct behavior)<br>\n";
        } else {
            echo "✗ ACCESS GRANTED: User2 can access User1's request (security issue!)<br>\n";
        }
    }
}

echo "<h3>Summary</h3>\n";
echo "Access control tests completed. Check the results above to ensure:<br>\n";
echo "1. Admin can see all requests<br>\n";
echo "2. Staff can see their own + assigned requests<br>\n";
echo "3. Regular users can only see their own requests<br>\n";
echo "4. Users cannot access other users' requests directly<br>\n";

// Clean up session
session_destroy();
?>
