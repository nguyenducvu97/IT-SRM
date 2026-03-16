<?php
// Create notification directly in database
require_once 'config/database.php';
require_once 'config/session.php';

// Start session
startSession();

// Create admin session
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['full_name'] = 'System Administrator';
$_SESSION['role'] = 'admin';
$_SESSION['email'] = 'admin@test.com';

echo "Session ID: " . session_id() . "\n";
echo "Session data: ";
print_r($_SESSION);

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create test notification for admin
try {
    $stmt = $db->prepare("
        INSERT INTO notifications (user_id, title, message, type, related_id, related_type)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        1, // admin user_id
        'Test Notification',
        'This is a test notification for admin user',
        'info',
        1,
        'test'
    ]);
    
    if ($result) {
        echo "✅ Test notification created successfully!\n";
        
        // Get notifications for admin
        $stmt = $db->prepare("
            SELECT id, title, message, type, is_read, created_at
            FROM notifications 
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 5
        ");
        $stmt->execute([1]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Admin notifications:\n";
        print_r($notifications);
        
    } else {
        echo "❌ Failed to create notification\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
