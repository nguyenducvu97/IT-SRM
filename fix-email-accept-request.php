<?php
// Fix for email not being sent when staff accepts request
// This file modifies the accept_request action to ensure emails are sent

echo "<h1>Fix Email Issue - Staff Accept Request</h1>";

// The problem: fastcgi_finish_request() may not work in development
// Solution: Process emails BEFORE sending response, or use proper background processing

echo "<h2>Problem Analysis:</h2>";
echo "<p><strong>Current Issue:</strong> Emails are processed after fastcgi_finish_request(), but this may not work in development environment.</p>";
echo "<p><strong>Solution:</strong> Process emails immediately before sending response, or implement proper background processing.</p>";

echo "<h2>Recommended Fix:</h2>";

// Show the fix code
$fix_code = '
// FIXED VERSION - Process emails BEFORE response
if ($update_stmt->execute()) {
    // Get request details for notifications
    $request_query = "SELECT sr.*, u.full_name as requester_name, u.email as requester_email, 
                             staff.full_name as assigned_name, staff.email as assigned_email, c.name as category_name
                      FROM service_requests sr
                      LEFT JOIN users u ON sr.user_id = u.id
                      LEFT JOIN users staff ON sr.assigned_to = staff.id
                      LEFT JOIN categories c ON sr.category_id = c.id
                      WHERE sr.id = :request_id";
    $request_stmt = $db->prepare($request_query);
    $request_stmt->bindParam(":request_id", $request_id);
    $request_stmt->execute();
    $request_data = $request_stmt->fetch(PDO::FETCH_ASSOC);
    
    // PROCESS NOTIFICATIONS AND EMAILS IMMEDIATELY (before response)
    if ($request_data) {
        try {
            error_log("NOTIFICATIONS: Starting processing for request #$request_id");
            
            require_once __DIR__ . "/../lib/ServiceRequestNotificationHelper.php";
            $notificationHelper = new ServiceRequestNotificationHelper();
            
            // 1. Notify user that request is in progress
            error_log("NOTIFICATIONS: Notifying user for request #$request_id");
            $userNotificationResult = $notificationHelper->notifyUserRequestInProgress(
                $request_id, 
                $request_data["user_id"], 
                $request_data["assigned_name"]
            );
            error_log("NOTIFICATIONS: User notification result: " . ($userNotificationResult ? "SUCCESS" : "FAILED"));
            
            // 2. Notify admins about assignment
            error_log("NOTIFICATIONS: Notifying admins for request #$request_id");
            $adminNotificationResult = $notificationHelper->notifyAdminStatusChange(
                $request_id, 
                "open", 
                "in_progress", 
                $request_data["assigned_name"], 
                $request_data["title"]
            );
            error_log("NOTIFICATIONS: Admin notification result: " . ($adminNotificationResult ? "SUCCESS" : "FAILED"));
            
            // 3. Send email notification to requester
            error_log("EMAIL: Sending email for request #$request_id");
            $emailHelper = new EmailHelper();
            
            $subject = "Yêu câu #{$request_id} - Tràng thái thay thành \'in_progress\'";
            $body = "Chào {$request_data["requester_name"]},\n\n";
            $body .= "Yêu câu #{$request_id} (\'{$request_data["title"]}\') cua ban da duoc nhan boi nhân viên IT.\n\n";
            $body .= "Nhân viên phu trách: {$request_data["assigned_name"]}\n\n";
            $body .= "Trang thái: in_progress\n\n";
            $body .= "Ban có the xem chi tiêt tai: http://localhost/it-service-request/request-detail.html?id={$request_id}\n\n";
            $body .= "Trân tr,\n";
            $body .= "IT Service Request System";
            
            $emailResult = $emailHelper->sendEmail(
                $request_data["requester_email"],
                $request_data["requester_name"],
                $subject,
                $body
            );
            error_log("EMAIL: Email result for request #$request_id: " . ($emailResult ? "SUCCESS" : "FAILED"));
            
        } catch (Exception $e) {
            error_log("NOTIFICATIONS: Critical error in processing: " . $e->getMessage());
        }
    }
    
    // NOW send response (after processing is complete)
    serviceJsonResponse(true, "Request accepted successfully");
    
} else {
    serviceJsonResponse(false, "Failed to accept request");
}
';

echo "<h3>Fixed Code:</h3>";
echo "<pre style='background-color: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto;'>" . htmlspecialchars($fix_code) . "</pre>";

echo "<h2>Implementation Steps:</h2>";
echo "<ol>";
echo "<li>Open <code>api/service_requests.php</code></li>";
echo "<li>Find the <code>accept_request</code> action (around line 7151)</li>";
echo "<li>Replace the current implementation with the fixed code above</li>";
echo "<li>Test the functionality</li>";
echo "</ol>";

echo "<h2>Alternative: Use PHPMailer for Better Email Delivery</h2>";
echo "<p>If PHP mail() is not working, consider using PHPMailer:</p>";

$phpmailer_code = '
// Install PHPMailer: composer require phpmailer/phpmailer
// Then use this instead of PHP mail():

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

public function sendEmailWithPHPMailer($to, $toName, $subject, $body) {
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host       = "gw.sgitech.com.vn";
        $mail->SMTPAuth   = true;
        $mail->Username   = "ndvu@sgitech.com.vn";
        $mail->Password   = "ndvu@123";
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom("ndvu@sgitech.com.vn", "IT Service Request System");
        $mail->addAddress($to, $toName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $e->getMessage());
        return false;
    }
}
';

echo "<pre style='background-color: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto;'>" . htmlspecialchars($phpmailer_code) . "</pre>";

echo "<h2>Quick Test:</h2>";
echo "<p>Before implementing the fix, test with:</p>";
echo "<ul>";
echo "<li><a href='test-email-accept-request.php'>Test Email Function</a> - Check if PHP mail works</li>";
echo "<li><a href='test-full-notification-flow.php'>Test Full Flow</a> - Test complete accept request flow</li>";
echo "</ul>";

echo "<h2>Debug Steps:</h2>";
echo "<ol>";
echo "<li>Check <code>logs/api_errors.log</code> for PHP errors</li>";
echo "<li>Check <code>logs/email_activity.log</code> for email attempts</li>";
echo "<li>Test with a real email address (not test@example.com)</li>";
echo "<li>Check spam/junk folders for test emails</li>";
echo "</ol>";

echo "<div style='background-color: #fff3cd; padding: 15px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #ffc107;'>";
echo "<h3>Important Note:</h3>";
echo "<p>The main issue is likely that <code>fastcgi_finish_request()</code> doesn't work properly in development environments, causing the email processing code to never execute.</p>";
echo "<p>Moving the email processing BEFORE the response will ensure it runs, but may slightly increase response time.</p>";
echo "<p>For production, consider implementing proper background processing with queue system.</p>";
echo "</div>";

echo "<hr>";
echo "<p><a href='index.html'>Back to Main Application</a></p>";
echo "<p><a href='api/service_requests.php'>View API File</a></p>";
?>
