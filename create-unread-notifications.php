<?php
// Create some unread notifications for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Create Unread Notifications for Testing</h2>";

try {
    $conn = new PDO("mysql:host=localhost;dbname=it_service_request", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Database connected</p>";
    
    // Get admin user
    $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "<p>Admin user: " . htmlspecialchars($admin['full_name']) . " (ID: " . $admin['id'] . ")</p>";
        
        // Create 3 unread notifications
        $unreadNotifications = [
            [
                'title' => 'Yêu cầu mới cần xử lý #' . rand(1000, 9999),
                'message' => 'Người dùng đã tạo yêu cầu mới về sự cố mạng. Vui lòng kiểm tra và xử lý.',
                'type' => 'info',
                'related_id' => rand(1, 100),
                'related_type' => 'request'
            ],
            [
                'title' => 'Bình luận mới từ customer #' . rand(1000, 9999),
                'message' => 'Customer đã bình luận về yêu cầu hỗ trợ của họ.',
                'type' => 'info',
                'related_id' => rand(1, 100),
                'related_type' => 'comment'
            ],
            [
                'title' => 'Yêu cầu đã được giải quyết #' . rand(1000, 9999),
                'message' => 'Staff đã giải quyết thành công yêu cầu về lỗi phần mềm.',
                'type' => 'success',
                'related_id' => rand(1, 100),
                'related_type' => 'request'
            ]
        ];
        
        foreach ($unreadNotifications as $notif) {
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, title, message, type, related_id, related_type, is_read)
                VALUES (?, ?, ?, ?, ?, ?, FALSE)
            ");
            $stmt->execute([
                $admin['id'],
                $notif['title'],
                $notif['message'],
                $notif['type'],
                $notif['related_id'],
                $notif['related_type']
            ]);
            
            echo "<p style='color: green;'>✅ Created unread: " . htmlspecialchars($notif['title']) . "</p>";
        }
        
        // Check current counts
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ?");
        $stmt->execute([$admin['id']]);
        $total = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $conn->prepare("SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = FALSE");
        $stmt->execute([$admin['id']]);
        $unread = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h3>Current Status:</h3>";
        echo "<p>Total notifications: " . $total['total'] . "</p>";
        echo "<p>Unread notifications: " . $unread['unread'] . "</p>";
        
        // Test API
        echo "<h3>Testing API:</h3>";
        
        session_start();
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['username'] = 'admin';
        $_SESSION['role'] = 'admin';
        
        // Change to api directory
        $old_dir = getcwd();
        chdir(__DIR__ . '/api');
        
        // Test count endpoint
        $_GET['action'] = 'count';
        ob_start();
        include 'notifications.php';
        $countOutput = ob_get_clean();
        
        echo "<h4>Count API Response:</h4>";
        echo "<pre>" . htmlspecialchars($countOutput) . "</pre>";
        
        chdir($old_dir);
        
        echo "<h3 style='color: green;'>✅ Unread notifications created!</h3>";
        echo "<p><strong>Next steps:</strong></p>";
        echo "<ol>";
        echo "<li><a href='index.html'>Go to IT Service Request System</a></li>";
        echo "<li>Login with admin account</li>";
        echo "<li>Check notification bell - should show count: " . $unread['unread'] . "</li>";
        echo "</ol>";
        
    } else {
        echo "<p style='color: red;'>❌ No admin user found</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
}
?>
