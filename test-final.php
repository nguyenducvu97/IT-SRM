<?php
require_once 'config/database.php';
require_once 'config/session.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== FINAL LOGOUT TEST ===\n";

// Test logout
startSession();
$session_id = session_id();

echo "Session ID: $session_id\n";

// Delete from DB
$stmt = $db->prepare("DELETE FROM sessions WHERE id = ?");
$result = $stmt->execute([$session_id]);
echo "DB Delete: " . ($result ? 'OK' : 'FAIL') . "\n";

// Destroy session
session_destroy();
echo "Session destroyed\n";

// Test API
echo "\n=== API TEST ===\n";
echo "Visit: http://localhost/it-service-request/api/auth.php?action=check_session\n";
?>
