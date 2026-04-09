<?php
echo "<h2>TEST ACCEPT REQUEST API ENDPOINT</h2>";

// Test the exact API endpoint that browser calls
echo "<h3>Testing API Endpoint Directly</h3>";

// Simulate browser session
require_once __DIR__ . '/config/session.php';
startSession();

// Set session as if staff1 is logged in
$_SESSION['user_id'] = 2;
$_SESSION['username'] = 'staff1';
$_SESSION['full_name'] = 'John Smith';
$_SESSION['role'] = 'staff';

echo "<h4>Session Set:</h4>";
echo "<pre>" . json_encode($_SESSION, JSON_PRETTY_PRINT) . "</pre>";

// Test data that browser sends
$testData = [
    'action' => 'accept_request',
    'request_id' => 72  // Use request #72
];

echo "<h4>Test Data:</h4>";
echo "<pre>" . json_encode($testData, JSON_PRETTY_PRINT) . "</pre>";

// Make API call exactly like browser
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

// Check if request was updated
echo "<h4>Check Database After API Call:</h4>";
require_once __DIR__ . '/config/database.php';
$db = getDatabaseConnection();

try {
    $stmt = $db->prepare("SELECT id, title, status, assigned_to FROM service_requests WHERE id = ?");
    $stmt->execute([72]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($request) {
        echo "<table border='1' cellpadding='5'>";
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
    
    // Check notifications
    $stmt = $db->prepare("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Recent Notifications:</h4>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Title</th><th>Message</th><th>Type</th><th>Created</th></tr>";
    foreach ($notifications as $notif) {
        echo "<tr>";
        echo "<td>{$notif['id']}</td>";
        echo "<td>{$notif['user_id']}</td>";
        echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($notif['message'], 0, 50)) . "...</td>";
        echo "<td>{$notif['type']}</td>";
        echo "<td>{$notif['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check for accept request notifications
    $acceptNotifs = array_filter($notifications, function($n) {
        return strpos($n['message'], 'nhân') !== false || 
               strpos($n['message'], 'tiếp nhận') !== false || 
               strpos($n['message'], 'in_progress') !== false ||
               strpos($n['title'], 'xử lý') !== false;
    });
    
    if (count($acceptNotifs) > 0) {
        echo "<p style='color: green;'>&#10004; Found " . count($acceptNotifs) . " notifications related to accept request!</p>";
    } else {
        echo "<p style='color: red;'>&#10027; No notifications related to accept request found!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>&#10027; Database error: " . $e->getMessage() . "</p>";
}

echo "<h3>Debug Browser Issue</h3>";
echo "<div style='background-color: #e8f5e8; padding: 15px; border-radius: 5px;'>";
echo "<h4>&#128161; If API works but browser doesn't:</h4>";
echo "<ol>";
echo "<li><strong>Check browser login:</strong> Make sure user is logged in as staff1</li>";
echo "<li><strong>Clear browser cache:</strong> Ctrl+F5 to force refresh</li>";
echo "<li><strong>Check browser console:</strong> F12 -> Console for JavaScript errors</li>";
echo "<li><strong>Check Network tab:</strong> F12 -> Network for API call status</li>";
echo "<li><strong>Check session cookies:</strong> F12 -> Application -> Cookies</li>";
echo "</ol>";
echo "</div>";
?>
