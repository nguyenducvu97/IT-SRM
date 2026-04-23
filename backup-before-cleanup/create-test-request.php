<?php
// Create a new test request for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Create New Test Request</h1>";

try {
    require_once 'config/database.php';
    $db = (new Database())->getConnection();
    
    // Create a new test request
    $insertStmt = $db->prepare("INSERT INTO service_requests (user_id, title, description, category_id, priority, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $result = $insertStmt->execute([
        4, // user_id (ndvu)
        'Test Request for Staff Accept - ' . date('Y-m-d H:i:s'),
        'This is a test request created specifically for testing staff accept functionality with notifications.',
        1, // category_id
        'medium',
        'open'
    ]);
    
    if ($result) {
        $new_request_id = $db->lastInsertId();
        echo "<p style='color: green;'>Created new test request #{$new_request_id}</p>";
        
        // Display request details
        $stmt = $db->prepare("SELECT * FROM service_requests WHERE id = ?");
        $stmt->execute([$new_request_id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h3>New Request Details:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Title</th><th>User ID</th><th>Status</th><th>Created</th></tr>";
        echo "<tr>";
        echo "<td>{$request['id']}</td>";
        echo "<td>" . htmlspecialchars($request['title']) . "</td>";
        echo "<td>{$request['user_id']}</td>";
        echo "<td>{$request['status']}</td>";
        echo "<td>{$request['created_at']}</td>";
        echo "</tr>";
        echo "</table>";
        
        echo "<h3>Test with New Request:</h3>";
        echo "<p><a href='test-accept-minimal.php?request_id={$new_request_id}'>Test Accept Request #{$new_request_id}</a></p>";
        
        // Create a test version that uses the new request ID
        echo "<script>";
        echo "// Update test links to use new request ID";
        echo "document.addEventListener('DOMContentLoaded', function() {";
        echo "  const links = document.querySelectorAll('a[href*=\"test-accept-minimal.php\"]');";
        echo "  links.forEach(link => {";
        echo "    if (!link.href.includes('request_id=')) {";
        echo "      link.href = 'test-accept-minimal.php?request_id={$new_request_id}';";
        echo "    }";
        echo "  });";
        echo "});";
        echo "</script>";
        
    } else {
        echo "<p style='color: red;'>Failed to create test request</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Available Open Requests:</h3>";

// Show all open requests
$stmt = $db->query("SELECT id, title, user_id, status, created_at FROM service_requests WHERE status = 'open' AND (assigned_to IS NULL OR assigned_to = 0) ORDER BY created_at DESC LIMIT 5");
$open_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($open_requests)) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Title</th><th>User ID</th><th>Status</th><th>Created</th><th>Action</th></tr>";
    foreach ($open_requests as $request) {
        echo "<tr>";
        echo "<td>{$request['id']}</td>";
        echo "<td>" . htmlspecialchars(substr($request['title'], 0, 50)) . "...</td>";
        echo "<td>{$request['user_id']}</td>";
        echo "<td>{$request['status']}</td>";
        echo "<td>{$request['created_at']}</td>";
        echo "<td><a href='test-accept-minimal.php?request_id={$request['id']}'>Test Accept</a></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No open requests available for testing</p>";
}

echo "<hr>";
echo "<p><a href='test-accept-minimal.php'>Test Accept Request (Default)</a></p>";
echo "<p><a href='test-notification-creation.php'>Test Notification Creation</a></p>";
echo "<p><a href='check-notifications.php'>Check Notifications Database</a></p>";
?>
