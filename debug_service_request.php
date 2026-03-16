<?php
// Debug script to test service_requests API
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>DEBUG: service_requests.php API</h2>";

// Test database connection
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    echo "<p style='color: red;'>❌ Database connection failed</p>";
} else {
    echo "<p style='color: green;'>✅ Database connection successful</p>";
}

// Test session
require_once 'config/session.php';
startSession();

echo "<h3>Session Info:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Test specific request
$requestId = 43;
echo "<h3>Testing Request ID: $requestId</h3>";

try {
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
              WHERE sr.id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $requestId);
    
    echo "<h4>Query:</h4>";
    echo "<pre>" . htmlspecialchars($query) . "</pre>";
    
    if ($stmt->execute()) {
        echo "<p style='color: green;'>✅ Query executed successfully</p>";
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo "<h4>Result:</h4>";
            echo "<pre>";
            print_r($result);
            echo "</pre>";
            
            // Test JSON encoding
            $json = json_encode($result);
            echo "<h4>JSON:</h4>";
            echo "<pre>" . htmlspecialchars($json) . "</pre>";
            
            // Test JSON decode
            $decoded = json_decode($json);
            if ($decoded) {
                echo "<p style='color: green;'>✅ JSON encoding/decoding successful</p>";
            } else {
                echo "<p style='color: red;'>❌ JSON decoding failed</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ No result found</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Query execution failed</p>";
        echo "<p>Error info: ";
        print_r($stmt->errorInfo());
        echo "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

// Test with different session user
echo "<h3>Setting test session user:</h3>";
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';
$_SESSION['username'] = 'admin';

echo "<p>Test session set - try accessing API again</p>";
echo "<p><a href='api/service_requests.php?action=get&id=43'>Test API with session</a></p>";
?>
