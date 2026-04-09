<?php
echo "<h2>DEBUG NOTIFICATION TIME ISSUE</h2>";

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/NotificationHelper.php';

$db = getDatabaseConnection();
$notificationHelper = new NotificationHelper();

echo "<h3>1. KIÊM TRA NOTIFICATIONS GÂN NHÂT</h3>";

try {
    $stmt = $db->prepare("SELECT id, title, created_at FROM notifications ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($notifications) > 0) {
        echo "<p style='color: green;'><strong>&#10004; Found " . count($notifications) . " notifications:</strong></p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th><th>Title</th><th>Created At (DB)</th><th>Time Diff</th><th>Time Ago</th>";
        echo "</tr>";
        
        foreach ($notifications as $notif) {
            $dbTime = $notif['created_at'];
            $timestamp = strtotime($dbTime);
            $now = time();
            $diff = $now - $timestamp;
            
            // Test getTimeAgo function
            $timeAgo = $notificationHelper->getTimeAgo($dbTime);
            
            echo "<tr>";
            echo "<td><strong>{$notif['id']}</strong></td>";
            echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
            echo "<td>{$dbTime}</td>";
            echo "<td>{$diff} seconds</td>";
            echo "<td><strong>{$timeAgo}</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: red;'><strong>&#10027; No notifications found!</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<h3>2. KIÊM TRA TIMEZONE</h3>";
echo "<p><strong>PHP Timezone:</strong> " . date_default_timezone_get() . "</p>";
echo "<p><strong>Current PHP time:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Current timestamp:</strong> " . time() . "</p>";

// Check database timezone
try {
    $stmt = $db->query("SELECT NOW() as db_time");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Database time:</strong> " . $result['db_time'] . "</p>";
    
    $dbTime = $result['db_time'];
    $dbTimestamp = strtotime($dbTime);
    $phpTimestamp = time();
    $timeDiff = $dbTimestamp - $phpTimestamp;
    
    echo "<p><strong>Time difference:</strong> $timeDiff seconds</p>";
    
    if (abs($timeDiff) > 300) { // 5 minutes
        echo "<p style='color: orange;'>&#9888; WARNING: Database and PHP time differ significantly!</p>";
    } else {
        echo "<p style='color: green;'>&#10004; Database and PHP time are in sync</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking database time: " . $e->getMessage() . "</p>";
}

echo "<h3>3. TEST TIME AGO FUNCTION MANUALLY</h3>";

// Test with different timestamps
$testCases = [
    ['time' => date('Y-m-d H:i:s'), 'desc' => 'Current time'],
    ['time' => date('Y-m-d H:i:s', time() - 30), 'desc' => '30 seconds ago'],
    ['time' => date('Y-m-d H:i:s', time() - 300), 'desc' => '5 minutes ago'],
    ['time' => date('Y-m-d H:i:s', time() - 3600), 'desc' => '1 hour ago'],
    ['time' => date('Y-m-d H:i:s', time() - 7200), 'desc' => '2 hours ago'],
    ['time' => date('Y-m-d H:i:s', time() - 86400), 'desc' => '1 day ago'],
];

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Test</th><th>Time</th><th>Result</th><th>Analysis</th></tr>";

foreach ($testCases as $test) {
    $result = $notificationHelper->getTimeAgo($test['time']);
    
    $analysis = '';
    if ($result === 'Vài giây') {
        $analysis = 'Shows "Vài giây" - this is the issue!';
    } elseif (strpos($result, 'phút') !== false) {
        $analysis = 'Shows minutes - OK';
    } elseif (strpos($result, 'giò') !== false) {
        $analysis = 'Shows hours - OK';
    } elseif (strpos($result, 'ngày') !== false) {
        $analysis = 'Shows days - OK';
    } else {
        $analysis = 'Unknown format';
    }
    
    echo "<tr>";
    echo "<td>{$test['desc']}</td>";
    echo "<td>{$test['time']}</td>";
    echo "<td><strong>{$result}</strong></td>";
    echo "<td>{$analysis}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>4. CREATE TEST NOTIFICATION</h3>";

// Create a test notification with older timestamp
$oldTime = date('Y-m-d H:i:s', time() - 3600); // 1 hour ago

try {
    // Get admin user
    $stmt = $db->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        // Insert test notification with old timestamp
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([
            $admin['id'],
            'Test Notification Time',
            'This is a test notification with older timestamp',
            'info',
            $oldTime
        ]);
        
        if ($result) {
            $notifId = $db->lastInsertId();
            echo "<p style='color: green;'>&#10004; Created test notification #{$notifId} with timestamp: {$oldTime}</p>";
            
            // Test time ago for this notification
            $timeAgo = $notificationHelper->getTimeAgo($oldTime);
            echo "<p><strong>Time ago result:</strong> {$timeAgo}</p>";
            
            if ($timeAgo === 'Vài giây') {
                echo "<p style='color: red;'>&#10027; CONFIRMED: getTimeAgo function is broken!</p>";
            } else {
                echo "<p style='color: green;'>&#10004; Function working correctly!</p>";
            }
            
            // Clean up test notification
            $stmt = $db->prepare("DELETE FROM notifications WHERE id = ?");
            $stmt->execute([$notifId]);
            echo "<p>&#128465; Cleaned up test notification</p>";
            
        } else {
            echo "<p style='color: red;'>&#10027; Failed to create test notification</p>";
        }
    } else {
        echo "<p style='color: red;'>&#10027; No admin user found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<h3>5. CONCLUSION</h3>";
echo "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<h4>&#128204; Analysis Results:</h4>";
echo "<p><strong>If getTimeAgo returns 'Vài giây' for old timestamps:</strong></p>";
echo "<ul>";
echo "<li>1. Function logic is correct (line 395 shows 'phút')</li>";
echo "<li>2. Issue might be timezone mismatch</li>";
echo "<li>3. Issue might be strtotime() parsing</li>";
echo "<li>4. Issue might be time() vs database time</li>";
echo "</ul>";
echo "<p><strong>If notifications always show 'Vài giây':</strong></p>";
echo "<ul>";
echo "<li>1. All notifications are very recent (created in last 60 seconds)</li>";
echo "<li>2. Frontend is not using updated time_ago</li>";
echo "<li>3. API is not returning correct time_ago</li>";
echo "<li>4. Browser cache issue</li>";
echo "</ul>";
echo "</div>";
?>
