<?php
// Fix request #28 status to 'open' and investigate root cause
require_once 'config/database.php';

echo "<h2>Fix Request #28 Status và Điều tra Nguyên Nhân</h2>";

try {
    $db = getDatabaseConnection();
    
    // First, fix request #28 to 'open' status
    echo "<h3>Fix Request #28:</h3>";
    
    $stmt1 = $db->prepare("SELECT id, title, status FROM service_requests WHERE id = 28");
    $stmt1->execute();
    $request28 = $stmt1->fetch(PDO::FETCH_ASSOC);
    
    if ($request28) {
        echo "<p>Current: Request #28 - {$request28['title']} (Status: {$request28['status']})</p>";
        
        // Update to 'open' status
        $stmt2 = $db->prepare("UPDATE service_requests SET status = 'open', updated_at = NOW() WHERE id = 28");
        $result = $stmt2->execute();
        
        if ($result) {
            echo "<p style='color: green;'>✅ Fixed: {$request28['status']} → open</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to update request #28</p>";
        }
    }
    
    // Now investigate the root cause
    echo "<h3>Điều tra nguyên nhân gốc rễ:</h3>";
    
    // Check all requests that should have 'open' status
    $requestsToCheck = [18, 22, 27, 28];
    
    foreach ($requestsToCheck as $requestId) {
        echo "<h4>Request #{$requestId}:</h4>";
        
        $stmt = $db->prepare("SELECT id, title, status, created_at, updated_at, assigned_to FROM service_requests WHERE id = ?");
        $stmt->execute([$requestId]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($request) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Field</th><th>Value</th><th>Analysis</th></tr>";
            
            // Analyze each field
            $fields = [
                'id' => $request['id'],
                'title' => substr($request['title'], 0, 50) . '...',
                'status' => $request['status'],
                'created_at' => $request['created_at'],
                'updated_at' => $request['updated_at'],
                'assigned_to' => $request['assigned_to'] ?: 'NULL'
            ];
            
            foreach ($fields as $key => $value) {
                $analysis = '';
                
                if ($key === 'status') {
                    if ($requestId === 28 && $value === 'open') {
                        $analysis = '✅ Correct (new request)';
                    } elseif ($value === 'in_progress') {
                        $analysis = '✅ Correct (being processed)';
                    } elseif ($value === 'rejected') {
                        $analysis = '❌ Wrong (should have reject request)';
                    } else {
                        $analysis = '⚠️ Check';
                    }
                } elseif ($key === 'assigned_to') {
                    if ($value === 'NULL' || $value === '') {
                        $analysis = '📝 New request (not assigned)';
                    } else {
                        $analysis = '👤 Assigned to staff';
                    }
                } elseif ($key === 'created_at') {
                    $created = new DateTime($request['created_at']);
                    $now = new DateTime();
                    $interval = $created->diff($now);
                    $analysis = "{$interval->d} days ago";
                }
                
                echo "<tr><td>{$key}</td><td>{$value}</td><td>{$analysis}</td></tr>";
            }
            
            echo "</table>";
            
            // Determine correct status based on logic
            echo "<h5>Correct status should be:</h5>";
            
            if ($request['assigned_to'] === null || $request['assigned_to'] === '') {
                echo "<p style='color: blue;'>📝 <strong>open</strong> - Not assigned to staff yet</p>";
            } elseif ($request['status'] === 'in_progress') {
                echo "<p style='color: green;'>✅ <strong>in_progress</strong> - Assigned and being processed</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ Need to check workflow</p>";
            }
        }
        
        echo "<hr>";
    }
    
    // Check for potential causes in code
    echo "<h3>🔍 Potential Causes in Code:</h3>";
    
    // Check API endpoints that might update status
    $apiFiles = [
        'api/service_requests.php',
        'api/reject_requests.php', 
        'api/support_requests.php'
    ];
    
    foreach ($apiFiles as $file) {
        if (file_exists($file)) {
            echo "<h4>Checking {$file}:</h4>";
            
            $content = file_get_contents($file);
            
            // Look for direct status updates
            if (strpos($content, "status = 'rejected'") !== false) {
                echo "<p style='color: red;'>⚠️ Found direct status update to 'rejected'</p>";
            }
            
            if (strpos($content, "UPDATE service_requests SET status") !== false) {
                echo "<p style='color: orange;'>⚠️ Found direct status UPDATE</p>";
            }
            
            // Look for reject request creation
            if (strpos($content, "INSERT INTO reject_requests") !== false) {
                echo "<p style='color: green;'>✅ Found reject request creation</p>";
            }
        }
    }
    
    echo "<h3>🛡️ Prevention Measures:</h3>";
    echo "<ol>";
    echo "<li><strong>Database Trigger:</strong> Add trigger to prevent status 'rejected' without reject request</li>";
    echo "<li><strong>Code Validation:</strong> Add validation in all status update endpoints</li>";
    echo "<li><strong>Audit Log:</strong> Log all status changes with user and timestamp</li>";
    echo "<li><strong>API Validation:</strong> Ensure all status changes follow proper workflow</li>";
    echo "</ol>";
    
    // Create database trigger suggestion
    echo "<h3>💡 Database Trigger Solution:</h3>";
    echo "<pre style='background: #f5f5f5; padding: 10px;'>";
    echo "CREATE TRIGGER prevent_invalid_rejected_status
BEFORE UPDATE ON service_requests
FOR EACH ROW
BEGIN
    IF NEW.status = 'rejected' THEN
        -- Check if there's an approved reject request
        DECLARE reject_count INT;
        SELECT COUNT(*) INTO reject_count 
        FROM reject_requests 
        WHERE service_request_id = NEW.id AND status = 'approved';
        
        IF reject_count = 0 THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Cannot set status to rejected without approved reject request';
        END IF;
    END IF;
END;";
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
}
?>
