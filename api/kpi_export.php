<?php
// KPI Export API for Staff Performance Evaluation
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/api_errors.log');

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
    } elseif ($action === 'get_kpi_data') {
        getKPIData($db);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}   

function exportKPIExcel($db) {
    try {
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
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for proper Excel display
        fwrite($output, "\xEF\xBB\xBF");
        
        // Add headers with proper KPI names and scores (removed satisfaction_rate and recommendation_rate)
        $headers = [
            'Mã NV',
            'Họ và tên',
            'Email',
            'Phòng ban',
            'Tổng yêu cầu',
            'Đã hoàn thành',
            'Đang xử lý',
            'Chờ xử lý',
            'Thời gian phản hồi TB (phút) - ART',
            'Thời gian hoàn thành TB (giờ) - ACT',
            'Tổng đánh giá',
            'Điểm đánh giá TB (1-5) - AR',
            'Đánh giá tích cực',
            'Đánh giá tiêu cực',
            'Sẵn sàng giới thiệu (%) - AWR',
            'Tỷ lệ hoàn thành (%)',
            'Tỷ lệ phản hồi (%)',
            // Individual KPI Scores
            'Điểm ART (0-100)',
            'Điểm ACT (0-100)',
            'Điểm AR (0-100)',
            'Điểm AWR (0-100)',
            // Total KPI Score
            'Điểm KPI Tổng hợp (0-100)'
        ];
        
        fputcsv($output, $headers);
        
        // Add data with proper KPI values and scores (removed satisfaction_rate and recommendation_rate)
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
                round($staff['avg_response_time_minutes'], 2), // ART in minutes
                round($staff['avg_completion_time_hours'], 2), // ACT in hours
                $staff['total_feedback'],
                round($staff['avg_rating'], 2), // AR
                $staff['positive_feedback'],
                $staff['negative_feedback'],
                round($staff['recommendation_rate'], 2), // AWR
                round($staff['completion_rate'], 2),
                round($staff['feedback_response_rate'], 2),
                // Individual KPI Scores
                round($staff['art_score'], 2),
                round($staff['act_score'], 2),
                round($staff['ar_score'], 2),
                round($staff['awr_score'], 2),
                // Total KPI Score
                $staff['total_kpi_score']
            ];
            
            fputcsv($output, $row);
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
                ELSE NULL END) as avg_completion_time_hours
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
            -- Average Would Recommend (AWR): Net Promoter Score variation
            SUM(CASE WHEN rf.would_recommend IN ('yes', '1', '4') THEN 1 ELSE 0 END) as would_recommend_count,
            COUNT(DISTINCT rf.service_request_id) as rated_requests
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
        
        // Average Would Recommend (AWR): Net Promoter Score variation
        $recommendation_rate = $total_feedback > 0 ? ($would_recommend_count / $total_feedback) * 100 : 0;
        
        // Feedback response rate (how many completed requests have feedback)
        $feedback_response_rate = $completed_requests > 0 ? ($feedback['rated_requests'] / $completed_requests) * 100 : 0;
        
        // Calculate normalized scores for Total KPI Score (0-100 scale)
        // ART: Lower is better, normalize to 0-100 (target: 15-30 minutes = 100 points)
        $art_score = 0;
        if ($avg_response_time_minutes > 0) {
            if ($avg_response_time_minutes <= 30) {
                $art_score = 100; // Excellent: <= 30 minutes
            } elseif ($avg_response_time_minutes <= 60) {
                $art_score = 80; // Good: 31-60 minutes
            } elseif ($avg_response_time_minutes <= 120) {
                $art_score = 60; // Fair: 61-120 minutes
            } else {
                $art_score = 40; // Poor: > 120 minutes
            }
        }
        
        // ACT: Lower is better, normalize to 0-100 (target: SLA dependent)
        $act_score = 0;
        if ($avg_completion_time_hours > 0) {
            if ($avg_completion_time_hours <= 8) {
                $act_score = 100; // Excellent: <= 8 hours
            } elseif ($avg_completion_time_hours <= 24) {
                $act_score = 80; // Good: 9-24 hours
            } elseif ($avg_completion_time_hours <= 72) {
                $act_score = 60; // Fair: 25-72 hours
            } else {
                $act_score = 40; // Poor: > 72 hours
            }
        }
        
        // AR: Higher is better, direct scale (0-100)
        $ar_score = ($avg_rating_score / 5) * 100;
        
        // AWR: Higher is better, direct scale (0-100)
        $awr_score = $recommendation_rate;
        
        // Total KPI Score with weighted formula
        // Score_Total = (ART × 20%) + (ACT × 40%) + (AR × 30%) + (AWR × 10%)
        $total_kpi_score = ($art_score * 0.20) + ($act_score * 0.40) + ($ar_score * 0.30) + ($awr_score * 0.10);
        
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
            'avg_response_time_minutes' => $avg_response_time_minutes, // ART in minutes
            'avg_completion_time_hours' => $avg_completion_time_hours, // ACT in hours
            
            // Quality Metrics
            'total_feedback' => $total_feedback,
            'avg_rating' => $avg_rating_score, // AR
            'positive_feedback' => $positive_feedback,
            'negative_feedback' => $negative_feedback,
            'would_recommend_count' => $would_recommend_count,
            'rated_requests' => (int)$feedback['rated_requests'],
            
            // Individual KPI Scores (0-100 scale)
            'art_score' => $art_score, // Response Time Score
            'act_score' => $act_score, // Completion Time Score
            'ar_score' => $ar_score, // Rating Score
            'awr_score' => $awr_score, // Would Recommend Score
            
            // Calculated KPI Rates
            'completion_rate' => $completion_rate,
            'feedback_response_rate' => $feedback_response_rate,
            
            // Total KPI Score (weighted)
            'total_kpi_score' => round($total_kpi_score, 2)
        ];
    }
    
    return $kpi_data;
}
?>
