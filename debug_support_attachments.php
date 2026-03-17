<?php
// Debug script to check support request attachments
require_once 'config/database.php';

echo "<h2>Debug Support Request Attachments</h2>";

try {
    $pdo = getDatabaseConnection();
    echo "<p>✓ Database connection successful</p>";
    
    // Check recent support requests and their attachments
    echo "<h3>Recent Support Requests with Attachments:</h3>";
    $stmt = $pdo->prepare("
        SELECT sr.id, sr.support_details, sr.created_at,
               COUNT(sra.id) as attachment_count,
               GROUP_CONCAT(sra.original_name) as attachment_names
        FROM support_requests sr 
        LEFT JOIN support_request_attachments sra ON sr.id = sra.support_request_id 
        GROUP BY sr.id 
        ORDER BY sr.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($requests)) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Details</th><th>Created</th><th>Attachments</th><th>File Names</th></tr>";
        foreach ($requests as $req) {
            echo "<tr>";
            echo "<td>{$req['id']}</td>";
            echo "<td>" . substr($req['support_details'], 0, 50) . "...</td>";
            echo "<td>{$req['created_at']}</td>";
            echo "<td><strong>{$req['attachment_count']}</strong></td>";
            echo "<td>{$req['attachment_names']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No support requests found</p>";
    }
    
    // Check specific support request attachments table
    echo "<h3>Support Request Attachments Table:</h3>";
    $stmt = $pdo->prepare("SELECT * FROM support_request_attachments ORDER BY uploaded_at DESC LIMIT 10");
    $stmt->execute();
    $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($attachments)) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Support Request ID</th><th>Original Name</th><th>File Name</th><th>Size</th><th>Uploaded</th></tr>";
        foreach ($attachments as $att) {
            echo "<tr>";
            echo "<td>{$att['id']}</td>";
            echo "<td>{$att['support_request_id']}</td>";
            echo "<td>{$att['original_name']}</td>";
            echo "<td>{$att['filename']}</td>";
            echo "<td>{$att['file_size']}</td>";
            echo "<td>{$att['uploaded_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No support request attachments found in database</p>";
    }
    
    // Check if the support_request_attachments table exists
    echo "<h3>Table Structure Check:</h3>";
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'support_request_attachments'");
    $stmt->execute();
    $table_exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($table_exists) {
        echo "<p>✓ Table 'support_request_attachments' exists</p>";
        
        // Show table structure
        $stmt = $pdo->prepare("DESCRIBE support_request_attachments");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>✗ Table 'support_request_attachments' does not exist</p>";
        echo "<p>This might be why attachments are not showing up!</p>";
    }
    
} catch (Exception $e) {
    echo "<p>✗ Error: " . $e->getMessage() . "</p>";
}
?>
