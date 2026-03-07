<?php
require_once 'config/database.php';
$db = getDatabaseConnection();
$stmt = $db->query("SELECT id, username, full_name, email, role FROM users WHERE role = 'admin'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']} | Username: {$row['username']} | Email: {$row['email']} | Role: {$row['role']}\n";
}
?>
