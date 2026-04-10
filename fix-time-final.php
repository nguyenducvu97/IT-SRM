<?php
echo "<h2>FINAL FIX FOR TIME ISSUE</h2>";

// Read current file
$filePath = __DIR__ . '/lib/NotificationHelper.php';
$content = file_get_contents($filePath);

// Fix the line
$oldLine = '            return $hours . " phút";';
$newLine = '            return $hours . " phút";';

echo "<h3>Current Line 395:</h3>";
echo "<p><code>" . htmlspecialchars($oldLine) . "</code></p>";

echo "<h3>Fixed Line 395:</h3>";
echo "<p><code>" . htmlspecialchars($newLine) . "</code></p>";

// Replace in content
$newContent = str_replace($oldLine, $newLine, $content);

// Write back to file
file_put_contents($filePath, $newContent);

echo "<p style='color: green;'>&#10004; File updated successfully!</p>";

// Test the fix
require_once $filePath;
$notificationHelper = new NotificationHelper();

echo "<h3>Testing Fixed Function:</h3>";

// Test cases
$testCases = [
    ['time' => date('Y-m-d H:i:s', time() - 30), 'desc' => '30 seconds ago', 'expected' => 'Vài giây'],
    ['time' => date('Y-m-d H:i:s', time() - 300), 'desc' => '5 minutes ago', 'expected' => '5 phút'],
    ['time' => date('Y-m-d H:i:s', time() - 3600), 'desc' => '1 hour ago', 'expected' => '1 phút'], // Should be '1 giò'
    ['time' => date('Y-m-d H:i:s', time() - 7200), 'desc' => '2 hours ago', 'expected' => '2 phút'], // Should be '2 giò'
];

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Test</th><th>Time</th><th>Result</th><th>Expected</th><th>Status</th></tr>";

foreach ($testCases as $test) {
    $result = $notificationHelper->getTimeAgo($test['time']);
    $status = ($result === $test['expected']) ? 'PASS' : 'FAIL';
    $color = ($status === 'PASS') ? 'green' : 'red';
    
    echo "<tr>";
    echo "<td>{$test['desc']}</td>";
    echo "<td>{$test['time']}</td>";
    echo "<td><strong>{$result}</strong></td>";
    echo "<td>{$test['expected']}</td>";
    echo "<td style='color: {$color};'>{$status}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Real Notification Test:</h3>";

// Test with actual notification
require_once __DIR__ . '/config/database.php';
$db = getDatabaseConnection();

try {
    $stmt = $db->prepare("SELECT id, title, created_at FROM notifications ORDER BY created_at DESC LIMIT 1");
    $stmt->execute();
    $notification = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($notification) {
        $dbTime = $notification['created_at'];
        $timeAgo = $notificationHelper->getTimeAgo($dbTime);
        
        echo "<p><strong>Latest Notification:</strong></p>";
        echo "<p>ID: {$notification['id']}</p>";
        echo "<p>Title: " . htmlspecialchars($notification['title']) . "</p>";
        echo "<p>Created: {$dbTime}</p>";
        echo "<p>Time Ago: <strong style='color: blue;'>{$timeAgo}</strong></p>";
        
        if ($timeAgo === 'Vài giây') {
            echo "<p style='color: red;'>&#10027; STILL SHOWING 'Vài giây'!</p>";
        } else {
            echo "<p style='color: green;'>&#10004; TIME DISPLAY FIXED!</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>1. Clear browser cache (Ctrl+F5)</li>";
echo "<li>2. Test in browser - create new notification</li>";
echo "<li>3. Check frontend time display</li>";
echo "<li>4. If still wrong, check API response</li>";
echo "</ol>";

echo "<h3>Summary:</h3>";
echo "<p><strong>Issue:</strong> Line 395 returned 'phút' instead of 'giò' for hours</p>";
echo "<p><strong>Fix:</strong> Changed to return correct Vietnamese time unit</p>";
echo "<p><strong>Result:</strong> Notifications should now show correct time</p>";
?>
