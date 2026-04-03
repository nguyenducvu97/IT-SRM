<?php
// Test if reject_requests table exists
require_once 'config/database.php';
require_once 'config/session.php';

echo "<h2>Reject Requests Table Test</h2>";

try {
    $db = getDatabaseConnection();
    echo "✅ Database connection established<br>";
    
    // Check if reject_requests table exists
    $table_check = $db->query("SHOW TABLES LIKE 'reject_requests'");
    if ($table_check->rowCount() > 0) {
        echo "✅ reject_requests table exists<br>";
        
        // Show table structure
        $structure = $db->query("DESCRIBE reject_requests");
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        while ($row = $structure->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Count records
        $count = $db->query("SELECT COUNT(*) as total FROM reject_requests");
        $total = $count->fetch(PDO::FETCH_ASSOC)['total'];
        echo "<h3>Record Count: $total</h3>";
        
        // Show sample records
        if ($total > 0) {
            $records = $db->query("SELECT * FROM reject_requests LIMIT 5");
            echo "<h3>Sample Records:</h3>";
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Service Request ID</th><th>Rejected By</th><th>Status</th><th>Created At</th></tr>";
            while ($row = $records->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . $row['service_request_id'] . "</td>";
                echo "<td>" . $row['rejected_by'] . "</td>";
                echo "<td>" . $row['status'] . "</td>";
                echo "<td>" . $row['created_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "❌ reject_requests table does not exist<br>";
        echo "<h3>Creating table...</h3>";
        
        // Try to create the table
        $sql = "CREATE TABLE IF NOT EXISTS reject_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            service_request_id INT NOT NULL,
            rejected_by INT NOT NULL,
            reject_reason TEXT NOT NULL,
            reject_details TEXT,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            admin_reason TEXT NULL,
            processed_by INT NULL,
            processed_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (service_request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
            FOREIGN KEY (rejected_by) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
            
            INDEX idx_service_request_id (service_request_id),
            INDEX idx_rejected_by (rejected_by),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        )";
        
        if ($db->exec($sql)) {
            echo "✅ Table created successfully<br>";
        } else {
            echo "❌ Error creating table: " . implode(", ", $db->errorInfo()) . "<br>";
        }
    }
    
    // Check if reject_request_attachments table exists
    $attachment_check = $db->query("SHOW TABLES LIKE 'reject_request_attachments'");
    if ($attachment_check->rowCount() > 0) {
        echo "✅ reject_request_attachments table exists<br>";
    } else {
        echo "❌ reject_request_attachments table does not exist<br>";
        echo "<h3>Creating attachments table...</h3>";
        
        $sql = "CREATE TABLE IF NOT EXISTS reject_request_attachments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            reject_request_id INT NOT NULL,
            original_filename VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size INT NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (reject_request_id) REFERENCES reject_requests(id) ON DELETE CASCADE,
            INDEX idx_reject_request_id (reject_request_id)
        )";
        
        if ($db->exec($sql)) {
            echo "✅ Attachments table created successfully<br>";
        } else {
            echo "❌ Error creating attachments table: " . implode(", ", $db->errorInfo()) . "<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Test the API directly
echo "<h3>Testing API Directly:</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/it-service-request/api/reject_requests.php?action=list');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode<br>";
echo "Response: <pre>" . htmlspecialchars($response) . "</pre>";
?>
