<?php
// Debug Excel Export Issue
require_once 'config/session.php';
require_once 'config/database.php';

// Start session and login as admin
startSession();

// Simulate admin login
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

echo "<h2>🔍 Debug Excel Export - Bộ lọc ngày tháng</h2>";

// Test different date ranges
$test_cases = [
    [
        'start' => '2026-04-01',
        'end' => '2026-04-30',
        'desc' => 'Tháng 4/2026 (nên có 94 requests)'
    ],
    [
        'start' => '2026-05-01', 
        'end' => '2026-05-06',
        'desc' => '1-6/5/2026 (nên có 0 requests vì chưa assign)'
    ],
    [
        'start' => '2026-01-01',
        'end' => '2026-12-31',
        'desc' => 'Cả năm 2026 (nên có 94 requests)'
    ]
];

foreach ($test_cases as $case) {
    echo "<h3>📅 Test: {$case['desc']}</h3>";
    echo "<p><strong>Khoảng ngày:</strong> {$case['start']} - {$case['end']}</p>";
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        if ($db) {
            // Test 1: Count ALL requests (không filter assigned_to)
            $all_query = "SELECT COUNT(*) as count 
                         FROM service_requests 
                         WHERE created_at BETWEEN :start AND CONCAT(:end, ' 23:59:59')";
            
            $stmt = $db->prepare($all_query);
            $stmt->bindParam(':start', $case['start']);
            $stmt->bindParam(':end', $case['end']);
            $stmt->execute();
            $all_count = $stmt->fetchColumn();
            
            echo "<p><strong>Tất cả requests:</strong> $all_count</p>";
            
            // Test 2: Count ASSIGNED requests (filter assigned_to IS NOT NULL)
            $assigned_query = "SELECT COUNT(*) as count 
                              FROM service_requests 
                              WHERE assigned_to IS NOT NULL 
                              AND created_at BETWEEN :start AND CONCAT(:end, ' 23:59:59')";
            
            $stmt = $db->prepare($assigned_query);
            $stmt->bindParam(':start', $case['start']);
            $stmt->bindParam(':end', $case['end']);
            $stmt->execute();
            $assigned_count = $stmt->fetchColumn();
            
            echo "<p><strong>Requests đã assign:</strong> $assigned_count</p>";
            
            // Test 3: Get KPI data using the same function as export
            $kpi_data = getKPIDataArray($db, $case['start'], $case['end']);
            $kpi_total_requests = 0;
            foreach ($kpi_data as $staff) {
                $kpi_total_requests += $staff['total_requests'];
            }
            
            echo "<p><strong>KPI data total requests:</strong> $kpi_total_requests</p>";
            
            // Test 4: Show sample data
            if (!empty($kpi_data)) {
                echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
                echo "<tr><th>Staff</th><th>Dept</th><th>Total</th><th>Completed</th><th>K1</th><th>K2</th><th>K3</th><th>K4</th><th>KPI Total</th></tr>";
                foreach ($kpi_data as $staff) {
                    echo "<tr>";
                    echo "<td>{$staff['full_name']}</td>";
                    echo "<td>{$staff['department']}</td>";
                    echo "<td><strong>{$staff['total_requests']}</strong></td>";
                    echo "<td>{$staff['completed_requests']}</td>";
                    echo "<td>{$staff['k1_score']}</td>";
                    echo "<td>{$staff['k2_score']}</td>";
                    echo "<td>{$staff['k3_score']}</td>";
                    echo "<td>{$staff['k4_score']}</td>";
                    echo "<td><strong>{$staff['total_kpi_score']}</strong></td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
            // Test 5: Check if date format conversion works
            echo "<h4>📝 Test chuyển đổi định dạng ngày:</h4>";
            $converted_start = $case['start'];
            $converted_end = $case['end'];
            
            // Convert date format from d/m/Y to Y-m-d if needed
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $converted_start)) {
                $date = DateTime::createFromFormat('d/m/Y', $converted_start);
                $converted_start = $date ? $date->format('Y-m-d') : $converted_start;
            }
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $converted_end)) {
                $date = DateTime::createFromFormat('d/m/Y', $converted_end);
                $converted_end = $date ? $date->format('Y-m-d') : $converted_end;
            }
            
            echo "<p>Original: {$case['start']} → Converted: $converted_start</p>";
            echo "<p>Original: {$case['end']} → Converted: $converted_end</p>";
            
            // Summary
            if ($all_count > 0 && $assigned_count == 0) {
                echo "<p style='color: orange;'><strong>⚠️ Phát hiện:</strong> Có requests nhưng chưa được assign cho staff nào!</p>";
            } elseif ($all_count == $assigned_count && $assigned_count == $kpi_total_requests) {
                echo "<p style='color: green;'><strong>✅ OK:</strong> Bộ lọc ngày tháng hoạt động đúng!</p>";
            } else {
                echo "<p style='color: red;'><strong>❌ Lỗi:</strong> Có sự không nhất quán trong dữ liệu!</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
    echo "<hr>";
}

// Include the KPI export function (copy from kpi_export.php)
function getKPIDataArray($db, $start_date, $end_date) {
    $kpi_data = [];
    
    // Get staff users only (exclude admin from KPI calculation)
    $staff_query = "SELECT id, username, email, full_name, department 
                   FROM users 
                   WHERE role = 'staff' 
                   ORDER BY full_name";
    $staff_stmt = $db->prepare($staff_query);
    $staff_stmt->execute();
    $staff_list = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($staff_list as $staff) {
        $staff_id = $staff['id'];
        
        // Enhanced KPI statistics with proper formulas
        $stats_query = "SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN sr.status IN ('resolved', 'closed') THEN 1 ELSE 0 END) as completed_requests,
        SUM(CASE WHEN sr.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_requests,
        SUM(CASE WHEN sr.status = 'open' THEN 1 ELSE 0 END) as open_requests,
        -- Average Response Time (ART): Time from submitted to acknowledged
        AVG(CASE WHEN sr.assigned_at IS NOT NULL 
            THEN TIMESTAMPDIFF(MINUTE, sr.created_at, sr.assigned_at) 
            ELSE NULL END) as avg_response_time_minutes,
        -- Average Completion Time (ACT): Time from submitted to closed (includes both resolved and closed)
        AVG(CASE WHEN sr.status IN ('resolved', 'closed') AND sr.resolved_at IS NOT NULL
            THEN TIMESTAMPDIFF(HOUR, sr.created_at, sr.resolved_at) 
            ELSE NULL END) as avg_completion_time_hours,
        -- Include estimated completion for K2 calculation
        AVG(sr.estimated_completion) as avg_estimated_completion
        FROM service_requests sr
        WHERE sr.assigned_to = :staff_id 
        AND sr.created_at BETWEEN :start_date AND CONCAT(:end_date, ' 23:59:59')";
        
        $stats_stmt = $db->prepare($stats_query);
        $stats_stmt->bindParam(':staff_id', $staff_id);
        $stats_stmt->bindParam(':start_date', $start_date);
        $stats_stmt->bindParam(':end_date', $end_date);
        $stats_stmt->execute();
        $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get user feedback ratings with proper AWR calculation
        $feedback_query = "SELECT 
            COUNT(rf.id) as total_feedback,
            AVG(rf.rating) as avg_rating,
            SUM(CASE WHEN rf.rating >= 4 THEN 1 ELSE 0 END) as positive_feedback,
            SUM(CASE WHEN rf.rating <= 2 THEN 1 ELSE 0 END) as negative_feedback,
            -- Average Processing Results (APR): Handle different data types (1-5 scale matching UI)
            SUM(CASE 
                WHEN rf.processing_results = '5' OR rf.processing_results = 5 THEN 1.0
                WHEN rf.processing_results = '4' OR rf.processing_results = 4 THEN 0.8
                WHEN rf.processing_results = '3' OR rf.processing_results = 3 THEN 0.6
                WHEN rf.processing_results = '2' OR rf.processing_results = 2 THEN 0.4
                WHEN rf.processing_results = '1' OR rf.processing_results = 1 THEN 0.2
                WHEN rf.processing_results = 'yes' THEN 1.0
                WHEN rf.processing_results = 'maybe' THEN 0.6
                WHEN rf.processing_results = 'no' THEN 0.2
                ELSE 0 
            END) as processing_results_count,
            COUNT(DISTINCT rf.service_request_id) as rated_requests,
            -- Also get raw processing_results values for debugging
            GROUP_CONCAT(DISTINCT rf.processing_results) as processing_results_values
            FROM request_feedback rf
            JOIN service_requests sr ON rf.service_request_id = sr.id
            WHERE sr.assigned_to = :staff_id
            AND sr.status IN ('resolved', 'closed')
            AND sr.created_at BETWEEN :start_date AND CONCAT(:end_date, ' 23:59:59')
            -- Only include requests with complete information for KPI
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
        $processing_results_count = (float)($feedback['processing_results_count'] ?? 0);
        $rated_requests = (int)($feedback['rated_requests'] ?? 0);
        
        // Calculate derived metrics
        $completion_rate = $total_requests > 0 ? ($completed_requests / $total_requests) * 100 : 0;
        $feedback_response_rate = $completed_requests > 0 ? ($rated_requests / $completed_requests) * 100 : 0;
        $recommendation_rate = $total_feedback > 0 ? ($processing_results_count / $total_feedback) * 100 : 0;
        
        // Get KPI formulas from database config
        $kpi_formulas = getKPIFormulas($db);
        
        // Calculate KPI scores using configured formulas (scale 1-5)
        
        // K1: Response Time Score (Toc do phan hoi) - Theo công thức mới
        $k1_score = 1;
        if ($avg_response_time_minutes > 0) {
            // Công thức: =MAX(1; MIN(5; 5 - (L2/30)))
            // Quy tắc điểm: 5 điểm ≤ 15 phút, 3 điểm ≤ 60 phút, 1 điểm > 60 phút
            if ($avg_response_time_minutes <= 15) {
                $k1_score = 5;
            } elseif ($avg_response_time_minutes <= 60) {
                $k1_score = 3;
            } else {
                $k1_score = 1;
            }
        }
        
        // K2: On-time Completion Score (Tien do hoan thanh) - Use configured formula
        $k2_score = 1;
        if ($completed_requests > 0) {
            $delta_ratio_query = "SELECT AVG(
                CASE 
                    WHEN sr.estimated_completion IS NOT NULL AND sr.resolved_at IS NOT NULL
                    THEN TIMESTAMPDIFF(HOUR, sr.created_at, sr.resolved_at) / TIMESTAMPDIFF(HOUR, sr.created_at, sr.estimated_completion)
                    ELSE NULL
                END
            ) as avg_delta_ratio
            FROM service_requests sr
            WHERE sr.assigned_to = :staff_id
            AND sr.status IN ('resolved', 'closed')
            AND sr.created_at BETWEEN :start_date AND CONCAT(:end_date, ' 23:59:59')
            AND sr.estimated_completion IS NOT NULL
            AND sr.resolved_at IS NOT NULL";
            
            $delta_ratio_stmt = $db->prepare($delta_ratio_query);
            $delta_ratio_stmt->bindParam(':staff_id', $staff_id);
            $delta_ratio_stmt->bindParam(':start_date', $start_date);
            $delta_ratio_stmt->bindParam(':end_date', $end_date);
            $delta_ratio_stmt->execute();
            $delta_ratio_result = $delta_ratio_stmt->fetch(PDO::FETCH_ASSOC);
            $avg_delta_ratio = (float)($delta_ratio_result['avg_delta_ratio'] ?? 1);
            
            $avg_delta_t = $avg_delta_ratio; // For compatibility
            
            // 5 points: Delta ratio <= 0.8 (hoan thanh truoc hoac dung han)
            // 4 points: Delta ratio <= 0.9 (hoan thanh gan dung han)
            // 3 points: Delta ratio <= 1.0 (hoan thanh sau han khong qua 1 ngay)
            // 2 points: Delta ratio <= 1.1 (hoan thanh sau han 1 ngay)
            // 1 point: Delta ratio > 1.2 (tre hon 1 ngay)
            if ($avg_delta_ratio <= 0.8) {
                $k2_score = 5;
            } elseif ($avg_delta_ratio <= 0.9) {
                $k2_score = 4;
            } elseif ($avg_delta_ratio <= 1.0) {
                $k2_score = 3;
            } elseif ($avg_delta_ratio <= 1.1) {
                $k2_score = 2;
            } else {
                $k2_score = 1; // tre hon 1 ngay
            }
        } else {
            $avg_delta_t = null; // No completed requests
        }
        
        // K3: Quality Score (Chat luong xu ly) - Use configured formula
        $k3_config = $kpi_formulas['K3'] ?? ['formula' => 'default', 'weight' => 40];
        $k3_score = $avg_rating_score > 0 ? round($avg_rating_score, 1) : 1;
        
        // K4: Processing Results Score (Chat luong xu ly) - Theo công thức mới
        $k4_config = $kpi_formulas['K4'] ?? ['formula' => 'default', 'weight' => 10];
        $k4_score = 1;
        if ($total_feedback > 0) {
            // Công thức: =MAX(1; MIN(5; O2/20))
            // O2 = Đánh giá kết quả xử lý (percentage 0-100)
            // Quy tắc điểm: 5 điểm ≥ 80%, 4 điểm ≥ 60%, 3 điểm ≥ 40%, 2 điểm ≥ 20%, 1 điểm < 20%
            if ($recommendation_rate >= 80) {
                $k4_score = 5;  // Rất hài lòng
            } elseif ($recommendation_rate >= 60) {
                $k4_score = 4;  // Hài lòng
            } elseif ($recommendation_rate >= 40) {
                $k4_score = 3;  // Bình thường
            } elseif ($recommendation_rate >= 20) {
                $k4_score = 2;  // Không hài lòng
            } else {
                $k4_score = 1;  // Rất không hài lòng
            }
        }
        
        // Final KPI Score with configured weights
        // Công thức: =(P2*0.15)+(Q2*0.35)+(R2*0.40)+(S2*0.10)
        // P2=K1(15%), Q2=K2(35%), R2=K3(40%), S2=K4(10%)
        if ($total_requests == 0) {
            $total_kpi_score = 1.0; // Staff không có yêu cầu: điểm thấp nhất
        } else {
            $k1_weight = ($kpi_formulas['K1']['weight'] ?? 15) / 100;
            $k2_weight = ($kpi_formulas['K2']['weight'] ?? 35) / 100;
            $k3_weight = ($kpi_formulas['K3']['weight'] ?? 40) / 100;
            $k4_weight = ($kpi_formulas['K4']['weight'] ?? 10) / 100;
            
            $total_kpi_score = ($k1_score * $k1_weight) + ($k2_score * $k2_weight) + ($k3_score * $k3_weight) + ($k4_score * $k4_weight);
        }
        
        // Debug logging for KPI data
        error_log("KPI Debug - Staff ID: $staff_id, Total Requests: $total_requests, Completed: $completed_requests, Avg Response: $avg_response_time_minutes, Avg Rating: $avg_rating_score, Processing Results Count: $processing_results_count, Total Feedback: $total_feedback");
        error_log("KPI Debug - KPI Scores: K1=$k1_score, K2=$k2_score, K3=$k3_score, K4=$k4_score, Final=$total_kpi_score");
        error_log("KPI Debug - Processing Results Values: " . ($feedback['processing_results_values'] ?? 'none'));
        
        $kpi_data[] = array_merge($staff, [
            'total_requests' => $total_requests,
            'completed_requests' => $completed_requests,
            'in_progress_requests' => $in_progress_requests,
            'open_requests' => $open_requests,
            'avg_response_time_minutes' => $avg_response_time_minutes,
            'avg_completion_time_hours' => $avg_completion_time_hours,
            'avg_delta_t' => $avg_delta_t,
            'total_feedback' => $total_feedback,
            'avg_rating' => $avg_rating_score,
            'positive_feedback' => $positive_feedback,
            'negative_feedback' => $negative_feedback,
            'processing_results_count' => $processing_results_count,
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
    
    return $kpi_data;
}

function getKPIFormulas($db) {
    try {
        $stmt = $db->prepare("SELECT kpi_type, formula, weight_percentage FROM kpi_config ORDER BY id");
        $stmt->execute();
        $formulas = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $formulas[$row['kpi_type']] = [
                'formula' => $row['formula'],
                'weight' => (float)$row['weight_percentage']
            ];
        }
        
        return $formulas;
    } catch (Exception $e) {
        error_log("Error getting KPI formulas: " . $e->getMessage());
        return [
            'K1' => ['formula' => 'default', 'weight' => 15],
            'K2' => ['formula' => 'default', 'weight' => 35],
            'K3' => ['formula' => 'default', 'weight' => 40],
            'K4' => ['formula' => 'default', 'weight' => 10]
        ];
    }
}

echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3, h4 { color: #333; }
hr { margin: 20px 0; }
table { border-collapse: collapse; margin: 10px 0; }
th, td { padding: 8px; text-align: center; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
</style>";
?>
