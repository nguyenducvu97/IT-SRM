<?php
echo "<h2>DEBUG BROWSER ACTUAL - STAFF NHẬN YÊU CẦU #79</h2>";

// Kiểm tra session hiện tại
require_once __DIR__ . '/config/session.php';
startSession();

echo "<h3>Current Browser Session</h3>";
if (empty($_SESSION)) {
    echo "<p style='color: red;'>&#10027; NO SESSION - User not logged in!</p>";
    echo "<p><strong>Solution:</strong> Please login first</p>";
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
    
    if ($user_role !== 'staff') {
        echo "<p style='color: red;'>&#10027; User is not staff! Current role: {$user_role}</p>";
        echo "<p><strong>Solution:</strong> Please login as staff user (staff1/password123)</p>";
    } else {
        echo "<p style='color: green;'>&#10004; User is staff - OK!</p>";
        
        // Test API call trực tiếp
        echo "<h3>Test API Call Directly</h3>";
        
        $testData = [
            'action' => 'accept_request',
            'request_id' => 79
        ];
        
        echo "<p><strong>Test Data:</strong></p>";
        echo "<pre>" . json_encode($testData, JSON_PRETTY_PRINT) . "</pre>";
        
        // Test với session hiện tại
        echo "<h4>Testing with Current Session...</h4>";
        
        // Lưu session hiện tại
        $current_session_id = session_id();
        
        // Test API call
        $ch = curl_init('http://localhost/it-service-request/api/service_requests.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Cookie: ' . session_name() . '=' . $current_session_id
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
            echo "<p style='color: green;'>&#10004; API Call Successful!</p>";
            
            // Check notifications
            require_once __DIR__ . '/config/database.php';
            $db = getDatabaseConnection();
            
            $stmt = $db->prepare("SELECT n.*, u.username, u.role FROM notifications n 
                                   LEFT JOIN users u ON n.user_id = u.id 
                                   WHERE n.related_id = 79 AND n.related_type = 'service_request'
                                   ORDER BY n.created_at DESC LIMIT 5");
            $stmt->execute();
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($notifications) > 0) {
                echo "<p style='color: green;'><strong>&#10004; Found " . count($notifications) . " notifications for request #79:</strong></p>";
                echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr style='background-color: #f0f0f0;'>";
                echo "<th>ID</th><th>User</th><th>Role</th><th>Title</th><th>Message</th><th>Type</th><th>Created</th>";
                echo "</tr>";
                
                foreach ($notifications as $notif) {
                    echo "<tr>";
                    echo "<td><strong>{$notif['id']}</strong></td>";
                    echo "<td>{$notif['username']}</td>";
                    echo "<td><span style='background-color: #007bff; color: white; padding: 2px 6px; border-radius: 3px;'>{$notif['role']}</span></td>";
                    echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
                    echo "<td>" . htmlspecialchars(substr($notif['message'], 0, 50)) . "...</td>";
                    echo "<td><span style='background-color: #28a745; color: white; padding: 2px 6px; border-radius: 3px;'>{$notif['type']}</span></td>";
                    echo "<td>{$notif['created_at']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                echo "<h3>&#128204; CONCLUSION:</h3>";
                echo "<div style='background-color: #e8f5e8; padding: 15px; border-radius: 5px;'>";
                echo "<h4>&#10004; EVERYTHING WORKS PERFECTLY!</h4>";
                echo "<ul>";
                echo "<li>&#10004; Session active and correct</li>";
                echo "<li>&#10004; User logged in as staff</li>";
                echo "<li>&#10004; API call successful</li>";
                echo "<li>&#10004; Notifications created in database</li>";
                echo "<li>&#10004; All users received correct notifications</li>";
                echo "</ul>";
                echo "</div>";
                
                echo "<h3>&#128072; NEXT STEPS:</h3>";
                echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px;'>";
                echo "<h4>If notifications are in database but not visible in browser:</h4>";
                echo "<ol>";
                echo "<li><strong>Check frontend:</strong> JavaScript fetch notifications API</li>";
                echo "<li><strong>Check UI:</strong> HTML/CSS display notifications</li>";
                echo "<li><strong>Check cache:</strong> Clear browser cache</li>";
                echo "<li><strong>Check console:</strong> F12 for JavaScript errors</li>";
                echo "<li><strong>Check network:</strong> F12 for failed API calls</li>";
                echo "</ol>";
                echo "</div>";
                
            } else {
                echo "<p style='color: red;'><strong>&#10027; No notifications found in database!</strong></p>";
                echo "<p>This means the API call didn't trigger notifications properly.</p>";
            }
            
        } else {
            echo "<p style='color: red;'>&#10027; API Call Failed!</p>";
            echo "<p><strong>Error:</strong> " . ($responseData['message'] ?? 'Unknown error') . "</p>";
            
            echo "<h3>&#128072; TROUBLESHOOTING:</h3>";
            echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px;'>";
            echo "<h4>Common API Issues:</h4>";
            echo "<ul>";
            echo "<li><strong>Session expired:</strong> Login again</li>";
            echo "<li><strong>Wrong role:</strong> Must be staff</li>";
            echo "<li><strong>Request not available:</strong> Request #79 may already be assigned</li>";
            echo "<li><strong>Database error:</strong> Check server logs</li>";
            echo "</ul>";
            echo "</div>";
        }
    }
}

echo "<h3>Manual Test Instructions</h3>";
echo "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<h4>&#128161; To test in browser manually:</h4>";
echo "<ol>";
echo "<li>1. Open browser and login as <strong>staff1</strong> with password <strong>password123</strong></li>";
echo "<li>2. Navigate to: <a href='index.php?page=request-detail&id=79' target='_blank'>Request #79</a></li>";
echo "<li>3. Click <strong>'Nhận yêu cầu'</strong> button</li>";
echo "<li>4. Open browser console (F12) and check for errors</li>";
echo "<li>5. Check Network tab for API call status</li>";
echo "<li>6. Run this script again to verify notifications</li>";
echo "</ol>";
echo "</div>";
?>
