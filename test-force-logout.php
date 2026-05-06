<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== TESTING FORCE LOGOUT ===\n\n";

// Test force logout endpoint
$response = file_get_contents('http://localhost/it-service-request/api/force-logout.php');
echo "Force logout response:\n";
echo $response . "\n\n";

// Test check_session after force logout
echo "Testing check_session after force logout:\n";
$check_response = file_get_contents('http://localhost/it-service-request/api/auth.php?action=check_session');
echo $check_response . "\n";

echo "\n=== DONE ===\n";
?>
