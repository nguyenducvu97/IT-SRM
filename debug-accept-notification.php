<?php
require_once 'config/database.php';

echo "<h2>DEBUG CHUC NANG THONG BAO KHI STAFF NHAN YEU CAU</h2>";

// 1. Kiêm tra logic trong API khi staff accept request
echo "<h3>1. Kiêm tra API endpoint assign request</h3>";

// Tìm trong API service_requests.php logic assign request
$apiFile = 'api/service_requests.php';
$apiContent = file_get_contents($apiFile);

if (strpos($apiContent, 'assign') !== false) {
    echo "<p>Found assign logic in API</p>";
    
    // Tìm section assign
    if (preg_match('/elseif.*action.*==.*assign.*\{.*?\}/s', $apiContent, $matches)) {
        echo "<pre>" . htmlspecialchars($matches[0]) . "</pre>";
    }
} else {
    echo "<p>Không tìm logic assign trong API</p>";
}

// 2. Kiêm tra NotificationHelper có method notify khi staff accept không
echo "<h3>2. Kiêm tra NotificationHelper</h3>";

try {
    $pdo = getDatabaseConnection();
    
    // Kiêm tra request #83 - status và assigned_to
    $checkQuery = "SELECT id, title, status, assigned_to, user_id, created_at, assigned_at, accepted_at 
                   FROM service_requests WHERE id = 83";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute();
    $request = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($request) {
        echo "<h4>Thông tin request #83:</h4>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        foreach ($request as $key => $value) {
            echo "<tr><td>{$key}</td><td>" . ($value ?: 'NULL') . "</td></tr>";
        }
        echo "</table>";
        
        // Kiêm tra xem request có status 'in_progress' không (staff accept)
        if ($request['status'] == 'in_progress') {
            echo "<p>Request có status 'in_progress' - staff already accepted</p>";
        } else {
            echo "<p>Request status: {$request['status']} - staff hasn't accepted yet</p>";
        }
        
        // Kiêm tra assigned_to và accepted_at
        if ($request['assigned_to'] && $request['accepted_at']) {
            echo "<p>Request có assigned_to và accepted_at - staff has accepted</p>";
            
            // Kiêm tra trong notifications table
            echo "<h4>Kiêm tra notifications table:</h4>";
            $notifQuery = "SELECT * FROM notifications 
                          WHERE (type LIKE '%in_progress%' OR type LIKE '%accept%' OR type LIKE '%assign%') 
                          AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                          ORDER BY created_at DESC LIMIT 10";
            $notifStmt = $pdo->prepare($notifQuery);
            $notifStmt->execute();
            $notifications = $notifStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($notifications) > 0) {
                echo "<p>Tìm " . count($notifications) . " notifications liên quan:</p>";
                echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr><th>ID</th><th>User ID</th><th>Type</th><th>Message</th><th>Created At</th><th>Read</th></tr>";
                foreach ($notifications as $notif) {
                    echo "<tr>";
                    echo "<td>{$notif['id']}</td>";
                    echo "<td>{$notif['user_id']}</td>";
                    echo "<td>{$notif['type']}</td>";
                    echo "<td>" . htmlspecialchars($notif['message']) . "</td>";
                    echo "<td>{$notif['created_at']}</td>";
                    echo "<td>{$notif['is_read']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>Không tìm notifications nào trong 1 gio qua</p>";
            }
            
        } else {
            echo "<p>Request không có assigned_to và accepted_at - staff hasn't accepted</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>Lõi database: " . $e->getMessage() . "</p>";
}

// 3. Kiêm tra ServiceRequestNotificationHelper
echo "<h3>3. Kiêm tra ServiceRequestNotificationHelper</h3>";

$notificationHelperFile = 'lib/ServiceRequestNotificationHelper.php';
if (file_exists($notificationHelperFile)) {
    echo "<p>Found ServiceRequestNotificationHelper.php</p>";
    
    $helperContent = file_get_contents($notificationHelperFile);
    
    // Tìm các method liên quan
    $methods = [];
    if (preg_match_all('/public function (\w+)/', $helperContent, $matches)) {
        $methods = $matches[1];
    }
    
    echo "<h4>Các methods có trong NotificationHelper:</h4>";
    echo "<ul>";
    foreach ($methods as $method) {
        if (strpos($method, 'accept') !== false || strpos($method, 'progress') !== false || strpos($method, 'assign') !== false) {
            echo "<li style='color: green; font-weight: bold;'>{$method}</li>";
        } else {
            echo "<li>{$method}</li>";
        }
    }
    echo "</ul>";
    
    // Kiêm tra method notifyUserRequestInProgress
    if (in_array('notifyUserRequestInProgress', $methods)) {
        echo "<p>Found notifyUserRequestInProgress method - Good!</p>";
    } else {
        echo "<p>Missing notifyUserRequestInProgress method - Problem!</p>";
    }
    
} else {
    echo "<p>Không tìm ServiceRequestNotificationHelper.php</p>";
}

// 4. Test manual notification
echo "<h3>4. Test manual notification</h3>";

try {
    // Giá mô phông staff accept request
    $requestId = 83;
    $userId = 4; // User who created request
    $assignedTo = 2; // Staff who accepted
    
    echo "<p>Testing manual notification for request #83...</p>";
    
    // Kiêm tra xem có user và staff trong database không
    $userQuery = "SELECT id, full_name, role FROM users WHERE id IN (?, ?)";
    $userStmt = $pdo->prepare($userQuery);
    $userStmt->execute([$userId, $assignedTo]);
    $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Users involved:</h4>";
    foreach ($users as $user) {
        echo "<p>User ID {$user['id']}: {$user['full_name']} ({$user['role']})</p>";
    }
    
    // Tìm admin users
    $adminQuery = "SELECT id, full_name FROM users WHERE role = 'admin'";
    $adminStmt = $pdo->prepare($adminQuery);
    $adminStmt->execute();
    $admins = $adminStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Admin users:</h4>";
    foreach ($admins as $admin) {
        echo "<p>Admin ID {$admin['id']}: {$admin['full_name']}</p>";
    }
    
} catch (Exception $e) {
    echo "<p>Lõi test: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>5. Phân tích vân dê</h3>";
echo "<p><strong>Các vân dê có thê:</strong></p>";
echo "<ol>";
echo "<li>API assign request không gõi notification</li>";
echo "<li>NotificationHelper không có method phù hop</li>";
echo "<li>Logic notification sai user target</li>";
echo "<li>Database không luu notification</li>";
echo "<li>Frontend không hiên notification</li>";
echo "</ol>";

echo "<h3>6. Các kiêm tra tiêp theo:</h3>";
echo "<ul>";
echo "<li>Kiêm tra API assign request có gõi notification không</li>";
echo "<li>Kiêm tra NotificationHelper method</li>";
echo "<li>Test manual notification</li>";
echo "<li>Kiêm tra frontend notification display</li>";
echo "</ul>";
?>
