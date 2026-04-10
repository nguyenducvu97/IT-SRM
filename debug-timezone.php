<?php
echo "<h2>DEBUG TIMEZONE ISSUE</h2>";

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/NotificationHelper.php';

$db = getDatabaseConnection();
$notificationHelper = new NotificationHelper();

echo "<h3>1. KIÊM TRA TIMEZONE SETTINGS</h3>";
echo "<p><strong>PHP timezone:</strong> " . date_default_timezone_get() . "</p>";
echo "<p><strong>Current PHP time:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Current timestamp:</strong> " . time() . "</p>";

echo "<h3>2. KIÊM TRA DATABASE TIME</h3>";

try {
    $stmt = $db->query("SELECT NOW() as db_time, UTC_TIMESTAMP() as utc_time");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Database time:</strong> " . $result['db_time'] . "</p>";
    echo "<p><strong>Database UTC time:</strong> " . $result['utc_time'] . "</p>";
    
    $dbTime = $result['db_time'];
    $dbTimestamp = strtotime($dbTime);
    $phpTimestamp = time();
    $timeDiff = $dbTimestamp - $phpTimestamp;
    
    echo "<p><strong>Time difference:</strong> $timeDiff seconds</p>";
    
    if (abs($timeDiff) > 300) { // 5 minutes
        echo "<p style='color: red;'>&#10027; WARNING: Database and PHP time differ significantly!</p>";
    } else {
        echo "<p style='color: green;'>&#10004; Database and PHP time are in sync</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking database time: " . $e->getMessage() . "</p>";
}

echo "<h3>3. KIÊM TRA NOTIFICATIONS GÂN NHÂT</h3>";

try {
    $stmt = $db->prepare("SELECT id, title, created_at FROM notifications ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($notifications) > 0) {
        echo "<p style='color: green;'><strong>&#10004; Found " . count($notifications) . " recent notifications:</strong></p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th><th>Title</th><th>Created At (DB)</th><th>Timestamp</th><th>Time Diff</th><th>Time Ago</th><th>Expected</th>";
        echo "</tr>";
        
        foreach ($notifications as $notif) {
            $dbTime = $notif['created_at'];
            $timestamp = strtotime($dbTime);
            $now = time();
            $diff = $now - $timestamp;
            
            // Test getTimeAgo function
            $timeAgo = $notificationHelper->getTimeAgo($dbTime);
            
            // Calculate expected
            $expected = '';
            if ($diff < 60) {
                $expected = 'Vài giây';
            } elseif ($diff < 3600) {
                $minutes = floor($diff / 60);
                $expected = $minutes . ' phút';
            } elseif ($diff < 86400) {
                $hours = floor($diff / 3600);
                $expected = $hours . ' phút'; // Should be 'giò'
            } else {
                $expected = date('d/m/Y', $timestamp);
            }
            
            $status = ($timeAgo === $expected) ? 'PASS' : 'FAIL';
            $color = ($status === 'PASS') ? 'green' : 'red';
            
            echo "<tr>";
            echo "<td><strong>{$notif['id']}</strong></td>";
            echo "<td>" . htmlspecialchars(substr($notif['title'], 0, 30)) . "...</td>";
            echo "<td>{$dbTime}</td>";
            echo "<td>{$timestamp}</td>";
            echo "<td>{$diff}s</td>";
            echo "<td><strong>{$timeAgo}</strong></td>";
            echo "<td>{$expected}</td>";
            echo "<td style='color: {$color};'>{$status}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: red;'><strong>&#10027; No notifications found!</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<h3>4. TEST FUNCTION ISOLATION</h3>";

// Test function with known values
$testCases = [
    ['time' => date('Y-m-d H:i:s'), 'desc' => 'Now', 'diff' => 0],
    ['time' => date('Y-m-d H:i:s', time() - 30), 'desc' => '30s ago', 'diff' => 30],
    ['time' => date('Y-m-d H:i:s', time() - 300), 'desc' => '5m ago', 'diff' => 300],
    ['time' => date('Y-m-d H:i:s', time() - 3600), 'desc' => '1h ago', 'diff' => 3600],
    ['time' => date('Y-m-d H:i:s', time() - 7200), 'desc' => '2h ago', 'diff' => 7200],
];

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Test</th><th>Time</th><th>Diff</th><th>Result</th><th>Expected</th><th>Status</th></tr>";

foreach ($testCases as $test) {
    $result = $notificationHelper->getTimeAgo($test['time']);
    
    $expected = '';
    if ($test['diff'] < 60) {
        $expected = 'Vài giây';
    } elseif ($test['diff'] < 3600) {
        $minutes = floor($test['diff'] / 60);
        $expected = $minutes . ' phút';
    } elseif ($test['diff'] < 86400) {
        $hours = floor($test['diff'] / 3600);
        $expected = $hours . ' phút'; // Should be 'giò'
    } else {
        $expected = date('d/m/Y', strtotime($test['time']));
    }
    
    $status = ($result === $expected) ? 'PASS' : 'FAIL';
    $color = ($status === 'PASS') ? 'green' : 'red';
    
    echo "<tr>";
    echo "<td>{$test['desc']}</td>";
    echo "<td>{$test['time']}</td>";
    echo "<td>{$test['diff']}s</td>";
    echo "<td><strong>{$result}</strong></td>";
    echo "<td>{$expected}</td>";
    echo "<td style='color: {$color};'>{$status}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>5. ANALYSIS</h3>";
echo "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<h4>&#128204; Possible Issues:</h4>";
echo "<ol>";
echo "<li><strong>Timezone mismatch:</strong> PHP timezone vs database timezone</li>";
echo "<li><strong>Function logic:</strong> getTimeAgo function calculation error</li>";
echo "<li><strong>strtotime() issue:</strong> Database time format not parsed correctly</li>";
echo "<li><strong>API caching:</strong> Frontend not getting updated time_ago</li>";
echo "<li><strong>Browser cache:</strong> Old frontend data cached</li>";
echo "</ol>";
echo "</div>";

echo "<h3>6. SOLUTION</h3>";
echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px;'>";
echo "<h4>&#128072; Steps to fix:</h4>";
echo "<ol>";
echo "<li><strong>1. Fix timezone:</strong> Ensure PHP and database use same timezone</li>";
echo "<li><strong>2. Fix function:</strong> Update getTimeAgo to return correct units</li>";
echo "<li><strong>3. Clear cache:</strong> Clear browser and server cache</li>";
echo "<li><strong>4. Test:</strong> Create new notification and verify</li>";
echo "</ol>";
echo "</div>";
?>
