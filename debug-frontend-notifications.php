<?php
require_once 'config/database.php';

echo "<h2>DEBUG FRONTEND NOTIFICATION SYSTEM</h2>";

// 1. Kiểm tra notifications table
echo "<h3>1. Kiểm tra notifications table</h3>";

try {
    $pdo = getDatabaseConnection();
    
    // Lấy tất cả notifications gần đây
    $notifQuery = "SELECT n.*, u.full_name as user_name 
                   FROM notifications n 
                   LEFT JOIN users u ON n.user_id = u.id 
                   WHERE n.created_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
                   ORDER BY n.created_at DESC LIMIT 20";
    $notifStmt = $pdo->prepare($notifQuery);
    $notifStmt->execute();
    $notifications = $notifStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Notifications trong 30 phút qua:</h4>";
    if (count($notifications) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>User ID</th><th>User Name</th><th>Type</th><th>Message</th><th>Created At</th><th>Is Read</th></tr>";
        foreach ($notifications as $notif) {
            echo "<tr>";
            echo "<td>{$notif['id']}</td>";
            echo "<td>{$notif['user_id']}</td>";
            echo "<td>{$notif['user_name']}</td>";
            echo "<td>{$notif['type']}</td>";
            echo "<td>" . htmlspecialchars($notif['message']) . "</td>";
            echo "<td>{$notif['created_at']}</td>";
            echo "<td>{$notif['is_read']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Không có notifications nào trong 30 phút qua</p>";
    }
    
    // Kiểm tra notifications cho user 4 và admin 1
    echo "<h4>Notifications cho user 4 và admin 1:</h4>";
    $targetQuery = "SELECT n.*, u.full_name as user_name 
                    FROM notifications n 
                    LEFT JOIN users u ON n.user_id = u.id 
                    WHERE n.user_id IN (1, 4) 
                    AND n.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                    ORDER BY n.created_at DESC LIMIT 10";
    $targetStmt = $pdo->prepare($targetQuery);
    $targetStmt->execute();
    $targetNotifications = $targetStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($targetNotifications) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>User</th><th>Message</th><th>Created At</th><th>Is Read</th></tr>";
        foreach ($targetNotifications as $notif) {
            echo "<tr>";
            echo "<td>{$notif['id']}</td>";
            echo "<td>{$notif['user_name']} (ID: {$notif['user_id']})</td>";
            echo "<td>" . htmlspecialchars($notif['message']) . "</td>";
            echo "<td>{$notif['created_at']}</td>";
            echo "<td>{$notif['is_read']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Không có notifications cho user 4 và admin 1 trong 1 giờ qua</p>";
    }
    
} catch (Exception $e) {
    echo "<p>Lỗi database: " . $e->getMessage() . "</p>";
}

// 2. Kiểm tra API endpoint notifications
echo "<h3>2. Kiểm tra API endpoint notifications</h3>";

$apiFiles = [
    'api/notifications.php',
    'api/notification.php', 
    'api/service_requests.php'
];

foreach ($apiFiles as $file) {
    if (file_exists($file)) {
        echo "<p>✅ Found: $file</p>";
        
        // Tìm notifications endpoint
        $content = file_get_contents($file);
        if (strpos($content, 'notification') !== false) {
            echo "<p>→ Contains notification logic</p>";
        }
    } else {
        echo "<p>❌ Missing: $file</p>";
    }
}

// 3. Kiểm tra JavaScript notification system
echo "<h3>3. Kiểm tra JavaScript notification system</h3>";

$jsFiles = [
    'assets/js/notifications.js',
    'assets/js/app.js',
    'assets/js/request-detail.js'
];

foreach ($jsFiles as $file) {
    if (file_exists($file)) {
        echo "<p>✅ Found: $file</p>";
        
        $content = file_get_contents($file);
        
        // Tìm các hàm notification
        if (preg_match_all('/function\s+(\w*[Nn]otification\w*)/', $content, $matches)) {
            echo "<p>→ Notification functions: " . implode(', ', $matches[1]) . "</p>";
        }
        
        // Tìm API calls
        if (strpos($content, 'api/notifications') !== false || strpos($content, 'notification') !== false) {
            echo "<p>→ Contains notification API calls</p>";
        }
        
        // Tìm WebSocket/SSE
        if (strpos($content, 'WebSocket') !== false || strpos($content, 'EventSource') !== false) {
            echo "<p>→ Contains real-time notification logic</p>";
        }
    } else {
        echo "<p>❌ Missing: $file</p>";
    }
}

// 4. Test API endpoint directly
echo "<h3>4. Test API endpoint directly</h3>";

// Tạo test API endpoint để lấy notifications
echo "<h4>Test API call:</h4>";
echo "<p>Testing: <code>api/notifications.php?action=get</code></p>";

// Simulate API call
$_GET['action'] = 'get';
$_GET['user_id'] = '4';

// Kiểm tra xem có file notifications API không
if (file_exists('api/notifications.php')) {
    echo "<p>✅ File api/notifications.php exists</p>";
    
    try {
        ob_start();
        include 'api/notifications.php';
        $apiResponse = ob_get_clean();
        
        echo "<h5>API Response:</h5>";
        echo "<pre>" . htmlspecialchars($apiResponse) . "</pre>";
        
        $data = json_decode($apiResponse, true);
        if ($data && isset($data['success'])) {
            echo "<p>✅ API trả về success: " . ($data['success'] ? 'true' : 'false') . "</p>";
            if (isset($data['data']) && is_array($data['data'])) {
                echo "<p>→ Số notifications: " . count($data['data']) . "</p>";
            }
        } else {
            echo "<p>❌ API response không đúng format</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Lỗi khi gọi API: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ File api/notifications.php không tồn tại</p>";
    
    // Tạo file notifications API
    echo "<h4>Tạo notifications API endpoint:</h4>";
    createNotificationsAPI();
}

// 5. Kiểm tra real-time notification
echo "<h3>5. Kiểm tra real-time notification</h3>";

echo "<p>Cần kiểm tra:</p>";
echo "<ul>";
echo "<li>WebSocket connection</li>";
echo "<li>Server-Sent Events (SSE)</li>";
echo "<li>JavaScript polling interval</li>";
echo "<li>Frontend notification display</li>";
echo "</ul>";

function createNotificationsAPI() {
    $apiContent = '<?php
require_once "../config/database.php";

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$action = $_GET["action"] ?? "";

if ($action == "get") {
    try {
        $pdo = getDatabaseConnection();
        
        $user_id = $_GET["user_id"] ?? $_SESSION["user_id"] ?? 0;
        
        if ($user_id <= 0) {
            echo json_encode(["success" => false, "message" => "User ID required"]);
            exit;
        }
        
        $query = "SELECT n.*, u.full_name as user_name 
                  FROM notifications n 
                  LEFT JOIN users u ON n.user_id = u.id 
                  WHERE n.user_id = :user_id 
                  ORDER BY n.created_at DESC LIMIT 50";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            "success" => true,
            "message" => "Notifications retrieved successfully",
            "data" => $notifications
        ]);
        
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid action"]);
}
?>';
    
    file_put_contents('api/notifications.php', $apiContent);
    echo "<p>✅ Đã tạo file api/notifications.php</p>";
}

echo "<hr>";
echo "<h3>6. Hướng dẫn kiểm tra frontend</h3>";
echo "<ol>";
echo "<li><strong>Kiểm tra API:</strong> <a href='api/notifications.php?action=get&user_id=4' target='_blank'>api/notifications.php?action=get&user_id=4</a></li>";
echo "<li><strong>Kiểm tra Admin:</strong> <a href='api/notifications.php?action=get&user_id=1' target='_blank'>api/notifications.php?action=get&user_id=1</a></li>";
echo "<li><strong>Kiểm tra JavaScript:</strong> Mở browser console (F12) và xem lỗi</li>";
echo "<li><strong>Kiểm tra Network:</strong> Xem tab Network trong F12 khi load trang</li>";
echo "<li><strong>Kiểm tra Real-time:</strong> Xem có WebSocket/SSE connection không</li>";
echo "</ol>";
?>
