<?php
echo "<h2>Test Accept Request API</h2>";

// Simulate accept_request API call
echo "<h3>Testing accept_request API endpoint directly</h3>";

// Create a test session (simulate staff login)
session_start();
$_SESSION['user_id'] = 2; // staff1
$_SESSION['username'] = 'staff1';
$_SESSION['role'] = 'staff';

// Test data
$testData = [
    'action' => 'accept_request',
    'request_id' => 72 // Open request from debug output
];

echo "<p><strong>Test Data:</strong></p>";
echo "<pre>" . json_encode($testData, JSON_PRETTY_PRINT) . "</pre>";

// Make API call
$ch = curl_init('http://localhost/it-service-request/api/service_requests.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: ' . session_name() . '=' . session_id()
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<h3>API Response</h3>";
echo "<p><strong>HTTP Status:</strong> {$httpCode}</p>";
if ($error) {
    echo "<p><strong>CURL Error:</strong> {$error}</p>";
}
echo "<p><strong>Response:</strong></p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Parse JSON response
$responseData = json_decode($response, true);
echo "<p><strong>Parsed Response:</strong></p>";
echo "<pre>" . print_r($responseData, true) . "</pre>";

// Check if request was actually updated
echo "<h3>Check Database After API Call</h3>";
require_once __DIR__ . '/config/database.php';
$db = getDatabaseConnection();

try {
    $stmt = $db->prepare("SELECT id, title, status, assigned_to FROM service_requests WHERE id = ?");
    $stmt->execute([72]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($request) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Assigned To</th></tr>";
        echo "<tr>";
        echo "<td>{$request['id']}</td>";
        echo "<td>" . htmlspecialchars($request['title']) . "</td>";
        echo "<td><strong>{$request['status']}</strong></td>";
        echo "<td>{$request['assigned_to']}</td>";
        echo "</tr>";
        echo "</table>";
        
        if ($request['status'] === 'in_progress' && $request['assigned_to'] == 2) {
            echo "<p style='color: green;'>&#10004; Request was successfully updated!</p>";
        } else {
            echo "<p style='color: red;'>&#10027; Request was NOT updated properly!</p>";
        }
    } else {
        echo "<p style='color: red;'>&#10027; Request not found!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>&#10027; Database error: " . $e->getMessage() . "</p>";
}

// Check notifications
echo "<h3>Check Notifications After API Call</h3>";
try {
    $stmt = $db->prepare("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($notifications) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Title</th><th>Message</th><th>Type</th><th>Created</th></tr>";
        foreach ($notifications as $notif) {
            echo "<tr>";
            echo "<td>{$notif['id']}</td>";
            echo "<td>{$notif['user_id']}</td>";
            echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
            echo "<td>" . htmlspecialchars($notif['message']) . "</td>";
            echo "<td>{$notif['type']}</td>";
            echo "<td>{$notif['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check if we have new notifications related to accept request
        $acceptNotifs = array_filter($notifications, function($n) {
            return strpos($n['message'], 'nhận') !== false || strpos($n['message'], 'in_progress') !== false;
        });
        
        if (count($acceptNotifs) > 0) {
            echo "<p style='color: green;'>&#10004; Found " . count($acceptNotifs) . " notifications related to accept request!</p>";
        } else {
            echo "<p style='color: red;'>&#10027; No notifications related to accept request found!</p>";
        }
    } else {
        echo "<p style='color: orange;'>&#9888; No notifications found in database</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>&#10027; Error checking notifications: " . $e->getMessage() . "</p>";
}

echo "<h3>Test Results Summary</h3>";
echo "<div style='background-color: #e8f5e8; padding: 15px; border-radius: 5px;'>";
echo "<h4>What This Test Shows:</h4>";
echo "<ul>";
echo "<li>If API returns success but no notifications appear, the issue is in the notification logic</li>";
echo "<li>If API returns error, the issue is in the accept_request action</li>";
echo "<li>If database is updated but no notifications, the register_shutdown_function isn't working</li>";
echo "<li>If notifications appear but users don't see them, the issue is in the frontend display</li>";
echo "</ul>";
echo "</div>";
?>
