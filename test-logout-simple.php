<?php
require_once 'config/database.php';
require_once 'config/session.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== LOGOUT TEST ===\n";

// Start session
startSession();
$session_id = session_id();

echo "Session ID: $session_id\n";

// Check database before
$stmt = $db->prepare("SELECT COUNT(*) as count FROM sessions WHERE id = ?");
$stmt->execute([$session_id]);
$count = $stmt->fetch();
echo "DB sessions before logout: " . $count['count'] . "\n";

// Perform logout
$_SESSION = array();
session_destroy();

// Delete from database
$stmt = $db->prepare("DELETE FROM sessions WHERE id = ?");
$stmt->execute([$session_id]);
echo "Deleted from database\n";

// Check database after
$stmt = $db->prepare("SELECT COUNT(*) as count FROM sessions WHERE id = ?");
$stmt->execute([$session_id]);
$count = $stmt->fetch();
echo "DB sessions after logout: " . $count['count'] . "\n";

// Test check_session API
echo "\n=== API TEST ===\n";
echo "Visit: http://localhost/it-service-request/api/auth.php?action=check_session\n";
?>
