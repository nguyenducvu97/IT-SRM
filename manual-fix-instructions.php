<?php
echo "<h2>MANUAL FIX INSTRUCTIONS - TIME ISSUE</h2>";

echo "<h3>&#128072; VÂN DEÄ:</h3>";
echo "<p>Notifications luôn hiåñ thi 'Vài giây' hoäc 'X phút' ngay caå khi ðöïïc taïo hôm qua.</p>";

echo "<h3>&#128072; NGUYÊN NHÂN:</h3>";
echo "<p>Dòng 395 trong lib/NotificationHelper.php:</p>";
echo "<pre style='background-color: #ffebee; padding: 10px; border-left: 4px solid #f44336;'>";
echo "return \$hours . \"phút\";  // &#10027; SAI - phai laå \"giò\"";
echo "</pre>";

echo "<h3>&#128072; GIAÛI PHAÙP:</h3>";
echo "<p>Så dòng 395 thành:</p>";
echo "<pre style='background-color: #e8f5e8; padding: 10px; border-left: 4px solid #4caf50;'>";
echo "return \$hours . \"giò\";  // &#10004; ÐUÕNG";
echo "</pre>";

echo "<h3>&#128072; CAÙCH FIX:</h3>";
echo "<ol>";
echo "<li>1. Moå file: <code>lib/NotificationHelper.php</code></li>";
echo "<li>2. Keå dòng 395</li>";
echo "<li>3. Thay theå: <code>return \$hours . \"phút\";</code></li>";
echo "<li>4. Bång: <code>return \$hours . \"giò\";</code></li>";
echo "<li>5. Save file</li>";
echo "<li>6. Clear browser cache (Ctrl+F5)</li>";
echo "</ol>";

echo "<h3>&#128072; KIÊM TRA HIÊN TAÏI:</h3>";

// Show current line 395
$lines = file(__DIR__ . '/lib/NotificationHelper.php');
echo "<p><strong>Dòng 395 hiêän taïi:</strong></p>";
echo "<pre style='background-color: #f5f5f5; padding: 10px;'>";
echo htmlspecialchars($lines[394]);
echo "</pre>";

echo "<h3>&#128072; TEST SAU KHI FIX:</h3>";

require_once __DIR__ . '/lib/NotificationHelper.php';
$notificationHelper = new NotificationHelper();

// Test with 2 hours ago
$twoHoursAgo = date('Y-m-d H:i:s', time() - 7200);
$result = $notificationHelper->getTimeAgo($twoHoursAgo);

echo "<p><strong>Test (2 hours ago):</strong> '{$result}'</p>";

if ($result === '2 phút') {
    echo "<p style='color: orange;'>&#9888; Vaän coøn 'phút' - câân fix!</p>";
} elseif ($result === '2 giò') {
    echo "<p style='color: green;'>&#10004; Ðaå fix thaønh coâng!</p>";
} else {
    echo "<p style='color: red;'>&#10027; Kêå quaå khoâng mong muoñ: '{$result}'</p>";
}

echo "<h3>&#128204; KÊÉT LUÃN:</h3>";
echo "<table border='1' cellpadding='5' style='width: 100%;'>";
echo "<tr><th>Time</th><th>Hiêän taïi</th><th>Sau khi fix</th></tr>";
echo "<tr><td>&lt; 60s</td><td>Vài giây</td><td>Vài giây</td></tr>";
echo "<tr><td>2-59m</td><td>X phút</td><td>X phút</td></tr>";
echo "<tr><td>1-23h</td><td>X phút &#10027;</td><td>X giò &#10004;</td></tr>";
echo "<tr><td>1-6d</td><td>X ngày</td><td>X ngày</td></tr>";
echo "</table>";

echo "<h3>&#127911; NÊU VAÄN VÂN 'VÀI GIÂY':</h3>";
echo "<ol>";
echo "<li>1. Kiêå tra timezone: PHP vs Database</li>";
echo "<li>2. Kiêå tra notifications creation time</li>";
echo "<li>3. Clear browser cache</li>";
echo "<li>4. Kiêå tra API response</li>";
echo "</ol>";

echo "<h3>&#128073; KÊÉT QUAÅ MONG MUOÁN:</h3>";
echo "<p>Sau khi fix, notifications seå hieån thi:</p>";
echo "<ul>";
echo "<li>&quot;Vài giây&quot; cho &lt; 60s</li>";
echo "<li>&quot;X phút&quot; cho 2-59m</li>";
echo "<li>&quot;X giò&quot; cho 1-23h</li>";
echo "<li>&quot;X ngày&quot; cho 1-6d</li>";
echo "<li>&quot;dd/mm/yyyy&quot; cho &gt;6d</li>";
echo "</ul>";
?>
