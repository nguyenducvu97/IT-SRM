<?php
require_once 'config/database.php';

echo "<h2>FIX STATUS CHO REQUEST #83</h2>";

try {
    $pdo = getDatabaseConnection();
    
    // Kiêm tra status hiên tai
    $checkQuery = "SELECT id, title, status, assigned_to, accepted_at, created_at FROM service_requests WHERE id = 83";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute();
    $request = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Trang thái hiên tai:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    foreach ($request as $key => $value) {
        echo "<tr><td>{$key}</td><td>" . ($value ?: 'NULL') . "</td></tr>";
    }
    echo "</table>";
    
    if ($request['status'] == 'request_support') {
        echo "<h3>Phân tích:</h3>";
        echo "<p>Request có status 'request_support' thay vì 'in_progress'</p>";
        echo "<p>-> Staff không 'accept' request mà 'yêu câu hõ trõ'</p>";
        echo "<p>-> Không trigger notification 'staff accept request'</p>";
        
        echo "<h3>Giãi pháp:</h3>";
        echo "<ol>";
        echo "<li><strong>Option 1:</strong> Update status thành 'in_progress' và gõi notification</li>";
        echo "<li><strong>Option 2:</strong> Gõi notification cho 'request_support' status</li>";
        echo "<li><strong>Option 3:</strong> Kiêm tra xem staff có accept request không</li>";
        echo "</ol>";
        
        // Option 1: Fix status và gõi notification
        echo "<h4>Option 1: Fix status thành 'in_progress'</h4>";
        
        $updateQuery = "UPDATE service_requests SET status = 'in_progress' WHERE id = 83";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->execute();
        
        echo "<p>Updated status thành 'in_progress'</p>";
        
        // Gõi notification manual
        echo "<h4>Gõi notification manual:</h4>";
        
        try {
            require_once 'lib/ServiceRequestNotificationHelper.php';
            $notificationHelper = new ServiceRequestNotificationHelper();
            
            // Notify user
            echo "<p>Gõi notification cho user...</p>";
            $userResult = $notificationHelper->notifyUserRequestInProgress(
                83, 
                4, // user_id
                'John Smith' // assigned_name
            );
            echo "<p>User notification: " . ($userResult ? 'SUCCESS' : 'FAILED') . "</p>";
            
            // Notify admin
            echo "<p>Gõi notification cho admin...</p>";
            $adminResult = $notificationHelper->notifyAdminStatusChange(
                83, 
                'open', 
                'in_progress', 
                'John Smith', 
                'testttt hõ trõ'
            );
            echo "<p>Admin notification: " . ($adminResult ? 'SUCCESS' : 'FAILED') . "</p>";
            
            echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "<h4>Hoàn thành!</h4>";
            echo "<p>Request #83 status: in_progress</p>";
            echo "<p>Notifications: User + Admin</p>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<p>Lõi notification: " . $e->getMessage() . "</p>";
        }
        
        // Kiêm tra notifications table
        echo "<h4>Kiêm tra notifications table:</h4>";
        $notifQuery = "SELECT * FROM notifications 
                      WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                      ORDER BY created_at DESC LIMIT 10";
        $notifStmt = $pdo->prepare($notifQuery);
        $notifStmt->execute();
        $notifications = $notifStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($notifications) > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>User ID</th><th>Type</th><th>Message</th><th>Created At</th></tr>";
            foreach ($notifications as $notif) {
                echo "<tr>";
                echo "<td>{$notif['id']}</td>";
                echo "<td>{$notif['user_id']}</td>";
                echo "<td>{$notif['type']}</td>";
                echo "<td>" . htmlspecialchars($notif['message']) . "</td>";
                echo "<td>{$notif['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>Không tìm notifications nào</p>";
        }
        
    } else {
        echo "<p>Request không có status 'request_support'</p>";
    }
    
} catch (Exception $e) {
    echo "<p>Lõi: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h4>Kiêm tra frontend:</h4>";
echo "<ol>";
echo "<li>Refresh browser tai trang notifications</li>";
echo "<li>Kiêm tra user và admin có notification mõi không</li>";
echo "<li>Nêu có -> Thành công!</li>";
echo "<li>Nêu không -> Kiêm tra frontend notification display</li>";
echo "</ol>";
?>
