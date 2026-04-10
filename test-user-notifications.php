<?php
require_once 'config/database.php';

echo "<h2>TEST USER NOTIFICATIONS</h2>";

try {
    $pdo = getDatabaseConnection();
    
    // 1. Kiêm tra notifications cho user 4
    echo "<h3>1. Kiêm tra notifications cho user 4 (Nguyêñ Ðúç Vû)</h3>";
    
    $userQuery = "SELECT n.*, u.full_name 
                 FROM notifications n 
                 LEFT JOIN users u ON n.user_id = u.id 
                 WHERE n.user_id = 4 
                 ORDER BY n.created_at DESC LIMIT 10";
    $userStmt = $pdo->prepare($userQuery);
    $userStmt->execute();
    $userNotifications = $userStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($userNotifications) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Message</th><th>Type</th><th>Created At</th><th>Is Read</th></tr>";
        foreach ($userNotifications as $notif) {
            echo "<tr>";
            echo "<td>{$notif['id']}</td>";
            echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
            echo "<td>" . htmlspecialchars($notif['message']) . "</td>";
            echo "<td>{$notif['type']}</td>";
            echo "<td>{$notif['created_at']}</td>";
            echo "<td>{$notif['is_read']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Không có notifications cho user 4</p>";
    }
    
    // 2. Tìm notifications cho request #83
    echo "<h3>2. Kiêm tra notifications cho request #83</h3>";
    
    $requestQuery = "SELECT n.*, u.full_name as user_name 
                     FROM notifications n 
                     LEFT JOIN users u ON n.user_id = u.id 
                     WHERE (n.related_id = 83 AND n.related_type = 'service_request')
                     OR (n.message LIKE '%#83%')
                     ORDER BY n.created_at DESC LIMIT 10";
    $requestStmt = $pdo->prepare($requestQuery);
    $requestStmt->execute();
    $requestNotifications = $requestStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($requestNotifications) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>User</th><th>Title</th><th>Message</th><th>Type</th><th>Created At</th></tr>";
        foreach ($requestNotifications as $notif) {
            echo "<tr>";
            echo "<td>{$notif['id']}</td>";
            echo "<td>{$notif['user_name']} (ID: {$notif['user_id']})</td>";
            echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
            echo "<td>" . htmlspecialchars($notif['message']) . "</td>";
            echo "<td>{$notif['type']}</td>";
            echo "<td>{$notif['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Không có notifications cho request #83</p>";
    }
    
    // 3. Tìm notifications do staff accept request
    echo "<h3>3. Kiêm tra notifications do staff accept request</h3>";
    
    $acceptQuery = "SELECT n.*, u.full_name as user_name 
                    FROM notifications n 
                    LEFT JOIN users u ON n.user_id = u.id 
                    WHERE (n.message LIKE '%tiêp nhãn%' OR n.message LIKE '%in_progress%' OR n.message LIKE '%John Smith%')
                    AND n.created_at >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
                    ORDER BY n.created_at DESC LIMIT 10";
    $acceptStmt = $pdo->prepare($acceptQuery);
    $acceptStmt->execute();
    $acceptNotifications = $acceptStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($acceptNotifications) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>User</th><th>Message</th><th>Created At</th></tr>";
        foreach ($acceptNotifications as $notif) {
            echo "<tr>";
            echo "<td>{$notif['id']}</td>";
            echo "<td>{$notif['user_name']} (ID: {$notif['user_id']})</td>";
            echo "<td>" . htmlspecialchars($notif['message']) . "</td>";
            echo "<td>{$notif['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Không có notifications do staff accept request</p>";
    }
    
    // 4. Kiêm tra xem có notifications cho user 4 không có trong 1 gio qua
    echo "<h3>4. Kiêm tra notifications cho user 4 trong 1 gio qua</h3>";
    
    $recentQuery = "SELECT n.*, u.full_name 
                    FROM notifications n 
                    LEFT JOIN users u ON n.user_id = u.id 
                    WHERE n.user_id = 4 
                    AND n.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                    ORDER BY n.created_at DESC LIMIT 10";
    $recentStmt = $pdo->prepare($recentQuery);
    $recentStmt->execute();
    $recentNotifications = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($recentNotifications) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Message</th><th>Created At</th></tr>";
        foreach ($recentNotifications as $notif) {
            echo "<tr>";
            echo "<td>{$notif['id']}</td>";
            echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
            echo "<td>" . htmlspecialchars($notif['message']) . "</td>";
            echo "<td>{$notif['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Không có notifications cho user 4 trong 1 gio qua</p>";
        echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h4>Vân dê:</h4>";
        echo "<p>User 4 không có notifications trong 1 gio qua</p>";
        echo "<p>Có thê:</p>";
        echo "<ul>";
        echo "<li>NotificationHelper không gõi notification cho user 4</li>";
        echo "<li>Staff accept request không trigger notification cho user</li>";
        echo "<li>NotificationHelper.notifyUserRequestInProgress() không chay</li>";
        echo "</ul>";
        echo "</div>";
    }
    
    // 5. Test API call cho user 4
    echo "<h3>5. Test API call cho user 4</h3>";
    
    // Simulate session for user 4
    $_SESSION['user_id'] = 4;
    $_SESSION['full_name'] = 'Nguyêñ Ðúç Vû';
    
    echo "<p>Testing: <code>api/notifications.php?action=get</code> cho user 4</p>";
    
    try {
        ob_start();
        include 'api/notifications.php';
        $apiResponse = ob_get_clean();
        
        echo "<h5>API Response:</h5>";
        echo "<pre>" . htmlspecialchars($apiResponse) . "</pre>";
        
        $data = json_decode($apiResponse, true);
        if ($data && isset($data['success'])) {
            echo "<p>API success: " . ($data['success'] ? 'true' : 'false') . "</p>";
            if (isset($data['data']) && is_array($data['data'])) {
                echo "<p>Sô notifications: " . count($data['data']) . "</p>";
                if (count($data['data']) > 0) {
                    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                    echo "<tr><th>ID</th><th>Message</th><th>Type</th><th>Is Read</th></tr>";
                    foreach ($data['data'] as $notif) {
                        echo "<tr>";
                        echo "<td>{$notif['id']}</td>";
                        echo "<td>" . htmlspecialchars($notif['message']) . "</td>";
                        echo "<td>{$notif['type']}</td>";
                        echo "<td>" . ($notif['is_read'] ? 'Yes' : 'No') . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
            }
        } else {
            echo "<p>API response không có format chuân</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>Lõi API: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>Lõi database: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>6. Tóm tát vân dê</h3>";
echo "<p><strong>Vân dê chính:</strong></p>";
echo "<ol>";
echo "<li>API response format sai (ã fix)</li>";
echo "<li>User 4 không có notifications trong 1 gio qua</li>";
echo "<li>Staff accept request không gõi notification cho user</li>";
echo "</ol>";

echo "<p><strong>Giãi pháp:</strong></p>";
echo "<ol>";
echo "<li>Test API format ã fix</li>";
echo "<li>Kiêm tra NotificationHelper.notifyUserRequestInProgress()</li>";
echo "<li>Manual test notification cho user 4</li>";
echo "<li>Kiêm tra frontend notification display</li>";
echo "</ol>";
?>
