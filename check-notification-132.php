<?php
/**
 * Check notifications for request #132
 */

require_once 'config/database.php';

$db = getDatabaseConnection();

echo "<h2>🔍 KIỂM TRA THÔNG BÁO YÊU CẦU #132</h2>";
echo "<style>
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; font-weight: bold; }
</style>";

// Kiểm tra thông báo cho yêu cầu #132
echo "<h3>📋 Thông báo cho yêu cầu #132:</h3>";
$stmt = $db->prepare("
    SELECT n.*, u.full_name as user_name, u.role as user_role, u.email as user_email
    FROM notifications n 
    LEFT JOIN users u ON n.user_id = u.id 
    WHERE n.related_id = 132 AND n.related_type IN ('request', 'assignment')
    ORDER BY n.created_at DESC
");
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($notifications)) {
    echo "<table>";
    echo "<tr><th>ID</th><th>User</th><th>Role</th><th>Email</th><th>Title</th><th>Message</th><th>Type</th><th>Created</th></tr>";
    foreach ($notifications as $notif) {
        echo "<tr>";
        echo "<td>{$notif['id']}</td>";
        echo "<td>{$notif['user_name']}</td>";
        echo "<td>{$notif['user_role']}</td>";
        echo "<td>{$notif['user_email']}</td>";
        echo "<td>{$notif['title']}</td>";
        echo "<td>" . htmlspecialchars(substr($notif['message'], 0, 100)) . "...</td>";
        echo "<td>{$notif['type']}</td>";
        echo "<td>{$notif['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>❌ Không tìm thấy thông báo nào cho yêu cầu #132</p>";
}

// Kiểm tra chi tiết yêu cầu #132
echo "<h3>📄 Chi tiết yêu cầu #132:</h3>";
$stmt = $db->prepare("
    SELECT sr.*, u.full_name as assigned_staff_name, u.email as assigned_staff_email
    FROM service_requests sr
    LEFT JOIN users u ON sr.assigned_to = u.id
    WHERE sr.id = 132
");
$stmt->execute();
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if ($request) {
    echo "<table>";
    echo "<tr><th>ID</th><td>{$request['id']}</td></tr>";
    echo "<tr><th>Title</th><td>{$request['title']}</td></tr>";
    echo "<tr><th>Status</th><td><span style='color:blue;'>{$request['status']}</span></td></tr>";
    echo "<tr><th>User ID</th><td>{$request['user_id']}</td></tr>";
    echo "<tr><th>Assigned To</th><td>{$request['assigned_to']} ({$request['assigned_staff_name']})</td></tr>";
    echo "<tr><th>Created</th><td>{$request['created_at']}</td></tr>";
    echo "<tr><th>Updated</th><td>{$request['updated_at']}</td></tr>";
    echo "<tr><th>Accepted At</th><td>{$request['accepted_at']}</td></tr>";
    echo "</table>";
} else {
    echo "<p class='error'>❌ Không tìm thấy yêu cầu #132</p>";
}

// Kiểm tra tất cả thông báo được tạo trong ngày hôm nay
echo "<h3>📋 Tất cả thông báo hôm nay:</h3>";
$stmt = $db->prepare("
    SELECT n.*, u.full_name as user_name, u.role as user_role
    FROM notifications n 
    LEFT JOIN users u ON n.user_id = u.id 
    WHERE DATE(n.created_at) = CURDATE()
    ORDER BY n.created_at DESC
    LIMIT 20
");
$stmt->execute();
$todayNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($todayNotifications)) {
    echo "<table>";
    echo "<tr><th>ID</th><th>User</th><th>Role</th><th>Title</th><th>Related ID</th><th>Type</th><th>Created</th></tr>";
    foreach ($todayNotifications as $notif) {
        $highlight = ($notif['related_id'] == 132) ? "style='background-color: yellow;'" : "";
        echo "<tr $highlight>";
        echo "<td>{$notif['id']}</td>";
        echo "<td>{$notif['user_name']}</td>";
        echo "<td>{$notif['user_role']}</td>";
        echo "<td>{$notif['title']}</td>";
        echo "<td>{$notif['related_id']}</td>";
        echo "<td>{$notif['type']}</td>";
        echo "<td>{$notif['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>❌ Không tìm thấy thông báo nào hôm nay</p>";
}

// Kiểm tra user và admin để xác nhận nên nhận thông báo
if ($request) {
    echo "<h3>👥 Những người nên nhận thông báo:</h3>";
    
    // User tạo yêu cầu
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$request['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Admin users
    $stmt = $db->prepare("SELECT * FROM users WHERE role = 'admin'");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th><th>Role</th><th>Email</th><th>Nên nhận thông báo</th></tr>";
    
    if ($user) {
        echo "<tr class='success'>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['full_name']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>✅ User (người tạo yêu cầu)</td>";
        echo "</tr>";
    }
    
    foreach ($admins as $admin) {
        echo "<tr class='info'>";
        echo "<td>{$admin['id']}</td>";
        echo "<td>{$admin['full_name']}</td>";
        echo "<td>{$admin['role']}</td>";
        echo "<td>{$admin['email']}</td>";
        echo "<td>✅ Admin (quản lý hệ thống)</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

echo "<h3>🔍 Kết luận:</h3>";
if (!empty($notifications)) {
    echo "<p class='success'>✅ Thông báo đã được tạo cho yêu cầu #132</p>";
} else {
    echo "<p class='error'>❌ KHÔNG có thông báo nào được tạo cho yêu cầu #132</p>";
    echo "<p><strong>Vấn đề có thể là:</strong></p>";
    echo "<ul>";
    echo "<li>Debug logging chưa được kích hoạt khi staff nhận yêu cầu</li>";
    echo "<li>Endpoint accept_request không được gọi đúng cách</li>";
    echo "<li>Có lỗi xảy ra trong quá trình tạo thông báo</li>";
    echo "<li>Browser cache đang load version cũ của JavaScript</li>";
    echo "</ul>";
}
?>
