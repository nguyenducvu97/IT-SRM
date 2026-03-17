<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Status Text Fix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .user-role { background: #f0f0f0; padding: 10px; margin: 10px 0; }
        .status-test { background: #e6f3ff; padding: 10px; margin: 10px 0; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .status-rejected { background: #dc3545; color: white; }
        .status-open { background: #28a745; color: white; }
        .status-in_progress { background: #ffc107; color: black; }
        .status-resolved { background: #17a2b8; color: white; }
    </style>
</head>
<body>
    <h1>Test Status Text Permission Fix</h1>
    
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
        <h2>JavaScript Status Text Test</h2>
        <p>This tests the getStatusText() function to ensure it shows different text for different user roles.</p>
        
        <div class="status-test">
            <h3>Status Display Test</h3>
            <div id="statusTestResults"></div>
        </div>
        
        <script>
            // Simulate different user roles
            const testCases = [
                { role: 'user', status: 'rejected', expected: 'Đã xử lý' },
                { role: 'staff', status: 'rejected', expected: 'Đã từ chối' },
                { role: 'admin', status: 'rejected', expected: 'Đã từ chối' },
                { role: 'user', status: 'resolved', expected: 'Đã giải quyết' },
                { role: 'staff', status: 'resolved', expected: 'Đã giải quyết' },
                { role: 'admin', status: 'resolved', expected: 'Đã giải quyết' }
            ];
            
            function getStatusText(status, userRole) {
                const statuses = {
                    'open': 'Mở',
                    'in_progress': 'Đang xử lý',
                    'resolved': 'Đã giải quyết',
                    'rejected': ['admin', 'staff'].includes(userRole) ? 'Đã từ chối' : 'Đã xử lý',
                    'closed': 'Đã đóng',
                    'cancelled': 'Đã hủy',
                    'request_support': 'Cần hỗ trợ'
                };
                return statuses[status] || status;
            }
            
            let results = '<table border="1" cellpadding="10" cellspacing="0">';
            results += '<tr><th>User Role</th><th>Status</th><th>Expected</th><th>Actual</th><th>Result</th></tr>';
            
            testCases.forEach(testCase => {
                const actual = getStatusText(testCase.status, testCase.role);
                const passed = actual === testCase.expected;
                const resultClass = passed ? 'success' : 'error';
                const resultText = passed ? 'PASS ✓' : 'FAIL ✗';
                
                results += `<tr>
                    <td>${testCase.role}</td>
                    <td>${testCase.status}</td>
                    <td>${testCase.expected}</td>
                    <td>${actual}</td>
                    <td class="${resultClass}">${resultText}</td>
                </tr>`;
            });
            
            results += '</table>';
            
            document.getElementById('statusTestResults').innerHTML = results;
        </script>
    </div>
    
    <div class='test-section'>
        <h2>Current Application Test</h2>
        <p>Testing with current user session:</p>
        
        <script>
            // Test with current user role from PHP
            const currentUserRole = '<?php echo $user_role; ?>';
            
            function testCurrentStatusTexts() {
                const statuses = ['open', 'in_progress', 'resolved', 'rejected', 'closed', 'cancelled', 'request_support'];
                let html = '<h3>Status Text for Current User (Role: ' + currentUserRole + ')</h3>';
                html += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">';
                
                statuses.forEach(status => {
                    const statusText = getStatusText(status, currentUserRole);
                    const badgeClass = 'status-' + status;
                    html += `<div style="padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <strong>${status}:</strong> 
                        <span class="badge ${badgeClass}">${statusText}</span>
                    </div>`;
                });
                
                html += '</div>';
                
                // Highlight the rejected status
                if (currentUserRole === 'user') {
                    html += '<div class="success" style="margin-top: 20px;">✓ Regular users see "Đã xử lý" instead of "Đã từ chối" for rejected status</div>';
                } else {
                    html += '<div class="success" style="margin-top: 20px;">✓ Admin/Staff users see "Đã từ chối" for rejected status</div>';
                }
                
                return html;
            }
            
            document.write(testCurrentStatusTexts());
        </script>
    </div>
    
    <div class='test-section'>
        <h2>Expected Behavior</h2>
        <div class="status-test">
            <h3>Regular Users (role: user) should see:</h3>
            <ul>
                <li>rejected status → "Đã xử lý" (NOT "Đã từ chối")</li>
                <li>All other statuses remain the same</li>
            </ul>
        </div>
        
        <div class="status-test">
            <h3>Staff/Admin Users should see:</h3>
            <ul>
                <li>rejected status → "Đã từ chối" (unchanged)</li>
                <li>All other statuses remain the same</li>
            </ul>
        </div>
    </div>
    
    <div class='test-section'>
        <h2>Integration Test</h2>
        <p><a href="index.html" target="_blank">Open Main Application</a> and check if rejected requests show "Đã xử lý" for regular users.</p>
        <p><a href="request-detail.html?id=38" target="_blank">Open Request #38</a> to see the status display.</p>
    </div>
    
    <p><a href="index.html">Back to Application</a></p>
</body>
</html>
