<?php
// Test script for permission system
require_once 'config/database.php';
require_once 'config/session.php';

// Start session
startSession();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Please login first");
}

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    die("Database connection failed");
}

$current_user = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'user';

echo "<h2>Permission System Test</h2>";
echo "<p><strong>Current User ID:</strong> $current_user</p>";
echo "<p><strong>Current User Role:</strong> $user_role</p>";

// Test support requests API
echo "<h3>Testing Support Requests API</h3>";

// Test 1: Get a support request
echo "<h4>Test 1: Get Support Request</h4>";
$test_support_id = 1; // Adjust this to a valid support request ID in your database

$stmt = $db->prepare("
    SELECT sr.*, 
           u.username as requester_name,
           srq.title as request_title
    FROM support_requests sr
    JOIN users u ON sr.requester_id = u.id
    JOIN service_requests srq ON sr.service_request_id = srq.id
    WHERE sr.id = ?
");
$stmt->execute([$test_support_id]);
$support_request = $stmt->fetch(PDO::FETCH_ASSOC);

if ($support_request) {
    echo "<p><strong>Original Support Request Data:</strong></p>";
    echo "<pre>" . print_r($support_request, true) . "</pre>";
    
    // Apply filtering based on user role
    if ($user_role === 'user') {
        unset($support_request['admin_reason']);
        unset($support_request['processed_by']);
        unset($support_request['processed_at']);
    }
    
    echo "<p><strong>Filtered Support Request Data (for role: $user_role):</strong></p>";
    echo "<pre>" . print_r($support_request, true) . "</pre>";
} else {
    echo "<p>No support request found with ID: $test_support_id</p>";
}

// Test 2: Get reject requests
echo "<h4>Test 2: Get Reject Request</h4>";
$test_reject_id = 1; // Adjust this to a valid reject request ID in your database

$stmt = $db->prepare("
    SELECT rr.*, 
           u.full_name as requester_name,
           sr.title as request_title
    FROM reject_requests rr
    JOIN users u ON rr.rejected_by = u.id
    JOIN service_requests sr ON rr.service_request_id = sr.id
    WHERE rr.id = ?
");
$stmt->execute([$test_reject_id]);
$reject_request = $stmt->fetch(PDO::FETCH_ASSOC);

if ($reject_request) {
    echo "<p><strong>Original Reject Request Data:</strong></p>";
    echo "<pre>" . print_r($reject_request, true) . "</pre>";
    
    // Apply filtering based on user role
    if ($user_role === 'user') {
        unset($reject_request['admin_reason']);
        unset($reject_request['processed_by']);
        unset($reject_request['processed_at']);
    }
    
    echo "<p><strong>Filtered Reject Request Data (for role: $user_role):</strong></p>";
    echo "<pre>" . print_r($reject_request, true) . "</pre>";
} else {
    echo "<p>No reject request found with ID: $test_reject_id</p>";
}

// Test 3: Get service request with support/reject info
echo "<h4>Test 3: Get Service Request with Support/Reject Info</h4>";
$test_service_id = 1; // Adjust this to a valid service request ID in your database

$stmt = $db->prepare("
    SELECT sr.*, u.username as requester_name, c.name as category_name,
           sreq.id as support_request_id, sreq.support_type, sreq.support_details, 
           sreq.support_reason, sreq.status as support_status, sreq.admin_reason,
           sreq.processed_by, sreq.processed_at, sreq.created_at as support_created_at,
           sreq_admin.full_name as support_admin_name,
           r.id as reject_id, r.reject_reason, r.reject_details, r.status as reject_status,
           r.admin_reason as reject_admin_reason, r.processed_by as reject_processed_by,
           r.processed_at as reject_processed_at, r.created_at as reject_created_at,
           r_admin.full_name as reject_admin_name
    FROM service_requests sr
    LEFT JOIN users u ON sr.user_id = u.id
    LEFT JOIN categories c ON sr.category_id = c.id
    LEFT JOIN support_requests sreq ON sr.id = sreq.service_request_id
    LEFT JOIN users sreq_admin ON sreq.processed_by = sreq_admin.id
    LEFT JOIN reject_requests r ON sr.id = r.service_request_id
    LEFT JOIN users r_admin ON r.processed_by = r_admin.id
    WHERE sr.id = ?
");
$stmt->execute([$test_service_id]);
$service_request = $stmt->fetch(PDO::FETCH_ASSOC);

if ($service_request) {
    echo "<p><strong>Original Service Request Data:</strong></p>";
    echo "<pre>" . print_r($service_request, true) . "</pre>";
    
    // Format support request data if exists
    if ($service_request['support_request_id']) {
        $service_request['support_request'] = [
            'id' => $service_request['support_request_id'],
            'support_type' => $service_request['support_type'],
            'support_details' => $service_request['support_details'],
            'support_reason' => $service_request['support_reason'],
            'status' => $service_request['support_status'],
            'admin_reason' => $service_request['admin_reason'],
            'processed_by' => $service_request['processed_by'],
            'processed_at' => $service_request['processed_at'],
            'created_at' => $service_request['support_created_at'],
            'admin_name' => $service_request['support_admin_name']
        ];
        
        // Filter sensitive information based on user role
        if ($user_role === 'user') {
            unset($service_request['support_request']['admin_reason']);
            unset($service_request['support_request']['processed_by']);
            unset($service_request['support_request']['processed_at']);
            unset($service_request['support_request']['admin_name']);
        }
    }
    
    // Format reject request data if exists
    if ($service_request['reject_id']) {
        $service_request['reject_request'] = [
            'id' => $service_request['reject_id'],
            'reject_reason' => $service_request['reject_reason'],
            'reject_details' => $service_request['reject_details'],
            'status' => $service_request['reject_status'],
            'admin_reason' => $service_request['reject_admin_reason'],
            'processed_by' => $service_request['reject_processed_by'],
            'processed_at' => $service_request['reject_processed_at'],
            'created_at' => $service_request['reject_created_at'],
            'admin_name' => $service_request['reject_admin_name']
        ];
        
        // Filter sensitive information based on user role
        if ($user_role === 'user') {
            unset($service_request['reject_request']['admin_reason']);
            unset($service_request['reject_request']['processed_by']);
            unset($service_request['reject_request']['processed_at']);
            unset($service_request['reject_request']['admin_name']);
        }
    }
    
    echo "<p><strong>Filtered Service Request Data (for role: $user_role):</strong></p>";
    echo "<pre>" . print_r($service_request, true) . "</pre>";
} else {
    echo "<p>No service request found with ID: $test_service_id</p>";
}

echo "<h3>Permission System Summary</h3>";
echo "<ul>";
echo "<li><strong>Regular Users (role: user):</strong> Cannot see admin_reason, processed_by, processed_at, admin_name fields</li>";
echo "<li><strong>Staff Users (role: staff):</strong> Can see all admin decision information</li>";
echo "<li><strong>Admin Users (role: admin):</strong> Can see all admin decision information</li>";
echo "</ul>";

echo "<p><a href='index.html'>Back to Application</a></p>";
?>
