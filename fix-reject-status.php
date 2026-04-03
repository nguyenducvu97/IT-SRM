<?php
// Fix inconsistent reject status
require_once 'config/database.php';

echo "<h2>Fix Service Requests với status 'rejected' nhưng không có reject request</h2>";

try {
    $db = getDatabaseConnection();
    
    // Get service requests with status 'rejected' but no reject request
    echo "<h3>Phân tích vấn đề:</h3>";
    
    $requestsToCheck = [18, 22, 27, 28];
    
    foreach ($requestsToCheck as $requestId) {
        echo "<h4>Request #{$requestId}:</h4>";
        
        // Check service request
        $stmt1 = $db->prepare("SELECT id, title, status, updated_at FROM service_requests WHERE id = ?");
        $stmt1->execute([$requestId]);
        $serviceRequest = $stmt1->fetch(PDO::FETCH_ASSOC);
        
        if ($serviceRequest) {
            echo "<p>✅ Service Request: {$serviceRequest['title']} (Status: {$serviceRequest['status']})</p>";
            
            // Check reject request
            $stmt2 = $db->prepare("SELECT id, status, processed_by, processed_at FROM reject_requests WHERE service_request_id = ?");
            $stmt2->execute([$requestId]);
            $rejectRequest = $stmt2->fetch(PDO::FETCH_ASSOC);
            
            if ($rejectRequest) {
                echo "<p style='color: green;'>✅ Có reject request: ID {$rejectRequest['id']}, Status: {$rejectRequest['status']}</p>";
            } else {
                echo "<p style='color: red;'>❌ KHÔNG CÓ reject request</p>";
                
                // Check if we should reset status
                echo "<h5>Options:</h5>";
                echo "<ol>";
                echo "<li><strong>Reset status về 'in_progress':</strong> Nếu yêu cầu đang được xử lý</li>";
                echo "<li><strong>Reset status về 'pending':</strong> Nếu yêu cầu chưa được xử lý</li>";
                echo "<li><strong>Tạo reject request:</strong> Nếu có lý do từ chối hợp lệ</li>";
                echo "</ol>";
                
                // Get request history to determine original status
                $stmt3 = $db->prepare("SELECT created_at FROM service_requests WHERE id = ?");
                $stmt3->execute([$requestId]);
                $created = $stmt3->fetch(PDO::FETCH_ASSOC);
                
                echo "<p>Created: {$created['created_at']}</p>";
                echo "<p>Updated: {$serviceRequest['updated_at']}</p>";
                
                // Calculate time difference
                $created = new DateTime($created['created_at']);
                $updated = new DateTime($serviceRequest['updated_at']);
                $interval = $created->diff($updated);
                
                echo "<p>Thời gian xử lý: {$interval->h} giờ {$interval->i} phút</p>";
                
                // Suggest action based on time
                if ($interval->h < 1) {
                    echo "<p style='color: blue;'>💡 Gợi ý: Reset về 'pending' (yêu cầu mới)</p>";
                } else {
                    echo "<p style='color: blue;'>💡 Gợi ý: Reset về 'in_progress' (đã được xử lý)</p>";
                }
            }
        } else {
            echo "<p style='color: orange;'>⚠️ Service Request #{$requestId} không tồn tại</p>";
        }
        
        echo "<hr>";
    }
    
    // Create fix buttons
    echo "<h3>Thực hiện sửa:</h3>";
    echo "<form method='post' action='fix-reject-status-action.php'>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Request ID</th><th>Current Status</th><th>Action</th><th>New Status</th></tr>";
    
    foreach ($requestsToCheck as $requestId) {
        $stmt1 = $db->prepare("SELECT id, title, status FROM service_requests WHERE id = ?");
        $stmt1->execute([$requestId]);
        $serviceRequest = $stmt1->fetch(PDO::FETCH_ASSOC);
        
        if ($serviceRequest) {
            $stmt2 = $db->prepare("SELECT id FROM reject_requests WHERE service_request_id = ?");
            $stmt2->execute([$requestId]);
            $rejectRequest = $stmt2->fetch(PDO::FETCH_ASSOC);
            
            if (!$rejectRequest && $serviceRequest['status'] === 'rejected') {
                echo "<tr>";
                echo "<td>{$serviceRequest['id']}</td>";
                echo "<td>{$serviceRequest['status']}</td>";
                echo "<td>";
                echo "<select name='action_{$requestId}'>";
                echo "<option value=''>-- Chọn action --</option>";
                echo "<option value='reset_pending'>Reset về Pending</option>";
                echo "<option value='reset_in_progress'>Reset về In Progress</option>";
                echo "<option value='create_reject'>Tạo Reject Request</option>";
                echo "<option value='keep'>Giữ nguyên (đã xử lý)</option>";
                echo "</select>";
                echo "</td>";
                echo "<td><span id='new_status_{$requestId}'>-</span></td>";
                echo "</tr>";
            }
        }
    }
    
    echo "</table>";
    echo "<br><input type='submit' value='Thực hiện sửa' class='btn btn-primary'>";
    echo "</form>";
    
    echo "<script>";
    echo "document.querySelectorAll('select').forEach(select => {";
    echo "  select.addEventListener('change', function() {";
    echo "    const requestId = this.name.replace('action_', '');";
    echo "    const newStatusSpan = document.getElementById('new_status_' + requestId);";
    echo "    switch(this.value) {";
    echo "      case 'reset_pending': newStatusSpan.textContent = 'pending'; break;";
    echo "      case 'reset_in_progress': newStatusSpan.textContent = 'in_progress'; break;";
    echo "      case 'create_reject': newStatusSpan.textContent = 'rejected'; break;";
    echo "      case 'keep': newStatusSpan.textContent = 'rejected'; break;";
    echo "      default: newStatusSpan.textContent = '-';";
    echo "    }";
    echo "  });";
    echo "});";
    echo "</script>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
}
?>
