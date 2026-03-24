<?php
// Test resolution data in API response
require_once 'config/session.php';
require_once 'config/database.php';

startSession();
$_SESSION['user_id'] = 4;

echo "Testing resolution data in API response...\n";

$database = new Database();
$db = $database->getConnection();

// Test same query as API
$query = "SELECT sr.*, c.name as category_name, u.full_name as requester_name, 
                u.email as requester_email, u.phone as requester_phone,
                assigned.full_name as assigned_name, assigned.email as assigned_email,
                sr.assigned_at as assigned_at,
                sreq.id as support_request_id, sreq.support_type, sreq.support_details, 
                sreq.support_reason, sreq.status as support_status, sreq.admin_reason,
                sreq.processed_by, sreq.processed_at, sreq.created_at as support_created_at,
                sreq_admin.full_name as support_admin_name,
                sr.error_description as resolution_error_description,
                sr.error_type as resolution_error_type, sr.replacement_materials as resolution_replacement_materials,
                sr.solution_method as resolution_solution_method, 
                sr.resolved_at as resolution_resolved_at, assigned.full_name as resolver_name,
                res.resolved_by as resolution_resolved_by, res.error_description as res_error_description,
                res.error_type as res_error_type, res.replacement_materials as res_replacement_materials,
                res.solution_method as res_solution_method, res.resolved_at as res_resolved_at,
                resolver.full_name as resolution_resolver_name,
                rf.rating as feedback_rating, rf.feedback as feedback_text, rf.software_feedback,
                rf.would_recommend, rf.ease_of_use, rf.speed_stability, rf.requirement_meeting,
                rf.created_by as feedback_created_by, rf.created_at as feedback_created_at
         FROM service_requests sr
         LEFT JOIN categories c ON sr.category_id = c.id
         LEFT JOIN users u ON sr.user_id = u.id
         LEFT JOIN users assigned ON sr.assigned_to = assigned.id
         LEFT JOIN support_requests sreq ON sr.id = sreq.service_request_id
         LEFT JOIN users sreq_admin ON sreq.processed_by = sreq_admin.id
         LEFT JOIN resolutions res ON sr.id = res.service_request_id
         LEFT JOIN users resolver ON res.resolved_by = resolver.id
         LEFT JOIN request_feedback rf ON sr.id = rf.service_request_id
         WHERE sr.id = 5";

$stmt = $db->prepare($query);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    echo "✅ Request found\n";
    echo "   Status: {$result['status']}\n";
    echo "   Assigned Name: {$result['assigned_name']}\n";
    echo "   Resolved At: {$result['resolution_resolved_at']}\n";
    echo "   Resolver Name: {$result['resolution_resolver_name']}\n";
    echo "   Error Description: {$result['resolution_error_description']}\n";
    echo "   Error Type: {$result['resolution_error_type']}\n";
    echo "   Solution Method: {$result['resolution_solution_method']}\n";
    
    // Check if resolution data exists for JavaScript template
    $resolution_data = [
        'resolver_name' => $result['resolution_resolver_name'],
        'resolved_at' => $result['resolution_resolved_at'],
        'error_description' => $result['resolution_error_description'],
        'error_type' => $result['resolution_error_type'],
        'solution_method' => $result['resolution_solution_method']
    ];
    
    $has_resolution_data = !empty($resolution_data['resolver_name']) || 
                          !empty($resolution_data['resolved_at']) || 
                          !empty($resolution_data['error_description']);
    
    echo "\n   Resolution data for template: " . ($has_resolution_data ? 'Available' : 'Missing') . "\n";
    
    if ($has_resolution_data) {
        echo "   - Resolver: {$resolution_data['resolver_name']}\n";
        echo "   - Resolved At: {$resolution_data['resolved_at']}\n";
        echo "   - Error Description: {$resolution_data['error_description']}\n";
    }
} else {
    echo "❌ Request not found\n";
}

echo "\n";
?>
