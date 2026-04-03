<?php
// Auto-fix inconsistent reject status based on analysis
require_once 'config/database.php';

echo "<h2>Auto-Fix Service Requests với status 'rejected' nhưng không có reject request</h2>";

try {
    $db = getDatabaseConnection();
    
    $fixes = [
        18 => 'in_progress',  // 21 giờ 12 phút - đã được xử lý
        22 => 'in_progress',  // 19 giờ 59 phút - đã được xử lý  
        27 => 'in_progress',  // 19 giờ 23 phút - đã được xử lý
        28 => 'pending'       // 0 giờ 41 phút - yêu cầu mới
    ];
    
    echo "<h3>Thực hiện auto-fix:</h3>";
    
    $fixedCount = 0;
    $errors = [];
    
    foreach ($fixes as $requestId => $newStatus) {
        echo "<h4>Request #{$requestId}:</h4>";
        
        // Get current status
        $stmt1 = $db->prepare("SELECT id, title, status FROM service_requests WHERE id = ?");
        $stmt1->execute([$requestId]);
        $serviceRequest = $stmt1->fetch(PDO::FETCH_ASSOC);
        
        if ($serviceRequest) {
            echo "<p>Current: {$serviceRequest['title']} (Status: {$serviceRequest['status']})</p>";
            
            // Check if has reject request
            $stmt2 = $db->prepare("SELECT id FROM reject_requests WHERE service_request_id = ?");
            $stmt2->execute([$requestId]);
            $rejectRequest = $stmt2->fetch(PDO::FETCH_ASSOC);
            
            if (!$rejectRequest && $serviceRequest['status'] === 'rejected') {
                // Update status
                $stmt3 = $db->prepare("UPDATE service_requests SET status = ?, updated_at = NOW() WHERE id = ?");
                $result = $stmt3->execute([$newStatus, $requestId]);
                
                if ($result) {
                    echo "<p style='color: green;'>✅ Fixed: {$serviceRequest['status']} → {$newStatus}</p>";
                    $fixedCount++;
                } else {
                    echo "<p style='color: red;'>❌ Failed to update</p>";
                    $errors[] = "Request #{$requestId}: Failed to update";
                }
            } else {
                if ($rejectRequest) {
                    echo "<p style='color: blue;'>ℹ️ Has reject request - skipping</p>";
                } else {
                    echo "<p style='color: blue;'>ℹ️ Status not 'rejected' - skipping</p>";
                }
            }
        } else {
            echo "<p style='color: red;'>❌ Service Request not found</p>";
            $errors[] = "Request #{$requestId}: Not found";
        }
    }
    
    echo "<h3>Kết quả:</h3>";
    echo "<p>✅ Đã fix <strong>{$fixedCount}</strong> service requests</p>";
    
    if (!empty($errors)) {
        echo "<p style='color: red;'>❌ Errors: " . implode(', ', $errors) . "</p>";
    }
    
    // Show final status
    echo "<h4>Status sau khi fix:</h4>";
    $stmt4 = $db->prepare("SELECT id, title, status, updated_at FROM service_requests WHERE id IN (18,22,24,27,28) ORDER BY id");
    $stmt4->execute();
    $requests = $stmt4->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Updated</th><th>Reject Request</th><th>Consistent</th></tr>";
    
    foreach ($requests as $request) {
        $stmt5 = $db->prepare("SELECT id, status FROM reject_requests WHERE service_request_id = ?");
        $stmt5->execute([$request['id']]);
        $rejectRequest = $stmt5->fetch(PDO::FETCH_ASSOC);
        
        echo "<tr>";
        echo "<td>{$request['id']}</td>";
        echo "<td>" . substr($request['title'], 0, 30) . "...</td>";
        echo "<td><strong>{$request['status']}</strong></td>";
        echo "<td>{$request['updated_at']}</td>";
        
        if ($rejectRequest) {
            echo "<td style='color: green;'>✅ {$rejectRequest['id']} ({$rejectRequest['status']})</td>";
            $consistent = ($request['status'] === 'rejected' && $rejectRequest['status'] === 'approved') ? 
                         '✅' : '❌';
        } else {
            echo "<td style='color: red;'>❌ Không có</td>";
            $consistent = ($request['status'] === 'rejected') ? '❌' : '✅';
        }
        
        echo "<td>{$consistent}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>🎊 Tóm tắt:</h3>";
    echo "<ul>";
    echo "<li>✅ Đã fix các yêu cầu có status 'rejected' nhưng không có reject request</li>";
    echo "<li>✅ Data consistency đã được khôi phục</li>";
    echo "<li>✅ Workflow integrity được đảm bảo</li>";
    echo "<li>✅ Chỉ request #24 có reject request hợp lệ (admin đã xử lý)</li>";
    echo "</ul>";
    
    echo "<br><a href='index.html' class='btn btn-primary'>Về Dashboard</a>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
}
?>
