<?php
// Debug browser request to see exact what's happening
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/session.php';
require_once '../config/database.php';

// Start session
startSession();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log all request details
error_log("=== BROWSER REQUEST DEBUG ===");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Request URI: " . $_SERVER['REQUEST_URI']);
error_log("HTTP Headers: " . json_encode(getallheaders()));
error_log("Cookies: " . json_encode($_COOKIE));
error_log("Session ID: " . session_id());
error_log("Session Data: " . json_encode($_SESSION));

// Check authentication
if (!isset($_SESSION['user_id'])) {
    error_log("❌ No user session found");
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized - No session',
        'debug' => [
            'session_id' => session_id(),
            'session_data' => $_SESSION,
            'cookies' => $_COOKIE
        ]
    ]);
    exit;
}

// Get user role
$user_role = $_SESSION['role'] ?? 'user';
error_log("User role: $user_role");

// Check if admin/staff
if (!in_array($user_role, ['admin', 'staff'])) {
    error_log("❌ Access denied for role: $user_role");
    echo json_encode([
        'success' => false,
        'message' => 'Chỉ admin và staff mới có quyền xem yêu cầu từ chối',
        'debug' => [
            'user_role' => $user_role,
            'allowed_roles' => ['admin', 'staff'],
            'session_data' => $_SESSION
        ]
    ]);
    exit;
}

error_log("✅ Access granted for role: $user_role");

// Get database connection
try {
    $db = getDatabaseConnection();
    error_log("✅ Database connection established");
} catch (Exception $e) {
    error_log("❌ Database connection error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error: ' . $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'session_data' => $_SESSION
        ]
    ]);
    exit;
}

// Try to get reject requests
try {
    $query = "SELECT rr.*, 
              sr.title as service_request_title, sr.id as service_request_id,
              requester.username as requester_name,
              rejecter.username as rejecter_name,
              processor.username as processor_name
              FROM reject_requests rr 
              LEFT JOIN service_requests sr ON rr.service_request_id = sr.id
              LEFT JOIN users requester ON sr.user_id = requester.id
              LEFT JOIN users rejecter ON rr.rejected_by = rejecter.id
              LEFT JOIN users processor ON rr.processed_by = processor.id
              ORDER BY rr.created_at DESC 
              LIMIT 10";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $reject_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("✅ Query executed successfully, found " . count($reject_requests) . " requests");
    
    echo json_encode([
        'success' => true,
        'message' => 'Lấy danh sách yêu cầu từ chối thành công',
        'data' => [
            'reject_requests' => $reject_requests,
            'total' => count($reject_requests)
        ],
        'debug' => [
            'query' => $query,
            'count' => count($reject_requests),
            'session_data' => $_SESSION
        ]
    ]);
    
} catch (Exception $e) {
    error_log("❌ Query error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Query error: ' . $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'query' => $query,
            'session_data' => $_SESSION
        ]
    ]);
}
?>
