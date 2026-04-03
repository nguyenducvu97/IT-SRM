<?php
// Check reject_requests table structure
require_once 'config/database.php';

echo "<h2>Kiểm tra cấu trúc reject_requests table</h2>";

try {
    $db = getDatabaseConnection();
    
    // Get table structure
    $stmt = $db->prepare("DESCRIBE reject_requests");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Columns trong reject_requests table:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>" . ($col['Default'] ?: 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if admin decision columns exist
    echo "<h3>Kiểm tra admin decision columns:</h3>";
    $hasAdminDecision = false;
    $hasAdminDecisionAt = false;
    $hasAdminId = false;
    
    foreach ($columns as $col) {
        if (strpos($col['Field'], 'admin') !== false) {
            echo "<p style='color: blue;'>Found admin column: {$col['Field']} ({$col['Type']})</p>";
            $hasAdminDecision = true;
        }
        if (strpos($col['Field'], 'decision') !== false) {
            echo "<p style='color: blue;'>Found decision column: {$col['Field']} ({$col['Type']})</p>";
            $hasAdminDecision = true;
        }
    }
    
    if (!$hasAdminDecision) {
        echo "<p style='color: red;'>❌ Không tìm thấy admin decision columns</p>";
        echo "<h3>Các columns có thể có:</h3>";
        echo "<ul>";
        echo "<li>admin_decision</li>";
        echo "<li>admin_decision_at</li>";
        echo "<li>admin_id</li>";
        echo "<li>decision</li>";
        echo "<li>decision_by</li>";
        echo "<li>decision_at</li>";
        echo "</ul>";
    }
    
    // Check service requests with status rejected
    echo "<h3>Service Requests có status = 'rejected':</h3>";
    $stmt1 = $db->prepare("SELECT id, title, status, updated_at FROM service_requests WHERE id IN (18,22,24,27,28) AND status = 'rejected'");
    $stmt1->execute();
    $serviceRequests = $stmt1->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($serviceRequests)) {
        echo "<p>Không có service request nào có status 'rejected'</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Updated</th></tr>";
        
        foreach ($serviceRequests as $request) {
            echo "<tr>";
            echo "<td>{$request['id']}</td>";
            echo "<td>" . substr($request['title'], 0, 50) . "...</td>";
            echo "<td><strong>{$request['status']}</strong></td>";
            echo "<td>{$request['updated_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check reject requests for these service requests
        echo "<h3>Reject Requests tương ứng:</h3>";
        $stmt2 = $db->prepare("SELECT * FROM reject_requests WHERE service_request_id IN (18,22,24,27,28)");
        $stmt2->execute();
        $rejectRequests = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($rejectRequests)) {
            echo "<p style='color: red;'>❌ Không có reject requests nào cho các service request này</p>";
        } else {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr>";
            foreach (array_keys($rejectRequests[0]) as $key) {
                echo "<th>{$key}</th>";
            }
            echo "</tr>";
            
            foreach ($rejectRequests as $reject) {
                echo "<tr>";
                foreach ($reject as $key => $value) {
                    echo "<td>" . ($value ?: '-') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
}
?>
