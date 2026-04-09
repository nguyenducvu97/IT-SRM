<?php
echo "<h2>TEST TIME AGO FUNCTION</h2>";

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/NotificationHelper.php';

$db = getDatabaseConnection();
$notificationHelper = new NotificationHelper();

echo "<h3>1. KIÊM TRA TIMEZONE HIÊN TAI</h3>";
echo "<p><strong>Current timezone:</strong> " . date_default_timezone_get() . "</p>";
echo "<p><strong>Current time:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Current timestamp:</strong> " . time() . "</p>";

echo "<h3>2. KIÊM TRA NOTIFICATIONS TRONG DATABASE</h3>";

try {
    $stmt = $db->prepare("SELECT id, title, created_at FROM notifications ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($notifications) > 0) {
        echo "<p style='color: green;'><strong>&#10004; Found " . count($notifications) . " notifications:</strong></p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th><th>Title</th><th>Created At (DB)</th><th>Timestamp</th><th>Time Diff</th><th>Time Ago</th>";
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
            echo "<td>{$timestamp}</td>";
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

echo "<h3>3. TEST TIME AGO FUNCTION WITH DIFFERENT TIMES</h3>";

$testTimes = [
    ['time' => date('Y-m-d H:i:s'), 'label' => 'Now'],
    ['time' => date('Y-m-d H:i:s', time() - 30), 'label' => '30 seconds ago'],
    ['time' => date('Y-m-d H:i:s', time() - 120), 'label' => '2 minutes ago'],
    ['time' => date('Y-m-d H:i:s', time() - 3600), 'label' => '1 hour ago'],
    ['time' => date('Y-m-d H:i:s', time() - 7200), 'label' => '2 hours ago'],
    ['time' => date('Y-m-d H:i:s', time() - 86400), 'label' => '1 day ago'],
    ['time' => date('Y-m-d H:i:s', time() - 172800), 'label' => '2 days ago'],
];

echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>Label</th><th>Time</th><th>Timestamp</th><th>Diff</th><th>Time Ago Result</th>";
echo "</tr>";

foreach ($testTimes as $test) {
    $timestamp = strtotime($test['time']);
    $diff = time() - $timestamp;
    $timeAgo = $notificationHelper->getTimeAgo($test['time']);
    
    echo "<tr>";
    echo "<td>{$test['label']}</td>";
    echo "<td>{$test['time']}</td>";
    echo "<td>{$timestamp}</td>";
    echo "<td>{$diff}s</td>";
    echo "<td><strong>{$timeAgo}</strong></td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>4. KIÊM TRA API RESPONSE</h3>";

// Test API call
$ch = curl_init('http://localhost/it-service-request/api/notifications.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>API HTTP Status:</strong> {$httpCode}</p>";

if ($response) {
    $responseData = json_decode($response, true);
    
    if (isset($responseData['error'])) {
        echo "<p style='color: red;'><strong>API Error:</strong> {$responseData['error']}</p>";
        echo "<p><em>Note: This is expected since we're not logged in via curl</em></p>";
    } else {
        echo "<p><strong>API Response:</strong></p>";
        echo "<pre>" . json_encode($responseData, JSON_PRETTY_PRINT) . "</pre>";
    }
} else {
    echo "<p style='color: red;'>Failed to get API response</p>";
}

echo "<h3>5. DEBUG TIME AGO FUNCTION</h3>";

// Test with a specific notification time
echo "<h4>Testing with specific time...</h4>";

$testTime = '2026-04-09 19:51:33'; // From our earlier test
echo "<p><strong>Test time:</strong> {$testTime}</p>";

$timestamp = strtotime($testTime);
$now = time();
$diff = $now - $timestamp;

echo "<p><strong>Timestamp:</strong> {$timestamp}</p>";
echo "<p><strong>Now:</strong> {$now}</p>";
echo "<p><strong>Difference:</strong> {$diff} seconds</p>";

if ($diff < 60) {
    $expected = "Vài giây";
} elseif ($diff < 3600) {
    $minutes = floor($diff / 60);
    $expected = $minutes . " phút";
} elseif ($diff < 86400) {
    $hours = floor($diff / 3600);
    $expected = $hours . " giây"; // This is the bug!
} elseif ($diff < 604800) {
    $days = floor($diff / 86400);
    $expected = $days . " ngày";
} else {
    $expected = date('d/m/Y', $timestamp);
}

$actual = $notificationHelper->getTimeAgo($testTime);

echo "<p><strong>Expected:</strong> {$expected}</p>";
echo "<p><strong>Actual:</strong> {$actual}</p>";

if ($diff >= 3600 && $diff < 86400) {
    echo "<p style='color: red;'><strong>&#10027; BUG FOUND!</strong></p>";
    echo "<p>For hours, the function returns '{$hours} giây' instead of '{$hours} phút'!</p>";
}

echo "<h3>6. SOLUTION</h3>";
echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px;'>";
echo "<h4>&#128072; Issues found:</h4>";
echo "<ol>";
echo "<li><strong>Line 395:</strong> Returns 'giây' instead of 'giò' for hours</li>";
echo "<li><strong>Possible timezone issue:</strong> Database time vs PHP time</li>";
echo "<li><strong>Logic issue:</strong> May need better time calculation</li>";
echo "</ol>";
echo "<h4>&#128072; Fix needed:</h4>";
echo "<ol>";
echo "<li>1. Fix 'giây' to 'giò' in getTimeAgo function</li>";
echo "<li>2. Check timezone consistency</li>";
echo "<li>3. Test with real data</li>";
echo "</ol>";
echo "</div>";
?>
