<?php
// Test auth API session handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Testing Auth API Session ===<br>";

// Test login
$login_data = [
    'action' => 'login',
    'username' => 'admin',
    'password' => 'admin'
];

$ch = curl_init('http://localhost/it-service-request/api/auth.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($login_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');

$login_response = curl_exec($ch);
$login_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Login Status: $login_status<br>";
echo "Login Response: <pre>" . htmlspecialchars($login_response) . "</pre><br>";

// Test session check
$ch2 = curl_init('http://localhost/it-service-request/api/auth.php?action=check_session');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_COOKIEFILE, 'cookie.txt');

$session_response = curl_exec($ch2);
$session_status = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "Session Check Status: $session_status<br>";
echo "Session Check Response: <pre>" . htmlspecialchars($session_response) . "</pre><br>";

// Test notifications with session
$ch3 = curl_init('http://localhost/it-service-request/api/notifications.php?action=list');
curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch3, CURLOPT_COOKIEFILE, 'cookie.txt');

$notif_response = curl_exec($ch3);
$notif_status = curl_getinfo($ch3, CURLINFO_HTTP_CODE);
curl_close($ch3);

echo "Notifications Status: $notif_status<br>";
echo "Notifications Response: <pre>" . htmlspecialchars($notif_response) . "</pre><br>";

// Clean up
unlink('cookie.txt');
?>
