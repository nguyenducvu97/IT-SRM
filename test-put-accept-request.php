<?php
// Test PUT accept_request exactly like frontend
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simulate staff session
session_start();
$_SESSION['user_id'] = 2; // staff1 ID
$_SESSION['username'] = 'staff1';
$_SESSION['full_name'] = 'John Smith';
$_SESSION['role'] = 'staff';

// Test data like frontend
$data = [
    'action' => 'accept_request',
    'request_id' => 133 // Use existing request ID from test
];

echo "<h2>Test PUT Accept Request (Like Frontend)</h2>";
echo "Session: " . json_encode($_SESSION) . "<br>";
echo "Request data: " . json_encode($data) . "<br>";

// Make PUT request to API
$ch = curl_init('http://localhost/it-service-request/api/service_requests.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: ' . session_name() . '=' . session_id()
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<h3>API Response</h3>";
echo "HTTP Code: {$http_code}<br>";
if ($error) {
    echo "CURL Error: {$error}<br>";
}
echo "Response: <pre>{$response}</pre>";

// Check notifications after request
echo "<h3>Check Notifications After Request</h3>";
try {
    require_once __DIR__ . '/config/database.php';
    $db = getDatabaseConnection();
    
    $stmt = $db->prepare("SELECT * FROM notifications WHERE related_id = ? AND related_type = 'service_request' ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([133]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($notifications) . " notifications for request #133:<br>";
    foreach ($notifications as $notif) {
        $time = new DateTime($notif['created_at']);
        echo "- {$time->format('H:i:s')}: ID {$notif['id']}, User {$notif['user_id']}, {$notif['title']}<br>";
    }
} catch (Exception $e) {
    echo "Error checking notifications: " . $e->getMessage() . "<br>";
}
?>
