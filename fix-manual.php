<?php
echo "<h2>MANUAL FIX INSTRUCTIONS</h2>";

echo "<h3>&#128072; VÂN DEÄ:</h3>";
echo "<p>Dòng 395 trong lib/NotificationHelper.php:</p>";
echo "<pre style='background-color: #f5f5f5; padding: 10px;'>";
echo "return \$hours . \"phút\";";
echo "</pre>";
echo "<p>Phai là:</p>";
echo "<pre style='background-color: #e8f5e8; padding: 10px;'>";
echo "return \$hours . \"giò\";";
echo "</pre>";

echo "<h3>&#128072; CAÙCH FIX:</h3>";
echo "<ol>";
echo "<li>1. Mo file: <code>lib/NotificationHelper.php</code></li>";
echo "<li>2. Ke line 395</li>";
echo "<li>3. Thay the: <code>return \$hours . \"phút\";</code></li>";
echo "<li>4. Bàng: <code>return \$hours . \"giò\";</code></li>";
echo "<li>5. Save file</li>";
echo "</ol>";

echo "<h3>&#128072; TEST SAU KHI FIX:</h3>";

// Test current function
require_once __DIR__ . '/lib/NotificationHelper.php';
$notificationHelper = new NotificationHelper();

$testTime = '2026-04-09 14:00:00'; // 2+ hours ago
$result = $notificationHelper->getTimeAgo($testTime);

echo "<p><strong>Test (2+ hours ago):</strong> '$result'</p>";

if ($result === 'Vài giây') {
    echo "<p style='color: red;'>&#10027; Vaän deà: Function traå 'Vài giây' cho 2+ hours ago</p>";
    echo "<p><strong>Nguyên nhân:</strong> Dòng 395 return 'phút' thay vì 'giò'</p>";
} elseif (strpos($result, 'phút') !== false) {
    echo "<p style='color: orange;'>&#9888; Function traå 'phút' cho hours - CÂN FIX!</p>";
    echo "<p><strong>Giãi pháp:</strong> Så dòng 395 thành 'giò'</p>";
} elseif (strpos($result, 'giò') !== false) {
    echo "<p style='color: green;'>&#10004; Function hoaët âoñng âoñng!</p>";
} else {
    echo "<p style='color: blue;'>&#128071; Kêå quaå khoâng hieåu: '$result'</p>";
}

echo "<h3>&#128204; GHI NHÔ:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Time</th><th>Dòng 395 hiêän taïi</th><th>Kêå quaå</th><th>Kêå quaå mong muoón</th></tr>";
echo "<tr>";
echo "<td>&lt; 60s</td>";
echo "<td>return \"Vài giây\"</td>";
echo "<td>Vài giây</td>";
echo "<td>Vài giây</td>";
echo "</tr>";
echo "<tr>";
echo "<td>2-59m</td>";
echo "<td>return \$minutes . \"phút\"</td>";
echo "<td>X phút</td>";
echo "<td>X phút</td>";
echo "</tr>";
echo "<tr>";
echo "<td>1-23h</td>";
echo "<td>return \$hours . \"phút\"</td>";
echo "<td>X phút</td>";
echo "<td>X giò</td>";
echo "</tr>";
echo "<tr>";
echo "<td>1-6d</td>";
echo "<td>return \$days . \"ngày\"</td>";
echo "<td>X ngày</td>";
echo "<td>X ngày</td>";
echo "</tr>";
echo "</table>";

echo "<h3>&#127911; CAÙCH TEST TRONG BROWSER:</h3>";
echo "<ol>";
echo "<li>1. Login làm admin</li>";
echo "<li>2. Taï notification moi</li>";
echo "<li>3. Check frontend - nên âoå time âoñng âoñng</li>";
echo "<li>4. Clear browser cache</li>";
echo "<li>5. Test laïi</li>";
echo "</ol>";

echo "<h3>&#128073; KÊÉT LUÃN:</h3>";
echo "<p><strong>Vâñ ñeà:</strong> Thoâi gian notifications luôn 'Vài giây'</p>";
echo "<p><strong>Nguyên nhân:</strong> Dòng 395 return 'phút' cho hours thay vì 'giò'</p>";
echo "<p><strong>Giaãi pháp:</strong> Så 'phút' thành 'giò' ô dòng 395</p>";
echo "<p><strong>Kêët quaå:</strong> Notifications seå hieån thi time âoñng âoñng</p>";
?>
