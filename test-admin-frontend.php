<?php
echo "<h2>TEST ADMIN FRONTEND NOTIFICATIONS</h2>";

require_once __DIR__ . '/config/database.php';
$db = getDatabaseConnection();

echo "<h3>1. KIÊM TRA ADMIN LOGIN STATUS</h3>";

// Start session
require_once __DIR__ . '/config/session.php';
startSession();

if (empty($_SESSION)) {
    echo "<p style='color: red;'>&#10027; NO SESSION - Admin not logged in!</p>";
    echo "<p><strong>Solution:</strong> Please login as admin first</p>";
} else {
    echo "<p style='color: green;'>&#10004; Session data found:</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Key</th><th>Value</th></tr>";
    foreach ($_SESSION as $key => $value) {
        echo "<tr><td>{$key}</td><td>" . (is_array($value) ? json_encode($value) : htmlspecialchars($value)) . "</td></tr>";
    }
    echo "</table>";
    
    $user_role = $_SESSION['role'] ?? '';
    $user_id = $_SESSION['user_id'] ?? 0;
    
    echo "<h3>User Role Check</h3>";
    echo "<p><strong>Role:</strong> {$user_role}</p>";
    echo "<p><strong>User ID:</strong> {$user_id}</p>";
    
    if ($user_role !== 'admin') {
        echo "<p style='color: red;'>&#10027; User is not admin! Current role: {$user_role}</p>";
        echo "<p><strong>Solution:</strong> Please login as admin user (admin/password123)</p>";
    } else {
        echo "<p style='color: green;'>&#10004; User is admin - OK!</p>";
        
        echo "<h3>2. KIÊM TRA ADMIN NOTIFICATIONS TRONG DATABASE</h3>";
        
        try {
            $stmt = $db->prepare("SELECT n.*, u.username, u.role FROM notifications n 
                                   LEFT JOIN users u ON n.user_id = u.id 
                                   WHERE u.role = 'admin'
                                   ORDER BY n.created_at DESC LIMIT 10");
            $stmt->execute();
            $adminNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($adminNotifications) > 0) {
                echo "<p style='color: green;'><strong>&#10004; Found " . count($adminNotifications) . " notifications for admin in database:</strong></p>";
                echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr style='background-color: #f0f0f0;'>";
                echo "<th>ID</th><th>User</th><th>Title</th><th>Message</th><th>Type</th><th>Created</th><th>Read</th>";
                echo "</tr>";
                
                foreach ($adminNotifications as $notif) {
                    echo "<tr>";
                    echo "<td><strong>{$notif['id']}</strong></td>";
                    echo "<td>{$notif['username']}</td>";
                    echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
                    echo "<td>" . htmlspecialchars(substr($notif['message'], 0, 50)) . "...</td>";
                    echo "<td><span style='background-color: #28a745; color: white; padding: 2px 6px; border-radius: 3px;'>{$notif['type']}</span></td>";
                    echo "<td>{$notif['created_at']}</td>";
                    echo "<td>" . ($notif['is_read'] ? 'Yes' : 'No') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                echo "<h3>3. KIÊM TRA NOTIFICATIONS API</h3>";
                
                // Test notifications API
                echo "<h4>Testing notifications API...</h4>";
                
                $ch = curl_init('http://localhost/it-service-request/api/notifications.php');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Cookie: ' . session_name() . '=' . session_id()
                ]);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);
                
                echo "<h4>API Response:</h4>";
                echo "<p><strong>HTTP Status:</strong> {$httpCode}</p>";
                if ($error) {
                    echo "<p><strong>CURL Error:</strong> {$error}</p>";
                }
                echo "<pre>" . htmlspecialchars($response) . "</pre>";
                
                // Parse response
                $responseData = json_decode($response, true);
                echo "<h4>Parsed Response:</h4>";
                echo "<pre>" . print_r($responseData, true) . "</pre>";
                
                if ($responseData && isset($responseData['success']) && $responseData['success']) {
                    echo "<p style='color: green;'>&#10004; Notifications API working!</p>";
                    
                    if (isset($responseData['notifications']) && count($responseData['notifications']) > 0) {
                        echo "<p style='color: green;'><strong>&#10004; API returned " . count($responseData['notifications']) . " notifications:</strong></p>";
                        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
                        echo "<tr style='background-color: #f0f0f0;'>";
                        echo "<th>ID</th><th>Title</th><th>Message</th><th>Type</th><th>Created</th>";
                        echo "</tr>";
                        
                        foreach ($responseData['notifications'] as $notif) {
                            echo "<tr>";
                            echo "<td><strong>{$notif['id']}</strong></td>";
                            echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
                            echo "<td>" . htmlspecialchars(substr($notif['message'], 0, 50)) . "...</td>";
                            echo "<td><span style='background-color: #28a745; color: white; padding: 2px 6px; border-radius: 3px;'>{$notif['type']}</span></td>";
                            echo "<td>{$notif['created_at']}</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                        
                        echo "<h3>4. KIÊM TRA FRONTEND DISPLAY</h3>";
                        echo "<div style='background-color: #e8f5e8; padding: 15px; border-radius: 5px;'>";
                        echo "<h4>&#128204; ANALYSIS:</h4>";
                        echo "<p><strong>Database:</strong> &#10004; Admin has notifications</p>";
                        echo "<p><strong>API:</strong> &#10004; Notifications API working</p>";
                        echo "<p><strong>Session:</strong> &#10004; Admin logged in</p>";
                        echo "<p><strong>Issue:</strong> Frontend not displaying notifications</p>";
                        echo "</div>";
                        
                        echo "<h3>5. FRONTEND TROUBLESHOOTING</h3>";
                        echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px;'>";
                        echo "<h4>&#128072; Check in browser:</h4>";
                        echo "<ol>";
                        echo "<li><strong>Open browser:</strong> Login as admin (admin/password123)</li>";
                        echo "<li><strong>Open console:</strong> F12 -> Console</li>";
                        echo "<li><strong>Check for errors:</strong> Look for JavaScript errors</li>";
                        echo "<li><strong>Check network:</strong> F12 -> Network -> Look for notifications API call</li>";
                        echo "<li><strong>Check UI:</strong> Look for notification bell/icon</li>";
                        echo "<li><strong>Check dropdown:</strong> Click notification bell to see dropdown</li>";
                        echo "</ol>";
                        echo "</div>";
                        
                    } else {
                        echo "<p style='color: orange;'>&#9888; API returned success but no notifications!</p>";
                        echo "<p>API may have filtering issue or user ID mismatch.</p>";
                    }
                    
                } else {
                    echo "<p style='color: red;'>&#10027; Notifications API failed!</p>";
                    echo "<p><strong>Error:</strong> " . ($responseData['message'] ?? 'Unknown error') . "</p>";
                }
                
            } else {
                echo "<p style='color: red;'><strong>&#10027; No notifications found for admin in database!</strong></p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>Error checking admin notifications: " . $e->getMessage() . "</p>";
        }
    }
}

echo "<h3>6. MANUAL TEST INSTRUCTIONS</h3>";
echo "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<h4>&#128161; To test admin notifications in browser:</h4>";
echo "<ol>";
echo "<li>1. Open browser and login as <strong>admin</strong> with password <strong>password123</strong></li>";
echo "<li>2. Look for notification bell/icon in the header</li>";
echo "<li>3. Click the notification bell to see dropdown</li>";
echo "<li>4. Check if notifications are displayed</li>";
echo "<li>5. Open browser console (F12) and check for errors</li>";
echo "<li>6. Check Network tab for notifications API call</li>";
echo "<li>7. If no notifications, run this script again to verify</li>";
echo "</ol>";
echo "</div>";

echo "<h3>7. EXPECTED RESULTS</h3>";
echo "<div style='background-color: #e8f5e8; padding: 15px; border-radius: 5px;'>";
echo "<h4>&#10004; What admin should see:</h4>";
echo "<ul>";
echo "<li>Notification bell with count badge</li>";
echo "<li>Dropdown with recent notifications</li>";
echo "<li>Notifications like: 'Thay changed yêu request', 'Yêu request new', etc.</li>";
echo "<li>Click to view details or mark as read</li>";
echo "</ul>";
echo "</div>";
?>
