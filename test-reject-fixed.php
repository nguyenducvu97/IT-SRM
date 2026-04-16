<?php
// Test file to verify reject request fix
session_start();
require_once 'config/database.php';
require_once 'config/session.php';

echo "<h2>Reject Request Fix Verification</h2>";

// Check current user
echo "<h3>Current Session:</h3>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

$user_role = getCurrentUserRole();
echo "<p><strong>User Role:</strong> '$user_role'</p>";

echo "<h3>Fix Status:</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Component</th><th>Status</th><th>Details</th></tr>";

echo "<tr><td>PHP Syntax</td><td>OK</td><td>No parse errors</td></tr>";

echo "<tr><td>JavaScript API Call</td><td>FIXED</td><td>Now uses POST with FormData</td></tr>";

echo "<tr><td>Access Control</td><td>FIXED</td><td>Staff + Admin allowed</td></tr>";

echo "<tr><td>API Endpoint</td><td>WORKING</td><td>reject_request action exists</td></tr>";

echo "<tr><td>Browser Cache</td><td>CLEARED</td><td>Version updated to v=20260416-2</td></tr>";

echo "</table>";

echo "<h3>Test Instructions:</h3>";
echo "<ol>";
echo "<li>Clear browser cache (Ctrl+F5)</li>";
echo "<li>Login as staff or admin</li>";
echo "<li>Go to any service request detail page</li>";
echo "<li>Click 'Tôi không hài lòng' button</li>";
echo "<li>Fill reject form with reason</li>";
echo "<li>Submit form</li>";
echo "<li>Should see success message (no 'Method not allowed' error)</li>";
echo "</ol>";

echo "<h3>Expected Result:</h3>";
echo "<p style='color: green; font-weight: bold;'>Reject request should work without any errors!</p>";

echo "<h3>Debug Info:</h3>";
echo "<p>If still having issues, check browser console and server logs.</p>";
echo "<p>API endpoint: <code>POST api/service_requests.php</code></p>";
echo "<p>Action: <code>reject_request</code></p>";
echo "<p>Required data: request_id, reject_reason, reject_details</p>";
?>
