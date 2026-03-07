<?php
// Test UTF-8 encoding fix for Vietnamese characters
echo "<h2>🔧 Testing UTF-8 Encoding Fix for Vietnamese</h2>";

echo "<h3>🚨 Problem:</h3>";
echo "<p><strong>Original issue:</strong> 윍 Y챗u c梳쬾 d沼땉h v沼 m沼쌻 #25</p>";
echo "<p><strong>Cause:</strong> Improper UTF-8 encoding in email headers and body</p>";

echo "<hr>";

echo "<h3>🔧 Fix Applied:</h3>";
echo "<ul>";
echo "<li>✅ <strong>Subject encoding:</strong> Base64 encoded with UTF-8</li>";
echo "<li>✅ <strong>Body encoding:</strong> Base64 encoded with UTF-8</li>";
echo "<li>✅ <strong>Headers:</strong> Proper Content-Transfer-Encoding</li>";
echo "<li>✅ <strong>Charset:</strong> UTF-8 specified</li>";
echo "</ul>";

echo "<hr>";

require_once 'lib/PHPMailerEmailHelper.php';

echo "<h3>🧪 Testing Vietnamese Email:</h3>";

try {
    $emailHelper = new PHPMailerEmailHelper();
    
    // Test with Vietnamese characters
    $test_subject = "🔔 Yêu cầu dịch vụ mới #TEST-UTF8-" . time();
    $test_body = "
    <h2>📋 Yêu cầu dịch vụ mới</h2>
    <p><strong>Mã yêu cầu:</strong> #TEST-UTF8-" . time() . "</p>
    <p><strong>Tiêu đề:</strong> Kiểm tra hiển thị tiếng Việt</p>
    <p><strong>Người tạo:</strong> Nguyễn Văn A</p>
    <p><strong>Danh mục:</strong> Phần cứng</p>
    <p><strong>Ưu tiên:</strong> Cao</p>
    <p><strong>Mô tả:</strong> Đây là yêu cầu kiểm tra hiển thị các ký tự tiếng Việt: ă, â, ô, ô, đ, ê, ư, ơ, ờ, â, etc.</p>
    <p><strong>Đặc biệt:</strong> Các ký tự có dấu: á, à,ả,ã,ạ, ấ,ầ,ẩ,ẫ,ậ, ế,ề,ể,ễ,ệ, í, ì,ỉ,ĩ,ị, ó, ò,ỏ,õ,ọ, ố,ồ,ổ,ỗ,ộ, ú,ù,ủ,ũ,ụ, ứ,ừ,ử,ữ,ự, ý,ỳ,ỷ,ỹ,ỵ</p>
    <hr>
    <p>Vui lòng đăng nhập hệ thống để xem chi tiết và xử lý: <a href='http://localhost/it-service-request/'>http://localhost/it-service-request/</a></p>
    <p><em>Hệ thống Yêu cầu Dịch vụ IT</em></p>";
    
    $result = $emailHelper->sendEmail('ndvu@sgitech.com.vn', 'System Administrator', $test_subject, $test_body);
    
    if ($result) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>";
        echo "<h3>✅ Vietnamese Email Sent Successfully!</h3>";
        echo "<p><strong>To:</strong> ndvu@sgitech.com.vn</p>";
        echo "<p><strong>Subject:</strong> $test_subject</p>";
        echo "<p><strong>Check your inbox!</strong></p>";
        echo "<hr>";
        echo "<h4>🔍 What to Check:</h4>";
        echo "<ul>";
        echo "<li>Subject line should display correctly: '🔔 Yêu cầu dịch vụ mới'</li>";
        echo "<li>Body should show all Vietnamese characters properly</li>";
        echo "<li>No more garbled characters like: 윍 Y챗u c梳쬾 d沼땉h v沼</li>";
        echo "</ul>";
        echo "</div>";
        
        // Test new request notification with Vietnamese
        echo "<h3>🧪 Testing New Request Notification (Vietnamese):</h3>";
        
        $test_request_data = [
            'id' => 'UTF8-' . time(),
            'title' => 'Yêu cầu kiểm tra tiếng Việt',
            'requester_name' => 'Nguyễn Văn Test',
            'category' => 'Phần cứng',
            'priority' => 'Cao',
            'description' => 'Đây là mô tả yêu cầu bằng tiếng Việt với các ký tự đặc biệt: ă, â, ô, đ, ê, ư, ơ, và các dấu câu.'
        ];
        
        $notification_result = $emailHelper->sendNewRequestNotification($test_request_data);
        
        if ($notification_result) {
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>";
            echo "<h4>✅ Vietnamese Notification Sent!</h4>";
            echo "<p><strong>Request ID:</strong> {$test_request_data['id']}</p>";
            echo "<p><strong>Check admin inbox for Vietnamese notification!</strong></p>";
            echo "</div>";
        } else {
            echo "<p style='color: red;'>❌ Vietnamese notification failed</p>";
        }
        
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
        echo "<h3>❌ Vietnamese Email Failed</h3>";
        echo "<p>The encoding fix didn't work. Need further investigation.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ Exception: " . $e->getMessage() . "</h3>";
    echo "</div>";
}

echo "<hr>";

echo "<h3>📋 Encoding Details:</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 12px;'>";
echo "<strong>Subject Encoding:</strong><br>";
echo "Original: 🔔 Yêu cầu dịch vụ mới #TEST<br>";
echo "Encoded: =?UTF-8?B?" . base64_encode("🔔 Yêu cầu dịch vụ mới #TEST") . "?=<br><br>";
echo "<strong>Body Encoding:</strong><br>";
echo "Method: base64_encode() + chunk_split()<br>";
echo "Transfer-Encoding: base64<br>";
echo "Charset: UTF-8<br>";
echo "</div>";

echo "<hr>";

echo "<h3>🎯 Expected Result:</h3>";
echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>";
echo "<h4>✅ Should Display As:</h4>";
echo "<p><strong>Subject:</strong> 🔔 Yêu cầu dịch vụ mới #TEST-UTF8-[timestamp]</p>";
echo "<p><strong>Body:</strong> All Vietnamese characters displayed correctly</p>";
echo "<p><strong>No more:</strong> 윍 Y챗u c梳쬾 d沼땉h v沼 m沼쌻</p>";
echo "</div>";

echo "<hr>";

echo "<h3>🔍 What to Check:</h3>";
echo "<ol>";
echo "<li><strong>Check email subject:</strong> Should show '🔔 Yêu cầu dịch vụ mới' correctly</li>";
echo "<li><strong>Check email body:</strong> All Vietnamese characters should display properly</li>";
echo "<li><strong>Compare with old emails:</strong> New emails should not have garbled characters</li>";
echo "<li><strong>If still broken:</strong> May need to check email client encoding settings</li>";
echo "</ol>";

echo "<hr>";
echo "<p><strong>🎉 UTF-8 encoding fix applied!</strong></p>";
echo "<p><a href='javascript:history.back()'>← Back</a></p>";
?>
