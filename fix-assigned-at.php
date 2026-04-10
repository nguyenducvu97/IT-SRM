<?php
require_once 'config/database.php';

echo "<h2>🔧 FIX ASSIGNED_AT CHO REQUEST #83</h2>";

try {
    $pdo = getDatabaseConnection();
    
    // Kiểm tra lại request #83
    $checkQuery = "SELECT id, title, assigned_to, assigned_at, accepted_at, created_at, updated_at 
                   FROM service_requests WHERE id = 83";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute();
    $request = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>📋 Trạng thái hiện tại:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    foreach ($request as $key => $value) {
        echo "<tr><td>{$key}</td><td>" . ($value ?: 'NULL') . "</td></tr>";
    }
    echo "</table>";
    
    if ($request['assigned_to'] && !$request['assigned_at']) {
        echo "<h3>🔧 Đang fix assigned_at...</h3>";
        
        // Cập nhật assigned_at = created_at (khi request được tạo)
        $updateQuery = "UPDATE service_requests 
                        SET assigned_at = created_at, accepted_at = created_at 
                        WHERE id = 83";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->execute();
        
        echo "<p>✅ Đã cập nhật assigned_at và accepted_at</p>";
        
        // Kiểm tra lại
        $checkStmt->execute();
        $updatedRequest = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h3>📋 Trạng thái sau khi fix:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>assigned_at</td><td>" . $updatedRequest['assigned_at'] . "</td></tr>";
        echo "<tr><td>accepted_at</td><td>" . $updatedRequest['accepted_at'] . "</td></tr>";
        echo "</table>";
        
    } else {
        echo "<p>✅ Request đã có assigned_at</p>";
    }
    
    // Test API call again
    echo "<h3>🌐 Test API lại:</h3>";
    
    // Simulate API call
    $_GET['action'] = 'get';
    $_GET['id'] = '83';
    
    ob_start();
    include 'api/service_requests.php';
    $apiResponse = ob_get_clean();
    
    $data = json_decode($apiResponse, true);
    
    if ($data && $data['success']) {
        $requestData = $data['data'];
        
        echo "<p>✅ API trả về thành công!</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>assigned_to</td><td>" . $requestData['assigned_to'] . "</td></tr>";
        echo "<tr><td>assigned_at</td><td>" . ($requestData['assigned_at'] ?: 'NULL') . "</td></tr>";
        echo "<tr><td>accepted_at</td><td>" . ($requestData['accepted_at'] ?: 'NULL') . "</td></tr>";
        echo "</table>";
        
        if ($requestData['assigned_to'] && $requestData['accepted_at']) {
            echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "<h4>🎉 HOÀN HẢO!</h4>";
            echo "<p>Request #83 đã có đủ dữ liệu để hiển thị 'Thời gian staff nhận'.</p>";
            echo "</div>";
        }
    } else {
        echo "<p>❌ API lỗi: " . $apiResponse . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Lỗi: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h4>🔄 Các bước tiếp theo:</h4>";
echo "<ol>";
echo "<li><strong>Refresh browser</strong> (Ctrl+F5) tại trang chi tiết request #83</li>";
echo "<li><strong>Kiểm tra</strong> có thấy 'Thời gian staff nhận' không</li>";
echo "<li><strong>Nếu thấy</strong> → ✅ Thành công!</li>";
echo "<li><strong>Nếu chưa thấy</strong> → F12 kiểm tra console JavaScript</li>";
echo "</ol>";
?>
