<?php
// Simple Search Test - Bypass authentication for testing
header("Content-Type: application/json; charset=UTF-8");

// Disable authentication temporarily for testing
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['username'] = 'admin';

// Include required files
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$limit = max(1, isset($_GET['limit']) ? (int)$_GET['limit'] : 9);
$offset = ($page - 1) * $limit;

try {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db === null) {
        throw new Exception("Database connection failed");
    }
    
    // Build query
    $where_clause = "WHERE 1=1";
    $params = [];
    
    // Add search condition
    if (!empty($search)) {
        $where_clause .= " AND (sr.title LIKE :search_title OR sr.description LIKE :search_desc OR u.username LIKE :search_user OR sr.id LIKE :search_id)";
        $params[':search_title'] = '%' . $search . '%';
        $params[':search_desc'] = '%' . $search . '%';
        $params[':search_user'] = '%' . $search . '%';
        $params[':search_id'] = '%' . $search . '%';
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
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Search requests retrieved (TEST MODE)',
        'test_mode' => true,
        'data' => [
            'requests' => $requests,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit)
            ]
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'test_mode' => true
    ]);
}
?>
