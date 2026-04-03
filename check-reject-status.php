<?php
// Check reject requests vs service requests status
require_once 'config/database.php';

echo "<h2>Kiểm tra Reject Requests và Service Requests Status</h2>";

try {
    $db = getDatabaseConnection();
    
    // Check service requests with status 'rejected'
    echo "<h3>Service Requests có status = 'rejected':</h3>";
    $stmt1 = $db->prepare("SELECT id, title, status, updated_at FROM service_requests WHERE id IN (18,22,24,27,28)");
    $stmt1->execute();
    $serviceRequests = $stmt1->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Updated</th><th>Reject Request</th></tr>";
    
    foreach ($serviceRequests as $request) {
        // Check if there's a corresponding reject request
        $stmt2 = $db->prepare("SELECT id, status, admin_decision, admin_decision_at FROM reject_requests WHERE service_request_id = ?");
        $stmt2->execute([$request['id']]);
        $rejectRequest = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        echo "<tr>";
        echo "<td>{$request['id']}</td>";
        echo "<td>" . substr($request['title'], 0, 50) . "...</td>";
        echo "<td><strong>{$request['status']}</strong></td>";
        echo "<td>{$request['updated_at']}</td>";
        
        if ($rejectRequest) {
            echo "<td style='color: green;'>✅ ID: {$rejectRequest['id']} - Status: {$rejectRequest['status']}";
            if ($rejectRequest['admin_decision']) {
                echo " - Admin: {$rejectRequest['admin_decision']} ({$rejectRequest['admin_decision_at']})";
            }
            echo "</td>";
        } else {
            echo "<td style='color: red;'>❌ Không có reject request</td>";
        }
        
        echo "</tr>";
    }
    echo "</table>";
    
    // Check all reject requests for these service requests
    echo "<h3>Tất cả Reject Requests cho các service request này:</h3>";
    $stmt3 = $db->prepare("SELECT rr.id, rr.service_request_id, rr.status, rr.admin_decision, rr.admin_decision_at, rr.created_at, u.full_name as staff_name 
                          FROM reject_requests rr 
                          LEFT JOIN users u ON rr.staff_id = u.id 
                          WHERE rr.service_request_id IN (18,22,24,27,28)
                          ORDER BY rr.service_request_id, rr.created_at");
    $stmt3->execute();
    $rejectRequests = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($rejectRequests)) {
        echo "<p>Không có reject requests nào cho các service request này</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Reject ID</th><th>Service ID</th><th>Staff</th><th>Status</th><th>Admin Decision</th><th>Created</th></tr>";
        
        foreach ($rejectRequests as $reject) {
            echo "<tr>";
            echo "<td>{$reject['id']}</td>";
            echo "<td>{$reject['service_request_id']}</td>";
            echo "<td>{$reject['staff_name']}</td>";
            echo "<td>{$reject['status']}</td>";
            echo "<td>" . ($reject['admin_decision'] ?: 'Pending') . "</td>";
            echo "<td>{$reject['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check if status was updated without reject request
    echo "<h3>Phân tích vấn đề:</h3>";
    echo "<ul>";
    
    foreach ($serviceRequests as $request) {
        if ($request['status'] === 'rejected') {
            $stmt2 = $db->prepare("SELECT id FROM reject_requests WHERE service_request_id = ?");
            $stmt2->execute([$request['id']]);
            $rejectRequest = $stmt2->fetch(PDO::FETCH_ASSOC);
            
            if (!$rejectRequest) {
                echo "<li style='color: red;'>❌ Request #{$request['id']} có status 'rejected' nhưng không có reject request</li>";
            }
        }
    }
    
    echo "</ul>";
    
    // Suggest fixes
    echo "<h3>Giải pháp đề xuất:</h3>";
    echo "<ol>";
    echo "<li><strong>Option 1:</strong> Tạo reject request cho các service request đang có status 'rejected' nhưng không có reject request</li>";
    echo "<li><strong>Option 2:</strong> Reset status về trạng thái trước đó nếu không có reject request</li>";
    echo "<li><strong>Option 3:</strong> Kiểm tra log để xem ai đã thay đổi status và khi nào</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
}
?>
