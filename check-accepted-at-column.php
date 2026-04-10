<?php
require_once 'config/database.php';

echo "<h2>🔍 KIỂM TRA COLUMN ACCEPTED_AT</h2>";

try {
    // Check if accepted_at column exists
    $query = "SHOW COLUMNS FROM service_requests LIKE 'accepted_at'";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h4>✅ Column 'accepted_at' đã tồn tại!</h4>";
        
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p><strong>Type:</strong> " . $column['Type'] . "</p>";
        echo "<p><strong>Null:</strong> " . $column['Null'] . "</p>";
        echo "<p><strong>Default:</strong> " . $column['Default'] . "</p>";
        echo "</div>";
        
        // Check if any requests have accepted_at values
        $countQuery = "SELECT COUNT(*) as count FROM service_requests WHERE accepted_at IS NOT NULL";
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute();
        $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo "<p><strong>Số request có accepted_at:</strong> " . $count . "</p>";
        
        if ($count > 0) {
            echo "<p><strong>5 request đầu tiên có accepted_at:</strong></p>";
            $sampleQuery = "SELECT id, title, assigned_to, accepted_at FROM service_requests WHERE accepted_at IS NOT NULL LIMIT 5";
            $sampleStmt = $pdo->prepare($sampleQuery);
            $sampleStmt->execute();
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Title</th><th>Assigned To</th><th>Accepted At</th></tr>";
            while ($row = $sampleStmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                echo "<td>" . $row['assigned_to'] . "</td>";
                echo "<td>" . $row['accepted_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h4>❌ Column 'accepted_at' chưa tồn tại!</h4>";
        echo "<p>Cần thêm column này vào database.</p>";
        echo "</div>";
        
        // Add the column
        echo "<h4>🔧 Đang thêm column 'accepted_at'...</h4>";
        try {
            $alterQuery = "ALTER TABLE service_requests ADD COLUMN accepted_at TIMESTAMP NULL AFTER assigned_to";
            $alterStmt = $pdo->prepare($alterQuery);
            $alterStmt->execute();
            
            echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "<h4>✅ Đã thêm column 'accepted_at' thành công!</h4>";
            echo "</div>";
            
            // Update existing requests that are already assigned
            echo "<h4>🔄 Cập nhật các request đã được assign...</h4>";
            $updateQuery = "UPDATE service_requests SET accepted_at = updated_at WHERE assigned_to IS NOT NULL AND accepted_at IS NULL";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute();
            
            $updatedCount = $updateStmt->rowCount();
            echo "<p>✅ Đã cập nhật " . $updatedCount . " request có accepted_at.</p>";
            
        } catch (Exception $e) {
            echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "<h4>❌ Lỗi khi thêm column:</h4>";
            echo "<p>" . $e->getMessage() . "</p>";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h4>❌ Lỗi database:</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<h4>📋 Kiểm tra API response cho request #83:</h4>";

try {
    $testQuery = "SELECT sr.*, sr.accepted_at, assigned.full_name as assigned_name 
                 FROM service_requests sr 
                 LEFT JOIN users assigned ON sr.assigned_to = assigned.id 
                 WHERE sr.id = 83";
    $testStmt = $pdo->prepare($testQuery);
    $testStmt->execute();
    
    if ($testStmt->rowCount() > 0) {
        $request = $testStmt->fetch(PDO::FETCH_ASSOC);
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>ID</td><td>" . $request['id'] . "</td></tr>";
        echo "<tr><td>Title</td><td>" . htmlspecialchars($request['title']) . "</td></tr>";
        echo "<tr><td>Assigned To</td><td>" . $request['assigned_to'] . " (" . $request['assigned_name'] . ")</td></tr>";
        echo "<tr><td>Assigned At</td><td>" . $request['assigned_at'] . "</td></tr>";
        echo "<tr><td>Accepted At</td><td>" . ($request['accepted_at'] ?: 'NULL') . "</td></tr>";
        echo "<tr><td>Status</td><td>" . $request['status'] . "</td></tr>";
        echo "</table>";
        
        if ($request['assigned_to'] && !$request['accepted_at']) {
            echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "<h4>⚠️ Request đã được assign nhưng chưa có accepted_at!</h4>";
            echo "<p>Đang cập nhật accepted_at...</p>";
            
            $updateQuery = "UPDATE service_requests SET accepted_at = assigned_at WHERE id = 83";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute();
            
            echo "<p>✅ Đã cập nhật accepted_at cho request #83</p>";
            echo "</div>";
        }
    } else {
        echo "<p>❌ Không tìm thấy request #83</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Lỗi: " . $e->getMessage() . "</p>";
}
?>
