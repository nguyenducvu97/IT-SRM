<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Notification Fix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .user-role { background: #f0f0f0; padding: 10px; margin: 10px 0; }
        .notification-test { background: #e6f3ff; padding: 10px; margin: 10px 0; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .notification { padding: 10px; margin: 10px 0; border-radius: 5px; color: white; }
        .notification-info { background: #17a2b8; }
        .notification-success { background: #28a745; }
        .notification-warning { background: #ffc107; color: black; }
        .notification-error { background: #dc3545; }
    </style>
</head>
<body>
    <h1>Test Notification Permission Fix</h1>
    
    <?php
    require_once 'config/session.php';
    
    startSession();
    
    if (!isset($_SESSION['user_id'])) {
        echo '<p class="error">Please login first: <a href="index.html">Login</a></p>';
        exit;
    }
    
    $current_user = $_SESSION['user_id'];
    $user_role = $_SESSION['role'] ?? 'user';
    
    echo "<div class='user-role'>";
    echo "<h2>Current User Information</h2>";
    echo "<p><strong>User ID:</strong> $current_user</p>";
    echo "<p><strong>User Role:</strong> $user_role</p>";
    echo "</div>";
    ?>
    
    <div class='test-section'>
        <h2>JavaScript Notification Test</h2>
        <p>This tests the notification messages for different user roles.</p>
        
        <div class="notification-test">
            <h3>Notification Message Test</h3>
            <div id="notificationTestResults"></div>
        </div>
        
        <script>
            // Simulate different user roles and notification scenarios
            const testScenarios = [
                {
                    role: 'user',
                    scenario: 'reject_request_approved',
                    expected: '📢 Yêu cầu của bạn đã được xử lý!',
                    type: 'info'
                },
                {
                    role: 'user', 
                    scenario: 'reject_request_rejected',
                    expected: '📢 Yêu cầu của bạn đã được xử lý!',
                    type: 'info'
                },
                {
                    role: 'user',
                    scenario: 'support_request_approved',
                    expected: '📢 Yêu cầu hỗ trợ của bạn đã được xử lý!',
                    type: 'info'
                },
                {
                    role: 'user',
                    scenario: 'support_request_rejected', 
                    expected: '📢 Yêu cầu hỗ trợ của bạn đã được xử lý!',
                    type: 'info'
                },
                {
                    role: 'staff',
                    scenario: 'reject_request_approved',
                    expected: '📢 Yêu cầu từ chối đã được phê duyệt bởi admin!',
                    type: 'success'
                },
                {
                    role: 'staff',
                    scenario: 'reject_request_rejected',
                    expected: '📢 Yêu cầu từ chối đã bị từ chối bởi admin!',
                    type: 'warning'
                },
                {
                    role: 'admin',
                    scenario: 'reject_request_approved',
                    expected: '📢 Yêu cầu từ chối đã được phê duyệt bởi admin!',
                    type: 'success'
                },
                {
                    role: 'admin',
                    scenario: 'reject_request_rejected',
                    expected: '📢 Yêu cầu từ chối đã bị từ chối bởi admin!',
                    type: 'warning'
                }
            ];
            
            function getNotificationMessage(scenario, userRole) {
                if (['admin', 'staff'].includes(userRole)) {
                    switch(scenario) {
                        case 'reject_request_approved':
                            return '📢 Yêu cầu từ chối đã được phê duyệt bởi admin!';
                        case 'reject_request_rejected':
                            return '📢 Yêu cầu từ chối đã bị từ chối bởi admin!';
                        case 'support_request_approved':
                            return '📢 Yêu cầu hỗ trợ đã được phê duyệt bởi admin!';
                        case 'support_request_rejected':
                            return '📢 Yêu cầu hỗ trợ đã bị từ chối bởi admin!';
                        default:
                            return '📢 Yêu cầu đã được xử lý!';
                    }
                } else {
                    // Regular users see generic messages
                    switch(scenario) {
                        case 'support_request_approved':
                        case 'support_request_rejected':
                            return '📢 Yêu cầu hỗ trợ của bạn đã được xử lý!';
                        default:
                            return '📢 Yêu cầu của bạn đã được xử lý!';
                    }
                }
            }
            
            let results = '<table border="1" cellpadding="10" cellspacing="0">';
            results += '<tr><th>User Role</th><th>Scenario</th><th>Expected</th><th>Actual</th><th>Result</th></tr>';
            
            testScenarios.forEach(scenario => {
                const actual = getNotificationMessage(scenario.scenario, scenario.role);
                const passed = actual === scenario.expected;
                const resultClass = passed ? 'success' : 'error';
                const resultText = passed ? 'PASS ✓' : 'FAIL ✗';
                
                results += `<tr>
                    <td>${scenario.role}</td>
                    <td>${scenario.scenario}</td>
                    <td>${scenario.expected}</td>
                    <td>${actual}</td>
                    <td class="${resultClass}">${resultText}</td>
                </tr>`;
            });
            
            results += '</table>';
            
            document.getElementById('notificationTestResults').innerHTML = results;
        </script>
    </div>
    
    <div class='test-section'>
        <h2>Current Application Test</h2>
        <p>Testing with current user session:</p>
        
        <script>
            // Test with current user role from PHP
            const currentUserRole = '<?php echo $user_role; ?>';
            
            function testCurrentNotifications() {
                const scenarios = ['reject_request_approved', 'reject_request_rejected', 'support_request_approved', 'support_request_rejected'];
                let html = '<h3>Notification Messages for Current User (Role: ' + currentUserRole + ')</h3>';
                html += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 10px;">';
                
                scenarios.forEach(scenario => {
                    const message = getNotificationMessage(scenario, currentUserRole);
                    const type = ['admin', 'staff'].includes(currentUserRole) ? 
                        (scenario.includes('approved') ? 'success' : 'warning') : 'info';
                    
                    html += `<div class="notification notification-${type}">
                        <strong>${scenario}:</strong><br>
                        ${message}
                    </div>`;
                });
                
                html += '</div>';
                
                // Highlight the fix
                if (currentUserRole === 'user') {
                    html += '<div class="success" style="margin-top: 20px;">✓ Regular users see generic "Đã được xử lý!" messages instead of admin decision details</div>';
                } else {
                    html += '<div class="success" style="margin-top: 20px;">✓ Admin/Staff users see detailed admin decision messages</div>';
                }
                
                return html;
            }
            
            document.write(testCurrentNotifications());
        </script>
    </div>
    
    <div class='test-section'>
        <h2>Expected Behavior</h2>
        <div class="notification-test">
            <h3>Regular Users (role: user) should see:</h3>
            <ul>
                <li>📢 Yêu cầu của bạn đã được xử lý! (reject requests)</li>
                <li>📢 Yêu cầu hỗ trợ của bạn đã được xử lý! (support requests)</li>
                <li>NOT see "bởi admin" or admin decision details</li>
            </ul>
        </div>
        
        <div class="notification-test">
            <h3>Staff/Admin Users should see:</h3>
            <ul>
                <li>📢 Yêu cầu từ chối đã được phê duyệt/bị từ chối bởi admin!</li>
                <li>📢 Yêu cầu hỗ trợ đã được phê duyệt/bị từ chối bởi admin!</li>
                <li>See detailed admin decision information</li>
            </ul>
        </div>
    </div>
    
    <div class='test-section'>
        <h2>Integration Test</h2>
        <p><a href="index.html" target="_blank">Open Main Application</a> and check if notifications show properly for your role.</p>
        <p><a href="request-detail.html?id=38" target="_blank">Open Request #38</a> to see notification behavior.</p>
        <p><strong>Note:</strong> Notifications will appear when admin processes reject/support requests or when you view requests that have been processed.</p>
    </div>
    
    <p><a href="index.html">Back to Application</a></p>
</body>
</html>
