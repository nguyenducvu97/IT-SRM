<?php
// Emergency security investigation script
// Check request ID 27 ownership and access control

require_once 'config/database.php';

echo "<h2>SECURITY INVESTIGATION - Request ID 27</h2>\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db === null) {
        echo "Database connection failed!\n";
        exit;
    }
    
    // 1. Check request ID 27 details
    echo "<h3>Request ID 27 Details:</h3>\n";
    $query = "SELECT sr.*, u.username, u.full_name, u.email 
              FROM service_requests sr 
              LEFT JOIN users u ON sr.user_id = u.id 
              WHERE sr.id = 27";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($request) {
        echo "Request ID: " . $request['id'] . "<br>\n";
        echo "Title: " . $request['title'] . "<br>\n";
        echo "Owner ID: " . $request['user_id'] . "<br>\n";
        echo "Owner Name: " . $request['full_name'] . " (" . $request['username'] . ")<br>\n";
        echo "Owner Email: " . $request['email'] . "<br>\n";
        echo "Assigned To: " . ($request['assigned_to'] ?: 'None') . "<br>\n";
        echo "Status: " . $request['status'] . "<br>\n";
        echo "Created: " . $request['created_at'] . "<br>\n";
    } else {
        echo "Request ID 27 not found in database!<br>\n";
    }
    
    // 2. Check user Nguyễn văn Tín details
    echo "<h3>User Nguyễn văn Tín Details:</h3>\n";
    $query = "SELECT id, username, full_name, email, role 
              FROM users 
              WHERE full_name LIKE '%Nguyễn văn Tín%' OR username LIKE '%tin%'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        echo "User ID: " . $user['id'] . "<br>\n";
        echo "Name: " . $user['full_name'] . "<br>\n";
        echo "Username: " . $user['username'] . "<br>\n";
        echo "Email: " . $user['email'] . "<br>\n";
        echo "Role: " . $user['role'] . "<br>\n";
        echo "---<br>\n";
    }
    
    // 3. Check if Nguyễn văn Tín is assigned to request 27
    if ($request && !empty($users)) {
        echo "<h3>Assignment Check:</h3>\n";
        foreach ($users as $user) {
            if ($request['assigned_to'] == $user['id']) {
                echo "⚠️ SECURITY ISSUE: User " . $user['full_name'] . " is ASSIGNED to request 27<br>\n";
                echo "This would explain access through assigned_to filter<br>\n";
            }
        }
    }
    
    // 4. Check access logs if available
    echo "<h3>Recent Access Patterns:</h3>\n";
    echo "Note: Check application logs for recent access to request ID 27<br>\n";
    
    // 5. Verify current access control logic
    echo "<h3>Current Access Control Logic:</h3>\n";
    echo "service_requests.php line 77-80:<br>\n";
    echo "if (\$user_role != 'admin' && \$user_role != 'staff') {<br>\n";
    echo "    \$where_clause .= \" AND sr.user_id = :user_id\";<br>\n";
    echo "    \$params[':user_id'] = \$user_id;<br>\n";
    echo "}<br>\n";
    echo "<br>\n";
    echo "service_requests.php line 191-194:<br>\n";
    echo "if (\$user_role != 'admin' && \$user_role != 'staff' && <br>\n";
    echo "    \$request['user_id'] != \$user_id) {<br>\n";
    echo "    serviceJsonResponse(false, \"Access denied\");<br>\n";
    echo "}<br>\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>\n";
}
?>
