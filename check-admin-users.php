<?php
require_once 'config/database.php';

echo "=== CHECK ADMIN USERS ===" . PHP_EOL;

try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->query("SELECT id, username, full_name FROM users WHERE role = 'admin'");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Number of admin users: " . count($admins) . PHP_EOL;
    echo PHP_EOL;
    
    foreach ($admins as $admin) {
        echo "ID: " . $admin['id'] . ", Username: " . $admin['username'] . ", Name: " . $admin['full_name'] . PHP_EOL;
    }
    
    echo PHP_EOL;
    
    if (count($admins) > 1) {
        echo "⚠️  ISSUE FOUND: Multiple admin users detected!" . PHP_EOL;
        echo "Each admin will receive duplicate notifications." . PHP_EOL;
        echo "This explains why admin gets 2+ notifications for each request." . PHP_EOL;
    } else {
        echo "✅ Only one admin user found - notifications should be unique." . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}

echo "=== CHECK COMPLETE ===" . PHP_EOL;
?>
