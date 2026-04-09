<?php
echo "<h2>Test Staff Login and Accept Request</h2>";

// Step 1: Login as staff
echo "<h3>Step 1: Login as Staff</h3>";

require_once __DIR__ . '/config/session.php';
startSession();

// Login data
$loginData = [
    'action' => 'login',
    'username' => 'staff1',
    'password' => 'password123' // Assuming default password
];

$ch = curl_init('http://localhost/it-service-request/api/auth.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: ' . session_name() . '=' . session_id()
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>Login Response:</strong></p>";
echo "<p>HTTP Status: {$httpCode}</p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Step 2: Check session after login
echo "<h3>Step 2: Check Session After Login</h3>";

if (empty($_SESSION)) {
    echo "<p style='color: red;'>&#10027; Still no session data after login</p>";
} else {
    echo "<p style='color: green;'>&#10004; Session data found after login</p>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Key</th><th>Value</th></tr>";
    foreach ($_SESSION as $key => $value) {
        echo "<tr>";
        echo "<td>{$key}</td>";
        echo "<td>" . (is_array($value) ? json_encode($value) : htmlspecialchars($value)) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Step 3: Test accept request with logged in session
echo "<h3>Step 3: Test Accept Request</h3>";

if (function_exists('isLoggedIn') && isLoggedIn()) {
    echo "<p style='color: green;'>&#10004; User is logged in, testing accept request...</p>";
    
    $acceptData = [
        'action' => 'accept_request',
        'request_id' => 72
    ];

    $ch = curl_init('http://localhost/it-service-request/api/service_requests.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($acceptData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Cookie: ' . session_name() . '=' . session_id()
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    echo "<p><strong>Accept Request Response:</strong></p>";
    echo "<p>HTTP Status: {$httpCode}</p>";
    if ($error) {
        echo "<p>CURL Error: {$error}</p>";
    }
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    // Check if request was updated
    require_once __DIR__ . '/config/database.php';
    $db = getDatabaseConnection();
    
    $stmt = $db->prepare("SELECT id, title, status, assigned_to FROM service_requests WHERE id = ?");
    $stmt->execute([72]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($request) {
        echo "<h4>Request Status After Accept:</h4>";
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
    }
    
    // Check notifications
    $stmt = $db->prepare("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 3");
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Recent Notifications:</h4>";
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
    
} else {
    echo "<p style='color: red;'>&#10027; User is not logged in, cannot test accept request</p>";
}

echo "<h3>Browser Testing Instructions</h3>";
echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px;'>";
echo "<h4>To test in browser:</h4>";
echo "<ol>";
echo "<li>1. Open browser and go to login page</li>";
echo "<li>2. Login as <strong>staff1</strong> with password</li>";
echo "<li>3. Navigate to request detail page (e.g., request #72)</li>";
echo "<li>4. Click 'Nhân yêu câu' button</li>";
echo "<li>5. Check browser console for errors</li>";
echo "<li>6. Check notifications in database</li>";
echo "</ol>";
echo "<p><strong>Expected Result:</strong></p>";
echo "<ul>";
echo "<li>Request status changes to 'in_progress'</li>";
echo "<li>Request assigned_to becomes staff ID (2)</li>";
echo "<li>Notifications created for user, admin, and other staff</li>";
echo "</ul>";
echo "</div>";
?>
