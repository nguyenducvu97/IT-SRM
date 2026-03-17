<?php
// Test reject_requests list query
header('Content-Type: application/json');

try {
    require_once '../config/database.php';
    require_once '../config/session.php';
    
    startSession();
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Test the exact query from reject_requests.php
    $status = 'pending';
    $where_clause = "WHERE rr.status = ?";
    $params = [$status];
    
    echo "Testing with where_clause: $where_clause\n";
    
    // Test count query
    $count_query = "
        SELECT COUNT(*) as total
        FROM reject_requests rr
        $where_clause
    ";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute($params);
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Test main query
    $limit = 20;
    $offset = 0;
    $query = "
        SELECT rr.*, 
               u.full_name as requester_name,
               sr.title as request_title
        FROM reject_requests rr
        JOIN users u ON rr.rejected_by = u.id
        JOIN service_requests sr ON rr.service_request_id = sr.id
        $where_clause
        ORDER BY rr.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Query executed successfully',
        'total' => $total,
        'count' => count($requests),
        'requests' => $requests
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
