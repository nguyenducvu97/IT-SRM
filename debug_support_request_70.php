<?php
// Debug script to check support request attachments for a specific request
require_once 'config/database.php';

echo "<h2>Debug Support Request Attachments for Request ID 70</h2>";

try {
    $pdo = getDatabaseConnection();
    echo "<p>✓ Database connection successful</p>";
    
    // Check if there's a support request for service request ID 70
    echo "<h3>Step 1: Check Support Request for Service Request #70:</h3>";
    $stmt = $pdo->prepare("
        SELECT sr.*, sra.id as support_request_id 
        FROM support_requests sr 
        LEFT JOIN service_requests sra ON sr.service_request_id = sra.id 
        WHERE sr.service_request_id = 70
    ");
    $stmt->execute();
    $support_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($support_requests)) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Support ID</th><th>Service ID</th><th>Type</th><th>Details</th><th>Status</th><th>Created</th></tr>";
        foreach ($support_requests as $sr) {
            echo "<tr>";
            echo "<td>{$sr['id']}</td>";
            echo "<td>{$sr['service_request_id']}</td>";
            echo "<td>{$sr['support_type']}</td>";
            echo "<td>" . substr($sr['support_details'], 0, 30) . "...</td>";
            echo "<td>{$sr['status']}</td>";
            echo "<td>{$sr['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check attachments for each support request
        foreach ($support_requests as $sr) {
            echo "<h3>Step 2: Check Attachments for Support Request #{$sr['id']}:</h3>";
            
            $stmt = $pdo->prepare("
                SELECT * FROM support_request_attachments 
                WHERE support_request_id = ?
                ORDER BY uploaded_at DESC
            ");
            $stmt->execute([$sr['id']]);
            $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($attachments)) {
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>ID</th><th>Original Name</th><th>File Name</th><th>Size</th><th>Type</th><th>Uploaded</th></tr>";
                foreach ($attachments as $att) {
                    echo "<tr>";
                    echo "<td>{$att['id']}</td>";
                    echo "<td>{$att['original_name']}</td>";
                    echo "<td>{$att['filename']}</td>";
                    echo "<td>" . number_format($att['file_size']) . " bytes</td>";
                    echo "<td>{$att['mime_type']}</td>";
                    echo "<td>{$att['uploaded_at']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                // Check if files exist physically
                echo "<h4>Physical File Check:</h4>";
                foreach ($attachments as $att) {
                    $filePath = __DIR__ . '/uploads/support_requests/' . $att['filename'];
                    $exists = file_exists($filePath);
                    $color = $exists ? 'green' : 'red';
                    $status = $exists ? '✓ EXISTS' : '✗ MISSING';
                    echo "<p style='color: $color;'>File '{$att['filename']}: $status</p>";
                }
                
            } else {
                echo "<p style='color: orange;'>⚠ No attachments found for support request #{$sr['id']}</p>";
            }
        }
        
    } else {
        echo "<p style='color: red;'>✗ No support request found for service request #70</p>";
        
        // Check all support requests
        echo "<h3>All Support Requests:</h3>";
        $stmt = $pdo->prepare("SELECT * FROM support_requests ORDER BY created_at DESC LIMIT 10");
        $stmt->execute();
        $all_support = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($all_support)) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Service ID</th><th>Type</th><th>Details</th><th>Status</th><th>Created</th></tr>";
            foreach ($all_support as $sr) {
                echo "<tr>";
                echo "<td>{$sr['id']}</td>";
                echo "<td>{$sr['service_request_id']}</td>";
                echo "<td>{$sr['support_type']}</td>";
                echo "<td>" . substr($sr['support_details'], 0, 30) . "...</td>";
                echo "<td>{$sr['status']}</td>";
                echo "<td>{$sr['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No support requests at all</p>";
        }
    }
    
    // Test API call simulation
    echo "<h3>Step 3: Test API Response Simulation:</h3>";
    $stmt = $pdo->prepare("
        SELECT sr.*, c.name as category_name, u.full_name as requester_name, 
               u.email as requester_email, u.phone as requester_phone,
               assigned.full_name as assigned_name, assigned.email as assigned_email,
               sreq.id as support_request_id, sreq.support_type, sreq.support_details, 
               sreq.support_reason, sreq.status as support_status, sreq.admin_reason,
               sreq.processed_by, sreq.processed_at, sreq.created_at as support_created_at,
               sreq_admin.full_name as support_admin_name
        FROM service_requests sr
        LEFT JOIN categories c ON sr.category_id = c.id
        LEFT JOIN users u ON sr.user_id = u.id
        LEFT JOIN users assigned ON sr.assigned_to = assigned.id
        LEFT JOIN support_requests sreq ON sr.id = sreq.service_request_id
        LEFT JOIN users sreq_admin ON sreq.processed_by = sreq_admin.id
        WHERE sr.id = 70
    ");
    $stmt->execute();
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($request && $request['support_request_id']) {
        echo "<p>✓ Found support request data in API simulation</p>";
        echo "<p>Support Request ID: {$request['support_request_id']}</p>";
        echo "<p>Support Type: {$request['support_type']}</p>";
        echo "<p>Support Details: {$request['support_details']}</p>";
        
        // Get attachments like the API does
        $support_attachments_query = "SELECT id, filename, original_name, file_size, mime_type, uploaded_at 
                                     FROM support_request_attachments 
                                     WHERE support_request_id = ? 
                                     ORDER BY uploaded_at ASC";
        $support_attachments_stmt = $pdo->prepare($support_attachments_query);
        $support_attachments_stmt->execute([$request['support_request_id']]);
        $support_attachments = $support_attachments_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>API Attachments Result:</h4>";
        if (!empty($support_attachments)) {
            echo "<p style='color: green;'>✓ Found " . count($support_attachments) . " attachments</p>";
            foreach ($support_attachments as $att) {
                echo "<p>- {$att['original_name']} ({$att['mime_type']})</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠ No attachments found in API query</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ No support request found in API simulation</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='request-detail.html?id=70'>← Back to Request Detail</a></p>";
?>
