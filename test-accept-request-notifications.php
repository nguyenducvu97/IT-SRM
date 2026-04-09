<?php
echo "<h2>Test Accept Request Notifications</h2>";

// Test the accept_request notification logic
echo "<h3>Testing Accept Request Notifications Flow</h3>";

echo "<div style='background-color: #e8f5e8; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
echo "<h4>Fixed Issues:</h4>";
echo "<ul>";
echo "<li>&#10004; <strong>Original Problem:</strong> accept_request action had commented out notification code</li>";
echo "<li>&#10004; <strong>Missing Integration:</strong> ServiceRequestNotificationHelper was not being used</li>";
echo "<li>&#10004; <strong>Wrong Logic:</strong> Code was trying to use old NotificationHelper instead of ServiceRequestNotificationHelper</li>";
echo "</ul>";
echo "</div>";

echo "<h3>What Happens When Staff Accepts Request (Open => In Progress)</h3>";

echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%'>";
echo "<tr style='background-color: #f0f0f0;'>
        <th>Step</th>
        <th>Who Gets Notified</th>
        <th>Notification Function</th>
        <th>Message Content</th>
      </tr>";

echo "<tr>
        <td>1</td>
        <td><strong>Nguyên dùng (Requester)</strong></td>
        <td><code>notifyUserRequestInProgress()</code></td>
        <td>'Yêu câu #{$request_id} cua ban dã duoc nhân viên IT tiêp nhân và dang xuly. Nhân viên phu trách: {$assignedStaffName}'</td>
      </tr>";

echo "<tr>
        <td>2</td>
        <td><strong>Admin</strong></td>
        <td><code>notifyAdminStatusChange()</code></td>
        <td>'Nhân viên {$staffName} dã thay dôi trang thái yêu câu #{$request_id} - {$requestTitle} tu 'open' thành 'in_progress''</td>
      </tr>";

echo "<tr>
        <td>3</td>
        <td><strong>Staff khác</strong></td>
        <td><code>notifyStaffAdminApproved()</code></td>
        <td>'Admin dã phê duyêt yêu câu #{$request_id} - {$requestTitle} bôi {$adminName}. Vui lòng bât dâu thuc hiên k thuât.'</td>
      </tr>";

echo "</table>";

echo "<h3>Code Changes Made</h3>";
echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px;'>";
echo "<h4>Before (BROKEN):</h4>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 3px;'>";
echo "// Notify admins about assignment
try {
    // require_once __DIR__ . '/../lib/NotificationHelper.php';
    // $notificationHelper = new NotificationHelper($db);
    
    $title = \"Yêu câu #\" . $request_id . \" dã duoc nhên\";
    $message = \"Yêu câu #\" . $request_id . \" dã duoc nhên bôi \" . ($request_data['assigned_name'] ?? 'Staff member');
    
    // Get all admin users
    $admin_stmt = $db->prepare(\"SELECT id FROM users WHERE role = 'admin'\");
    $admin_stmt->execute();
    $admins = $admin_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($admins)) {
        foreach ($admins as $admin_id) {
            // $notificationHelper->createNotification($admin_id, $title, $message, 'info', $request_id, 'request', true);
        }
    }
} catch (Exception \$e) {
    error_log(\"Failed to notify admins about assignment: \" . \$e->getMessage());
}";
echo "</pre>";

echo "<h4>After (FIXED):</h4>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 3px;'>";
echo "// Send notifications using ServiceRequestNotificationHelper
try {
    require_once __DIR__ . '/../lib/ServiceRequestNotificationHelper.php';
    \$notificationHelper = new ServiceRequestNotificationHelper();
    
    // 1. Notify user that request is in progress
    \$notificationHelper->notifyUserRequestInProgress(
        \$request_id, 
        \$request_data['user_id'], 
        \$request_data['assigned_name']
    );
    
    // 2. Notify admins about assignment
    \$notificationHelper->notifyAdminStatusChange(
        \$request_id, 
        'open', 
        'in_progress', 
        \$request_data['assigned_name'], 
        \$request_data['title']
    );
    
    // 3. Notify other staff (excluding the assigned staff)
    \$notificationHelper->notifyStaffAdminApproved(
        \$request_id, 
        \$request_data['title'], 
        \$request_data['assigned_name']
    );
    
    error_log(\"Notifications sent for request #\$request_id acceptance\");
} catch (Exception \$e) {
    error_log(\"Failed to send notifications for request #\$request_id: \" . \$e->getMessage());
}";
echo "</pre>";
echo "</div>";

echo "<h3>Test Steps</h3>";
echo "<ol>";
echo "<li>Login as <strong>Staff</strong> account</li>";
echo "<li>Find a request with status 'Open'</li>";
echo "<li>Click 'Nhân yêu câu' (Accept Request)</li>";
echo "<li>Check notifications for:</li>";
echo "<ul>";
echo "<li><strong>Requester:</strong> Should receive 'Yêu câu dang duoc xuly' notification</li>";
echo "<li><strong>Admin:</strong> Should receive 'Thay dôi trang thái yêu câu' notification</li>";
echo "<li><strong>Other Staff:</strong> Should receive 'Yêu câu duoc Admin phê duyêt' notification</li>";
echo "</ul>";
echo "<li>Check error logs for: 'Notifications sent for request #X acceptance'</li>";
echo "</ol>";

echo "<h3>Expected Results</h3>";
echo "<div style='background-color: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<h4 style='color: #155724;'>Success Indicators:</h4>";
echo "<ul>";
echo "<li>&#10004; Request status changes from 'Open' to 'In Progress'</li>";
echo "<li>&#10004; Requester gets notification about staff assignment</li>";
echo "<li>&#10004; Admin gets notification about status change</li>";
echo "<li>&#10004; Other staff get notification about admin approval</li>";
echo "<li>&#10004; Email notification sent to requester</li>";
echo "<li>&#10004; Log entry: 'Notifications sent for request #X acceptance'</li>";
echo "</ul>";
echo "</div>";

echo "<h3>Troubleshooting</h3>";
echo "<div style='background-color: #f8d7da; padding: 15px; border-radius: 5px;'>";
echo "<h4 style='color: #721c24;'>If Notifications Don't Work:</h4>";
echo "<ul>";
echo "<li>Check if ServiceRequestNotificationHelper.php exists and is accessible</li>";
echo "<li>Verify database connection in the notification helper</li>";
echo "<li>Check if notification tables exist and are properly structured</li>";
echo "<li>Look for error logs: 'Failed to send notifications for request #X'</li>";
echo "<li>Verify user roles and permissions in the database</li>";
echo "</ul>";
echo "</div>";

echo "<p><strong>Note:</strong> The fix ensures that when staff accepts a request, ALL relevant parties are notified according to the requirements.</p>";
?>
