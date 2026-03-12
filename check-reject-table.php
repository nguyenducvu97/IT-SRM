<?php
// Check reject_requests table structure
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Check reject_requests Table Structure</h2>";

try {
    $conn = new PDO("mysql:host=localhost;dbname=it_service_request", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Database connected</p>";
    
    // Check if table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'reject_requests'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ reject_requests table exists</p>";
        
        // Show table structure
        echo "<h3>Table Structure:</h3>";
        $stmt = $conn->query("DESCRIBE reject_requests");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th>";
        echo "</tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check existing data
        echo "<h3>Existing Data:</h3>";
        $stmt = $conn->query("SELECT COUNT(*) as total FROM reject_requests");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Total records: " . $count['total'] . "</p>";
        
        if ($count['total'] > 0) {
            $stmt = $conn->query("SELECT * FROM reject_requests LIMIT 3");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<p>Sample data:</p>";
            echo "<pre>" . print_r($data, true) . "</pre>";
        }
        
        // Create test data with correct column names
        echo "<h3>Creating Test Data:</h3>";
        
        // Get a service request to create reject request for
        $stmt = $conn->prepare("SELECT id, user_id FROM service_requests LIMIT 1");
        $stmt->execute();
        $service_request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($service_request) {
            echo "<p>Found service request ID: " . $service_request['id'] . "</p>";
            
            // Use correct column names based on table structure
            $stmt = $conn->prepare("
                INSERT INTO reject_requests (service_request_id, rejected_by, reject_reason, reject_details, status, created_at)
                VALUES (?, ?, ?, ?, 'pending', NOW())
            ");
            $result = $stmt->execute([
                $service_request['id'],
                $service_request['user_id'], // Use rejected_by instead of user_id
                'Test reject reason for staff view',
                'Test reject details for staff view'
            ]);
            
            if ($result) {
                echo "<p style='color: green;'>✅ Created test reject request successfully!</p>";
                
                // Test API again
                echo "<h3>Testing API with test data:</h3>";
                
                session_start();
                $_SESSION['user_id'] = 17;
                $_SESSION['username'] = 'nvnam';
                $_SESSION['role'] = 'staff';
                
                $old_dir = getcwd();
                chdir(__DIR__ . '/api');
                
                $_GET['action'] = 'list';
                $_GET['status'] = 'pending';
                $_SERVER['REQUEST_METHOD'] = 'GET';
                
                ob_start();
                include 'reject_requests.php';
                $output = ob_get_clean();
                
                chdir($old_dir);
                
                $response = json_decode($output, true);
                if ($response && $response['success']) {
                    echo "<p style='color: green;'>🎉 API Success with test data!</p>";
                    echo "<p>Found " . count($response['data']) . " reject requests</p>";
                    
                    if (!empty($response['data'])) {
                        echo "<p>Sample request:</p>";
                        echo "<pre>" . print_r($response['data'][0], true) . "</pre>";
                    }
                }
                
            } else {
                echo "<p style='color: red;'>❌ Failed to create test reject request</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ No service requests found to create reject request for</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ reject_requests table does not exist</p>";
        
        // Create table
        echo "<h3>Creating reject_requests table...</h3>";
        $sql = "CREATE TABLE reject_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            service_request_id INT NOT NULL,
            rejected_by INT NOT NULL,
            reject_reason TEXT NOT NULL,
            reject_details TEXT,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            admin_reason TEXT,
            processed_by INT,
            processed_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (service_request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
            FOREIGN KEY (rejected_by) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->exec($sql);
        echo "<p style='color: green;'>✅ Created reject_requests table</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ Database Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='index.html'>Test in Main Application</a></p>";
?>
