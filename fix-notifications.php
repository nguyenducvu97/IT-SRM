<?php
require_once 'config/database.php';
require_once 'config/session.php';

// Start session and set admin user
startSession();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['username'] = 'admin';
$_SESSION['full_name'] = 'Administrator';

echo "Session set for admin user<br>";

// Test database connection
$database = new Database();
$db = $database->getConnection();

echo "Database connected<br>";

// Create a test notification
$title = "Test Notification";
$message = "This is a test notification for admin";
$type = "info";

$stmt = $db->prepare("
    INSERT INTO notifications (user_id, title, message, type, created_at) 
    VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
");

if ($stmt->execute([1, $title, $message, $type])) {
    echo "✓ Test notification created successfully<br>";
    $notif_id = $db->lastInsertId();
    echo "Notification ID: $notif_id<br>";
    
    // Test retrieving the notification
    $test_stmt = $db->prepare("
        SELECT id, title, message, type, is_read, created_at
        FROM notifications 
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $test_stmt->execute([1]);
    $notifications = $test_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✓ Retrieved " . count($notifications) . " notifications<br>";
    
    // Format as JSON
    $formatted = [];
    foreach ($notifications as $notif) {
        $formatted[] = [
            'id' => $notif['id'],
            'title' => $notif['title'],
            'message' => $notif['message'],
            'type' => $notif['type'],
            'is_read' => (bool)$notif['is_read'],
            'created_at' => $notif['created_at'],
            'time_ago' => 'Vừa xong'
        ];
    }
    
    echo "✓ Formatted JSON output<br>";
    echo "<pre>" . json_encode($formatted, JSON_PRETTY_PRINT) . "</pre>";
    
} else {
    echo "✗ Failed to create test notification<br>";
    echo "Error: " . print_r($stmt->errorInfo(), true) . "<br>";
}
?>
