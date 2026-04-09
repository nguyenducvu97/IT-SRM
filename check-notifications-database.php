<?php
echo "<h2>KIỂM TRA THÔNG BÁO TRONG DATABASE</h2>";

require_once __DIR__ . '/config/database.php';
$db = getDatabaseConnection();

echo "<h3>1. TỔNG SỐ THÔNG BÁO</h3>";

try {
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Total notifications:</strong> {$count['total']}</p>";
    
    echo "<h3>2. THÔNG BÁO GẦN NHẤT (10 mới nhất)</h3>";
    
    $stmt = $db->prepare("SELECT n.*, u.username, u.role FROM notifications n 
                           LEFT JOIN users u ON n.user_id = u.id 
                           ORDER BY n.created_at DESC LIMIT 10");
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($notifications) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th><th>User</th><th>Role</th><th>Title</th><th>Message</th><th>Type</th><th>Created</th>";
        echo "</tr>";
        
        foreach ($notifications as $notif) {
            $message = strlen($notif['message']) > 80 ? substr($notif['message'], 0, 80) . '...' : $notif['message'];
            echo "<tr>";
            echo "<td><strong>{$notif['id']}</strong></td>";
            echo "<td>{$notif['username']}</td>";
            echo "<td><span style='background-color: #007bff; color: white; padding: 2px 6px; border-radius: 3px;'>{$notif['role']}</span></td>";
            echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
            echo "<td>" . htmlspecialchars($message) . "</td>";
            echo "<td><span style='background-color: #28a745; color: white; padding: 2px 6px; border-radius: 3px;'>{$notif['type']}</span></td>";
            echo "<td>{$notif['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>Không có notifications trong database!</p>";
    }
    
    echo "<h3>3. THÔNG BÁO THEO LOẠI</h3>";
    
    $types = ['info', 'success', 'warning', 'error'];
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>Type</th><th>Count</th><th>Latest</th>";
    echo "</tr>";
    
    foreach ($types as $type) {
        $stmt = $db->prepare("SELECT COUNT(*) as count, MAX(created_at) as latest FROM notifications WHERE type = ?");
        $stmt->execute([$type]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<tr>";
        echo "<td><span style='background-color: #007bff; color: white; padding: 2px 6px; border-radius: 3px;'>{$type}</span></td>";
        echo "<td>{$result['count']}</td>";
        echo "<td>{$result['latest']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>4. THÔNG BÁO THEO USER ROLE</h3>";
    
    $roles = ['user', 'staff', 'admin'];
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>Role</th><th>User Count</th><th>Notification Count</th><th>Latest</th>";
    echo "</tr>";
    
    foreach ($roles as $role) {
        $stmt = $db->prepare("SELECT COUNT(DISTINCT u.id) as user_count, COUNT(n.id) as notif_count, MAX(n.created_at) as latest 
                               FROM users u 
                               LEFT JOIN notifications n ON u.id = n.user_id 
                               WHERE u.role = ?");
        $stmt->execute([$role]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<tr>";
        echo "<td><span style='background-color: #007bff; color: white; padding: 2px 6px; border-radius: 3px;'>{$role}</span></td>";
        echo "<td>{$result['user_count']}</td>";
        echo "<td>{$result['notif_count']}</td>";
        echo "<td>{$result['latest']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>5. THÔNG BÁO CHO REQUEST #78 (Mới tạo)</h3>";
    
    $stmt = $db->prepare("SELECT n.*, u.username, u.role FROM notifications n 
                           LEFT JOIN users u ON n.user_id = u.id 
                           WHERE n.related_id = 78 AND n.related_type = 'service_request'
                           ORDER BY n.created_at DESC");
    $stmt->execute();
    $request78Notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($request78Notifications) > 0) {
        echo "<p style='color: green;'><strong>&#10004; Found " . count($request78Notifications) . " notifications for request #78:</strong></p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th><th>User</th><th>Role</th><th>Title</th><th>Message</th><th>Type</th><th>Created</th>";
        echo "</tr>";
        
        foreach ($request78Notifications as $notif) {
            echo "<tr>";
            echo "<td><strong>{$notif['id']}</strong></td>";
            echo "<td>{$notif['username']}</td>";
            echo "<td><span style='background-color: #007bff; color: white; padding: 2px 6px; border-radius: 3px;'>{$notif['role']}</span></td>";
            echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
            echo "<td>" . htmlspecialchars($notif['message']) . "</td>";
            echo "<td><span style='background-color: #28a745; color: white; padding: 2px 6px; border-radius: 3px;'>{$notif['type']}</span></td>";
            echo "<td>{$notif['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check if all expected notifications are present
        $expectedNotifications = [
            'Yêu cầu đang được xử lý' => 'user',
            'Thay đổi trạng thái yêu cầu' => 'admin',
            'Yêu cầu được Admin phê duyệt' => 'staff'
        ];
        
        echo "<h4>Kiểm tra logic thông báo cho request #78:</h4>";
        foreach ($expectedNotifications as $title => $expectedRole) {
            $found = false;
            foreach ($request78Notifications as $notif) {
                if (strpos($notif['title'], $title) !== false && $notif['role'] === $expectedRole) {
                    $found = true;
                    break;
                }
            }
            
            if ($found) {
                echo "<p style='color: green;'>&#10004; {$title} → {$expectedRole}: <strong>FOUND</strong></p>";
            } else {
                echo "<p style='color: red;'>&#10027; {$title} → {$expectedRole}: <strong>MISSING</strong></p>";
            }
        }
        
    } else {
        echo "<p style='color: red;'><strong>&#10027; No notifications found for request #78!</strong></p>";
        echo "<p>Điều này có nghĩa là:</p>";
        echo "<ul>";
        echo "<li>Staff chưa nhận request #78</li>";
        echo "<li>Hoặc notifications không được tạo khi staff nhận request</li>";
        echo "<li>Hoặc có lỗi trong API</li>";
        echo "</ul>";
    }
    
    echo "<h3>6. KIỂM TRA FRONTEND HIỂN THỊ THÔNG BÁO</h3>";
    
    echo "<p><strong>Để kiểm tra frontend có hiển thị notifications không:</strong></p>";
    echo "<ol>";
    echo "<li>1. Login làm user (ndvu/password123) → Kiểm tra có thấy notifications không</li>";
    echo "<li>2. Login làm admin (admin/password123) → Kiểm tra có thấy notifications không</li>";
    echo "<li>3. Login làm staff (staff1/password123) → Kiểm tra có thấy notifications không</li>";
    echo "<li>4. Mở browser console (F12) → Kiểm tra có lỗi JavaScript không</li>";
    echo "<li>5. Kiểm tra Network tab → Xem có API calls lỗi không</li>";
    echo "</ol>";
    
    echo "<h3>7. TÌM VẤN ĐỀ</h3>";
    
    if ($count['total'] > 0) {
        echo "<div style='background-color: #e8f5e8; padding: 15px; border-radius: 5px;'>";
        echo "<h4>&#128204; PHÂN TÍCH VẤN ĐỀ:</h4>";
        
        if (count($request78Notifications) > 0) {
            echo "<p style='color: green;'><strong>&#10004; Database có notifications → Vấn đề ở frontend</strong></p>";
            echo "<ul>";
            echo "<li>JavaScript không fetch notifications từ API</li>";
            echo "<li>HTML không hiển thị notifications</li>";
            echo "<li>CSS không style notifications đúng</li>";
            echo "<li>Browser cache issue</li>";
            echo "</ul>";
        } else {
            echo "<p style='color: orange;'><strong>&#9888; Database không có notifications cho request #78 → Vấn đề ở API/backend</strong></p>";
            echo "<ul>";
            echo "<li>Staff chưa thực sự nhận request</li>";
            echo "<li>API không gọi notification functions</li>";
            echo "<li>Notification functions có lỗi</li>";
            echo "<li>Database insert failed</li>";
            echo "</ul>";
        }
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<h3>8. HƯỚNG DẪN FIX</h3>";
echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px;'>";
echo "<h4>&#128072; Các bước thực hiện:</h4>";
echo "<ol>";
echo "<li><strong>1. Test trong browser:</strong> Login staff1 → Nhận request #78</li>";
echo "<li><strong>2. Kiểm tra database:</strong> Chạy lại script này để xem notifications</li>";
echo "<li><strong>3. Nếu có notifications:</strong> Kiểm tra frontend hiển thị</li>";
echo "<li><strong>4. Nếu không có notifications:</strong> Kiểm tra API call</li>";
echo "<li><strong>5. Debug API:</strong> Xem console và network tab</li>";
echo "</ol>";
echo "</div>";
?>
