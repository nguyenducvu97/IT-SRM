<?php
// Simple test of exportKPIExcel function
echo "<h2>🧪 Simple Test exportKPIExcel Function</h2>";

// Include required files
require_once 'config/session.php';
require_once 'config/database.php';

// Start session and login as admin
startSession();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

echo "<p>✅ Session started and logged in as admin</p>";

// Get database connection
$database = new Database();
$db = $database->getConnection();

if ($db) {
    echo "<p>✅ Database connected</p>";
    
    // Set GET parameters
    $_GET['action'] = 'export_kpi';
    $_GET['start_date'] = '2026-04-01';
    $_GET['end_date'] = '2026-04-30';
    
    echo "<p>✅ GET parameters set: action={$_GET['action']}, start_date={$_GET['start_date']}, end_date={$_GET['end_date']}</p>";
    
    // Test the function directly
    try {
        // Capture output
        ob_start();
        exportKPIExcel($db);
        $output = ob_get_clean();
        
        echo "<p>✅ Function executed</p>";
        echo "<p>Output length: " . strlen($output) . " characters</p>";
        
        if (strlen($output) > 0) {
            file_put_contents('simple_test_export.csv', $output);
            echo "<p>✅ File saved: <a href='simple_test_export.csv' target='_blank'>simple_test_export.csv</a></p>";
            
            // Show first few lines
            $lines = explode("\n", $output);
            echo "<h4>First 10 lines:</h4>";
            echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
            for ($i = 0; $i < min(10, count($lines)); $i++) {
                echo htmlspecialchars($lines[$i]) . "\n";
            }
            echo "</pre>";
        } else {
            echo "<p>❌ No output generated</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error: " . $e->getMessage() . "</p>";
        echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
    }
    
} else {
    echo "<p>❌ Database connection failed</p>";
}

// Include the function
function exportKPIExcel($db) {
    try {
        echo "<p>🔧 Inside exportKPIExcel function</p>";
        
        // Get date range from parameters
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-01-01');
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        
        echo "<p>🔧 Date range: $start_date to $end_date</p>";
        
        // Get KPI data
        $kpi_data = getKPIDataArray($db, $start_date, $end_date);
        
        echo "<p>🔧 KPI data count: " . count($kpi_data) . "</p>";
        
        if (empty($kpi_data)) {
            echo "<p>⚠️ No KPI data found</p>";
            return;
        }
        
        // Create CSV content
        $csv_content = "\xEF\xBB\xBF"; // UTF-8 BOM
        
        // Headers
        $headers = [
            'Mã NV',
            'Họ và tên',
            'Email',
            'Phòng ban',
            'Tổng yêu cầu',
            'Đã hoàn thành',
            'Đang xử lý',
            'Chờ xử lý',
            'Thời gian phản hồi TB (phút)',
            'Thời gian hoàn thành TB (giờ)',
            'Tổng đánh giá',
            'Điểm đánh giá TB (1-5)',
            'Đánh giá tích cực',
            'Đánh giá tiêu cực',
            'Sẵn sàng giới thiệu (%)',
            'Tỷ lệ hoàn thành (%)',
            'Tỷ lệ phản hồi (%)',
            'Điểm K1 - Tốc độ phản hồi (1-5)',
            'Điểm K2 - Tiến độ hoàn thành (1-5)',
            'Điểm K3 - Đánh giá chung (1-5)',
            'Điểm K4 - Chất lượng xử lý (1-5)',
            'Điểm KPI Tổng hợp (1-5)'
        ];
        
        $csv_content .= implode(',', $headers) . "\n";
        
        // Summary info
        $csv_content .= "\n";
        $csv_content .= "THỐNG KÊ TỔNG HỢP\n";
        $csv_content .= "Khoảng thời gian: $start_date đến $end_date\n";
        $csv_content .= "Ngày xuất: " . date('d/m/Y H:i:s') . "\n";
        $csv_content .= "LƯU Ý: KPI chỉ tính cho yêu cầu có ĐẦY ĐỦ thông tin\n";
        $csv_content .= "\n";
        
        // Calculate summary
        $total_requests = 0;
        $completed_requests = 0;
        $in_progress_requests = 0;
        $open_requests = 0;
        
        foreach ($kpi_data as $staff) {
            $total_requests += $staff['total_requests'];
            $completed_requests += $staff['completed_requests'];
            $in_progress_requests += $staff['in_progress_requests'];
            $open_requests += $staff['open_requests'];
        }
        
        // Summary row
        $csv_content .= "TỔNG,,,,$total_requests,$completed_requests,$in_progress_requests,$open_requests\n";
        $csv_content .= "\n";
        
        // Data rows
        foreach ($kpi_data as $row) {
            $csv_row = [
                $row['id'],
                $row['full_name'],
                $row['email'],
                $row['department'],
                $row['total_requests'],
                $row['completed_requests'],
                $row['in_progress_requests'],
                $row['open_requests'],
                round($row['avg_response_time_minutes'], 2),
                round($row['avg_completion_time_hours'], 2),
                $row['total_feedback'],
                round($row['avg_rating'], 2),
                $row['positive_feedback'],
                $row['negative_feedback'],
                round($row['recommendation_rate'], 2),
                round($row['completion_rate'], 2),
                round($row['feedback_response_rate'], 2),
                $row['k1_score'],
                $row['k2_score'],
                $row['k3_score'],
                $row['k4_score'],
                $row['total_kpi_score']
            ];
            $csv_content .= implode(',', $csv_row) . "\n";
        }
        
        echo "<p>🔧 CSV content generated, length: " . strlen($csv_content) . "</p>";
        
        // Output CSV
        echo $csv_content;
        
    } catch (Exception $e) {
        echo "<p>❌ Function error: " . $e->getMessage() . "</p>";
    }
}

function getKPIDataArray($db, $start_date, $end_date) {
    echo "<p>🔧 Inside getKPIDataArray</p>";
    
    $kpi_data = [];
    
    // Get staff users only
    $staff_query = "SELECT id, username, email, full_name, department 
                   FROM users 
                   WHERE role = 'staff' 
                   ORDER BY full_name";
    $staff_stmt = $db->prepare($staff_query);
    $staff_stmt->execute();
    $staff_list = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>🔧 Found " . count($staff_list) . " staff members</p>";
    
    foreach ($staff_list as $staff) {
        $staff_id = $staff['id'];
        
        // Get statistics
        $stats_query = "SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN sr.status IN ('resolved', 'closed') THEN 1 ELSE 0 END) as completed_requests,
        SUM(CASE WHEN sr.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_requests,
        SUM(CASE WHEN sr.status = 'open' THEN 1 ELSE 0 END) as open_requests,
        AVG(CASE WHEN sr.assigned_at IS NOT NULL 
            THEN TIMESTAMPDIFF(MINUTE, sr.created_at, sr.assigned_at) 
            ELSE NULL END) as avg_response_time_minutes,
        AVG(CASE WHEN sr.status IN ('resolved', 'closed') AND sr.resolved_at IS NOT NULL
            THEN TIMESTAMPDIFF(HOUR, sr.created_at, sr.resolved_at) 
            ELSE NULL END) as avg_completion_time_hours
        FROM service_requests sr
        WHERE sr.assigned_to = :staff_id 
        AND sr.created_at BETWEEN :start_date AND CONCAT(:end_date, ' 23:59:59')";
        
        $stats_stmt = $db->prepare($stats_query);
        $stats_stmt->bindParam(':staff_id', $staff_id);
        $stats_stmt->bindParam(':start_date', $start_date);
        $stats_stmt->bindParam(':end_date', $end_date);
        $stats_stmt->execute();
        $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get feedback data
        $feedback_query = "SELECT 
            COUNT(rf.id) as total_feedback,
            AVG(rf.rating) as avg_rating,
            SUM(CASE WHEN rf.rating >= 4 THEN 1 ELSE 0 END) as positive_feedback,
            SUM(CASE WHEN rf.rating <= 2 THEN 1 ELSE 0 END) as negative_feedback,
            COUNT(DISTINCT rf.service_request_id) as rated_requests
            FROM request_feedback rf
            JOIN service_requests sr ON rf.service_request_id = sr.id
            WHERE sr.assigned_to = :staff_id
            AND sr.status IN ('resolved', 'closed')
            AND sr.created_at BETWEEN :start_date AND CONCAT(:end_date, ' 23:59:59')
            AND sr.assigned_at IS NOT NULL
            AND sr.estimated_completion IS NOT NULL
            AND sr.resolved_at IS NOT NULL
            AND rf.rating IS NOT NULL
            AND rf.processing_results IS NOT NULL";
        
        $feedback_stmt = $db->prepare($feedback_query);
        $feedback_stmt->bindParam(':staff_id', $staff_id);
        $feedback_stmt->bindParam(':start_date', $start_date);
        $feedback_stmt->bindParam(':end_date', $end_date);
        $feedback_stmt->execute();
        $feedback = $feedback_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Extract and calculate metrics
        $total_requests = (int)($stats['total_requests'] ?? 0);
        $completed_requests = (int)($stats['completed_requests'] ?? 0);
        $in_progress_requests = (int)($stats['in_progress_requests'] ?? 0);
        $open_requests = (int)($stats['open_requests'] ?? 0);
        $avg_response_time_minutes = (float)($stats['avg_response_time_minutes'] ?? 0);
        $avg_completion_time_hours = (float)($stats['avg_completion_time_hours'] ?? 0);
        
        // Feedback metrics
        $total_feedback = (int)($feedback['total_feedback'] ?? 0);
        $avg_rating_score = (float)($feedback['avg_rating'] ?? 0);
        $positive_feedback = (int)($feedback['positive_feedback'] ?? 0);
        $negative_feedback = (int)($feedback['negative_feedback'] ?? 0);
        $rated_requests = (int)($feedback['rated_requests'] ?? 0);
        
        // Calculate derived metrics
        $completion_rate = $total_requests > 0 ? ($completed_requests / $total_requests) * 100 : 0;
        $feedback_response_rate = $completed_requests > 0 ? ($rated_requests / $completed_requests) * 100 : 0;
        $recommendation_rate = $total_feedback > 0 ? 80 : 0; // Simplified
        
        // Calculate KPI scores (simplified)
        $k1_score = $avg_response_time_minutes > 0 && $avg_response_time_minutes <= 60 ? 3 : 1;
        $k2_score = $completed_requests > 0 ? 3 : 1;
        $k3_score = $avg_rating_score > 0 ? round($avg_rating_score, 1) : 1;
        $k4_score = $total_feedback > 0 ? 4 : 1;
        
        // Total KPI Score
        $total_kpi_score = ($k1_score * 0.15) + ($k2_score * 0.35) + ($k3_score * 0.40) + ($k4_score * 0.10);
        
        $kpi_data[] = array_merge($staff, [
            'total_requests' => $total_requests,
            'completed_requests' => $completed_requests,
            'in_progress_requests' => $in_progress_requests,
            'open_requests' => $open_requests,
            'avg_response_time_minutes' => $avg_response_time_minutes,
            'avg_completion_time_hours' => $avg_completion_time_hours,
            'total_feedback' => $total_feedback,
            'avg_rating' => $avg_rating_score,
            'positive_feedback' => $positive_feedback,
            'negative_feedback' => $negative_feedback,
            'processing_results_count' => 0,
            'rated_requests' => $rated_requests,
            'recommendation_rate' => $recommendation_rate,
            'completion_rate' => $completion_rate,
            'feedback_response_rate' => $feedback_response_rate,
            'k1_score' => $k1_score,
            'k2_score' => $k2_score,
            'k3_score' => $k3_score,
            'k4_score' => $k4_score,
            'total_kpi_score' => round($total_kpi_score, 2)
        ]);
    }
    
    echo "<p>🔧 Returning " . count($kpi_data) . " KPI records</p>";
    return $kpi_data;
}

echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3, h4 { color: #333; }
pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; }
</style>";
?>
