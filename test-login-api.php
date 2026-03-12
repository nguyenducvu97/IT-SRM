<?php
// Test login API directly
$data = [
    'action' => 'login',
    'username' => 'admin',
    'password' => 'admin'
];

$ch = curl_init('http://localhost/it-service-request/api/auth.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

header('Content-Type: text/plain');
echo "HTTP Status: $http_code\n";
echo "CURL Error: $curl_error\n";
echo "Response: $response\n";

// Try password verification
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("SELECT password_hash FROM users WHERE username = 'admin'");
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "\nPassword verification test:\n";
    echo "Hash: " . $user['password_hash'] . "\n";
    echo "Verify 'admin': " . (password_verify('admin', $user['password_hash']) ? "TRUE" : "FALSE") . "\n";
    echo "Verify 'password': " . (password_verify('password', $user['password_hash']) ? "TRUE" : "FALSE") . "\n";
}
?>
