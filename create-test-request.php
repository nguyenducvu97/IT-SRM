<?php
echo "<h2>TẠO REQUEST TEST ĐỂ KIỂM TRA THÔNG BÁO</h2>";

require_once __DIR__ . '/config/database.php';
$db = getDatabaseConnection();

try {
    // Tạo một test request
    $insert_query = "INSERT INTO service_requests (user_id, title, description, category_id, priority, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $db->prepare($insert_query);
    $result = $stmt->execute([
        1, // user_id (ndvu)
        'Test Request for Notifications ' . date('Y-m-d H:i:s'),
        'Đây là request test để kiểm tra chức năng thông báo. Khi staff nhận request này, user và admin phải nhận được thông báo.',
        1, // category_id
        'medium', // priority
        'open' // status
    ]);
    
    if ($result) {
        $request_id = $db->lastInsertId();
        echo "<p style='color: green;'>&#10004; Test request created successfully!</p>";
        echo "<p><strong>Request ID:</strong> {$request_id}</p>";
        echo "<p><strong>Title:</strong> Test Request for Notifications " . date('Y-m-d H:i:s') . "</p>";
        echo "<p><strong>Status:</strong> open</p>";
        echo "<p><strong>User ID:</strong> 1 (ndvu)</p>";
        
        echo "<h3>Test Steps:</h3>";
        echo "<ol>";
        echo "<li>1. Login as <strong>staff1</strong> with password <strong>password123</strong></li>";
        echo "<li>2. Navigate to request detail: <a href='index.php?page=request-detail&id={$request_id}' target='_blank'>Request #{$request_id}</a></li>";
        echo "<li>3. Click <strong>'Nhận yêu cầu'</strong> button</li>";
        echo "<li>4. Check if notifications are created:</li>";
        echo "<ul>";
        echo "<li>User (ID 1) should receive: 'Yêu cầu đang được xử lý'</li>";
        echo "<li>Admin (ID 1) should receive: 'Thay đổi trạng thái yêu cầu'</li>";
        echo "<li>Other staff should receive: 'Yêu cầu được Admin phê duyệt'</li>";
        echo "</ul>";
        echo "<li>5. Check database: <a href='test-all-notification-scenarios.php' target='_blank'>Test All Scenarios</a></li>";
        echo "</ol>";
        
        echo "<h3>Direct Test Links:</h3>";
        echo "<ul>";
        echo "<li><a href='test-all-notification-scenarios.php' target='_blank'>Test All Notification Scenarios</a></li>";
        echo "<li><a href='debug-session-api.php' target='_blank'>Debug Session API</a></li>";
        echo "<li><a href='browser-debug-checklist.html' target='_blank'>Browser Debug Checklist</a></li>";
        echo "</ul>";
        
        echo "<h3>Expected Results:</h3>";
        echo "<div style='background-color: #e8f5e8; padding: 15px; border-radius: 5px;'>";
        echo "<h4>&#10004; When staff accepts request #{$request_id}:</h4>";
        echo "<ul>";
        echo "<li><strong>User Notification:</strong> 'Yêu cầu đang được xử lý' - User ID 1</li>";
        echo "<li><strong>Admin Notification:</strong> 'Thay đổi trạng thái yêu cầu' - Admin ID 1</li>";
        echo "<li><strong>Staff Notification:</strong> 'Yêu cầu được Admin phê duyệt' - Other staff (ID 2,3)</li>";
        echo "<li><strong>Request Status:</strong> Changes from 'open' to 'in_progress'</li>";
        echo "<li><strong>Assigned To:</strong> Changes to staff ID 2</li>";
        echo "</ul>";
        echo "</div>";
        
    } else {
        echo "<p style='color: red;'>&#10027; Failed to create test request</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<h3>Check Current Requests:</h3>";

// Hiển thị các requests hiện tại
try {
    $stmt = $db->prepare("SELECT id, title, status, assigned_to, user_id FROM service_requests ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($requests) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th><th>Title</th><th>Status</th><th>Assigned To</th><th>User ID</th><th>Action</th>";
        echo "</tr>";
        
        foreach ($requests as $request) {
            echo "<tr>";
            echo "<td><strong>{$request['id']}</strong></td>";
            echo "<td>" . htmlspecialchars($request['title']) . "</td>";
            echo "<td><span style='background-color: #007bff; color: white; padding: 2px 6px; border-radius: 3px;'>{$request['status']}</span></td>";
            echo "<td>{$request['assigned_to']}</td>";
            echo "<td>{$request['user_id']}</td>";
            echo "<td><a href='index.php?page=request-detail&id={$request['id']}' target='_blank'>View</a></td>";
            echo "</tr>";
        }
        echo "</table>";
        
        $openCount = 0;
        foreach ($requests as $request) {
            if ($request['status'] === 'open' && (!$request['assigned_to'] || $request['assigned_to'] == 0)) {
                $openCount++;
            }
        }
        
        echo "<p><strong>Open requests available for testing:</strong> {$openCount}</p>";
        
    } else {
        echo "<p style='color: orange;'>&#9888; No requests found in database</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking requests: " . $e->getMessage() . "</p>";
}
?>
