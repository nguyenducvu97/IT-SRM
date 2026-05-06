<?php
// Test Excel Export Directly
require_once 'config/session.php';
require_once 'config/database.php';

// Start session and login as admin
startSession();

// Simulate admin login
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

echo "<h2>🧪 Test Excel Export Direct</h2>";

// Simulate GET parameters for export
$_GET['action'] = 'export_kpi';
$_GET['start_date'] = '2026-04-01';
$_GET['end_date'] = '2026-04-30';

echo "<p><strong>Parameters:</strong> action={$_GET['action']}, start_date={$_GET['start_date']}, end_date={$_GET['end_date']}</p>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "<p style='color: green;'>✅ Database connected</p>";
        
        // Test the KPI data function first
        $kpi_data = getKPIDataArray($db, $_GET['start_date'], $_GET['end_date']);
        
        echo "<h3>📊 KPI Data Preview:</h3>";
        echo "<p><strong>Total staff:</strong> " . count($kpi_data) . "</p>";
        
        if (!empty($kpi_data)) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Staff</th><th>Dept</th><th>Total</th><th>Completed</th><th>K1</th><th>K2</th><th>K3</th><th>K4</th><th>KPI Total</th></tr>";
            
            $total_requests = 0;
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
                $total_requests += $staff['total_requests'];
            }
            echo "</table>";
            echo "<p><strong>Total requests in KPI data:</strong> $total_requests</p>";
            
            // Now test the actual export function (but capture output instead of sending to browser)
            echo "<h3>📥 Testing Export Function:</h3>";
            
            // Capture the output
            ob_start();
            exportKPIExcel($db);
            $output = ob_get_clean();
            
            echo "<p><strong>Output length:</strong> " . strlen($output) . " characters</p>";
            
            if (strlen($output) > 0) {
                // Save to file for inspection
                file_put_contents('test_export.csv', $output);
                echo "<p style='color: green;'>✅ Export saved to <a href='test_export.csv' target='_blank'>test_export.csv</a></p>";
                
                // Show first few lines
                $lines = explode("\n", $output);
                echo "<h4>First 10 lines of CSV:</h4>";
                echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
                for ($i = 0; $i < min(10, count($lines)); $i++) {
                    echo htmlspecialchars($lines[$i]) . "\n";
                }
                echo "</pre>";
            } else {
                echo "<p style='color: red;'>❌ No output from export function</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ No KPI data found</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

// Include the necessary functions
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

function exportKPIExcel($db) {
    try {
        // Clean all output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
        
        // Get date range from parameters
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-01-01'); // Default to start of year
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); // Default to today
        
        // Convert date format from d/m/Y to Y-m-d if needed
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $start_date)) {
            $date = DateTime::createFromFormat('d/m/Y', $start_date);
            $start_date = $date ? $date->format('Y-m-d') : $start_date;
        }
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $end_date)) {
            $date = DateTime::createFromFormat('d/m/Y', $end_date);
            $end_date = $date ? $date->format('Y-m-d') : $end_date;
        }
        
        // Validate date format
        if (!DateTime::createFromFormat('Y-m-d', $start_date) || !DateTime::createFromFormat('Y-m-d', $end_date)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid date format. Use Y-m-d']);
            exit();
        }
        
        // Get KPI data
        $kpi_data = getKPIDataArray($db, $start_date, $end_date);
        
        // Set filename
        $filename = 'KPI_Staff_Report_' . date('Y-m-d_H-i-s') . '.csv';
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Expires: 0');
        header('Pragma: public');
        
        // Open output stream with UTF-8 encoding
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for proper Excel display
        fwrite($output, "\xEF\xBB\xBF");
        
        // Better encoding handling for Vietnamese characters
        // Use UTF-8 without transliteration to preserve Vietnamese characters
        // Excel will properly display UTF-8 with BOM
        
        // Add headers with new KPI scoring system
        $headers = [
            'Mã NV',
            'Họ và tên',
            'Email',    
            'Phòng ban',
            'Tổng yêu cầu',
            'Đã hoàn thành',
            'Đang xử lý',
            'Chờ xử lý',
            'Thời gian phản hồi TB (phút) - T_res',
            'Thời gian hoàn thành TB (giờ)',
            'Tổng đánh giá',
            'Điểm đánh giá TB (1-5) - K3',
            'Đánh giá tích cực',
            'Đánh giá tiêu cực',
            'Sẵn sàng giới thiệu (%) - K4',
            'Tỷ lệ hoàn thành (%)',
            'Tỷ lệ phản hồi (%)',
            // New KPI Scores (1-5 scale)
            'Điểm K1 - Tốc độ phản hồi (1-5)',
            'Điểm K2 - Tiến độ hoàn thành (1-5)',
            'Điểm K3 - Đánh giá chung (1-5)',
            'Điểm K4 - Chất lượng xử lý (1-5)',
            // Final KPI Score
            'Điểm KPI Tổng hợp (1-5)'
        ];
        
        // Apply UTF-8 encoding to headers
        $encoded_headers = array_map('ensureUTF8', $headers);
        fputcsv($output, $encoded_headers);
        
        // Calculate summary statistics (only for staff with requests)
        $summary = calculateKPISummary($kpi_data);
        
        // Add summary row
        fputcsv($output, array_map('ensureUTF8', [
            'Tổng cộng',
            'Báo cáo KPI Staff',
            'Khoảng thời gian: ' . $start_date . ' đến ' . $end_date,
            'Ngày xuất: ' . date('d/m/Y H:i:s'),
            $summary['total_requests'],
            $summary['completed_requests'],
            $summary['in_progress_requests'],
            $summary['open_requests'],
            round($summary['avg_response_time_minutes'], 2),
            round($summary['avg_completion_time_hours'], 2),
            $summary['total_feedback'],
            round($summary['avg_rating'], 2),
            $summary['positive_feedback'],
            $summary['negative_feedback'],
            round($summary['recommendation_rate'], 2),
            round($summary['completion_rate'], 2),
            round($summary['feedback_response_rate'], 2),
            round($summary['k1_score'], 2),
            round($summary['k2_score'], 2),
            round($summary['k3_score'], 2),
            round($summary['k4_score'], 2),
            round($summary['total_kpi_score'], 2)
        ]));
        
        // Add data rows
        foreach ($kpi_data as $row) {
            fputcsv($output, array_map('ensureUTF8', [
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
                round($row['k1_score'], 2),
                round($row['k2_score'], 2),
                round($row['k3_score'], 2),
            round($row['k4_score'], 2),
                round($row['total_kpi_score'], 2)
            ]));
        }
        
        fclose($output);
        exit();
        
    } catch (Exception $e) {
        error_log("Error exporting KPI Excel: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error exporting KPI data: ' . $e->getMessage()]);
        exit();
    }
}

function calculateKPISummary($kpi_data) {
    $summary = [
        'total_requests' => 0,
        'completed_requests' => 0,
        'in_progress_requests' => 0,
        'open_requests' => 0,
        'avg_response_time_minutes' => 0,
        'avg_completion_time_hours' => 0,
        'total_feedback' => 0,
        'avg_rating' => 0,
        'positive_feedback' => 0,
        'negative_feedback' => 0,
        'recommendation_rate' => 0,
        'completion_rate' => 0,
        'feedback_response_rate' => 0,
        'k1_score' => 0,
        'k2_score' => 0,
        'k3_score' => 0,
        'k4_score' => 0,
        'total_kpi_score' => 0
    ];
    
    $staff_with_requests = 0;
    $total_response_time = 0;
    $total_completion_time = 0;
    $total_rating = 0;
    $rated_staff_count = 0;
    
    foreach ($kpi_data as $staff) {
        $summary['total_requests'] += $staff['total_requests'];
        $summary['completed_requests'] += $staff['completed_requests'];
        $summary['in_progress_requests'] += $staff['in_progress_requests'];
        $summary['open_requests'] += $staff['open_requests'];
        $summary['total_feedback'] += $staff['total_feedback'];
        $summary['positive_feedback'] += $staff['positive_feedback'];
        $summary['negative_feedback'] += $staff['negative_feedback'];
        $summary['recommendation_rate'] += $staff['recommendation_rate'];
        $summary['completion_rate'] += $staff['completion_rate'];
        $summary['feedback_response_rate'] += $staff['feedback_response_rate'];
        $summary['k1_score'] += $staff['k1_score'];
        $summary['k2_score'] += $staff['k2_score'];
        $summary['k3_score'] += $staff['k3_score'];
        $summary['k4_score'] += $staff['k4_score'];
        $summary['total_kpi_score'] += $staff['total_kpi_score'];
        
        if ($staff['total_requests'] > 0) {
            $staff_with_requests++;
            $total_response_time += $staff['avg_response_time_minutes'];
            $total_completion_time += $staff['avg_completion_time_hours'];
        }
        
        if ($staff['total_feedback'] > 0) {
            $total_rating += $staff['avg_rating'];
            $rated_staff_count++;
        }
    }
    
    if ($staff_with_requests > 0) {
        $summary['avg_response_time_minutes'] = $total_response_time / $staff_with_requests;
        $summary['avg_completion_time_hours'] = $total_completion_time / $staff_with_requests;
    }
    
    if ($rated_staff_count > 0) {
        $summary['avg_rating'] = $total_rating / $rated_staff_count;
    }
    
    if ($staff_with_requests > 0) {
        $summary['recommendation_rate'] = $summary['recommendation_rate'] / $staff_with_requests;
        $summary['completion_rate'] = $summary['completion_rate'] / $staff_with_requests;
        $summary['feedback_response_rate'] = $summary['feedback_response_rate'] / $staff_with_requests;
        $summary['k1_score'] = $summary['k1_score'] / $staff_with_requests;
        $summary['k2_score'] = $summary['k2_score'] / $staff_with_requests;
        $summary['k3_score'] = $summary['k3_score'] / $staff_with_requests;
        $summary['k4_score'] = $summary['k4_score'] / $staff_with_requests;
        $summary['total_kpi_score'] = $summary['total_kpi_score'] / $staff_with_requests;
    }
    
    return $summary;
}

function ensureUTF8($string) {
    return mb_convert_encoding($string, 'UTF-8', 'UTF-8');
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
