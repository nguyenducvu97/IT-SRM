<?php
// KPI Export API for Staff Performance Evaluation
error_reporting(0); // Disable all error reporting
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/api_errors.log');

// Clean all output buffers and start fresh
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Function to ensure UTF-8 encoding for Vietnamese characters
function ensureUTF8($text) {
    if (is_string($text)) {
        // First, detect if text is properly UTF-8 encoded
        if (!mb_check_encoding($text, 'UTF-8')) {
            // Try to convert from common encodings to UTF-8
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        }
        
        // Ensure proper UTF-8 encoding
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'auto');
        }
        
        // Clean any invalid UTF-8 sequences
        $text = iconv('UTF-8', 'UTF-8//IGNORE', $text);
        
        // Normalize Unicode characters for better Excel compatibility
        if (function_exists('normalizer_normalize')) {
            $text = normalizer_normalize($text, Normalizer::FORM_C);
        }
    }
    return $text;
}

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

// Start session for authentication
startSession();

// Debug session information
error_log("=== KPI EXPORT SESSION DEBUG ===");
error_log("Session ID: " . session_id());
error_log("Cookie data: " . json_encode($_COOKIE));
error_log("Session data: " . json_encode($_SESSION));
error_log("===============================");

if (!isLoggedIn()) {
    error_log("KPI Export: User not logged in");
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access - Please login first']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$user_id = getCurrentUserId();
$user_role = getCurrentUserRole();

// Only admin can access KPI export
if ($user_role !== 'admin') {
    error_log("KPI Export: Access denied - User role: " . $user_role . " (admin required)");
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied - Admin only']);
    exit();
}

error_log("KPI Export: Access granted - User ID: " . $user_id . ", Role: " . $user_role);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    if ($action === 'export_kpi') {
        exportKPIExcel($db);
    } elseif ($action === 'export_kpi_detailed') {
        exportKPIDetailed($db);
    } elseif ($action === 'export_staff_details') {
        exportStaffDetails($db);
    } elseif ($action === 'get_kpi_data') {
        getKPIData($db);
    } elseif ($action === 'get_staff_list') {
        getStaffList($db);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
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
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
        
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
        fputcsv($output, []);
        fputcsv($output, ['THỐNG KÊ TỔNG HỢP']);
        fputcsv($output, ['LƯU Ý: KPI chỉ tính cho yêu cầu có ĐẦY ĐỦ thông tin (thời gian tạo, nhận, dự kiến, hoàn thành, đánh giá)']);
        fputcsv($output, []);
        fputcsv($output, [
            'TỔNG',
            '',
            '',
            '',
            $summary['total_requests'],
            $summary['total_completed'],
            '',
            '',
            round($summary['avg_response_time'], 2),
            round($summary['avg_completion_time'], 2),
            $summary['total_feedback'],
            round($summary['avg_rating'], 2),
            '',
            '',
            round($summary['avg_recommendation_rate'], 2),
            '',
            '',
            '',
            '',
            '',
            '',
            round($summary['avg_kpi_score'], 2)
        ]);
        fputcsv($output, []);
        
        // Add data with new KPI scores
        foreach ($kpi_data as $staff) {
            $row = [
                $staff['id'],
                $staff['full_name'],
                $staff['email'],
                $staff['department'] ?? 'N/A',
                $staff['total_requests'],
                $staff['completed_requests'],
                $staff['in_progress_requests'],
                $staff['open_requests'],
                round($staff['avg_response_time_minutes'], 2), // T_res in minutes
                round($staff['avg_completion_time_hours'], 2), // ACT in hours
                $staff['total_feedback'],
                round($staff['avg_rating'], 2), // K3
                $staff['positive_feedback'],
                $staff['negative_feedback'],
                round($staff['recommendation_rate'], 2), // K4 percentage
                round($staff['completion_rate'], 2),
                round($staff['feedback_response_rate'], 2),
                // New KPI Scores (1-5 scale)
                $staff['k1_score'] ?? 1, // Response Time Score
                $staff['k2_score'] ?? 1, // Completion Score
                $staff['k3_score'] ?? 1, // Quality Score
                $staff['k4_score'] ?? 1, // Recommendation Score
                // Final KPI Score (1-5)
                $staff['total_kpi_score']
            ];
            
            // Apply UTF-8 encoding to data row
            $encoded_row = array_map('ensureUTF8', $row);
            fputcsv($output, $encoded_row);
        }
        
        fclose($output);
        exit();
        
    } catch (Exception $e) {
        error_log("KPI Export Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Export failed: ' . $e->getMessage()]);
    }
}

function getKPIData($db) {
    try {
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
        
        // Convert date format from d/m/Y to Y-m-d if needed
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $start_date)) {
            $date = DateTime::createFromFormat('d/m/Y', $start_date);
            $start_date = $date ? $date->format('Y-m-d') : $start_date;
        }
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $end_date)) {
            $date = DateTime::createFromFormat('d/m/Y', $end_date);
            $end_date = $date ? $date->format('Y-m-d') : $end_date;
        }
        
        $kpi_data = getKPIDataArray($db, $start_date, $end_date);
        
        echo json_encode([
            'success' => true,
            'data' => $kpi_data,
            'period' => [
                'start_date' => $start_date,
                'end_date' => $end_date
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("KPI Data Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to get KPI data: ' . $e->getMessage()]);
    }
}

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
        // stats_query: TINH TAT CA REQUESTS CHO SUMMARY
        // feedback_query: CHI TINH REQUEST CO DAY DU 6 THONG TIN CHO K1-K4
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
        // CHI TINH REQUEST CO DAY DU 6 THONG TIN CHO K1-K4:
        // 1. created_at (thoi gian tao)
        // 2. assigned_at (thoi gian nhan)
        // 3. estimated_completion (thoi gian du kien)
        // 4. resolved_at (thoi gian hoan thanh)
        // 5. rating (danh gia chung)
        // 6. processing_results (danh gia ket qua)
        $feedback_query = "SELECT 
            COUNT(rf.id) as total_feedback,
            AVG(rf.rating) as avg_rating,
            SUM(CASE WHEN rf.rating >= 4 THEN 1 ELSE 0 END) as positive_feedback,
            SUM(CASE WHEN rf.rating <= 2 THEN 1 ELSE 0 END) as negative_feedback,
            -- Average Processing Results (APR): Handle different data types (1-5 scale matching UI)
            -- UI Mapping: 1=Rất không hài lòng, 2=Không hài lòng, 3=Bình thường, 4=Khá tốt, 5=Rất hài lòng
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
        
        // Get ALL feedback for summary (khong filter 6 thong tin)
        $all_feedback_query = "SELECT 
            COUNT(rf.id) as all_total_feedback,
            AVG(rf.rating) as all_avg_rating
            FROM request_feedback rf
            JOIN service_requests sr ON rf.service_request_id = sr.id
            WHERE sr.assigned_to = :staff_id
            AND sr.created_at BETWEEN :start_date AND CONCAT(:end_date, ' 23:59:59')";
        
        $all_feedback_stmt = $db->prepare($all_feedback_query);
        $all_feedback_stmt->bindParam(':staff_id', $staff_id);
        $all_feedback_stmt->bindParam(':start_date', $start_date);
        $all_feedback_stmt->bindParam(':end_date', $end_date);
        $all_feedback_stmt->execute();
        $all_feedback = $all_feedback_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate KPI metrics with proper formulas
        $total_requests = (int)$stats['total_requests'];
        $completed_requests = (int)$stats['completed_requests'];
        $completion_rate = $total_requests > 0 ? ($completed_requests / $total_requests) * 100 : 0;
        
        // Response Time in minutes (ART)
        $avg_response_time_minutes = (float)($stats['avg_response_time_minutes'] ?? 0);
        
        // Completion Time in hours (ACT)
        $avg_completion_time_hours = (float)($stats['avg_completion_time_hours'] ?? 0);
        
        $total_feedback = (int)$feedback['total_feedback'];
        $avg_rating = $feedback['avg_rating'] ?? 0;
        $positive_feedback = (int)$feedback['positive_feedback'];
        $negative_feedback = (int)$feedback['negative_feedback'];
        $processing_results_count = (float)$feedback['processing_results_count'];
        
        // Average Rating (AR)
        $avg_rating_score = (float)$avg_rating;
        
        // Average Processing Results (APR): Handle new calculation logic
        $recommendation_rate = $total_feedback > 0 ? ($processing_results_count / $total_feedback) * 100 : 0;
        
        // Feedback response rate (how many completed requests have feedback)
        $feedback_response_rate = $completed_requests > 0 ? ($feedback['rated_requests'] / $completed_requests) * 100 : 0;
        
        // Debug logging for KPI data
        error_log("KPI Debug - Staff ID: $staff_id, Total Requests: $total_requests, Completed: $completed_requests, Avg Response: $avg_response_time_minutes, Avg Rating: $avg_rating, Processing Results Count: $processing_results_count, Total Feedback: $total_feedback");
        error_log("KPI Debug - KPI Scores: K1=$k1_score, K2=$k2_score, K3=$k3_score, K4=$k4_score, Final=$total_kpi_score");
        error_log("KPI Debug - Processing Results Values: " . ($feedback['processing_results_values'] ?? 'none'));
        
        // Get KPI formulas from database config
        $kpi_formulas = getKPIFormulas($db);
        
        // Calculate KPI scores using configured formulas (scale 1-5)
        
        // K1: Response Time Score (Toc do phan hoi) - Parse and execute configured formula
        $k1_score = 1;
        if ($avg_response_time_minutes > 0) {
            $k1_config = $kpi_formulas['K1'] ?? ['formula' => 'default', 'weight' => 15];
            $k1_formula = $k1_config['formula'];
            
            // Parse and execute formula
            $k1_score = parseKPIFormula($k1_formula, $avg_response_time_minutes, 'K1');
        }
        
        // K2: On-time Completion Score (Tien do hoan thanh) - Use configured formula
        $k2_score = 1;
        if ($completed_requests > 0) {
            $delta_t_query = "SELECT AVG(
                CASE 
                    WHEN sr.estimated_completion IS NOT NULL AND sr.resolved_at IS NOT NULL
                    THEN TIMESTAMPDIFF(HOUR, sr.estimated_completion, sr.resolved_at)
                    ELSE NULL
                END
            ) as avg_delta_t
            FROM service_requests sr
            WHERE sr.assigned_to = :staff_id
            AND sr.status IN ('resolved', 'closed')
            AND sr.created_at BETWEEN :start_date AND CONCAT(:end_date, ' 23:59:59')
            -- Only include requests with complete information for KPI
            AND sr.assigned_at IS NOT NULL
            AND sr.estimated_completion IS NOT NULL
            AND sr.resolved_at IS NOT NULL";
            
            $delta_t_stmt = $db->prepare($delta_t_query);
            $delta_t_stmt->bindParam(':staff_id', $staff_id);
            $delta_t_stmt->bindParam(':start_date', $start_date);
            $delta_t_stmt->bindParam(':end_date', $end_date);
            $delta_t_stmt->execute();
            $delta_t_result = $delta_t_stmt->fetch(PDO::FETCH_ASSOC);
            $avg_delta_t = (float)($delta_t_result['avg_delta_t'] ?? 0);
            
            // Use K2 config
            $k2_config = $kpi_formulas['K2'] ?? ['formula' => 'default', 'weight' => 35];
            
            // 5 points: Delta T <= 0 (hoan thanh dung hoac truoc han)
            // 3 points: Delta T > 0 but tre khong qua 2 gio lam viec
            // 1 point: Tre hon 1 ngay
            if ($avg_delta_t <= 0) {
                $k2_score = 5;
            } elseif ($avg_delta_t <= 2) {
                $k2_score = 3;
            } elseif ($avg_delta_t <= 24) {
                $k2_score = 1;
            } else {
                $k2_score = 1; // Tre hon 1 ngay
            }
        }
        
        // K3: Quality Score (Chat luong xu ly) - Use configured formula
        $k3_config = $kpi_formulas['K3'] ?? ['formula' => 'default', 'weight' => 40];
        $k3_score = $avg_rating_score > 0 ? round($avg_rating_score, 1) : 1;
        
        // K4: Recommendation Score (Su tin tuong) - Use configured formula
        $k4_config = $kpi_formulas['K4'] ?? ['formula' => 'default', 'weight' => 10];
        $k4_score = 1;
        if ($total_feedback > 0) {
            if ($recommendation_rate >= 80) {
                $k4_score = 5;
            } elseif ($recommendation_rate >= 60) {
                $k4_score = 4;
            } elseif ($recommendation_rate >= 40) {
                $k4_score = 3;
            } elseif ($recommendation_rate >= 20) {
                $k4_score = 2;
            } else {
                $k4_score = 1;
            }
        }
        
        // Final KPI Score with configured weights
        // Use weights from database config instead of hardcoded values
        if ($total_requests == 0) {
            $total_kpi_score = 1.0;
        } else {
            $k1_weight = ($kpi_formulas['K1']['weight'] ?? 15) / 100;
            $k2_weight = ($kpi_formulas['K2']['weight'] ?? 35) / 100;
            $k3_weight = ($kpi_formulas['K3']['weight'] ?? 40) / 100;
            $k4_weight = ($kpi_formulas['K4']['weight'] ?? 10) / 100;
            
            $total_kpi_score = ($k1_score * $k1_weight) + ($k2_score * $k2_weight) + ($k3_score * $k3_weight) + ($k4_score * $k4_weight);
        }
        
        // Also provide normalized scores (0-100 scale) for compatibility
        $art_score = ($k1_score / 5) * 100;
        $act_score = ($k2_score / 5) * 100;
        $ar_score = ($k3_score / 5) * 100;
        $awr_score = ($k4_score / 5) * 100;
        
        $kpi_data[] = [
            'id' => $staff['id'],
            'username' => $staff['username'],
            'full_name' => $staff['full_name'],
            'email' => $staff['email'],
            'department' => $staff['department'],
            'total_requests' => $total_requests,
            'completed_requests' => $completed_requests,
            'in_progress_requests' => (int)$stats['in_progress_requests'],
            'open_requests' => (int)$stats['open_requests'],
            
            // Time Metrics
            'avg_response_time_minutes' => $avg_response_time_minutes, // T_res in minutes
            'avg_completion_time_hours' => $avg_completion_time_hours, // ACT in hours
            'avg_delta_t' => $avg_delta_t, // Delta T for K2 calculation
            
            // Quality Metrics
            'total_feedback' => $total_feedback,
            'avg_rating' => $avg_rating_score, // K3
            'positive_feedback' => $positive_feedback,
            'negative_feedback' => $negative_feedback,
            'processing_results_count' => $processing_results_count,
            'rated_requests' => (int)$feedback['rated_requests'],
            'recommendation_rate' => $recommendation_rate, // K4 percentage
            
            // New KPI Scores (1-5 scale)
            'k1_score' => $k1_score, // Response Time Score
            'k2_score' => $k2_score, // Completion Score
            'k3_score' => $k3_score, // Quality Score
            'k4_score' => $k4_score, // Recommendation Score
            
            // Legacy normalized scores (0-100 scale) for compatibility
            'art_score' => $art_score,
            'act_score' => $act_score,
            'ar_score' => $ar_score,
            'awr_score' => $awr_score,
            
            // Calculated KPI Rates
            'completion_rate' => $completion_rate,
            'feedback_response_rate' => $feedback_response_rate,
            
            // Summary data from ALL feedback (not just 6-info requests)
            'all_total_feedback' => (int)$all_feedback['all_total_feedback'],
            'all_avg_rating' => round((float)$all_feedback['all_avg_rating'], 2),
            
            // Total KPI Score (weighted, 1-5 scale)
            'total_kpi_score' => round($total_kpi_score, 1)
        ];
    }
    
    return $kpi_data;
}

function getStaffList($db) {
    try {
        $query = "SELECT id, username, full_name, department 
                  FROM users 
                  WHERE role = 'staff' 
                  ORDER BY full_name";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $staff
        ]);
        
    } catch (Exception $e) {
        error_log("Staff List Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to get staff list: ' . $e->getMessage()]);
    }
}

function exportKPIDetailed($db) {
    try {
        // Clean all output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
        
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
        
        // Convert date format if needed
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $start_date)) {
            $date = DateTime::createFromFormat('d/m/Y', $start_date);
            $start_date = $date ? $date->format('Y-m-d') : $start_date;
        }
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $end_date)) {
            $date = DateTime::createFromFormat('d/m/Y', $end_date);
            $end_date = $date ? $date->format('Y-m-d') : $end_date;
        }
        
        // Get detailed KPI data with individual request scores
        $detailed_data = getDetailedKPIData($db, $start_date, $end_date);
        
        $filename = 'KPI_Detailed_Report_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Expires: 0');
        header('Pragma: public');
        
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        
        // Set UTF-8 encoding for stream
        stream_filter_append($output, 'convert.iconv.UTF-8/UTF-8//TRANSLIT', STREAM_FILTER_WRITE);
        
        // Detailed headers
        $headers = [
            'Mã NV',
            'Họ và tên',
            'Phòng ban',
            'Mã yêu cầu',
            'Tiêu đề yêu cầu',
            'Danh mục',
            'Mô tả',
            'Ngày tạo',
            'Ngày tiếp nhận',
            'Ngày hoàn thành',
            'Thời gian dự kiến hoàn thành',
            'Thời gian phản hồi (phút)',
            'Thời gian hoàn thành (giờ)',
            'Đánh giá (1-5)',
            'Sẵn sàng giới thiệu',
            'K1 - Tốc độ phản hồi (1-5)',
            'K2 - Tiến độ hoàn thành (1-5)',
            'K3 - Đánh giá chung (1-5)',
            'K4 - Chất lượng xử lý (1-5)',
            'KPI yêu cầu (1-5)'
        ];
        
        // Apply UTF-8 encoding to headers
        $encoded_headers = array_map('ensureUTF8', $headers);
        fputcsv($output, $encoded_headers);
        
        foreach ($detailed_data as $staff) {
            // Calculate staff summary statistics
            $k1_scores = [];
            $k2_scores = [];
            $k3_scores = [];
            $k4_scores = [];
            $kpi_scores = [];
            
            foreach ($staff['requests'] as $request) {
                $k1_scores[] = $request['k1_score'] ?? 1;
                $k2_scores[] = $request['k2_score'] ?? 1;
                $k3_scores[] = $request['k3_score'] ?? 1;
                $k4_scores[] = $request['k4_score'] ?? 1;
                $kpi_scores[] = $request['request_kpi_score'] ?? 1;
                
                $row = [
                    $staff['id'],
                    $staff['full_name'],
                    $staff['department'] ?? 'N/A',
                    $request['id'],
                    $request['title'],
                    $request['category_name'] ?? 'N/A',
                    $request['description'],
                    $request['created_at'],
                    $request['assigned_at'] ?? 'N/A',
                    $request['resolved_at'] ?? 'N/A',
                    $request['estimated_completion'] ?? 'N/A',
                    $request['response_time_minutes'] ?? 'N/A',
                    $request['completion_time_hours'] ?? 'N/A',
                    $request['rating'] ?? 'N/A',
                    $request['processing_results'] ?? 'N/A',
                    round($request['k1_score'] ?? 1, 1),
                    round($request['k2_score'] ?? 1, 1),
                    round($request['k3_score'] ?? 1, 1),
                    round($request['k4_score'] ?? 1, 1),
                    round($request['request_kpi_score'] ?? 1, 1)
                ];
                
                // Apply UTF-8 encoding to data row
                $encoded_row = array_map('ensureUTF8', $row);
                fputcsv($output, $encoded_row);
            }
            
            // Add staff summary row after all requests
            if (count($staff['requests']) > 0) {
                $k1_avg = count($k1_scores) > 0 ? array_sum($k1_scores) / count($k1_scores) : 0;
                $k2_avg = count($k2_scores) > 0 ? array_sum($k2_scores) / count($k2_scores) : 0;
                $k3_avg = count($k3_scores) > 0 ? array_sum($k3_scores) / count($k3_scores) : 0;
                $k4_avg = count($k4_scores) > 0 ? array_sum($k4_scores) / count($k4_scores) : 0;
                $kpi_avg = count($kpi_scores) > 0 ? array_sum($kpi_scores) / count($kpi_scores) : 0;
                
                // Get KPI formulas from database
                $kpi_formulas = getKPIFormulas($db);
                
                // Calculate weighted KPI score using configured weights
                $total_kpi_score = ($k1_avg * ($kpi_formulas['K1']['weight']/100)) + 
                                 ($k2_avg * ($kpi_formulas['K2']['weight']/100)) + 
                                 ($k3_avg * ($kpi_formulas['K3']['weight']/100)) + 
                                 ($k4_avg * ($kpi_formulas['K4']['weight']/100));
                
                fputcsv($output, []); // Empty row
                fputcsv($output, ['THỐNG KÊ TỔNG HỢP CHO ' . strtoupper($staff['full_name'])]);
                fputcsv($output, ['Tổng yêu cầu', count($staff['requests'])]);
                fputcsv($output, ['K1 TB', round($k1_avg, 2)]);
                fputcsv($output, ['K2 TB', round($k2_avg, 2)]);
                fputcsv($output, ['K3 TB', round($k3_avg, 2)]);
                fputcsv($output, ['K4 TB', round($k4_avg, 2)]);
                fputcsv($output, ['KPI TB', round($kpi_avg, 2)]);
                fputcsv($output, ['KPI Tổng hợp (có trọng số)', round($total_kpi_score, 2)]);
                fputcsv($output, []);
                
                // Add KPI calculation formulas from database
                fputcsv($output, ['CÔNG THỨC TÍNH KPI']);
                fputcsv($output, ['K1 - Tốc độ phản hồi (1-5):', $kpi_formulas['K1']['formula'], $kpi_formulas['K1']['description']]);
                fputcsv($output, ['K2 - Tiến độ hoàn thành (1-5):', $kpi_formulas['K2']['formula'], $kpi_formulas['K2']['description']]);
                fputcsv($output, ['K3 - Đánh giá chung (1-5):', $kpi_formulas['K3']['formula'], $kpi_formulas['K3']['description']]);
                fputcsv($output, ['K4 - Chất lượng xử lý (1-5):', $kpi_formulas['K4']['formula'], $kpi_formulas['K4']['description']]);
                fputcsv($output, ['KPI Tổng hợp (1-5):', $kpi_formulas['TOTAL']['formula'], $kpi_formulas['TOTAL']['description']]);
                fputcsv($output, ['Ghi chú:', 'Công thức áp dụng cho dòng 2 tương tự. Copy công thức cho các dòng khác.']);
                fputcsv($output, []);
            } else {
                // Staff has no requests
                fputcsv($output, []); // Empty row
                fputcsv($output, ['THỐNG KÊ TỔNG HỢP CHO ' . strtoupper($staff['full_name'])]);
                fputcsv($output, ['Tổng yêu cầu', 0]);
                fputcsv($output, ['K1 TB', 0]);
                fputcsv($output, ['K2 TB', 0]);
                fputcsv($output, ['K3 TB', 0]);
                fputcsv($output, ['K4 TB', 0]);
                fputcsv($output, ['KPI TB', 0]);
                fputcsv($output, ['KPI Tổng hợp (có trọng số)', 0]);
                fputcsv($output, []);
                fputcsv($output, ['Ghi chú:', 'Không có yêu cầu nào trong khoảng thời gian này.']);
                fputcsv($output, []);
            }
        }
        
        fclose($output);
        exit();
        
    } catch (Exception $e) {
        error_log("KPI Detailed Export Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Export failed: ' . $e->getMessage()]);
    }
}

function exportStaffDetails($db) {
    try {
        // Clean all output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
        
        $staff_id = isset($_GET['staff_id']) ? (int)$_GET['staff_id'] : 0;
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
        
        if ($staff_id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Staff ID is required']);
            exit();
        }
        
        // Convert date format if needed
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $start_date)) {
            $date = DateTime::createFromFormat('d/m/Y', $start_date);
            $start_date = $date ? $date->format('Y-m-d') : $start_date;
        }
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $end_date)) {
            $date = DateTime::createFromFormat('d/m/Y', $end_date);
            $end_date = $date ? $date->format('Y-m-d') : $end_date;
        }
        
        // Get staff details
        $staff_query = "SELECT id, username, full_name, email, department 
                       FROM users WHERE id = :staff_id";
        $staff_stmt = $db->prepare($staff_query);
        $staff_stmt->bindParam(':staff_id', $staff_id);
        $staff_stmt->execute();
        $staff = $staff_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$staff) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Staff not found']);
            exit();
        }
        
        // Get staff's detailed KPI data
        error_log("Getting detailed KPI for staff_id: $staff_id, start: $start_date, end: $end_date");
        $detailed_data = getStaffDetailedKPI($db, $staff_id, $start_date, $end_date);
        error_log("Detailed KPI data result: " . print_r($detailed_data, true));
        
        if (!$detailed_data || !isset($detailed_data['requests'])) {
            error_log("Invalid detailed data structure");
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Invalid data structure returned']);
            exit();
        }
        
        $filename = 'KPI_Staff_' . $staff['full_name'] . '_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Expires: 0');
        header('Pragma: public');
        
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        
        // Better encoding handling for Vietnamese characters
        // Use UTF-8 without transliteration to preserve Vietnamese characters
        // Excel will properly display UTF-8 with BOM
        
        // Staff info header
        fputcsv($output, ['THÔNG TIN STAFF']);
        fputcsv($output, ['Mã NV', $staff['id']]);
        fputcsv($output, ['Hô và tên', $staff['full_name']]);
        fputcsv($output, ['Email', $staff['email']]);
        fputcsv($output, ['Phòng ban', $staff['department'] ?? 'N/A']);
        fputcsv($output, []);
        
        // Summary header
        fputcsv($output, ['TÔNG KÊ KPI']);
        fputcsv($output, ['Tông yêu càu', count($detailed_data['requests'])]);
        fputcsv($output, ['K1 TB', $detailed_data['summary']['k1_avg'] ?? 1]);
        fputcsv($output, ['K2 TB', $detailed_data['summary']['k2_avg'] ?? 1]);
        fputcsv($output, ['K3 TB', $detailed_data['summary']['k3_avg'] ?? 1]);
        fputcsv($output, ['K4 TB', $detailed_data['summary']['k4_avg'] ?? 1]);
        fputcsv($output, ['KPI TB', $detailed_data['summary']['kpi_avg'] ?? 1]);
        fputcsv($output, []);
        
        // Detailed requests header
        $headers = [
            'Phòng ban',
            'Mã yêu cầu',
            'Tiêu đề yêu cầu',
            'Danh mục',
            'Mô tả',
            'Ngày tạo',
            'Ngày tiếp nhận',
            'Ngày hoàn thành',
            'Thời gian dự kiến hoàn thành',
            'Thời gian phản hồi (phút)',
            'Thời gian hoàn thành (giờ)',
            'Đánh giá (1-5)',
            'Sẵn sàng giới thiệu',
            'K1 - Tốc độ phản hồi (1-5)',
            'K2 - Tiến độ hoàn thành (1-5)',
            'K3 - Đánh giá chung (1-5)',
            'K4 - Chất lượng xử lý (1-5)',
            'KPI yêu cầu (1-5)'
        ];
        
        fputcsv($output, ['CHI TIÊT YÊU CÀU']);
        // Apply UTF-8 encoding to headers
        $encoded_headers = array_map('ensureUTF8', $headers);
        fputcsv($output, $encoded_headers);
        
        foreach ($detailed_data['requests'] as $request) {
            $row = [
                $request['id'],
                $request['title'],
                $request['category_name'] ?? 'N/A',
                $request['description'],
                $request['created_at'],
                $request['assigned_at'] ?? 'N/A',
                $request['resolved_at'] ?? 'N/A',
                $request['estimated_completion'] ?? 'N/A',
                $request['response_time_minutes'] ?? 'N/A',
                $request['completion_time_hours'] ?? 'N/A',
                $request['rating'] ?? 'N/A',
                $request['processing_results'] ?? 'N/A',
                $request['k1_score'] ?? 1,
                $request['k2_score'] ?? 1,
                $request['k3_score'] ?? 1,
                $request['k4_score'] ?? 1,
                $request['request_kpi_score'] ?? 1
            ];
            
            // Apply UTF-8 encoding to data row
            $encoded_row = array_map('ensureUTF8', $row);
            fputcsv($output, $encoded_row);
        }
        
        fclose($output);
        exit();
        
    } catch (Exception $e) {
        error_log("Staff Details Export Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Export failed: ' . $e->getMessage()]);
    }
}

function parseKPIFormula($formula, $value, $kpi_type) {
    try {
        error_log("Parsing KPI formula: $formula for value: $value");
        
        // Handle default formula
        if ($formula === 'default') {
            switch ($kpi_type) {
                case 'K1':
                    if ($value <= 15) return 5;
                    elseif ($value <= 60) return 3;
                    else return 1;
                case 'K2':
                    if ($value <= 0) return 5;
                    elseif ($value <= 2) return 3;
                    else return 1;
                case 'K3':
                    return max(1, min(5, $value));
                case 'K4':
                    if ($value >= 80) return 5;
                    elseif ($value >= 60) return 4;
                    elseif ($value >= 40) return 3;
                    elseif ($value >= 20) return 2;
                    else return 1;
                default:
                    return 1;
            }
        }
        
        // Parse Excel-style formula
        // Example: MAX(1; MIN(5; 5 - (L2/10000)))
        // Remove leading = if present
        $formula = ltrim($formula, '=');
        
        // Replace L2 with actual value
        $formula = str_replace('L2', $value, $formula);
        $formula = str_replace('M2', $value, $formula);
        $formula = str_replace('N2', $value, $formula);
        $formula = str_replace('O2', $value, $formula);
        
        // Replace Excel functions with PHP equivalents
        $formula = str_replace('MAX', 'max', $formula);
        $formula = str_replace('MIN', 'min', $formula);
        $formula = str_replace(';', ',', $formula);
        
        // Evaluate the formula safely
        // Create a safe evaluation context
        $allowed_functions = ['max', 'min', 'round', 'abs', 'ceil', 'floor'];
        $allowed_operators = ['+', '-', '*', '/', '(', ')', ','];
        
        // Check if formula contains only allowed characters
        if (!preg_match('/^[0-9\.\+\-\*\/\(\),\s_a-zA-Z]+$/', $formula)) {
            error_log("Invalid formula characters: $formula");
            return 1;
        }
        
        // Evaluate the formula
        $result = eval("return $formula;");
        
        // Ensure result is within 1-5 range
        $result = max(1, min(5, $result));
        
        error_log("Formula result: $result");
        return $result;
        
    } catch (Exception $e) {
        error_log("Error parsing formula '$formula': " . $e->getMessage());
        return 1; // Default to 1 on error
    }
}

function getKPIFormulas($db) {
    try {
        // Check if kpi_config table exists
        $check_table = "SHOW TABLES LIKE 'kpi_config'";
        $result = $db->query($check_table);
        
        if ($result->rowCount() == 0) {
            // Return default formulas if table doesn't exist
            return [
                'K1' => ['formula' => '=MAX(1; MIN(5; 5 - (L2/30)))', 'description' => 'L2 = Thoi gian phan hoi (phut)', 'weight' => 15],
                'K2' => ['formula' => '=MAX(1; MIN(5; 5 - (M2/24)))', 'description' => 'M2 = Thoi gian hoan thanh (gio)', 'weight' => 35],
                'K3' => ['formula' => '=MAX(1; MIN(5; N2))', 'description' => 'N2 = Danh gia chung (1-5)', 'weight' => 40],
                'K4' => ['formula' => '=MAX(1; MIN(5; O2/20))', 'description' => 'O2 = Danh gia staff xu ly yeu cau', 'weight' => 10],
                'TOTAL' => ['formula' => '=(P2*0.15)+(Q2*0.35)+(R2*0.40)+(S2*0.10)', 'description' => 'P2=K1(15%), Q2=K2(35%), R2=K3(40%), S2=K4(10%)', 'weight' => 100]
            ];
        }
        
        $query = "SELECT kpi_type, formula, description, weight_percentage FROM kpi_config";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $formulas = [];
        foreach ($configs as $config) {
            $formulas[$config['kpi_type']] = [
                'formula' => $config['formula'],
                'description' => $config['description'],
                'weight' => $config['weight_percentage']
            ];
        }
        
        return $formulas;
        
    } catch (Exception $e) {
        error_log("Error getting KPI formulas: " . $e->getMessage());
        // Return default formulas on error
        return [
            'K1' => ['formula' => '=MAX(1; MIN(5; 5 - (L2/30)))', 'description' => 'L2 = Thoi gian phan hoi (phut)', 'weight' => 15],
            'K2' => ['formula' => '=MAX(1; MIN(5; 5 - (M2/24)))', 'description' => 'M2 = Thoi gian hoan thanh (gio)', 'weight' => 35],
            'K3' => ['formula' => '=MAX(1; MIN(5; N2))', 'description' => 'N2 = Danh gia chung (1-5)', 'weight' => 40],
            'K4' => ['formula' => '=MAX(1; MIN(5; O2/20))', 'description' => 'O2 = Danh gia staff xu ly yeu cau', 'weight' => 10],
            'TOTAL' => ['formula' => '=(P2*0.15)+(Q2*0.35)+(R2*0.40)+(S2*0.10)', 'description' => 'P2=K1(15%), Q2=K2(35%), R2=K3(40%), S2=K4(10%)', 'weight' => 100]
        ];
    }
}

function getDetailedKPIData($db, $start_date, $end_date) {
    $detailed_data = [];
    
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
        
        // Get all requests for this staff with detailed information
        // Only include requests with complete information for KPI calculation
        $requests_query = "SELECT sr.*, c.name as category_name, rf.rating, rf.processing_results
                          FROM service_requests sr
                          LEFT JOIN categories c ON sr.category_id = c.id
                          LEFT JOIN request_feedback rf ON sr.id = rf.service_request_id
                          WHERE sr.assigned_to = :staff_id 
                          AND sr.created_at BETWEEN :start_date AND CONCAT(:end_date, ' 23:59:59')
                          -- Only include requests with complete information for KPI
                          AND sr.assigned_at IS NOT NULL
                          AND sr.estimated_completion IS NOT NULL
                          AND sr.resolved_at IS NOT NULL
                          AND rf.rating IS NOT NULL
                          AND rf.processing_results IS NOT NULL
                          ORDER BY sr.created_at DESC";
        
        $requests_stmt = $db->prepare($requests_query);
        $requests_stmt->bindParam(':staff_id', $staff_id);
        $requests_stmt->bindParam(':start_date', $start_date);
        $requests_stmt->bindParam(':end_date', $end_date);
        $requests_stmt->execute();
        $requests = $requests_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $staff_requests = [];
        foreach ($requests as $request) {
            // Calculate response time
            $response_time_minutes = null;
            if ($request['assigned_at']) {
                $created = new DateTime($request['created_at']);
                $assigned = new DateTime($request['assigned_at']);
                $response_time_minutes = $created->diff($assigned)->i + $created->diff($assigned)->h * 60;
            }
            
            // Calculate completion time
            $completion_time_hours = null;
            if ($request['resolved_at']) {
                $created = new DateTime($request['created_at']);
                $resolved = new DateTime($request['resolved_at']);
                $completion_time_hours = $created->diff($resolved)->h + $created->diff($resolved)->d * 24;
            }
            
            // Calculate K1 score for this request
            $k1_score = 1;
            if ($response_time_minutes !== null && $response_time_minutes > 0) {
                if ($response_time_minutes <= 15) {
                    $k1_score = 5;
                } elseif ($response_time_minutes <= 60) {
                    $k1_score = 3;
                } else {
                    $k1_score = 1;
                }
            }
            
            // Calculate K2 score for this request
            $k2_score = 1;
            if ($request['estimated_completion'] && $request['resolved_at']) {
                $estimated = new DateTime($request['estimated_completion']);
                $resolved = new DateTime($request['resolved_at']);
                $delta_t = $estimated->diff($resolved)->h + $estimated->diff($resolved)->d * 24;
                $delta_t = $resolved > $estimated ? $delta_t : -$delta_t;
                
                if ($delta_t <= 0) {
                    $k2_score = 5;
                } elseif ($delta_t <= 2) {
                    $k2_score = 3;
                } else {
                    $k2_score = 1;
                }
            }
            
            // Calculate K3 score for this request
            $k3_score = $request['rating'] > 0 ? round($request['rating'], 1) : 1;
            
            // Calculate K4 score for this request (1-5 scale matching UI)
            // UI Mapping: 1=Rất không hài lòng, 2=Không hài lòng, 3=Bình thường, 4=Khá tốt, 5=Rất hài lòng
            $k4_score = 1;
            if ($request['processing_results'] == 5) {
                $k4_score = 5;  // Rất hài lòng
            } elseif ($request['processing_results'] == 4) {
                $k4_score = 4;  // Khá tốt
            } elseif ($request['processing_results'] == 3) {
                $k4_score = 3;  // Bình thường
            } elseif ($request['processing_results'] == 2) {
                $k4_score = 2;  // Không hài lòng
            } elseif ($request['processing_results'] == 1) {
                $k4_score = 1;  // Rất không hài lòng
            } else {
                $k4_score = 1;  // Mặc định
            }
            
            // Calculate request KPI score
            $request_kpi_score = ($k1_score * 0.15) + ($k2_score * 0.35) + ($k3_score * 0.40) + ($k4_score * 0.10);
            
            $staff_requests[] = [
                'id' => $request['id'],
                'title' => $request['title'],
                'category_name' => $request['category_name'],
                'description' => $request['description'],
                'created_at' => $request['created_at'],
                'assigned_at' => $request['assigned_at'],
                'resolved_at' => $request['resolved_at'],
                'estimated_completion' => $request['estimated_completion'],
                'response_time_minutes' => $response_time_minutes,
                'completion_time_hours' => $completion_time_hours,
                'rating' => $request['rating'],
                'processing_results' => $request['processing_results'],
                'k1_score' => $k1_score,
                'k2_score' => $k2_score,
                'k3_score' => $k3_score,
                'k4_score' => $k4_score,
                'request_kpi_score' => round($request_kpi_score, 2)
            ];
        }
        
        $detailed_data[] = [
            'id' => $staff['id'],
            'full_name' => $staff['full_name'],
            'department' => $staff['department'],
            'requests' => $staff_requests
        ];
    }
    
    return $detailed_data;
}

function getStaffDetailedKPI($db, $staff_id, $start_date, $end_date) {
    error_log("getStaffDetailedKPI called with staff_id: $staff_id, start: $start_date, end: $end_date");
    
    // Get staff details
    $staff_query = "SELECT id, username, email, full_name, department 
                   FROM users WHERE id = :staff_id";
    $staff_stmt = $db->prepare($staff_query);
    $staff_stmt->bindParam(':staff_id', $staff_id);
    $staff_stmt->execute();
    $staff = $staff_stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("Staff query result: " . print_r($staff, true));
    
    if (!$staff) {
        error_log("Staff not found for ID: $staff_id");
        return null;
    }
    
    // Get all requests for this staff with complete information for KPI
    $requests_query = "SELECT sr.*, c.name as category_name, rf.rating, rf.processing_results
                      FROM service_requests sr
                      LEFT JOIN categories c ON sr.category_id = c.id
                      LEFT JOIN request_feedback rf ON sr.id = rf.service_request_id
                      WHERE sr.assigned_to = :staff_id 
                      AND sr.created_at BETWEEN :start_date AND CONCAT(:end_date, ' 23:59:59')
                      -- Only include requests with complete information for KPI
                      AND sr.assigned_at IS NOT NULL
                      AND sr.estimated_completion IS NOT NULL
                      AND sr.resolved_at IS NOT NULL
                      AND rf.rating IS NOT NULL
                      AND rf.processing_results IS NOT NULL
                      ORDER BY sr.created_at DESC";
    
    $requests_stmt = $db->prepare($requests_query);
    $requests_stmt->bindParam(':staff_id', $staff_id);
    $requests_stmt->bindParam(':start_date', $start_date);
    $requests_stmt->bindParam(':end_date', $end_date);
    $requests_stmt->execute();
    $requests = $requests_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $staff_requests = [];
    $k1_scores = [];
    $k2_scores = [];
    $k3_scores = [];
    $k4_scores = [];
    $kpi_scores = [];
    
    foreach ($requests as $request) {
        // Calculate response time
        $response_time_minutes = null;
        if ($request['assigned_at']) {
            $created = new DateTime($request['created_at']);
            $assigned = new DateTime($request['assigned_at']);
            $response_time_minutes = $created->diff($assigned)->i + $created->diff($assigned)->h * 60;
        }
        
        // Calculate completion time
        $completion_time_hours = null;
        if ($request['resolved_at']) {
            $created = new DateTime($request['created_at']);
            $resolved = new DateTime($request['resolved_at']);
            $completion_time_hours = $created->diff($resolved)->h + $created->diff($resolved)->d * 24;
        }
        
        // Calculate K1 score
        $k1_score = 1;
        if ($response_time_minutes !== null && $response_time_minutes > 0) {
            if ($response_time_minutes <= 15) {
                $k1_score = 5;
            } elseif ($response_time_minutes <= 60) {
                $k1_score = 3;
            } else {
                $k1_score = 1;
            }
        }
        
        // Calculate K2 score
        $k2_score = 1;
        if ($request['estimated_completion'] && $request['resolved_at']) {
            $estimated = new DateTime($request['estimated_completion']);
            $resolved = new DateTime($request['resolved_at']);
            $delta_t = $estimated->diff($resolved)->h + $estimated->diff($resolved)->d * 24;
            $delta_t = $resolved > $estimated ? $delta_t : -$delta_t;
            
            if ($delta_t <= 0) {
                $k2_score = 5;
            } elseif ($delta_t <= 2) {
                $k2_score = 3;
            } else {
                $k2_score = 1;
            }
        }
        
        // Calculate K3 score
        $k3_score = $request['rating'] > 0 ? round($request['rating'], 1) : 1;
        
        // Calculate K4 score (1-5 scale)
        $k4_score = 1;
        if ($request['processing_results'] == 5) {
            $k4_score = 5;  // Rất hài lòng
        } elseif ($request['processing_results'] == 4) {
            $k4_score = 4;  // Hài lòng
        } elseif ($request['processing_results'] == 3) {
            $k4_score = 3;  // Bình thường
        } elseif ($request['processing_results'] == 2) {
            $k4_score = 2;  // Không hài lòng
        } elseif ($request['processing_results'] == 1) {
            $k4_score = 1;  // Rất không hài lòng
        } else {
            $k4_score = 1;  // Mặc định
        }
        
        // Calculate request KPI score
        $request_kpi_score = ($k1_score * 0.15) + ($k2_score * 0.35) + ($k3_score * 0.40) + ($k4_score * 0.10);
        
        $staff_requests[] = [
            'id' => $request['id'],
            'title' => $request['title'],
            'category_name' => $request['category_name'],
            'description' => $request['description'],
            'created_at' => $request['created_at'],
            'assigned_at' => $request['assigned_at'],
            'resolved_at' => $request['resolved_at'],
            'estimated_completion' => $request['estimated_completion'],
            'response_time_minutes' => $response_time_minutes,
            'completion_time_hours' => $completion_time_hours,
            'rating' => $request['rating'],
            'processing_results' => $request['processing_results'],
            'k1_score' => round($k1_score, 1),
            'k2_score' => round($k2_score, 1),
            'k3_score' => round($k3_score, 1),
            'k4_score' => round($k4_score, 1),
            'request_kpi_score' => round($request_kpi_score, 1)
        ];
        
        // Collect scores for summary
        $k1_scores[] = $k1_score;
        $k2_scores[] = $k2_score;
        $k3_scores[] = $k3_score;
        $k4_scores[] = $k4_score;
        $kpi_scores[] = $request_kpi_score;
    }
    
    // Calculate summary averages
    $summary = [
        'k1_avg' => count($k1_scores) > 0 ? round(array_sum($k1_scores) / count($k1_scores), 2) : 1,
        'k2_avg' => count($k2_scores) > 0 ? round(array_sum($k2_scores) / count($k2_scores), 2) : 1,
        'k3_avg' => count($k3_scores) > 0 ? round(array_sum($k3_scores) / count($k3_scores), 2) : 1,
        'k4_avg' => count($k4_scores) > 0 ? round(array_sum($k4_scores) / count($k4_scores), 2) : 1,
        'kpi_avg' => count($kpi_scores) > 0 ? round(array_sum($kpi_scores) / count($kpi_scores), 2) : 1
    ];
    
    return [
        'staff' => $staff,
        'requests' => $staff_requests,
        'summary' => $summary
    ];
}

function calculateKPISummary($kpi_data) {
    $total_requests = 0;
    $total_completed = 0;
    $total_feedback = 0;
    $sum_response_time = 0;
    $sum_completion_time = 0;
    $sum_rating = 0;
    $sum_recommendation_rate = 0;
    $sum_kpi_score = 0;
    $staff_with_requests = 0;
    $staff_with_feedback = 0;
    
    foreach ($kpi_data as $staff) {
        $total_requests += $staff['total_requests'];
        $total_completed += $staff['completed_requests'];
        $total_feedback += $staff['all_total_feedback']; // Use ALL feedback for summary
        
        // Only include staff with complete KPI data in averages
        if ($staff['total_requests'] > 0) {
            $sum_response_time += $staff['avg_response_time_minutes'];
            $sum_completion_time += $staff['avg_completion_time_hours'];
            $sum_kpi_score += $staff['total_kpi_score'];
            $staff_with_requests++;
        }
        
        // Only include staff with feedback in rating averages
        if ($staff['all_total_feedback'] > 0) {
            $sum_rating += $staff['all_avg_rating']; // Use ALL feedback rating for summary
            $sum_recommendation_rate += $staff['recommendation_rate'];
            $staff_with_feedback++;
        }
    }
    
    return [
        'total_requests' => $total_requests,
        'total_completed' => $total_completed,
        'total_feedback' => $total_feedback,
        'avg_response_time' => $staff_with_requests > 0 ? $sum_response_time / $staff_with_requests : 0,
        'avg_completion_time' => $staff_with_requests > 0 ? $sum_completion_time / $staff_with_requests : 0,
        'avg_rating' => $staff_with_feedback > 0 ? $sum_rating / $staff_with_feedback : 0,
        'avg_recommendation_rate' => $staff_with_feedback > 0 ? $sum_recommendation_rate / $staff_with_feedback : 0,
        'avg_kpi_score' => count($kpi_data) > 0 ? $sum_kpi_score / count($kpi_data) : 0
    ];
}
?>
