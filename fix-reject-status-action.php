<?php
// Action handler for fixing reject status
require_once 'config/database.php';

echo "<h2>Thực hiện sửa Service Requests Status</h2>";

try {
    $db = getDatabaseConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo "<h3>Kết quả xử lý:</h3>";
        
        $requestsToFix = [18, 22, 27, 28];
        $fixedCount = 0;
        
        foreach ($requestsToFix as $requestId) {
            $actionKey = "action_{$requestId}";
            
            if (isset($_POST[$actionKey]) && !empty($_POST[$actionKey])) {
                $action = $_POST[$actionKey];
                
                echo "<h4>Request #{$requestId}:</h4>";
                
                switch ($action) {
                    case 'reset_pending':
                        // Reset status to pending
                        $stmt = $db->prepare("UPDATE service_requests SET status = 'pending', updated_at = NOW() WHERE id = ?");
                        $result = $stmt->execute([$requestId]);
                        
                        if ($result) {
                            echo "<p style='color: green;'>✅ Reset status về 'pending' thành công</p>";
                            $fixedCount++;
                        } else {
                            echo "<p style='color: red;'>❌ Reset status thất bại</p>";
                        }
                        break;
                        
                    case 'reset_in_progress':
                        // Reset status to in_progress
                        $stmt = $db->prepare("UPDATE service_requests SET status = 'in_progress', updated_at = NOW() WHERE id = ?");
                        $result = $stmt->execute([$requestId]);
                        
                        if ($result) {
                            echo "<p style='color: green;'>✅ Reset status về 'in_progress' thành công</p>";
                            $fixedCount++;
                        } else {
                            echo "<p style='color: red;'>❌ Reset status thất bại</p>";
                        }
                        break;
                        
                    case 'create_reject':
                        // Create a reject request
                        echo "<p style='color: orange;'>⚠️ Chức năng tạo reject request cần thêm thông tin</p>";
                        echo "<p>Vui lòng tạo reject request thủ công từ interface</p>";
                        break;
                        
                    case 'keep':
                        echo "<p style='color: blue;'>ℹ️ Giữ nguyên status 'rejected'</p>";
                        break;
                        
                    default:
                        echo "<p style='color: gray;'>⚪ Không có action</p>";
                }
            }
        }
        
        echo "<h3>Tóm tắt:</h3>";
        echo "<p>Đã sửa <strong>{$fixedCount}</strong> service requests</p>";
        
        // Show updated status
        echo "<h4>Status sau khi sửa:</h4>";
        $stmt = $db->prepare("SELECT id, title, status FROM service_requests WHERE id IN (18,22,24,27,28) ORDER BY id");
        $stmt->execute();
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Reject Request</th></tr>";
        
        foreach ($requests as $request) {
            $stmt2 = $db->prepare("SELECT id, status FROM reject_requests WHERE service_request_id = ?");
            $stmt2->execute([$request['id']]);
            $rejectRequest = $stmt2->fetch(PDO::FETCH_ASSOC);
            
            echo "<tr>";
            echo "<td>{$request['id']}</td>";
            echo "<td>" . substr($request['title'], 0, 30) . "...</td>";
            echo "<td><strong>{$request['status']}</strong></td>";
            
            if ($rejectRequest) {
                echo "<td style='color: green;'>✅ {$rejectRequest['id']} ({$rejectRequest['status']})</td>";
            } else {
                echo "<td style='color: red;'>❌ Không có</td>";
            }
            
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<br><a href='fix-reject-status.php' class='btn btn-secondary'>Quay lại</a>";
        echo " | <a href='index.html' class='btn btn-primary'>Về Dashboard</a>";
        
    } else {
        echo "<p style='color: red;'>❌ Invalid request method</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
}
?>
