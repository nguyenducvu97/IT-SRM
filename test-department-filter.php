<?php
// Test department filter functionality
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';

startSession();

// Simulate admin user for testing
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

$database = new Database();
$db = $database->getConnection();

// Test different departments
$test_departments = ['BP. IT', 'Phòng Kế toán', 'Phòng Marketing'];

echo "<h1>Test Department Filter</h1>";

foreach ($test_departments as $dept) {
    echo "<h2>Testing Department: '$dept'</h2>";
    
    // Test API call
    $_GET['department'] = $dept;
    $_GET['page'] = '1';
    $_GET['limit'] = '10';
    
    echo "<h3>API Response:</h3>";
    
    // Test API call with proper session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost/it-service-request/api/users.php?department=" . urlencode($dept) . "&page=1&limit=10");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    $api_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p><strong>HTTP Status:</strong> $http_code</p>";
    echo "<pre>" . htmlspecialchars($api_response) . "</pre>";
    
    // Test direct database query
    echo "<h3>Direct Database Query:</h3>";
    $query = "SELECT id, username, full_name, department, role FROM users WHERE department = :department ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':department', $dept);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Found " . count($users) . " users</strong></p>";
    
    if (count($users) > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Department</th><th>Role</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['department']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No users found in this department.</p>";
    }
    
    echo "<hr>";
}

// Test all departments
echo "<h2>All Departments in Database:</h2>";
$query = "SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department";
$stmt = $db->prepare($query);
$stmt->execute();
$departments = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<ul>";
foreach ($departments as $dept) {
    $count_query = "SELECT COUNT(*) as count FROM users WHERE department = :department";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->bindParam(':department', $dept);
    $count_stmt->execute();
    $count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<li><strong>" . htmlspecialchars($dept) . "</strong>: $count users</li>";
}
echo "</ul>";
?>
