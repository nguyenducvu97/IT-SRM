<?php
// Check request #28 details for accept button
require_once 'config/database.php';

echo "<h2>Kiểm tra Request #28 - Nút Nhận Yêu Cầu</h2>";

try {
    $db = getDatabaseConnection();
    
    // Get request #28 details
    $stmt = $db->prepare("SELECT id, title, status, assigned_to, user_id, created_at, updated_at FROM service_requests WHERE id = 28");
    $stmt->execute();
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($request) {
        echo "<h3>Request #28 Details:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Value</th><th>Accept Button Logic</th></tr>";
        
        foreach ($request as $key => $value) {
            $logic = '';
            
            if ($key === 'status') {
                if ($value === 'open') {
                    $logic = '✅ status === "open" → Show button';
                } else {
                    $logic = '❌ status !== "open" → Hide button';
                }
            } elseif ($key === 'assigned_to') {
                if ($value === null || $value === '') {
                    $logic = '✅ !assigned_to → Show button';
                } else {
                    $logic = '❌ assigned_to exists → Hide button';
                }
            }
            
            echo "<tr><td>{$key}</td><td>" . ($value ?: 'NULL') . "</td><td>{$logic}</td></tr>";
        }
        echo "</table>";
        
        // Check the combined logic
        echo "<h3>Logic Check:</h3>";
        $showButton = ($request['status'] === 'open') && ($request['assigned_to'] === null || $request['assigned_to'] === '');
        
        echo "<p><strong>Condition:</strong> request.status === 'open' && !request.assigned_to</p>";
        echo "<p><strong>Evaluation:</strong> ('{$request['status']}' === 'open') && (" . ($request['assigned_to'] ? 'false' : 'true') . ")</p>";
        echo "<p><strong>Result:</strong> " . ($showButton ? '✅ SHOW BUTTON' : '❌ HIDE BUTTON') . "</p>";
        
        if (!$showButton) {
            echo "<h3>🔍 Why button is hidden:</h3>";
            
            if ($request['status'] !== 'open') {
                echo "<p style='color: red;'>❌ Status is '{$request['status']}' (should be 'open')</p>";
            }
            
            if ($request['assigned_to'] !== null && $request['assigned_to'] !== '') {
                echo "<p style='color: red;'>❌ Already assigned to user ID: {$request['assigned_to']}</p>";
                
                // Check who is assigned
                $userStmt = $db->prepare("SELECT username, full_name FROM users WHERE id = ?");
                $userStmt->execute([$request['assigned_to']]);
                $assignedUser = $userStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($assignedUser) {
                    echo "<p style='color: blue;'>👤 Assigned to: {$assignedUser['full_name']} ({$assignedUser['username']})</p>";
                }
            }
            
            echo "<h3>🔧 Solutions:</h3>";
            echo "<ol>";
            
            if ($request['status'] !== 'open') {
                echo "<li><strong>Fix status:</strong> UPDATE service_requests SET status = 'open' WHERE id = 28</li>";
            }
            
            if ($request['assigned_to'] !== null && $request['assigned_to'] !== '') {
                echo "<li><strong>Clear assignment:</strong> UPDATE service_requests SET assigned_to = NULL WHERE id = 28</li>";
                echo "<li><strong>Check if already assigned:</strong> Maybe request was already accepted</li>";
            }
            
            echo "</ol>";
        } else {
            echo "<h3>✅ Button should be visible!</h3>";
            echo "<p>If button is not showing, check:</p>";
            echo "<ul>";
            echo "<li>Browser cache (Ctrl+F5)</li>";
            echo "<li>JavaScript errors in console</li>";
            echo "<li>CSS display issues</li>";
            echo "<li>Current user role (must be staff/admin)</li>";
            echo "</ul>";
        }
        
        // Check current user session
        echo "<h3>Current User Session:</h3>";
        session_start();
        if (isset($_SESSION['user_id'])) {
            echo "<p>Logged in: User ID {$_SESSION['user_id']}</p>";
            echo "<p>Role: {$_SESSION['role']}</p>";
            
            if ($_SESSION['role'] === 'user') {
                echo "<p style='color: red;'>❌ User role 'user' cannot see accept button</p>";
            } else {
                echo "<p style='color: green;'>✅ Role '{$_SESSION['role']}' can see accept button</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Not logged in</p>";
        }
        
    } else {
        echo "<p style='color: red;'>Request #28 không tồn tại</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
}
?>
