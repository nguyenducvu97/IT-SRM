<?php
// Test file to identify and provide solution for reject request "Method not allowed" error

session_start();
require_once 'config/database.php';
require_once 'config/session.php';

echo "<h2>Reject Request Fix Analysis</h2>";

// Check current user role
echo "<h3>Current Session:</h3>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

$user_role = getCurrentUserRole();
echo "<p><strong>User Role:</strong> '$user_role'</p>";

// Test the exact issue
echo "<h3>Issue Analysis:</h3>";
echo "<p>The 'Method not allowed' error in reject request functionality is caused by:</p>";
echo "<ol>";
echo "<li><strong>JavaScript Bug:</strong> handleRejectSubmit was calling wrong API endpoint</li>";
echo "<li><strong>Access Control:</strong> Only staff allowed, but admin should also be allowed</li>";
echo "</ol>";

echo "<h3>Solution Applied:</h3>";
echo "<h4>1. JavaScript Fix (COMPLETED):</h4>";
echo "<p>Fixed in assets/js/request-detail.js line 19495:</p>";
echo "<code>";
echo "// BEFORE (WRONG):<br/>";
echo "const response = await this.apiCall('api/service_requests.php?action=accept_request&request_id=' + requestId, {<br/>";
echo "    method: 'GET',<br/>";
echo "    credentials: 'include'<br/>";
echo "});<br/><br/>";
echo "// AFTER (CORRECT):<br/>";
echo "const response = await this.apiCall('api/service_requests.php', {<br/>";
echo "    method: 'POST',<br/>";
echo "    body: apiFormData,<br/>";
echo "    credentials: 'include'<br/>";
echo "});";
echo "</code>";

echo "<h4>2. Access Control Fix (NEEDS MANUAL EDIT):</h4>";
echo "<p>In api/service_requests.php around line 9165, change:</p>";
echo "<code>";
echo "// BEFORE:<br/>";
echo "if (\$user_role != 'staff') {<br/>";
echo "    serviceJsonResponse(false, \"Access denied\");<br/>";
echo "}<br/><br/>";
echo "// AFTER:<br/>";
echo "if (\$user_role != 'staff' && \$user_role != 'admin') {<br/>";
echo "    serviceJsonResponse(false, \"Access denied - Only staff and admin can reject requests\");<br/>";
echo "}";
echo "</code>";

echo "<h3>Manual Fix Instructions:</h3>";
echo "<ol>";
echo "<li>Open api/service_requests.php</li>";
echo "<li>Search for: <code>if (\$user_role != 'staff')</code></li>";
echo "<li>Replace with: <code>if (\$user_role != 'staff' && \$user_role != 'admin')</code></li>";
echo "<li>Update error message to be more descriptive</li>";
echo "</ol>";

echo "<h3>Testing:</h3>";
echo "<p>After applying fixes:</p>";
echo "<ul>";
echo "<li>Clear browser cache (Ctrl+F5)</li>";
echo "<li>Login as staff or admin</li>";
echo "<li>Go to any service request detail page</li>";
echo "<li>Click 'Tôi không hài lòng' button</li>";
echo "<li>Fill reject form and submit</li>";
echo "<li>Should work without 'Method not allowed' error</li>";
echo "</ul>";

echo "<h3>Debug Information Added:</h3>";
echo "<p>Added comprehensive debug logging in api/service_requests.php:</p>";
echo "<ul>";
echo "<li>Session data logging</li>";
echo "<li>User role logging</li>";
echo "<li>POST data logging</li>";
echo "<li>FILES data logging</li>";
echo "</ul>";

echo "<p><strong>Status:</strong> JavaScript fix applied, access control fix needs manual edit.</p>";
?>
