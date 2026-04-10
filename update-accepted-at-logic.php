<?php
require_once 'config/database.php';

echo "<h2>🔧 CẬP NHẬT LOGIC ACCEPTED_AT CHO STAFF</h2>";

try {
    // 1. Đảm bảo tất cả requests đã được assign đều có accepted_at
    echo "<h3>1️⃣ Cập nhật accepted_at cho các request đã được assign</h3>";
    
    $updateQuery = "UPDATE service_requests 
                   SET accepted_at = CASE 
                       WHEN accepted_at IS NULL AND assigned_to IS NOT NULL THEN assigned_at
                       ELSE accepted_at
                   END
                   WHERE assigned_to IS NOT NULL";
    
    $stmt = $pdo->prepare($updateQuery);
    $stmt->execute();
    $updatedCount = $stmt->rowCount();
    
    echo "<p>✅ Đã cập nhật {$updatedCount} request</p>";
    
    // 2. Kiểm tra các request đang in_progress nhưng chưa có accepted_at
    echo "<h3>2️⃣ Kiểm tra request in_progress chưa có accepted_at</h3>";
    
    $checkQuery = "SELECT id, title, assigned_to, assigned_at, accepted_at, status 
                   FROM service_requests 
                   WHERE status = 'in_progress' AND accepted_at IS NULL";
    
    $stmt = $pdo->prepare($checkQuery);
    $stmt->execute();
    $missingAccepted = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($missingAccepted) > 0) {
        echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h4>⚠️ Các request in_progress chưa có accepted_at:</h4>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Assigned To</th><th>Assigned At</th><th>Status</th></tr>";
        
        foreach ($missingAccepted as $request) {
            echo "<tr>";
            echo "<td>" . $request['id'] . "</td>";
            echo "<td>" . htmlspecialchars($request['title']) . "</td>";
            echo "<td>" . $request['assigned_to'] . "</td>";
            echo "<td>" . $request['assigned_at'] . "</td>";
            echo "<td>" . $request['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
        
        // Cập nhật các request này
        $fixQuery = "UPDATE service_requests 
                     SET accepted_at = assigned_at 
                     WHERE status = 'in_progress' AND accepted_at IS NULL";
        $fixStmt = $pdo->prepare($fixQuery);
        $fixStmt->execute();
        $fixedCount = $fixStmt->rowCount();
        
        echo "<p>✅ Đã fix {$fixedCount} request in_progress</p>";
    } else {
        echo "<p>✅ Tất cả request in_progress đều có accepted_at</p>";
    }
    
    // 3. Thêm logic để tự động set accepted_at khi staff nhận request
    echo "<h3>3️⃣ Kiểm tra logic assign request</h3>";
    
    // Tìm trong API endpoint assign request
    echo "<p>Để đảm bảo accepted_at được set tự động khi staff nhận request, cần kiểm tra:</p>";
    echo "<ul>";
    echo "<li>✅ API assign request (<code>api/service_requests.php</code>)</li>";
    echo "<li>✅ Database trigger (nếu có)</li>";
    echo "<li>✅ Application logic</li>";
    echo "</ul>";
    
    // 4. Test với request #83
    echo "<h3>4️⃣ Test với request #83</h3>";
    
    $testQuery = "SELECT sr.*, u.full_name as assigned_name 
                  FROM service_requests sr 
                  LEFT JOIN users u ON sr.assigned_to = u.id 
                  WHERE sr.id = 83";
    
    $testStmt = $pdo->prepare($testQuery);
    $testStmt->execute();
    
    if ($testStmt->rowCount() > 0) {
        $request = $testStmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>ID</td><td>" . $request['id'] . "</td></tr>";
        echo "<tr><td>Title</td><td>" . htmlspecialchars($request['title']) . "</td></tr>";
        echo "<tr><td>Status</td><td>" . $request['status'] . "</td></tr>";
        echo "<tr><td>Assigned To</td><td>" . $request['assigned_to'] . " (" . $request['assigned_name'] . ")</td></tr>";
        echo "<tr><td>Assigned At</td><td>" . $request['assigned_at'] . "</td></tr>";
        echo "<tr><td>Accepted At</td><td>" . ($request['accepted_at'] ?: '<span style="color: red;">NULL</span>') . "</td></tr>";
        echo "</table>";
        
        if ($request['assigned_to'] && $request['accepted_at']) {
            echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "<h4>✅ Request #83 đã hoàn chỉnh!</h4>";
            echo "<p>Đã có staff assign và accepted_at. Sẽ hiển thị 'Thời gian staff nhận'.</p>";
            echo "</div>";
        } elseif ($request['assigned_to'] && !$request['accepted_at']) {
            echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "<h4>⚠️ Request #83 cần fix accepted_at!</h4>";
            
            $fixQuery = "UPDATE service_requests SET accepted_at = assigned_at WHERE id = 83";
            $fixStmt = $pdo->prepare($fixQuery);
            $fixStmt->execute();
            
            echo "<p>✅ Đã fix accepted_at cho request #83</p>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "<h4>❌ Request #83 chưa được assign cho staff nào!</h4>";
            echo "<p>Cần assign request cho staff trước khi có accepted_at.</p>";
            echo "</div>";
        }
    } else {
        echo "<p>❌ Không tìm thấy request #83</p>";
    }
    
    // 5. Tổng kết
    echo "<h3>📊 TỔNG KẾT</h3>";
    
    $totalQuery = "SELECT COUNT(*) as total FROM service_requests";
    $totalStmt = $pdo->prepare($totalQuery);
    $totalStmt->execute();
    $total = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $assignedQuery = "SELECT COUNT(*) as assigned FROM service_requests WHERE assigned_to IS NOT NULL";
    $assignedStmt = $pdo->prepare($assignedQuery);
    $assignedStmt->execute();
    $assigned = $assignedStmt->fetch(PDO::FETCH_ASSOC)['assigned'];
    
    $acceptedQuery = "SELECT COUNT(*) as accepted FROM service_requests WHERE accepted_at IS NOT NULL";
    $acceptedStmt = $pdo->prepare($acceptedQuery);
    $acceptedStmt->execute();
    $accepted = $acceptedStmt->fetch(PDO::FETCH_ASSOC)['accepted'];
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Loại</th><th>Số lượng</th><th>Tỷ lệ</th></tr>";
    echo "<tr><td>Tổng requests</td><td>{$total}</td><td>100%</td></tr>";
    echo "<tr><td>Đã assign</td><td>{$assigned}</td><td>" . round($assigned/$total*100, 1) . "%</td></tr>";
    echo "<tr><td>Có accepted_at</td><td>{$accepted}</td><td>" . round($accepted/$total*100, 1) . "%</td></tr>";
    echo "</table>";
    
    if ($assigned == $accepted) {
        echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h4>✅ HOÀN HẢO!</h4>";
        echo "<p>Tất cả request đã được assign đều có accepted_at.</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h4>⚠️ Cần chú ý:</h4>";
        echo "<p>Còn " . ($assigned - $accepted) . " request đã assign nhưng chưa có accepted_at.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h4>❌ Lỗi:</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<h4>🔄 Các bước tiếp theo:</h4>";
echo "<ol>";
echo "<li><strong>Refresh browser</strong> tại trang chi tiết request #83</li>";
echo "<li><strong>Kiểm tra</strong> có thấy 'Thời gian staff nhận' không</li>";
echo "<li><strong>Nếu vẫn không thấy</strong> → Kiểm tra console browser cho lỗi JavaScript</li>";
echo "<li><strong>Nếu có lỗi</strong> → F12 và xem tab Console</li>";
echo "</ol>";
?>
