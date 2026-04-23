<?php
echo "<h1>Email Fix Test (No Login Required)</h1>";

// Simulate user session for testing
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'staff';
$_SESSION['full_name'] = 'Test Staff';

echo "<p><strong>Test User:</strong> Test Staff (Role: staff)</p>";

require_once 'config/database.php';
require_once 'config/session.php';

try {
    $db = (new Database())->getConnection();
    
    echo "<h2>Test 1: New Request Email (ID Issue)</h2>";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_new_request'])) {
        echo "<h3>Creating new request to test ID issue...</h3>";
        
        // Find a regular user
        $user_query = "SELECT id, full_name, email FROM users WHERE role = 'user' LIMIT 1";
        $user_stmt = $db->prepare($user_query);
        $user_stmt->execute();
        $test_user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$test_user) {
            echo "<p style='color: red;'>No regular user found. Creating test user...</p>";
            
            // Create test user
            $insert_user = "INSERT INTO users (username, password, full_name, email, role, status, created_at) 
                           VALUES ('testuser', 'password', 'Test User', 'test@example.com', 'user', 'active', NOW())";
            $user_insert = $db->prepare($insert_user);
            $user_insert->execute();
            $test_user_id = $db->lastInsertId();
            
            $test_user = [
                'id' => $test_user_id,
                'full_name' => 'Test User',
                'email' => 'test@example.com'
            ];
        }
        
        // Simulate the new request creation with debugging
        $title = "Test Email Fix Request - " . date('Y-m-d H:i:s');
        $description = "This request tests the email ID fix functionality.";
        $category_id = 1;
        $priority = 'medium';
        
        echo "<p>Creating request for user: {$test_user['full_name']}</p>";
        echo "<p>Request title: " . htmlspecialchars($title) . "</p>";
        
        // Insert request
        $insert_query = "INSERT INTO service_requests (user_id, title, description, category_id, priority, status, created_at, updated_at) 
                         VALUES (:user_id, :title, :description, :category_id, :priority, 'open', NOW(), NOW())";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->execute([
            ':user_id' => $test_user['id'],
            ':title' => $title,
            ':description' => $description,
            ':category_id' => $category_id,
            ':priority' => $priority
        ]);
        
        $request_id = $db->lastInsertId();
        echo "<p style='color: green;'>Request created with ID: $request_id</p>";
        
        // Test email sending with the new ID
        require_once 'lib/EmailHelper.php';
        $emailHelper = new EmailHelper();
        
        // Get request details for email
        $request_query = "SELECT sr.*, u.full_name as requester_name, u.email as requester_email
                          FROM service_requests sr
                          LEFT JOIN users u ON sr.user_id = u.id
                          WHERE sr.id = :request_id";
        $request_stmt = $db->prepare($request_query);
        $request_stmt->bindParam(":request_id", $request_id);
        $request_stmt->execute();
        $request_data = $request_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h4>Email Debug Information:</h4>";
        echo "<p><strong>Database ID:</strong> {$request_id}</p>";
        echo "<p><strong>Email Data ID:</strong> {$request_data['id']}</p>";
        echo "<p><strong>Title:</strong> " . htmlspecialchars($request_data['title']) . "</p>";
        echo "<p><strong>Requester:</strong> {$request_data['requester_name']}</p>";
        
        // Send email
        $email_result = $emailHelper->sendNewRequestNotification($request_data);
        echo "<p><strong>Email Result:</strong> " . ($email_result ? "SUCCESS" : "FAILED") . "</p>";
        
        if ($email_result) {
            echo "<p style='color: green; font-weight: bold;'>Email sent successfully! Check admin inbox for request ID: $request_id</p>";
        }
        
        // Check logs
        echo "<h4>Recent Debug Logs:</h4>";
        $log_file = 'logs/api_errors.log';
        if (file_exists($log_file)) {
            $log_content = file_get_contents($log_file);
            $log_lines = explode("\n", $log_content);
            $recent_lines = array_slice($log_lines, -5);
            
            foreach ($recent_lines as $line) {
                if (strpos($line, 'EMAIL_DEBUG') !== false) {
                    echo "<p style='background-color: #f8f9fa; padding: 5px; font-family: monospace;'>" . htmlspecialchars($line) . "</p>";
                }
            }
        }
    }
    
    echo "<h2>Test 2: Staff Accept Request (Admin Email)</h2>";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_accept_request'])) {
        echo "<h3>Testing staff accept request with admin email...</h3>";
        
        // Find an open request
        $query = "SELECT sr.*, u.full_name as requester_name, u.email as requester_email
                  FROM service_requests sr
                  LEFT JOIN users u ON sr.user_id = u.id
                  WHERE sr.status = 'open' AND (sr.assigned_to IS NULL OR sr.assigned_to = 0)
                  ORDER BY sr.created_at DESC
                  LIMIT 1";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$request) {
            echo "<p style='color: orange;'>No open requests found. Creating one...</p>";
            
            // Create a test request
            $user_query = "SELECT id, full_name, email FROM users WHERE role = 'user' LIMIT 1";
            $user_stmt = $db->prepare($user_query);
            $user_stmt->execute();
            $test_user = $user_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$test_user) {
                echo "<p style='color: red;'>No users found. Please create a user first.</p>";
            } else {
                $insert_query = "INSERT INTO service_requests (user_id, title, description, category_id, priority, status, created_at, updated_at) 
                                 VALUES (:user_id, :title, :description, :category_id, :priority, 'open', NOW(), NOW())";
                $insert_stmt = $db->prepare($insert_query);
                $insert_stmt->execute([
                    ':user_id' => $test_user['id'],
                    ':title' => 'Test Admin Email Request',
                    ':description' => 'This request tests admin email when staff accepts',
                    ':category_id' => 1,
                    ':priority' => 'medium'
                ]);
                
                $request_id = $db->lastInsertId();
                
                // Get the created request
                $request_query = "SELECT sr.*, u.full_name as requester_name, u.email as requester_email
                                  FROM service_requests sr
                                  LEFT JOIN users u ON sr.user_id = u.id
                                  WHERE sr.id = :request_id";
                $request_stmt = $db->prepare($request_query);
                $request_stmt->bindParam(":request_id", $request_id);
                $request_stmt->execute();
                $request = $request_stmt->fetch(PDO::FETCH_ASSOC);
            }
        }
        
        if ($request) {
            echo "<h4>Request Details:</h4>";
            echo "<p><strong>ID:</strong> {$request['id']}</p>";
            echo "<p><strong>Title:</strong> " . htmlspecialchars($request['title']) . "</p>";
            echo "<p><strong>Requester:</strong> {$request['requester_name']} ({$request['requester_email']})</p>";
            
            // Simulate staff accept with admin email
            echo "<h4>Simulating staff accept with admin emails...</h4>";
            
            // Update request
            $update_query = "UPDATE service_requests 
                            SET assigned_to = 1, status = 'in_progress', 
                                assigned_at = NOW(), accepted_at = NOW(), updated_at = NOW() 
                            WHERE id = :request_id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(":request_id", $request['id']);
            
            if ($update_stmt->execute()) {
                echo "<p style='color: green;'>Database updated successfully</p>";
                
                // Get updated request data
                $request_query = "SELECT sr.*, u.full_name as requester_name, u.email as requester_email, 
                                         staff.full_name as assigned_name, staff.email as assigned_email
                                  FROM service_requests sr
                                  LEFT JOIN users u ON sr.user_id = u.id
                                  LEFT JOIN users staff ON sr.assigned_to = staff.id
                                  WHERE sr.id = :request_id";
                $request_stmt = $db->prepare($request_query);
                $request_stmt->bindParam(":request_id", $request['id']);
                $request_stmt->execute();
                $request_data = $request_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($request_data) {
                    // Send email to requester
                    require_once 'lib/EmailHelper.php';
                    $emailHelper = new EmailHelper();
                    
                    $subject = "TEST: Yêu câu #{$request_data['id']} - Tràng thái thay thành 'in_progress'";
                    $body = "Chào {$request_data['requester_name']},\n\n";
                    $body .= "Yêu câu #{$request_data['id']} ('{$request_data['title']}') cua ban da duoc nhan boi nhân viên IT.\n\n";
                    $body .= "Nhân viên phu trách: Test Staff\n\n";
                    $body .= "Trang thái: in_progress\n\n";
                    $body .= "Ban có the xem chi tiêt tai: http://localhost/it-service-request/request-detail.html?id={$request_data['id']}\n\n";
                    $body .= "Trân tr,\n";
                    $body .= "IT Service Request System";
                    
                    $requester_email_result = $emailHelper->sendEmail(
                        $request_data['requester_email'],
                        $request_data['requester_name'],
                        $subject,
                        $body
                    );
                    echo "<p><strong>Email to Requester:</strong> " . ($requester_email_result ? "SUCCESS" : "FAILED") . "</p>";
                    
                    // Send emails to admins
                    $admin_query = "SELECT email, full_name FROM users WHERE role = 'admin' AND status = 'active'";
                    $admin_stmt = $db->prepare($admin_query);
                    $admin_stmt->execute();
                    $admins = $admin_stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo "<p><strong>Admins found:</strong> " . count($admins) . "</p>";
                    
                    if (!empty($admins)) {
                        $admin_subject = "TEST: Staff Accepted Request #{$request_data['id']}";
                        $admin_body = "<h2>Request Accepted by Staff</h2>
                                      <p><strong>Request ID:</strong> #{$request_data['id']}</p>
                                      <p><strong>Title:</strong> " . htmlspecialchars($request_data['title']) . "</p>
                                      <p><strong>Requester:</strong> {$request_data['requester_name']}</p>
                                      <p><strong>Staff:</strong> Test Staff</p>
                                      <p><strong>Status:</strong> in_progress</p>
                                      <p><a href='http://localhost/it-service-request/request-detail.html?id={$request_data['id']}'>View Request Details</a></p>";
                        
                        $admin_success_count = 0;
                        foreach ($admins as $admin) {
                            $admin_email_result = $emailHelper->sendEmail(
                                $admin['email'],
                                $admin['full_name'],
                                $admin_subject,
                                $admin_body
                            );
                            echo "<p><strong>Email to Admin {$admin['email']}:</strong> " . ($admin_email_result ? "SUCCESS" : "FAILED") . "</p>";
                            if ($admin_email_result) {
                                $admin_success_count++;
                            }
                        }
                        
                        echo "<p style='color: green; font-weight: bold;'>Admin emails sent: $admin_success_count/" . count($admins) . "</p>";
                    } else {
                        echo "<p style='color: orange;'>No active admins found in database</p>";
                    }
                }
            }
        }
    }
    
    // Show test forms
    echo "<h2>Test Forms</h2>";
    
    echo "<div style='padding: 20px; background-color: #f8f9fa; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>Test 1: New Request Email (ID Issue)</h3>";
    echo "<p>This will create a new request and test if the email shows the correct ID.</p>";
    echo "<form method='POST'>";
    echo "<input type='hidden' name='test_new_request' value='1'>";
    echo "<button type='submit' style='background-color: #007bff; color: white; padding: 10px 20px; border: none; cursor: pointer; border-radius: 4px;'>
                Test New Request Email
            </button>";
    echo "</form>";
    echo "</div>";
    
    echo "<div style='padding: 20px; background-color: #f8f9fa; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>Test 2: Staff Accept Request (Admin Email)</h3>";
    echo "<p>This will test if admins receive emails when staff accepts a request.</p>";
    echo "<form method='POST'>";
    echo "<input type='hidden' name='test_accept_request' value='1'>";
    echo "<button type='submit' style='background-color: #28a745; color: white; padding: 10px 20px; border: none; cursor: pointer; border-radius: 4px;'>
                Test Staff Accept with Admin Email
            </button>";
    echo "</form>";
    echo "</div>";
    
    // Check recent email logs
    echo "<h2>Recent Email Logs</h2>";
    
    $log_file = 'logs/email_activity.log';
    if (file_exists($log_file)) {
        echo "<h3>Last 10 Email Activities:</h3>";
        $log_content = file_get_contents($log_file);
        $log_lines = explode("\n", $log_content);
        $recent_lines = array_slice($log_lines, -10);
        
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'><th>Timestamp</th><th>Status</th><th>To</th><th>Subject</th></tr>";
        
        foreach ($recent_lines as $line) {
            if (trim($line)) {
                if (preg_match('/\[(.*?)\] (\w+) \| To: (.*?) \| Subject: (.*)/', $line, $matches)) {
                    $highlight = (strpos($matches[4], 'TEST:') !== false) ? "style='background-color: #fff3cd;'" : "";
                    echo "<tr $highlight>";
                    echo "<td>{$matches[1]}</td>";
                    echo "<td style='color: " . ($matches[2] == 'SENT_PHPMAIL' ? 'green' : 'red') . ";'>{$matches[2]}</td>";
                    echo "<td>{$matches[3]}</td>";
                    echo "<td>" . htmlspecialchars($matches[4]) . "</td>";
                    echo "</tr>";
                }
            }
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>Email log file not found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='index.html'>Back to Main Application</a></p>";
echo "<p><strong>Note:</strong> This test file bypasses login for testing purposes.</p>";
?>
