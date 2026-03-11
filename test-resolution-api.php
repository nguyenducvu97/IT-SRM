<?php
// Test script to check if resolution data is properly returned
require_once 'config/database.php';
require_once 'config/session.php';

// Start session for authentication
startSession();

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Test query with resolution data
$query = "SELECT sr.*, c.name as category_name, u.full_name as requester_name, 
                 u.email as requester_email, u.phone as requester_phone,
                 assigned.full_name as assigned_name, assigned.email as assigned_email,
                 sreq.id as support_request_id, sreq.support_type, sreq.support_details, 
                 sreq.support_reason, sreq.status as support_status, sreq.admin_reason,
                 sreq.processed_by, sreq.processed_at, sreq.created_at as support_created_at,
                 sreq_admin.full_name as support_admin_name,
                 r.id as resolution_id, r.error_description as resolution_error_description,
                 r.error_type as resolution_error_type, r.replacement_materials as resolution_replacement_materials,
                 r.solution_method as resolution_solution_method, r.resolved_by as resolution_resolved_by,
                 r.resolved_at as resolution_resolved_at, resolver.full_name as resolver_name
          FROM service_requests sr
          LEFT JOIN categories c ON sr.category_id = c.id
          LEFT JOIN users u ON sr.user_id = u.id
          LEFT JOIN users assigned ON sr.assigned_to = assigned.id
          LEFT JOIN support_requests sreq ON sr.id = sreq.service_request_id
          LEFT JOIN users sreq_admin ON sreq.processed_by = sreq_admin.id
          LEFT JOIN resolutions r ON sr.id = r.service_request_id
          LEFT JOIN users resolver ON r.resolved_by = resolver.id
          WHERE sr.id = 1";

$stmt = $db->prepare($query);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Format resolution data if exists
    if ($request['resolution_id']) {
        $request['resolution'] = [
            'id' => $request['resolution_id'],
            'error_description' => $request['resolution_error_description'],
            'error_type' => $request['resolution_error_type'],
            'replacement_materials' => $request['resolution_replacement_materials'],
            'solution_method' => $request['resolution_solution_method'],
            'resolved_by' => $request['resolution_resolved_by'],
            'resolved_at' => $request['resolution_resolved_at'],
            'resolver_name' => $request['resolver_name']
        ];
        
        // Clean up the original resolution fields
        unset($request['resolution_id'], $request['resolution_error_description'],
              $request['resolution_error_type'], $request['resolution_replacement_materials'],
              $request['resolution_solution_method'], $request['resolution_resolved_by'],
              $request['resolution_resolved_at'], $request['resolver_name']);
    } else {
        $request['resolution'] = null;
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Test completed',
        'has_resolution' => $request['resolution'] !== null,
        'resolution_data' => $request['resolution']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'No request found with ID 1']);
}
?>
