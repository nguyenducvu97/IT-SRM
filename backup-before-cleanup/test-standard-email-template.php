<?php
echo "<h1>Test Standard Email Template</h1>";

echo "<h2>Objective:</h2>";
echo "<p>Apply the beautiful email template from 'Yêu câu dich vu moi #id' to ALL other emails with custom content only.</p>";

echo "<h2>Template Features:</h2>";
echo "<ul>";
echo "<li>Beautiful gradient header</li>";
echo "<li>Professional layout with consistent styling</li>";
echo "<li>Proper Vietnamese text</li>";
echo "<li>Call-to-action button</li>";
echo "<li>Professional footer</li>";
echo "</ul>";

echo "<h2>Test All Email Types:</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/database.php';
    require_once 'lib/EmailHelper.php';
    
    $db = (new Database())->getConnection();
    $emailHelper = new EmailHelper();
    
    // Test 1: New Request Email (Original - should remain unchanged)
    if (isset($_POST['test_new_request'])) {
        echo "<h3>Test 1: New Request Email (Original Template)</h3>";
        
        $test_data = [
            'id' => 195,
            'title' => 'Test New Request Template',
            'requester_name' => 'Test User',
            'category' => 'Hardware',
            'priority' => 'high',
            'description' => 'This is a test new request with the original beautiful template.'
        ];
        
        $result = $emailHelper->sendNewRequestNotification($test_data);
        echo "<p><strong>Result:</strong> " . ($result ? "SUCCESS" : "FAILED") . "</p>";
        echo "<p><strong>Template:</strong> Original beautiful template</p>";
    }
    
    // Test 2: Staff Accept Request - Requester Email
    if (isset($_POST['test_accept_requester'])) {
        echo "<h3>Test 2: Staff Accept Request - Requester Email</h3>";
        
        $subject = "Yêu câu #195 - Tràng thái thay thành 'in_progress'";
        $customContent = '<h2 style="color: #333; margin-bottom: 20px;">Yêu câu duoc nhân viên IT nhâp</h2>
        
                        <div style="background: #f8f9fa; border-left: 4px solid #667eea; padding: 20px; margin: 20px 0;">
                            <div style="margin-bottom: 12px;">
                                <span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Mã yêu câu:</span>
                                <span style="color: #212529;"><strong>#195</strong></span>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Tiêu dê:</span>
                                <span style="color: #212529;">Test Staff Accept Request</span>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Nguyên nhân:</span>
                                <span style="color: #212529;">Test User</span>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Nhân viên IT:</span>
                                <span style="color: #212529;">Test Staff</span>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Trang thái:</span>
                                <span style="color: #212529;"><span style="padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase; background: #e8f5e8; color: #28a745;">in_progress</span></span>
                            </div>
                        </div>
                        
                        <p style="color: #666; line-height: 1.6;">Yêu câu cua ban duoc nhân viên IT nhâp và dang trong quá trình x lý. Chúng tôi sê liên hê vôi ban nêu có thông tin thêm.</p>';
        
        $result = $emailHelper->sendStandardEmail(
            'test@example.com',
            'Test User',
            $subject,
            $customContent,
            195
        );
        
        echo "<p><strong>Result:</strong> " . ($result ? "SUCCESS" : "FAILED") . "</p>";
        echo "<p><strong>Template:</strong> Standard template with custom content</p>";
    }
    
    // Test 3: Staff Accept Request - Admin Email
    if (isset($_POST['test_accept_admin'])) {
        echo "<h3>Test 3: Staff Accept Request - Admin Email</h3>";
        
        $subject = "Staff Accepted Request #195";
        $customContent = '<h2 style="color: #333; margin-bottom: 20px;">Nhân viên IT nhâp yêu câu</h2>
        
                        <div style="background: #f8f9fa; border-left: 4px solid #667eea; padding: 20px; margin: 20px 0;">
                            <div style="margin-bottom: 12px;">
                                <span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Mã yêu câu:</span>
                                <span style="color: #212529;"><strong>#195</strong></span>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Tiêu dê:</span>
                                <span style="color: #212529;">Test Staff Accept Request</span>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Nguyên nhân:</span>
                                <span style="color: #212529;">Test User</span>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Nhân viên IT:</span>
                                <span style="color: #212529;">Test Staff</span>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Trang thái:</span>
                                <span style="color: #212529;"><span style="padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase; background: #e8f5e8; color: #28a745;">in_progress</span></span>
                            </div>
                        </div>
                        
                        <p style="color: #666; line-height: 1.6;">Nhân viên IT duoc phân công và bat dau x lý yêu câu này.</p>';
        
        $result = $emailHelper->sendStandardEmail(
            'admin@example.com',
            'Test Admin',
            $subject,
            $customContent,
            195
        );
        
        echo "<p><strong>Result:</strong> " . ($result ? "SUCCESS" : "FAILED") . "</p>";
        echo "<p><strong>Template:</strong> Standard template with custom content</p>";
    }
    
    // Test 4: Custom Email Example
    if (isset($_POST['test_custom'])) {
        echo "<h3>Test 4: Custom Email Example</h3>";
        
        $subject = "Custom Notification #195";
        $customContent = '<h2 style="color: #333; margin-bottom: 20px;">Thông báo tùy chinh</h2>
        
                        <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 20px; margin: 20px 0;">
                            <div style="margin-bottom: 12px;">
                                <span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Loai thông báo:</span>
                                <span style="color: #212529;">Thông báo chung</span>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Mã yêu câu:</span>
                                <span style="color: #212529;"><strong>#195</strong></span>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Nôi dung:</span>
                                <span style="color: #212529;">Day là ví du vê email tùy chinh su dung standard template</span>
                            </div>
                        </div>
                        
                        <p style="color: #666; line-height: 1.6;">Bân có the su dung standard template cho bat ky loai email nào chi vi thay dôi nôi dung.</p>';
        
        $result = $emailHelper->sendStandardEmail(
            'user@example.com',
            'Test User',
            $subject,
            $customContent,
            195
        );
        
        echo "<p><strong>Result:</strong> " . ($result ? "SUCCESS" : "FAILED") . "</p>";
        echo "<p><strong>Template:</strong> Standard template with custom content</p>";
    }
    
    echo "<hr>";
}

echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;'>";

echo "<div style='padding: 20px; background-color: #f8f9fa; border-radius: 5px;'>";
echo "<h3>Test 1: New Request Email</h3>";
echo "<p>Original beautiful template (unchanged)</p>";
echo "<form method='POST'>";
echo "<input type='hidden' name='test_new_request' value='1'>";
echo "<button type='submit' style='background-color: #007bff; color: white; padding: 10px 20px; border: none; cursor: pointer; border-radius: 4px; width: 100%;'>
            Test New Request Template
        </button>";
echo "</form>";
echo "</div>";

echo "<div style='padding: 20px; background-color: #f8f9fa; border-radius: 5px;'>";
echo "<h3>Test 2: Staff Accept - Requester</h3>";
echo "<p>Standard template with custom content</p>";
echo "<form method='POST'>";
echo "<input type='hidden' name='test_accept_requester' value='1'>";
echo "<button type='submit' style='background-color: #28a745; color: white; padding: 10px 20px; border: none; cursor: pointer; border-radius: 4px; width: 100%;'>
            Test Staff Accept (Requester)
        </button>";
echo "</form>";
echo "</div>";

echo "<div style='padding: 20px; background-color: #f8f9fa; border-radius: 5px;'>";
echo "<h3>Test 3: Staff Accept - Admin</h3>";
echo "<p>Standard template with custom content</p>";
echo "<form method='POST'>";
echo "<input type='hidden' name='test_accept_admin' value='1'>";
echo "<button type='submit' style='background-color: #ffc107; color: black; padding: 10px 20px; border: none; cursor: pointer; border-radius: 4px; width: 100%;'>
            Test Staff Accept (Admin)
        </button>";
echo "</form>";
echo "</div>";

echo "<div style='padding: 20px; background-color: #f8f9fa; border-radius: 5px;'>";
echo "<h3>Test 4: Custom Email</h3>";
echo "<p>Example of custom content usage</p>";
echo "<form method='POST'>";
echo "<input type='hidden' name='test_custom' value='1'>";
echo "<button type='submit' style='background-color: #6f42c1; color: white; padding: 10px 20px; border: none; cursor: pointer; border-radius: 4px; width: 100%;'>
            Test Custom Email
        </button>";
echo "</form>";
echo "</div>";

echo "</div>";

echo "<h2>Template Comparison:</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>Email Type</th><th>Template Used</th><th>Customization</th><th>Result</th>";
echo "</tr>";
echo "<tr>";
echo "<td><strong>New Request</strong></td>";
echo "<td>Original sendNewRequestNotification()</td>";
echo "<td>None (fixed content)</td>";
echo "<td style='color: green;'>Beautiful template</td>";
echo "</tr>";
echo "<tr>";
echo "<td><strong>Staff Accept - Requester</strong></td>";
echo "<td>sendStandardEmail()</td>";
echo "<td>Custom HTML content</td>";
echo "<td style='color: green;'>Beautiful template + custom</td>";
echo "</tr>";
echo "<tr>";
echo "<td><strong>Staff Accept - Admin</strong></td>";
echo "<td>sendStandardEmail()</td>";
echo "<td>Custom HTML content</td>";
echo "<td style='color: green;'>Beautiful template + custom</td>";
echo "</tr>";
echo "<tr>";
echo "<td><strong>Any Future Email</strong></td>";
echo "<td>sendStandardEmail()</td>";
echo "<td>Custom HTML content</td>";
echo "<td style='color: green;'>Beautiful template + custom</td>";
echo "</tr>";
echo "</table>";

echo "<h2>How to Use sendStandardEmail():</h2>";
echo "<div style='background-color: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; font-family: monospace;'>";
echo "<pre>";
echo "// Usage Example
\$emailHelper = new EmailHelper();
\$customContent = '&lt;h2&gt;Your Custom Title&lt;/h2&gt;
                   &lt;p&gt;Your custom content here&lt;/p&gt;';

\$result = \$emailHelper->sendStandardEmail(
    'recipient@example.com',
    'Recipient Name',
    'Email Subject',
    \$customContent,
    \$requestId  // Optional: for link
);";
echo "</pre>";
echo "</div>";

echo "<div style='background-color: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #28a745;'>";
echo "<h3>Implementation Summary:</h3>";
echo "<ul>";
echo "<li>Added sendStandardEmail() method to EmailHelper</li>";
echo "<li>Updated staff accept request emails (requester & admin)</li>";
echo "<li>All emails now use the same beautiful template</li>";
echo "<li>Only content changes, template remains consistent</li>";
echo "<li>Easy to extend for future email types</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><a href='index.html'>Back to Main Application</a></p>";
echo "<p><a href='test-formdata-email-fix.php'>Test FormData Fix</a></p>";
echo "<p><a href='test-comprehensive-email-fix.php'>Comprehensive Email Test</a></p>";
?>
