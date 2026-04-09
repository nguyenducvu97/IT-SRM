<?php
echo "<h2>KIỂM TRA USER CỦA REQUEST #78</h2>";

require_once __DIR__ . '/config/database.php';
$db = getDatabaseConnection();

try {
    // Check request #78 details
    $stmt = $db->prepare("SELECT sr.*, u.username, u.role FROM service_requests sr 
                           LEFT JOIN users u ON sr.user_id = u.id 
                           WHERE sr.id = 78");
    $stmt->execute();
    $request78 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($request78) {
        echo "<h3>Request #78 Details:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Request ID</th><td>{$request78['id']}</td></tr>";
        echo "<tr><th>Title</th><td>" . htmlspecialchars($request78['title']) . "</td></tr>";
        echo "<tr><th>User ID</th><td>{$request78['user_id']}</td></tr>";
        echo "<tr><th>Username</th><td>{$request78['username']}</td></tr>";
        echo "<tr><th>User Role</th><td><span style='background-color: #007bff; color: white; padding: 2px 6px; border-radius: 3px;'>{$request78['role']}</span></td></tr>";
        echo "<tr><th>Status</th><td>{$request78['status']}</td></tr>";
        echo "<tr><th>Assigned To</th><td>{$request78['assigned_to']}</td></tr>";
        echo "</table>";
        
        if ($request78['role'] === 'admin') {
            echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px;'>";
            echo "<h3>&#9888; VẤN ĐỀ TÌM THẤY!</h3>";
            echo "<p><strong>Request #78 được tạo bởi admin (ID 1), không phải user!</strong></p>";
            echo "<p>Đó là lý do tại sao:</p>";
            echo "<ul>";
            echo "<li>Admin nhận được notification 'Yêu cầu đang được xử lý' (vì admin là người tạo)</li>";
            echo "<li>User thật không nhận được notification (vì không phải người tạo)</li>";
            echo "<li>Logic notification hoạt động ĐÚNG</li>";
            echo "</ul>";
            echo "</div>";
            
            echo "<h3>Solution:</h3>";
            echo "<p>Cần tạo request bởi user thật để test:</p>";
            
            // Tìm một user thật
            $stmt = $db->prepare("SELECT id, username, full_name FROM users WHERE role = 'user' LIMIT 1");
            $stmt->execute();
            $realUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($realUser) {
                echo "<p><strong>User thật trong hệ thống:</strong></p>";
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>ID</th><td>{$realUser['id']}</td></tr>";
                echo "<tr><th>Username</th><td>{$realUser['username']}</td></tr>";
                echo "<tr><th>Full Name</th><td>{$realUser['full_name']}</td></tr>";
                echo "</table>";
                
                // Tạo request mới bởi user thật
                echo "<h3>Tạo request mới bởi user thật:</h3>";
                
                $insert_query = "INSERT INTO service_requests (user_id, title, description, category_id, priority, status, created_at) 
                                VALUES (?, ?, ?, ?, ?, ?, NOW())";
                
                $stmt = $db->prepare($insert_query);
                $result = $stmt->execute([
                    $realUser['id'], // user_id (user thật)
                    'Test Request by Real User ' . date('Y-m-d H:i:s'),
                    'Đây là request được tạo bởi user thật để test notifications. Khi staff nhận request này, user phải nhận được thông báo.',
                    1, // category_id
                    'medium', // priority
                    'open' // status
                ]);
                
                if ($result) {
                    $new_request_id = $db->lastInsertId();
                    echo "<p style='color: green;'>&#10004; Test request created by real user!</p>";
                    echo "<p><strong>New Request ID:</strong> {$new_request_id}</p>";
                    echo "<p><strong>Created by:</strong> {$realUser['username']} (user)</p>";
                    
                    echo "<h3>Test Steps:</h3>";
                    echo "<ol>";
                    echo "<li>1. Login as <strong>staff1</strong> with password <strong>password123</strong></li>";
                    echo "<li>2. Navigate to: <a href='index.php?page=request-detail&id={$new_request_id}' target='_blank'>Request #{$new_request_id}</a></li>";
                    echo "<li>3. Click <strong>'Nhận yêu cầu'</strong> button</li>";
                    echo "<li>4. Check notifications:</li>";
                    echo "<ul>";
                    echo "<li>User ({$realUser['username']}) should receive: 'Yêu cầu đang được xử lý'</li>";
                    echo "<li>Admin should receive: 'Thay đổi trạng thái yêu cầu'</li>";
                    echo "<li>Other staff should receive: 'Yêu cầu được Admin phê duyệt'</li>";
                    echo "</ul>";
                    echo "<li>5. Verify in database: <a href='check-notifications-database.php' target='_blank'>Check Notifications</a></li>";
                    echo "</ol>";
                    
                } else {
                    echo "<p style='color: red;'>&#10027; Failed to create test request</p>";
                }
            } else {
                echo "<p style='color: red;'>&#10027; No real users found in system</p>";
            }
            
        } else {
            echo "<p style='color: green;'>&#10004; Request #78 được tạo bởi user - OK!</p>";
        }
        
    } else {
        echo "<p style='color: red;'>&#10027; Request #78 not found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<h3>Check All Users in System:</h3>";

try {
    $stmt = $db->prepare("SELECT id, username, full_name, role FROM users ORDER BY role, username");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th><th>Username</th><th>Full Name</th><th>Role</th>";
        echo "</tr>";
        
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
            echo "<td><span style='background-color: #007bff; color: white; padding: 2px 6px; border-radius: 3px;'>{$user['role']}</span></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>No users found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking users: " . $e->getMessage() . "</p>";
}
?>
