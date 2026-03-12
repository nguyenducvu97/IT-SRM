<?php
// Debug notifications API step by step
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1: Testing session include...<br>";

try {
    require_once 'config/session.php';
    echo "✓ Session config loaded<br>";
    
    startSession();
    echo "✓ Session started<br>";
    
    // Set test session
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
    $_SESSION['username'] = 'admin';
    
    echo "Session data: " . json_encode($_SESSION) . "<br>";
    
} catch (Exception $e) {
    echo "✗ Session error: " . $e->getMessage() . "<br>";
}

echo "<br>Step 2: Testing database include...<br>";

try {
    require_once 'config/database.php';
    echo "✓ Database config loaded<br>";
    
    $database = new Database();
    $pdo = $database->getConnection();
    echo "✓ Database connected<br>";
    
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "<br>";
}

echo "<br>Step 3: Testing notifications table...<br>";

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Notifications table accessible, total records: " . $count['count'] . "<br>";
    
} catch (Exception $e) {
    echo "✗ Notifications table error: " . $e->getMessage() . "<br>";
}

echo "<br>Step 4: Testing user notifications...<br>";

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ?");
    $stmt->execute([1]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ User notifications: " . $count['count'] . "<br>";
    
} catch (Exception $e) {
    echo "✗ User notifications error: " . $e->getMessage() . "<br>";
}

echo "<br>Step 5: Testing API response format...<br>";

try {
    $stmt = $pdo->prepare("
        SELECT id, title, message, type, related_id, related_type, 
               is_read, created_at, read_at
        FROM notifications 
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute([1]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✓ Query successful, found " . count($notifications) . " notifications<br>";
    
    $formattedNotifications = [];
    foreach ($notifications as $notif) {
        $formattedNotifications[] = [
            'id' => $notif['id'],
            'title' => $notif['title'],
            'message' => $notif['message'],
            'type' => $notif['type'],
            'related_id' => $notif['related_id'],
            'related_type' => $notif['related_type'],
            'is_read' => (bool)$notif['is_read'],
            'created_at' => $notif['created_at'],
            'read_at' => $notif['read_at'],
            'time_ago' => 'Vừa xong'
        ];
    }
    
    echo "✓ Formatting successful<br>";
    echo "JSON output: " . json_encode($formattedNotifications) . "<br>";
    
} catch (Exception $e) {
    echo "✗ API format error: " . $e->getMessage() . "<br>";
}
?>
