<?php
// Test creating a new service request via API
require_once 'config/database.php';
require_once 'config/session.php';

// Start session and set test user
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'testuser';
$_SESSION['role'] = 'user';

// Simulate POST request data
$_POST['title'] = 'Test Request API ' . date('Y-m-d H:i:s');
$_POST['description'] = 'This is a test request created via API to check if email notifications work properly.';
$_POST['category_id'] = 1;
$_POST['priority'] = 'medium';

echo "<h2>🧪 Test Service Request API</h2>";
echo "<p><strong>Title:</strong> " . $_POST['title'] . "</p>";
echo "<p><strong>Description:</strong> " . $_POST['description'] . "</p>";
echo "<p><strong>Category ID:</strong> " . $_POST['category_id'] . "</p>";
echo "<p><strong>Priority:</strong> " . $_POST['priority'] . "</p>";
echo "<hr>";

// Include the service_requests API
include 'api/service_requests.php';

echo "<hr>";
echo "<p><a href='javascript:history.back()'>← Back</a></p>";
?>
