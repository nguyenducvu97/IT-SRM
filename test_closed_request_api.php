<?php
// Test API for closed request with resolution data
require_once 'config/session.php';
require_once 'config/database.php';

startSession();
$_SESSION['user_id'] = 4;

echo "Testing API for closed request with resolution data...\n";

// Mock API call
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'get';
$_GET['id'] = '5';

// Test database directly instead of API
// ob_start();
// include 'api/service_requests.php';
// $api_response = ob_get_clean();

$database = new Database();
$db = $database->getConnection();

// Use same query as API
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
$request = $stmt->fetch(PDO::FETCH_ASSOC);

// Simulate API response format
if ($request) {
    // Format resolution data
    if (($request['status'] === 'resolved' || $request['status'] === 'closed') && $request['resolution_resolver_name']) {
        $request['resolution'] = [
            'resolver_name' => $request['resolution_resolver_name'],
            'error_description' => $request['res_error_description'],
            'error_type' => $request['res_error_type'],
            'replacement_materials' => $request['res_replacement_materials'],
            'solution_method' => $request['res_solution_method'],
            'resolved_at' => $request['res_resolved_at']
        ];
    } else {
        $request['resolution'] = null;
    }
    
    // Get resolution attachments
    if ($request['status'] === 'resolved' || $request['status'] === 'closed') {
        $resolution_attachments_query = "SELECT id, filename, original_name, file_size, mime_type, uploaded_at 
                                         FROM complete_request_attachments 
                                         WHERE service_request_id = :id 
                                         ORDER BY uploaded_at ASC";
        $resolution_attachments_stmt = $db->prepare($resolution_attachments_query);
        $resolution_attachments_stmt->bindParam(":id", $request['id']);
        $resolution_attachments_stmt->execute();
        $request['resolution_attachments'] = $resolution_attachments_stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $request['resolution_attachments'] = [];
    }
}

if ($request) {
    echo "✅ Database Query Success\n";
    echo "   Status: {$request['status']}\n";
    echo "   Assigned Name: {$request['assigned_name']}\n";
    
    // Check resolution data
    if (isset($request['resolution']) && $request['resolution']) {
        echo "✅ Resolution Data Available:\n";
        echo "   - Resolver: {$request['resolution']['resolver_name']}\n";
        echo "   - Resolved At: {$request['resolution']['resolved_at']}\n";
        echo "   - Error Description: {$request['resolution']['error_description']}\n";
        echo "   - Error Type: {$request['resolution']['error_type']}\n";
        echo "   - Solution Method: {$request['resolution']['solution_method']}\n";
    } else {
        echo "❌ Resolution Data Missing\n";
        echo "   - status: {$request['status']}\n";
        echo "   - resolution_resolver_name: " . ($request['resolution_resolver_name'] ?? 'null') . "\n";
    }
    
    // Check resolution attachments
    if (isset($request['resolution_attachments']) && !empty($request['resolution_attachments'])) {
        echo "✅ Resolution Attachments Available: " . count($request['resolution_attachments']) . " files\n";
        foreach ($request['resolution_attachments'] as $attachment) {
            echo "   - {$attachment['original_name']} ({$attachment['file_size']} bytes)\n";
        }
    } else {
        echo "❌ Resolution Attachments Missing\n";
        echo "   - Count: " . count($request['resolution_attachments'] ?? []) . "\n";
    }
    
    // Check feedback data
    if (isset($request['feedback_rating'])) {
        echo "✅ Feedback Data Available\n";
        echo "   - Rating: {$request['feedback_rating']}/5\n";
        echo "   - Feedback: {$request['feedback_text']}\n";
    } else {
        echo "❌ Feedback Data Missing\n";
    }
    
} else {
    echo "❌ Database Query Failed\n";
}

echo "\n🎉 Closed request API test completed!\n";
?>
