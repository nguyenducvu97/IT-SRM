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
        // Remove any existing encoding issues
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        // Ensure proper UTF-8 encoding
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'auto');
        }
        // Handle any remaining special characters
        $text = iconv('UTF-8', 'UTF-8//IGNORE', $text);
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
        
        // Set UTF-8 encoding for stream
        stream_filter_append($output, 'convert.iconv.UTF-8/UTF-8//TRANSLIT', STREAM_FILTER_WRITE);
        
        // Add headers with new KPI scoring system
        $headers = [
            'Mã NV',
            'Hô và tên',
            'Email',
            'Phòng ban',
            'Tông yêu càu',
            'Da hoàn thành',
            'Dang xù lý',
            'Cho xù lý',
            'Thòi gian phan hòi TB (phút) - T_res',
            'Thòi gian hoàn thành TB (giò)',
            'Tông danh giá',
            'Dièm danh giá TB (1-5) - K3',
            'Danh giá tích cúc',
            'Danh giá tiêu cúc',
            'San sàng giói thiêu (%) - K4',
            'Ty lê hoàn thành (%)',
            'Ty lê phan hòi (%)',
            // New KPI Scores (1-5 scale)
            'Dièm K1 - Tôc dô phan hòi (1-5)',
            'Dièm K2 - Tiên dô hoàn thành (1-5)',
            'Dièm K3 - Chat lòe xù lý (1-5)',
            'Dièm K4 - Sù tin tuòng (1-5)',
            // Final KPI Score
            'Dièm KPI Tông hòp (1-5)'
        ];
        
        // Apply UTF-8 encoding to headers
        $encoded_headers = array_map('ensureUTF8', $headers);
        fputcsv($output, $encoded_headers);
        
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
    
    // Get all staff users (including admin who can handle requests)
    $staff_query = "SELECT id, username, email, full_name, department 
                   FROM users 
                   WHERE role IN ('admin', 'staff') 
                   ORDER BY full_name";
    $staff_stmt = $db->prepare($staff_query);
    $staff_stmt->execute();
    $staff_list = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($staff_list as $staff) {
        $staff_id = $staff['id'];
        
        // Enhanced KPI statistics with proper formulas
        $stats_query = "SELECT 
            COUNT(*) as total_requests,
            SUM(CASE WHEN status IN ('resolved', 'closed') THEN 1 ELSE 0 END) as completed_requests,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_requests,
            SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_requests,
            -- Average Response Time (ART): Time from submitted to acknowledged
            AVG(CASE WHEN assigned_at IS NOT NULL 
                THEN TIMESTAMPDIFF(MINUTE, created_at, assigned_at) 
                ELSE NULL END) as avg_response_time_minutes,
            -- Average Completion Time (ACT): Time from submitted to closed (includes both resolved and closed)
            AVG(CASE WHEN status IN ('resolved', 'closed') AND resolved_at IS NOT NULL
                THEN TIMESTAMPDIFF(HOUR, created_at, resolved_at) 
                ELSE NULL END) as avg_completion_time_hours,
            -- Include estimated completion for K2 calculation
            AVG(estimated_completion) as avg_estimated_completion
            FROM service_requests 
            WHERE assigned_to = :staff_id 
            AND created_at BETWEEN :start_date AND CONCAT(:end_date, ' 23:59:59')";
        
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
            -- Average Would Recommend (AWR): Handle different data types (yes/no/maybe/1-5)
            SUM(CASE 
                WHEN rf.would_recommend = 'yes' OR rf.would_recommend = '1' OR rf.would_recommend = 1 OR rf.would_recommend = 5 THEN 1 
                WHEN rf.would_recommend = 'maybe' OR rf.would_recommend = '2' OR rf.would_recommend = 2 OR rf.would_recommend = 4 THEN 0.8 
                WHEN rf.would_recommend = '3' THEN 0.6
                WHEN rf.would_recommend = 'no' OR rf.would_recommend = '0' THEN 0 
                ELSE 0 
            END) as would_recommend_count,
            COUNT(DISTINCT rf.service_request_id) as rated_requests,
            -- Also get raw would_recommend values for debugging
            GROUP_CONCAT(DISTINCT rf.would_recommend) as would_recommend_values
            FROM request_feedback rf
            JOIN service_requests sr ON rf.service_request_id = sr.id
            WHERE sr.assigned_to = :staff_id
            AND sr.status IN ('resolved', 'closed')
            AND sr.created_at BETWEEN :start_date AND CONCAT(:end_date, ' 23:59:59')";
        
        $feedback_stmt = $db->prepare($feedback_query);
        $feedback_stmt->bindParam(':staff_id', $staff_id);
        $feedback_stmt->bindParam(':start_date', $start_date);
        $feedback_stmt->bindParam(':end_date', $end_date);
        $feedback_stmt->execute();
        $feedback = $feedback_stmt->fetch(PDO::FETCH_ASSOC);
        
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
        $would_recommend_count = (int)$feedback['would_recommend_count'];
        
        // Average Rating (AR)
        $avg_rating_score = (float)$avg_rating;
        
        // Average Would Recommend (AWR): Handle new calculation logic
        $recommendation_rate = $total_feedback > 0 ? ($would_recommend_count / $total_feedback) * 100 : 0;
        
        // Feedback response rate (how many completed requests have feedback)
        $feedback_response_rate = $completed_requests > 0 ? ($feedback['rated_requests'] / $completed_requests) * 100 : 0;
        
        // Debug logging for KPI data
        error_log("KPI Debug - Staff ID: $staff_id, Total Requests: $total_requests, Completed: $completed_requests, Avg Response: $avg_response_time_minutes, Avg Rating: $avg_rating, Would Recommend Count: $would_recommend_count, Total Feedback: $total_feedback");
        error_log("KPI Debug - KPI Scores: K1=$k1_score, K2=$k2_score, K3=$k3_score, K4=$k4_score, Final=$total_kpi_score");
        error_log("KPI Debug - Would Recommend Values: " . ($feedback['would_recommend_values'] ?? 'none'));
        
        // Calculate KPI scores according to new requirements (scale 1-5)
        
        // K1: Response Time Score (Toc do phan hoi)
        // 5 points: T_res <= 15 minutes
        // 3 points: 15 < T_res <= 60 minutes  
        // 1 point: T_res > 60 minutes
        $k1_score = 1;
        if ($avg_response_time_minutes > 0) {
            if ($avg_response_time_minutes <= 15) {
                $k1_score = 5;
            } elseif ($avg_response_time_minutes <= 60) {
                $k1_score = 3;
            } else {
                $k1_score = 1;
            }
        }
        
        // K2: On-time Completion Score (Tien do hoan thanh)
        // Only calculate if staff has completed requests
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
            AND sr.created_at BETWEEN :start_date AND CONCAT(:end_date, ' 23:59:59')";
            
            $delta_t_stmt = $db->prepare($delta_t_query);
            $delta_t_stmt->bindParam(':staff_id', $staff_id);
            $delta_t_stmt->bindParam(':start_date', $start_date);
            $delta_t_stmt->bindParam(':end_date', $end_date);
            $delta_t_stmt->execute();
            $delta_t_result = $delta_t_stmt->fetch(PDO::FETCH_ASSOC);
            $avg_delta_t = (float)($delta_t_result['avg_delta_t'] ?? 0);
            
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
        
        // K3: Quality Score (Chat luong xu ly)
        // Directly from rating (1-5 scale)
        $k3_score = $avg_rating_score > 0 ? round($avg_rating_score, 1) : 1;
        
        // K4: Recommendation Score (Su tin tuong)
        // Only calculate if staff has feedback
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
        
        // Final KPI Score with weighted formula
        // Score_Final = (K1 × 15%) + (K2 × 35%) + (K3 × 40%) + (K4 × 10%)
        // For staff with no requests, KPI should be 1.0 (neutral)
        if ($total_requests == 0) {
            $total_kpi_score = 1.0;
        } else {
            $total_kpi_score = ($k1_score * 0.15) + ($k2_score * 0.35) + ($k3_score * 0.40) + ($k4_score * 0.10);
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
            'would_recommend_count' => $would_recommend_count,
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
            
            // Total KPI Score (weighted, 1-5 scale)
            'total_kpi_score' => round($total_kpi_score, 2)
        ];
    }
    
    return $kpi_data;
}

function getStaffList($db) {
    try {
        $query = "SELECT id, username, full_name, department 
                  FROM users 
                  WHERE role IN ('admin', 'staff') 
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
            'Hô và tên',
            'Phòng ban',
            'Mã yêu càu',
            'Tiêuêu yêu càu',
            'Danh muc',
            'Mô tà',
            'Ngày tao',
            'Ngày tiep nhân',
            'Ngày hoàn thành',
            'Thòi gian dư kiên hoàn thành',
            'Thòi gian phan hòi (phút)',
            'Thòi gian hoàn thành (giò)',
            'Dánh giá (1-5)',
            'Sãn sàng giói thiêu',
            'K1 - Tôc dô phan hòi (1-5)',
            'K2 - Tiên dô hoàn thành (1-5)',
            'K3 - Chat lòe xù lý (1-5)',
            'K4 - Sù tin tuòng (1-5)',
            'KPI yêu càu (1-5)'
        ];
        
        // Apply UTF-8 encoding to headers
        $encoded_headers = array_map('ensureUTF8', $headers);
        fputcsv($output, $encoded_headers);
        
        foreach ($detailed_data as $staff) {
            foreach ($staff['requests'] as $request) {
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
                    $request['would_recommend'] ?? 'N/A',
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
        $detailed_data = getStaffDetailedKPI($db, $staff_id, $start_date, $end_date);
        
        $filename = 'KPI_Staff_' . $staff['full_name'] . '_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Expires: 0');
        header('Pragma: public');
        
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        
        // Set UTF-8 encoding for stream
        stream_filter_append($output, 'convert.iconv.UTF-8/UTF-8//TRANSLIT', STREAM_FILTER_WRITE);
        
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
            'Mã yêu càu',
            'Tiêuêu yêu càu',
            'Danh muc',
            'Mô tà',
            'Ngày tao',
            'Ngày tiep nhân',
            'Ngày hoàn thành',
            'Thòi gian dư kiên hoàn thành',
            'Thòi gian phan hòi (phút)',
            'Thòi gian hoàn thành (giò)',
            'Dánh giá (1-5)',
            'Sãn sàng giói thiêu',
            'K1 - Tôc dô phan hòi (1-5)',
            'K2 - Tiên dô hoàn thành (1-5)',
            'K3 - Chat lòe xù lý (1-5)',
            'K4 - Sù tin tuòng (1-5)',
            'KPI yêu càu (1-5)'
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
                $request['would_recommend'] ?? 'N/A',
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

function getDetailedKPIData($db, $start_date, $end_date) {
    $detailed_data = [];
    
    // Get all staff users
    $staff_query = "SELECT id, username, email, full_name, department 
                   FROM users 
                   WHERE role IN ('admin', 'staff') 
                   ORDER BY full_name";
    $staff_stmt = $db->prepare($staff_query);
    $staff_stmt->execute();
    $staff_list = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($staff_list as $staff) {
        $staff_id = $staff['id'];
        
        // Get all requests for this staff with detailed information
        $requests_query = "SELECT sr.*, c.name as category_name, rf.rating, rf.would_recommend
                          FROM service_requests sr
                          LEFT JOIN categories c ON sr.category_id = c.id
                          LEFT JOIN request_feedback rf ON sr.id = rf.service_request_id
                          WHERE sr.assigned_to = :staff_id 
                          AND sr.created_at BETWEEN :start_date AND CONCAT(:end_date, ' 23:59:59')
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
            
            // Calculate K4 score for this request
            $k4_score = 1;
            if ($request['would_recommend'] === 'yes' || $request['would_recommend'] === '1' || $request['would_recommend'] == 1 || $request['would_recommend'] == 5) {
                $k4_score = 5;
            } elseif ($request['would_recommend'] === 'maybe' || $request['would_recommend'] === '2' || $request['would_recommend'] == 2 || $request['would_recommend'] == 4) {
                $k4_score = 4;
            } elseif ($request['would_recommend'] == 3) {
                $k4_score = 3;
            } elseif ($request['would_recommend'] === 'no' || $request['would_recommend'] === '0' || $request['would_recommend'] == 0) {
                $k4_score = 1;
            } else {
                $k4_score = 1;
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
                'would_recommend' => $request['would_recommend'],
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
    // Get staff details
    $staff_query = "SELECT id, username, email, full_name, department 
                   FROM users WHERE id = :staff_id";
    $staff_stmt = $db->prepare($staff_query);
    $staff_stmt->bindParam(':staff_id', $staff_id);
    $staff_stmt->execute();
    $staff = $staff_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$staff) {
        return null;
    }
    
    // Get all requests for this staff
    $requests_query = "SELECT sr.*, c.name as category_name, rf.rating, rf.would_recommend
                      FROM service_requests sr
                      LEFT JOIN categories c ON sr.category_id = c.id
                      LEFT JOIN request_feedback rf ON sr.id = rf.service_request_id
                      WHERE sr.assigned_to = :staff_id 
                      AND sr.created_at BETWEEN :start_date AND CONCAT(:end_date, ' 23:59:59')
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
        
        // Calculate K4 score
        $k4_score = 1;
        if ($request['would_recommend'] === 'yes' || $request['would_recommend'] === '1' || $request['would_recommend'] == 1 || $request['would_recommend'] == 5) {
            $k4_score = 5;
        } elseif ($request['would_recommend'] === 'maybe' || $request['would_recommend'] === '2' || $request['would_recommend'] == 2 || $request['would_recommend'] == 4) {
            $k4_score = 4;
        } elseif ($request['would_recommend'] == 3) {
            $k4_score = 3;
        } elseif ($request['would_recommend'] === 'no' || $request['would_recommend'] === '0' || $request['would_recommend'] == 0) {
            $k4_score = 1;
        } else {
            $k4_score = 1;
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
            'response_time_minutes' => $response_time_minutes,
            'completion_time_hours' => $completion_time_hours,
            'rating' => $request['rating'],
            'would_recommend' => $request['would_recommend'],
            'k1_score' => $k1_score,
            'k2_score' => $k2_score,
            'k3_score' => $k3_score,
            'k4_score' => $k4_score,
            'request_kpi_score' => round($request_kpi_score, 2)
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
?>
