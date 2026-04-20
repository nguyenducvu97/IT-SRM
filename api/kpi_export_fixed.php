<?php
// KPI Export API - Fixed Vietnamese Font Version
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/api_errors.log');

// Clean all output buffers and start fresh
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Set locale and encoding for Vietnamese
mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'vi_VN.UTF-8', 'vi_VN');

// Function to ensure UTF-8 encoding for Vietnamese characters
function ensureUTF8($text) {
    if (is_string($text)) {
        // First, detect if text is properly UTF-8 encoded
        if (!mb_check_encoding($text, 'UTF-8')) {
            // Try to convert from common encodings to UTF-8
            $text = mb_convert_encoding($text, 'UTF-8', ['UTF-8', 'Windows-1252', 'ISO-8859-1']);
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

if (!isLoggedIn()) {
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
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied - Admin only']);
    exit();
}

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
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename="' . rawurlencode($filename) . '"');
        header('Cache-Control: max-age=0');
        header('Expires: 0');
        header('Pragma: public');
        
        // Open output stream with UTF-8 encoding
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for proper Excel display
        fwrite($output, "\xEF\xBB\xBF");
        
        // Add export information header
        $export_time = date('d/m/Y H:i:s');
        $period_start = date('d/m/Y', strtotime($start_date));
        $period_end = date('d/m/Y', strtotime($end_date));
        
        fputcsv($output, ['THÔNG TIN XUẤT FILE']);
        fputcsv($output, ['Thời gian xuất:', $export_time]);
        fputcsv($output, ['Khoảng thời gian:', "$period_start - $period_end"]);
        fputcsv($output, []);
        
        // Add KPI calculation formulas section
        fputcsv($output, ['CÔNG THỨC TÍNH KPI']);
        fputcsv($output, ['K1 - Tốc độ phản hồi (1-5):', '=MAX(1; MIN(5; 5 - (I2/30)))', 'I2 = Thời gian phản hồi TB (phút)']);
        fputcsv($output, ['K2 - Tiến độ hoàn thành (1-5):', '=MAX(1; MIN(5; 5 - (J2/24)))', 'J2 = Thời gian hoàn thành TB (giờ)']);
        fputcsv($output, ['K3 - Đánh giá chung (1-5):', '=MAX(1; MIN(5; L2))', 'L2 = Điểm đánh giá TB (1-5)']);
        fputcsv($output, ['K4 - Chất lượng xử lý (1-5):', '=MAX(1; MIN(5; O2/20))', 'O2 = Sẵn sàng giới thiệu (%)']);
        fputcsv($output, ['KPI Tổng hợp (1-5):', '=(Q2*0.15)+(R2*0.35)+(S2*0.40)+(T2*0.10)', 'Q2=K1(15%), R2=K2(35%), S2=K3(40%), T2=K4(10%)']);
        fputcsv($output, ['Ghi chú:', 'Công thức áp dụng cho dòng 2 tương tự. Copy công thức cho các dòng khác.']);
        fputcsv($output, []);
        
        // Add headers with proper Vietnamese characters
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
            'Tổng danh giá',
            'Điểm đánh giá chung TB (1-5) - K3',
            'Đánh giá tích cực',
            'Đánh giá tiêu cực',
            'Chất lượng xử lý (%) - K4',
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

function getStaffList($db) {
    try {
        $query = "SELECT id, username, email, full_name, department 
                  FROM users 
                  WHERE role IN ('admin', 'staff') 
                  ORDER BY full_name";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'staff' => $staff
        ]);
        
    } catch (Exception $e) {
        error_log("Get Staff List Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to get staff list']);
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
            SUM(CASE WHEN `sr`.`status` = 'completed' THEN 1 ELSE 0 END) as completed_requests,
            SUM(CASE WHEN `sr`.`status` = 'in_progress' THEN 1 ELSE 0 END) as in_progress_requests,
            SUM(CASE WHEN `sr`.`status` = 'open' THEN 1 ELSE 0 END) as open_requests,
            AVG(CASE 
                WHEN `sr`.`assigned_at` IS NOT NULL AND `sr`.`created_at` IS NOT NULL 
                THEN TIMESTAMPDIFF(MINUTE, `sr`.`created_at`, `sr`.`assigned_at`) 
                ELSE NULL 
            END) as avg_response_time_minutes,
            AVG(CASE 
                WHEN `sr`.`resolved_at` IS NOT NULL AND `sr`.`created_at` IS NOT NULL 
                THEN TIMESTAMPDIFF(HOUR, `sr`.`created_at`, `sr`.`resolved_at`) 
                ELSE NULL 
            END) as avg_completion_time_hours,
            COUNT(`rf`.`id`) as total_feedback,
            AVG(`rf`.`rating`) as avg_rating,
            SUM(CASE WHEN `rf`.`rating` >= 4 THEN 1 ELSE 0 END) as positive_feedback,
            SUM(CASE WHEN `rf`.`rating` <= 2 THEN 1 ELSE 0 END) as negative_feedback,
            AVG(CASE 
                WHEN `rf`.`would_recommend` = 'yes' THEN 5
                WHEN `rf`.`would_recommend` = 'maybe' THEN 4
                WHEN `rf`.`would_recommend` = 'no' THEN 1
                ELSE 3
            END) as avg_recommendation_score
            FROM `service_requests` `sr`
            LEFT JOIN `request_feedback` `rf` ON `sr`.`id` = `rf`.`service_request_id`
            WHERE `sr`.`assigned_to` = :staff_id 
            AND `sr`.`created_at` BETWEEN :start_date AND CONCAT(:end_date, ' 23:59:59')";
        
        $stats_stmt = $db->prepare($stats_query);
        $stats_stmt->bindParam(':staff_id', $staff_id);
        $stats_stmt->bindParam(':start_date', $start_date);
        $stats_stmt->bindParam(':end_date', $end_date);
        $stats_stmt->execute();
        $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate completion rate
        $completion_rate = $stats['total_requests'] > 0 ? 
            ($stats['completed_requests'] / $stats['total_requests']) * 100 : 0;
        
        // Calculate feedback response rate
        $feedback_response_rate = $stats['total_requests'] > 0 ? 
            ($stats['total_feedback'] / $stats['total_requests']) * 100 : 0;
        
        // Calculate recommendation rate (K4)
        $recommendation_rate = $stats['total_feedback'] > 0 ? 
            ($stats['avg_recommendation_score'] / 5) * 100 : 0;
        
        // Calculate K1 Score (Response Time Score)
        $k1_score = 1;
        if ($stats['avg_response_time_minutes'] !== null) {
            if ($stats['avg_response_time_minutes'] <= 30) $k1_score = 5;
            elseif ($stats['avg_response_time_minutes'] <= 60) $k1_score = 4;
            elseif ($stats['avg_response_time_minutes'] <= 120) $k1_score = 3;
            elseif ($stats['avg_response_time_minutes'] <= 240) $k1_score = 2;
        }
        
        // Calculate K2 Score (Completion Time Score)
        $k2_score = 1;
        if ($stats['avg_completion_time_hours'] !== null) {
            if ($stats['avg_completion_time_hours'] <= 24) $k2_score = 5;
            elseif ($stats['avg_completion_time_hours'] <= 48) $k2_score = 4;
            elseif ($stats['avg_completion_time_hours'] <= 72) $k2_score = 3;
            elseif ($stats['avg_completion_time_hours'] <= 120) $k2_score = 2;
        }
        
        // K3 Score is the average rating (Quality Score)
        $k3_score = $stats['avg_rating'] ?: 1;
        
        // K4 Score is the recommendation score (Trust Score)
        $k4_score = $stats['avg_recommendation_score'] ?: 1;
        
        // Calculate Total KPI Score (weighted average)
        $total_kpi_score = ($k1_score * 0.15) + ($k2_score * 0.35) + ($k3_score * 0.40) + ($k4_score * 0.10);
        
        $kpi_data[] = [
            'id' => $staff['id'],
            'full_name' => $staff['full_name'],
            'email' => $staff['email'],
            'department' => $staff['department'],
            'total_requests' => (int)$stats['total_requests'],
            'completed_requests' => (int)$stats['completed_requests'],
            'in_progress_requests' => (int)$stats['in_progress_requests'],
            'open_requests' => (int)$stats['open_requests'],
            'avg_response_time_minutes' => round($stats['avg_response_time_minutes'] ?: 0, 2),
            'avg_completion_time_hours' => round($stats['avg_completion_time_hours'] ?: 0, 2),
            'total_feedback' => (int)$stats['total_feedback'],
            'avg_rating' => round($stats['avg_rating'] ?: 0, 2),
            'positive_feedback' => (int)$stats['positive_feedback'],
            'negative_feedback' => (int)$stats['negative_feedback'],
            'recommendation_rate' => round($recommendation_rate, 2),
            'completion_rate' => round($completion_rate, 2),
            'feedback_response_rate' => round($feedback_response_rate, 2),
            'k1_score' => round($k1_score, 2),
            'k2_score' => round($k2_score, 2),
            'k3_score' => round($k3_score, 2),
            'k4_score' => round($k4_score, 2),
            'total_kpi_score' => round($total_kpi_score, 2)
        ];
    }
    
    return $kpi_data;
}

function exportKPIDetailed($db) {
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
        
        // Get all KPI data
        $kpi_data = getKPIDataArray($db, $start_date, $end_date);
        
        // Set filename
        $filename = 'KPI_Detailed_Report_' . date('Y-m-d_H-i-s') . '.csv';
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename="' . rawurlencode($filename) . '"');
        header('Cache-Control: max-age=0');
        header('Expires: 0');
        header('Pragma: public');
        
        // Open output stream with UTF-8 encoding
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for proper Excel display
        fwrite($output, "\xEF\xBB\xBF");
        
        // Add export information header
        $export_time = date('d/m/Y H:i:s');
        $period_start = date('d/m/Y', strtotime($start_date));
        $period_end = date('d/m/Y', strtotime($end_date));
        
        fputcsv($output, ['THÔNG TIN XUẤT FILE']);
        fputcsv($output, ['Thời gian xuất:', $export_time]);
        fputcsv($output, ['Khoảng thời gian:', "$period_start - $period_end"]);
        fputcsv($output, []);
        
        // Add detailed headers
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
            'Điểm đánh giá chung TB (1-5) - K3',
            'Đánh giá tích cực',
            'Đánh giá tiêu cực',
            'Chất lượng xử lý (%) - K4',
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
        
        // Add all staff data with detailed KPI calculations
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
        
        if (!$detailed_data || !isset($detailed_data['requests'])) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Invalid data structure returned']);
            exit();
        }
        
        $filename = 'KPI_Staff_' . $staff['full_name'] . '_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename="' . rawurlencode($filename) . '"');
        header('Cache-Control: max-age=0');
        header('Expires: 0');
        header('Pragma: public');
        
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        
        // Add export information header
        $export_time = date('d/m/Y H:i:s');
        $period_start = date('d/m/Y', strtotime($start_date));
        $period_end = date('d/m/Y', strtotime($end_date));
        
        fputcsv($output, ['THÔNG TIN XUẤT FILE']);
        fputcsv($output, ['Thời gian xuất:', $export_time]);
        fputcsv($output, ['Khoảng thời gian:', "$period_start - $period_end"]);
        fputcsv($output, []);
        
        // Better encoding handling for Vietnamese characters
        // Use UTF-8 without transliteration to preserve Vietnamese characters
        // Excel will properly display UTF-8 with BOM
        
        // Staff info header
        fputcsv($output, ['THÔNG TIN STAFF']);
        fputcsv($output, ['Mã NV', $staff['id']]);
        fputcsv($output, ['Họ và tên', $staff['full_name']]);
        fputcsv($output, ['Email', $staff['email']]);
        fputcsv($output, ['Phòng ban', $staff['department'] ?? 'N/A']);
        fputcsv($output, []);
        
        // Summary header
        fputcsv($output, ['THỐNG KÊ KPI']);
        fputcsv($output, ['Tổng yêu cầu', count($detailed_data['requests'])]);
        fputcsv($output, ['K1 TB', $detailed_data['summary']['k1_avg'] ?? 1]);
        fputcsv($output, ['K2 TB', $detailed_data['summary']['k2_avg'] ?? 1]);
        fputcsv($output, ['K3 TB', $detailed_data['summary']['k3_avg'] ?? 1]);
        fputcsv($output, ['K4 TB', $detailed_data['summary']['k4_avg'] ?? 1]);
        fputcsv($output, ['KPI TB', $detailed_data['summary']['kpi_avg'] ?? 1]);
        fputcsv($output, []);
        
        // Add KPI calculation formulas section
        fputcsv($output, ['CÔNG THỨC TÍNH KPI']);
        fputcsv($output, ['K1 - Tốc độ phản hồi (1-5):', '=MAX(1; MIN(5; 5 - (L2/30)))', 'L2 = Thời gian phản hồi (phút)']);
        fputcsv($output, ['K2 - Tiến độ hoàn thành (1-5):', '=MAX(1; MIN(5; 5 - (M2/24)))', 'M2 = Thời gian hoàn thành (giờ)']);
        fputcsv($output, ['K3 - Đánh giá chung (1-5):', '=MAX(1; MIN(5; N2))', 'N2 = Đánh giá (1-5)']);
        fputcsv($output, ['K4 - Chất lượng xử lý (1-5):', '=MAX(1; MIN(5; O2/20))', 'O2 = Sẵn sàng giới thiệu']);
        fputcsv($output, ['KPI yêu cầu (1-5):', '=(P2*0.15)+(Q2*0.35)+(R2*0.40)+(S2*0.10)', 'P2=K1(15%), Q2=K2(35%), R2=K3(40%), S2=K4(10%)']);
        fputcsv($output, ['Ghi chú:', 'Công thức áp dụng cho dòng 2 tương tự. Copy công thức cho các dòng khác.']);
        fputcsv($output, []);
        
        // Detailed requests header
        $headers = [
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
            'Đánh giá chung (1-5)',
            'Chất lượng xử lý',
            'K1 - Tốc độ phản hồi (1-5)',
            'K2 - Tiến độ hoàn thành (1-5)',
            'K3 - Đánh giá chung (1-5)',
            'K4 - Chất lượng xử lý (1-5)',
            'KPI yêu cầu (1-5)'
        ];
        
        fputcsv($output, ['CHI TIẾT YÊU CẦU']);
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
    
    // Get all requests for this staff
    $requests_query = "SELECT `sr`.*, `c`.`name` as category_name, `rf`.`rating`, `rf`.`would_recommend`
                      FROM `service_requests` `sr`
                      LEFT JOIN `categories` `c` ON `sr`.`category_id` = `c`.`id`
                      LEFT JOIN `request_feedback` `rf` ON `sr`.`id` = `rf`.`service_request_id`
                      WHERE `sr`.`assigned_to` = :staff_id 
                      AND `sr`.`created_at` BETWEEN :start_date AND CONCAT(:end_date, ' 23:59:59')
                      ORDER BY `sr`.`created_at` DESC";
    
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
        // Calculate response time in minutes
        $response_time_minutes = null;
        if ($request['assigned_at'] && $request['created_at']) {
            $assigned = new DateTime($request['assigned_at']);
            $created = new DateTime($request['created_at']);
            $response_time_minutes = $created->diff($assigned)->i + ($created->diff($assigned)->h * 60);
        }
        
        // Calculate completion time in hours
        $completion_time_hours = null;
        if ($request['resolved_at'] && $request['created_at']) {
            $resolved = new DateTime($request['resolved_at']);
            $created = new DateTime($request['created_at']);
            $completion_time_hours = $created->diff($resolved)->h + ($created->diff($resolved)->d * 24);
        }
        
        // Calculate K1 score (Response Time Score)
        $k1_score = 1;
        if ($response_time_minutes !== null) {
            if ($response_time_minutes <= 30) $k1_score = 5;
            elseif ($response_time_minutes <= 60) $k1_score = 4;
            elseif ($response_time_minutes <= 120) $k1_score = 3;
            elseif ($response_time_minutes <= 240) $k1_score = 2;
        }
        
        // Calculate K2 score (Completion Time Score)
        $k2_score = 1;
        if ($completion_time_hours !== null) {
            if ($completion_time_hours <= 24) $k2_score = 5;
            elseif ($completion_time_hours <= 48) $k2_score = 4;
            elseif ($completion_time_hours <= 72) $k2_score = 3;
            elseif ($completion_time_hours <= 120) $k2_score = 2;
        }
        
        // Calculate K3 score (Quality Score - based on rating)
        $k3_score = $request['rating'] ?: 1;
        
        // Calculate K4 score (Recommendation Score)
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
