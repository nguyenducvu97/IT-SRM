<?php
// Test Excel Export with date filters
echo "<h2>🧪 Test Excel Export với bộ lọc ngày tháng</h2>";

// Test 1: Test date format conversion
echo "<h3>📅 Test chuyển đổi định dạng ngày</h3>";
$test_dates = [
    '2026-01-01',
    '01/01/2026', 
    '2026-05-06',
    '06/05/2026'
];

foreach ($test_dates as $date) {
    $converted = $date;
    // Convert date format from d/m/Y to Y-m-d if needed
    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $converted)) {
        $date_obj = DateTime::createFromFormat('d/m/Y', $converted);
        $converted = $date_obj ? $date_obj->format('Y-m-d') : $converted;
    }
    echo "<p>Original: $date → Converted: $converted</p>";
}

// Test 2: Test API call with different date ranges
echo "<h3>🔍 Test API calls với các khoảng ngày khác nhau</h3>";

$test_ranges = [
    ['start' => '2026-01-01', 'end' => '2026-01-31', 'desc' => 'Tháng 1/2026'],
    ['start' => '2026-05-01', 'end' => '2026-05-06', 'desc' => 'Tuần này tháng 5/2026'],
    ['start' => '2026-04-01', 'end' => '2026-04-30', 'desc' => 'Tháng 4/2026'],
];

foreach ($test_ranges as $range) {
    echo "<h4>Test: {$range['desc']} ({$range['start']} - {$range['end']})</h4>";
    
    // Test KPI data API
    $api_url = "http://localhost/it-service-request/api/kpi_export.php?action=get_kpi_data&start_date={$range['start']}&end_date={$range['end']}";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Cookie: PHPSESSID=" . ($_COOKIE['PHPSESSID'] ?? 'test')
        ]
    ]);
    
    $response = file_get_contents($api_url, false, $context);
    
    if ($response) {
        $data = json_decode($response, true);
        if ($data && $data['success']) {
            $total_requests = 0;
            foreach ($data['data'] as $staff) {
                $total_requests += $staff['total_requests'];
            }
            echo "<p style='color: green;'>✅ API Success - Total requests: $total_requests</p>";
            
            // Show first staff data for verification
            if (!empty($data['data'])) {
                $first_staff = $data['data'][0];
                echo "<small>Staff sample: {$first_staff['full_name']} - Requests: {$first_staff['total_requests']}</small>";
            }
        } else {
            echo "<p style='color: red;'>❌ API Error: " . ($data['message'] ?? 'Unknown error') . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Failed to call API</p>";
    }
    echo "<hr>";
}

// Test 3: Test Excel export directly
echo "<h3>📊 Test Excel Export trực tiếp</h3>";
echo "<p><a href='api/kpi_export.php?action=export_kpi&start_date=2026-05-01&end_date=2026-05-06' target='_blank'>📥 Download Excel (1-6/5/2026)</a></p>";
echo "<p><a href='api/kpi_export.php?action=export_kpi&start_date=2026-01-01&end_date=2026-01-31' target='_blank'>📥 Download Excel (Tháng 1/2026)</a></p>";

// Test 4: Check database data for verification
echo "<h3>🔎 Kiểm tra dữ liệu trong database</h3>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        // Check requests in different date ranges
        $ranges = [
            ['start' => '2026-01-01', 'end' => '2026-01-31', 'desc' => 'Tháng 1'],
            ['start' => '2026-05-01', 'end' => '2026-05-06', 'desc' => 'Tuần này'],
        ];
        
        foreach ($ranges as $range) {
            $query = "SELECT COUNT(*) as count, 
                     MIN(created_at) as min_date, 
                     MAX(created_at) as max_date
                     FROM service_requests 
                     WHERE created_at BETWEEN :start AND CONCAT(:end, ' 23:59:59')";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':start', $range['start']);
            $stmt->bindParam(':end', $range['end']);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<p><strong>{$range['desc']}:</strong> {$result['count']} requests";
            if ($result['count'] > 0) {
                echo " (Từ: {$result['min_date']} Đến: {$result['max_date']})";
            }
            echo "</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}

echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3, h4 { color: #333; }
hr { margin: 20px 0; }
</style>";
?>
