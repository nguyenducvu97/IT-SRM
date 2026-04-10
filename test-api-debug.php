<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 DEBUG API SERVICE_REQUESTS</h2>";

// Test basic database connection
try {
    require_once 'config/database.php';
    $pdo = getDatabaseConnection();
    echo "<p>✅ Database connection: OK</p>";
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Test if accepted_at column exists
try {
    $query = "SHOW COLUMNS FROM service_requests LIKE 'accepted_at'";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Column accepted_at exists</p>";
    } else {
        echo "<p>❌ Column accepted_at does not exist - THIS IS THE PROBLEM!</p>";
        
        // Add the column
        echo "<p>🔧 Adding accepted_at column...</p>";
        $alterQuery = "ALTER TABLE service_requests ADD COLUMN accepted_at TIMESTAMP NULL AFTER assigned_to";
        $alterStmt = $pdo->prepare($alterQuery);
        $alterStmt->execute();
        echo "<p>✅ Added accepted_at column</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error checking accepted_at: " . $e->getMessage() . "</p>";
}

// Test the exact query from API
try {
    echo "<h3>🧪 Testing API Query:</h3>";
    
    $testId = 83;
    $query = "SELECT sr.*, c.name as category_name, u.full_name as requester_name, 
                            u.email as requester_email, u.phone as requester_phone,
                            assigned.full_name as assigned_name, assigned.email as assigned_email, sr.assigned_at, sr.accepted_at,
                            sreq.id as support_request_id, sreq.support_type, sreq.support_details, 
                            sreq.support_reason, sreq.status as support_status, sreq.admin_reason,
                            sreq.processed_by, sreq.processed_at, sreq.created_at as support_created_at,
                            sreq_admin.full_name as support_admin_name,
                            rf.rating as feedback_rating, rf.feedback as feedback_text, 
                            rf.software_feedback, rf.ease_of_use, rf.speed_stability, 
                            rf.requirement_meeting, rf.would_recommend, rf.created_at as feedback_created_at,
                            sr.error_description as resolution_error_description,
                            sr.error_type as resolution_error_type, sr.replacement_materials as resolution_replacement_materials,
                            sr.solution_method as resolution_solution_method, 
                            sr.resolved_at as resolution_resolved_at, assigned.full_name as resolver_name,
                            res.resolved_by as resolution_resolved_by, res.error_description as res_error_description,
                            res.error_type as res_error_type, res.replacement_materials as res_replacement_materials,
                            res.solution_method as res_solution_method, res.resolved_at as res_resolved_at,
                            resolver.full_name as resolution_resolver_name
                     FROM service_requests sr
                     LEFT JOIN categories c ON sr.category_id = c.id
                     LEFT JOIN users u ON sr.user_id = u.id
                     LEFT JOIN users assigned ON sr.assigned_to = assigned.id
                     LEFT JOIN support_requests sreq ON sr.id = sreq.service_request_id
                     LEFT JOIN users sreq_admin ON sreq.processed_by = sreq_admin.id
                     LEFT JOIN request_feedback rf ON sr.id = rf.service_request_id
                     LEFT JOIN resolutions res ON sr.id = res.service_request_id
                     LEFT JOIN users resolver ON res.resolved_by = resolver.id
                     WHERE sr.id = :id";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":id", $testId);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>✅ Query executed successfully</p>";
        echo "<p>✅ Found request #83</p>";
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>id</td><td>" . $request['id'] . "</td></tr>";
        echo "<tr><td>title</td><td>" . htmlspecialchars($request['title']) . "</td></tr>";
        echo "<tr><td>assigned_to</td><td>" . $request['assigned_to'] . "</td></tr>";
        echo "<tr><td>assigned_at</td><td>" . $request['assigned_at'] . "</td></tr>";
        echo "<tr><td>accepted_at</td><td>" . ($request['accepted_at'] ?: 'NULL') . "</td></tr>";
        echo "<tr><td>status</td><td>" . $request['status'] . "</td></tr>";
        echo "</table>";
        
        // Update accepted_at if missing
        if ($request['assigned_to'] && !$request['accepted_at']) {
            echo "<p>⚠️ Request has assigned_to but no accepted_at - Fixing...</p>";
            $updateQuery = "UPDATE service_requests SET accepted_at = assigned_at WHERE id = 83";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute();
            echo "<p>✅ Fixed accepted_at for request #83</p>";
        }
        
    } else {
        echo "<p>❌ Request #83 not found</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Query failed: " . $e->getMessage() . "</p>";
    echo "<p><strong>Stack trace:</strong> " . $e->getTraceAsString() . "</p>";
}

// Test API call directly
echo "<h3>🌐 Testing API Call:</h3>";
echo "<p>Testing: <code>api/service_requests.php?action=get&id=83</code></p>";

// Simulate the API call
$_GET['action'] = 'get';
$_GET['id'] = '83';

// Start output buffering to capture API response
ob_start();
try {
    include 'api/service_requests.php';
    $apiOutput = ob_get_clean();
    echo "<p>✅ API included successfully</p>";
    echo "<pre>" . htmlspecialchars($apiOutput) . "</pre>";
} catch (Exception $e) {
    $apiOutput = ob_get_clean();
    echo "<p>❌ API failed: " . $e->getMessage() . "</p>";
    echo "<pre>" . htmlspecialchars($apiOutput) . "</pre>";
}

echo "<hr>";
echo "<h4>🔧 Next Steps:</h4>";
echo "<ol>";
echo "<li><strong>Fix accepted_at column</strong> (done above)</li>";
echo "<li><strong>Update request data</strong> (done above)</li>";
echo "<li><strong>Refresh browser</strong> at request detail page</li>";
echo "<li><strong>Check console</strong> for any remaining errors</li>";
echo "</ol>";
?>
