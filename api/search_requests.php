<?php
// Minimal Search API - Quick Fix
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

// Enable error logging
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/api_errors.log');

try {
    // Include session configuration first
    require_once __DIR__ . '/../config/session.php';
    
    // Start session with proper configuration
    startSession();
    
    // Debug session
    error_log("=== SEARCH API SESSION DEBUG ===");
    error_log("Session ID: " . session_id());
    error_log("Session data before check: " . json_encode($_SESSION));
    error_log("Cookies: " . json_encode($_COOKIE));
    
    // Check authentication
    error_log("=== SEARCH API DEBUG ===");
    error_log("Session data: " . json_encode($_SESSION));
    error_log("User ID: " . ($_SESSION['user_id'] ?? 'null'));
    error_log("User role: " . ($_SESSION['role'] ?? 'null'));
    
    if (!isset($_SESSION['user_id'])) {
        error_log("ERROR: User not logged in");
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    // Get search parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
    $limit = max(1, isset($_GET['limit']) ? (int)$_GET['limit'] : 9);
    $offset = ($page - 1) * $limit;
    
    // Get user info
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'] ?? 'user';
    
    // Database connection
    require_once __DIR__ . '/../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db === null) {
        throw new Exception("Database connection failed");
    }
    
    // Build query
    $where_clause = "WHERE 1=1";
    $params = [];
    
    // Add user filter for non-admin/non-staff
    error_log("Role filter check: user_role='$user_role'");
    if ($user_role != 'admin' && $user_role != 'staff') {
        error_log("Applying user_id filter for non-admin/non-staff: user_id=$user_id");
        $where_clause .= " AND sr.user_id = :user_id";
        $params[':user_id'] = $user_id;
    } else {
        error_log("No user_id filter - admin/staff access");
    }
    
    // Add search condition
    if (!empty($search)) {
        $where_clause .= " AND (sr.title LIKE :search OR sr.description LIKE :search OR u.username LIKE :search OR sr.id LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    
    // Add status filter
    if (!empty($status)) {
        if ($status === 'request_support') {
            // Special handling for request_support: only show requests with approved support requests
            $where_clause .= " AND EXISTS (SELECT 1 FROM support_requests sreq WHERE sreq.service_request_id = sr.id AND sreq.status = 'approved')";
        } else {
            $where_clause .= " AND sr.status = :status";
            $params[':status'] = $status;
        }
    }
    
    // Main query
    $query = "SELECT sr.*, u.username as requester_name, c.name as category_name
              FROM service_requests sr 
              LEFT JOIN users u ON sr.user_id = u.id 
              LEFT JOIN categories c ON sr.category_id = c.id 
              $where_clause 
              ORDER BY sr.created_at DESC
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM service_requests sr 
                   LEFT JOIN users u ON sr.user_id = u.id 
                   $where_clause";
    
    $count_stmt = $db->prepare($count_query);
    foreach ($params as $key => $value) {
        $count_stmt->bindValue($key, $value);
    }
    $count_stmt->execute();
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get status counts
    $status_counts_array = ['open' => 0, 'in_progress' => 0, 'resolved' => 0, 'rejected' => 0, 'request_support' => 0, 'closed' => 0];
    
    $status_query = "SELECT status, COUNT(*) as count FROM service_requests";
    if ($user_role != 'admin' && $user_role != 'staff') {
        $status_query .= " WHERE user_id = :user_id";
        $status_stmt = $db->prepare($status_query);
        $status_stmt->bindValue(':user_id', $user_id);
    } else {
        $status_stmt = $db->prepare($status_query);
    }
    $status_query .= " GROUP BY status";
    $status_stmt->execute();
    
    $status_results = $status_stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($status_results as $result) {
        $status_counts_array[$result['status']] = $result['count'];
    }
    
    // Calculate request_support count (requests with approved support requests)
    $support_query = "SELECT COUNT(DISTINCT sr.id) as count 
                    FROM service_requests sr 
                    LEFT JOIN support_requests sreq ON sr.id = sreq.service_request_id 
                    WHERE sreq.id IS NOT NULL AND sreq.status = 'approved'";
    
    // Only filter by user for non-admin/non-staff
    if ($user_role != 'admin' && $user_role != 'staff') {
        $support_query .= " AND sr.user_id = :user_id";
        $support_stmt = $db->prepare($support_query);
        $support_stmt->bindValue(":user_id", $user_id);
    } else {
        $support_stmt = $db->prepare($support_query);
    }
    $support_stmt->execute();
    $support_result = $support_stmt->fetch(PDO::FETCH_ASSOC);
    $status_counts_array['request_support'] = $support_result['count'] ?? 0;
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Search requests retrieved',
        'data' => [
            'requests' => $requests,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit)
            ],
            'status_counts' => $status_counts_array
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Search API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

?>
