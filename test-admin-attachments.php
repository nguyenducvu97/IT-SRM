<?php
// Simple test for admin view of service request attachments
session_start();

// Set admin session
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['full_name'] = 'System Administrator';
$_SESSION['role'] = 'admin';

echo "<h2>Admin Service Request Attachments Test</h2>";

// Test specific request ID
$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 28;

echo "<h3>Testing Request ID: $request_id</h3>";

// Load the API directly
require_once 'config/database.php';
require_once 'config/session.php';

try {
    $db = getDatabaseConnection();
    
    // Get request details
    $query = "SELECT sr.*, c.name as category_name, u.full_name as requester_name, 
                     u.email as requester_email, u.phone as requester_phone,
                     assigned.full_name as assigned_name, assigned.email as assigned_email
             FROM service_requests sr
             LEFT JOIN categories c ON sr.category_id = c.id
             LEFT JOIN users u ON sr.user_id = u.id
             LEFT JOIN users assigned ON sr.assigned_to = assigned.id
             WHERE sr.id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $request_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h4>Request Details:</h4>";
        echo "<p><strong>Title:</strong> " . $request['title'] . "</p>";
        echo "<p><strong>Status:</strong> " . $request['status'] . "</p>";
        echo "<p><strong>Requester:</strong> " . $request['requester_name'] . "</p>";
        echo "<p><strong>Assigned to:</strong> " . ($request['assigned_name'] ?: 'N/A') . "</p>";
        
        // Get attachments
        $attachments_query = "SELECT id, filename, original_name, file_size, mime_type, uploaded_at 
                              FROM attachments 
                              WHERE service_request_id = :id 
                              ORDER BY uploaded_at ASC";
        
        $attachments_stmt = $db->prepare($attachments_query);
        $attachments_stmt->bindParam(":id", $request_id);
        $attachments_stmt->execute();
        
        $attachments = $attachments_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>Attachments Found: " . count($attachments) . "</h4>";
        
        if (count($attachments) > 0) {
            foreach ($attachments as $attachment) {
                echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px 0;'>";
                echo "<strong>" . $attachment['original_name'] . "</strong><br>";
                echo "Size: " . number_format($attachment['file_size']) . " bytes<br>";
                echo "Type: " . $attachment['mime_type'] . "<br>";
                echo "Uploaded: " . $attachment['uploaded_at'] . "<br>";
                echo "</div>";
            }
        } else {
            echo "<p style='color: orange;'>No attachments found for this request</p>";
        }
        
        // Check if this is the same as API response
        echo "<h4>API Response Check:</h4>";
        
        // Simulate API call
        $_GET['action'] = 'get';
        $_GET['id'] = $request_id;
        
        // Capture output
        ob_start();
        include 'api/service_requests.php';
        $api_output = ob_get_clean();
        
        echo "<pre>" . htmlspecialchars($api_output) . "</pre>";
        
        // Parse API response
        $api_data = json_decode($api_output, true);
        if ($api_data && $api_data['success']) {
            echo "<h5>API Parsed Data:</h5>";
            echo "<p>Attachments in API response: " . (isset($api_data['data']['attachments']) ? count($api_data['data']['attachments']) : 0) . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>Request not found!</p>";
    }
    
} catch (Exception $e) {
    echo "<h4>Error:</h4>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>
