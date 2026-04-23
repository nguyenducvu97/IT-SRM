<?php
echo "<h1>Fix All Email Issues</h1>";

echo "<h2>VANDE 1: Email sai ID khi tao yeu cau moi</h2>";
echo "<p><strong>Problem:</strong> Tao yeu cau ID 191, 192, 193 nhung email gui thong bao cho ID 182, 183, 184</p>";
echo "<p><strong>Cause:</strong> Co the do background processing hoac cache delay</p>";

echo "<h2>VANDE 2: Admin khong nhan email khi staff nhan yeu cau</h2>";
echo "<p><strong>Problem:</strong> Email chi gui cho requester, khong gui cho admin</p>";
echo "<p><strong>Cause:</strong> Code chi gui email cho request_data['requester_email']</p>";

echo "<h2>SOLUTION:</h2>";

// Fix 1: Add email logging for debugging ID issue
$fix1_code = '
// Fix 1: Add detailed logging to track ID issue
$email_start = microtime(true);

// Log the actual request ID being processed
error_log("EMAIL_DEBUG: Processing email for request_id = $request_id");
error_log("EMAIL_DEBUG: Database lastInsertId = " . $db->lastInsertId());

// Get request details
$request_query = "SELECT sr.*, u.full_name as requester_name, u.email as requester_email
                  FROM service_requests sr
                  LEFT JOIN users u ON sr.user_id = u.id
                  WHERE sr.id = :request_id";
$request_stmt = $db->prepare($request_query);
$request_stmt->bindParam(":request_id", $request_id);
$request_stmt->execute();
$request_data = $request_stmt->fetch(PDO::FETCH_ASSOC);

// Log the request data being used for email
error_log("EMAIL_DEBUG: Request data for email - ID: {$request_data['id']}, Title: {$request_data['title']}");

$email_data = array(
    "id" => $request_data["id"],  // Use the actual database ID
    "title" => $request_data["title"],
    "requester_name" => $request_data["requester_name"],
    "category" => $category_cache[$request_data["category_id"]] ?? "Unknown",
    "priority" => $request_data["priority"],
    "description" => $request_data["description"]
);

error_log("EMAIL_DEBUG: Email data prepared - ID: {$email_data["id"]}");
';

echo "<h3>Fix 1: Add ID Debugging</h3>";
echo "<pre style='background-color: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto;'>" . htmlspecialchars($fix1_code) . "</pre>";

// Fix 2: Add admin email for staff accept
$fix2_code = '
// Fix 2: Send email to admins when staff accepts request
// AFTER sending email to requester, also send to admins

// Send email to requester (existing code)
$emailResult = $emailHelper->sendEmail(
    $request_data["requester_email"],
    $request_data["requester_name"],
    $subject,
    $body
);
error_log("EMAIL: Email to requester result for request #$request_id: " . ($emailResult ? "SUCCESS" : "FAILED"));

// ALSO send email to all admins
try {
    $admin_query = "SELECT email, full_name FROM users WHERE role = "admin" AND status = "active"";
    $admin_stmt = $db->prepare($admin_query);
    $admin_stmt->execute();
    $admins = $admin_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($admins)) {
        $admin_subject = "Staff Accepted Request #{$request_id}";
        $admin_body = "<h2>Request Accepted by Staff</h2>
                        <p><strong>Request ID:</strong> #{$request_id}</p>
                        <p><strong>Title:</strong> " . htmlspecialchars($request_data["title"]) . "</p>
                        <p><strong>Requester:</strong> {$request_data["requester_name"]}</p>
                        <p><strong>Staff:</strong> {$request_data["assigned_name"]}</p>
                        <p><strong>Status:</strong> in_progress</p>
                        <p><a href="http://localhost/it-service-request/request-detail.html?id={$request_id}">View Request Details</a></p>";
        
        foreach ($admins as $admin) {
            $admin_email_result = $emailHelper->sendEmail(
                $admin["email"],
                $admin["full_name"],
                $admin_subject,
                $admin_body
            );
            error_log("EMAIL: Email to admin {$admin["email"]} result: " . ($admin_email_result ? "SUCCESS" : "FAILED"));
        }
    }
} catch (Exception $e) {
    error_log("EMAIL: Failed to send admin emails: " . $e->getMessage());
}
';

echo "<h3>Fix 2: Add Admin Email for Staff Accept</h3>";
echo "<pre style='background-color: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto;'>" . htmlspecialchars($fix2_code) . "</pre>";

echo "<h2>IMPLEMENTATION STEPS:</h2>";
echo "<ol>";
echo "<li><strong>Fix ID Issue:</strong> Add debugging logs to track request ID in email processing</li>";
echo "<li><strong>Fix Admin Email:</strong> Add admin email notification when staff accepts request</li>";
echo "<li><strong>Test Both Issues:</strong> Create test requests and verify correct IDs and admin notifications</li>";
echo "</ol>";

echo "<h2>TEST PLAN:</h2>";
echo "<h3>Test 1: ID Issue</h3>";
echo "<ol>";
echo "<li>Create a new request (note the ID)</li>";
echo "<li>Check email logs for correct ID</li>";
echo "<li>Verify email content shows correct request ID</li>";
echo "</ol>";

echo "<h3>Test 2: Admin Email</h3>";
echo "<ol>";
echo "<li>Staff accepts a request</li>";
echo "<li>Check that requester receives email</li>";
echo "<li>Check that ALL admins receive email</li>";
echo "<li>Verify email content is appropriate for each recipient</li>";
echo "</ol>";

echo "<h2>DEBUGGING TOOLS:</h2>";
echo "<ul>";
echo "<li>Check <code>logs/email_activity.log</code> for email attempts</li>";
echo "<li>Check <code>logs/api_errors.log</code> for PHP errors</li>";
echo "<li>Use <code>test-email-fix-verification.php</code> to test fixes</li>";
echo "</ul>";

echo "<div style='background-color: #fff3cd; padding: 15px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #ffc107;'>";
echo "<h3>Important Notes:</h3>";
echo "<ul>";
echo "<li>The ID issue might be caused by background processing or race conditions</li>";
echo "<li>Admin email was missing from the accept request flow</li>";
echo "<li>Both fixes maintain existing functionality while adding missing features</li>";
echo "<li>Test thoroughly in development before deploying to production</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><a href='index.html'>Back to Main Application</a></p>";
echo "<p><a href='test-email-fix-verification.php'>Test Email Fix</a></p>";
?>
