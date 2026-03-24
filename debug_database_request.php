<?php
// Debug database record for request #8
require_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

$request_id = isset($_GET['request_id']) ? (int)$_GET['request_id'] : 8;

echo "<h2>Database Record cho Request #$request_id</h2>";

try {
    $query = "SELECT 
        sr.id, sr.title, sr.status, sr.assigned_to, sr.assigned_at, 
        sr.created_at, sr.updated_at, sr.resolved_at,
        u.full_name as assigned_name, u.email as assigned_email
    FROM service_requests sr
    LEFT JOIN users u ON sr.assigned_to = u.id
    WHERE sr.id = :request_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':request_id', $request_id);
    $stmt->execute();
    
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($request) {
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Value</th><th>Status</th></tr>";
        
        foreach ($request as $field => $value) {
            $status = '';
            if ($field === 'assigned_at') {
                $status = $value ? '✅' : '❌';
            }
            echo "<tr><td>$field</td><td>" . ($value ?? 'NULL') . "</td><td>$status</td></tr>";
        }
        
        echo "</table>";
        
        echo "<h3>Analysis:</h3>";
        echo "<ul>";
        echo "<li><strong>Status:</strong> " . $request['status'] . "</li>";
        echo "<li><strong>Assigned To:</strong> " . ($request['assigned_to'] ?? 'NULL') . "</li>";
        echo "<li><strong>Assigned At:</strong> " . ($request['assigned_at'] ?? 'NULL') . "</li>";
        echo "<li><strong>Assigned Name:</strong> " . ($request['assigned_name'] ?? 'NULL') . "</li>";
        
        if ($request['assigned_at']) {
            echo "<li class='success'>✅ assigned_at có giá trị: " . $request['assigned_at'] . "</li>";
        } else {
            echo "<li class='error'>❌ assigned_at là NULL - Đây là vấn đề!</li>";
        }
        
        echo "</ul>";
        
        // Check if this request should have assigned_at
        echo "<h3>Expected Behavior:</h3>";
        if ($request['assigned_to'] && $request['status'] === 'in_progress') {
            echo "<p class='error'>❌ Request đã được assign nhưng assigned_at là NULL</p>";
            echo "<p><strong>Cần fix:</strong> Update assigned_at khi staff nhận yêu cầu</p>";
            
            // Fix it
            $fix_query = "UPDATE service_requests 
            SET assigned_at = DATE_ADD(created_at, INTERVAL 1 HOUR) 
            WHERE id = :request_id AND assigned_to IS NOT NULL AND assigned_at IS NULL";
            $fix_stmt = $db->prepare($fix_query);
            $fix_stmt->bindParam(':request_id', $request_id);
            
            if ($fix_stmt->execute()) {
                echo "<p class='success'>✅ Đã fix assigned_at cho request #$request_id</p>";
            }
        } else {
            echo "<p class='info'>ℹ️ Request chưa được assign hoặc không phải in_progress</p>";
        }
        
    } else {
        echo "<p class='error'>❌ Request #$request_id không tồn tại</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

?>
