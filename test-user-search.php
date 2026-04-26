<?php
// Test user search functionality
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';

startSession();

// Simulate admin user for testing
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

$database = new Database();
$db = $database->getConnection();

$search = 'admin'; // Test search for admin user
$where_clause = "WHERE 1=1";
$params = [];

// Add search condition with unique placeholders
if (!empty($search)) {
    $where_clause .= " AND (username LIKE :search_username OR email LIKE :search_email OR full_name LIKE :search_full_name)";
    $params[':search_username'] = "%$search%";
    $params[':search_email'] = "%$search%";
    $params[':search_full_name'] = "%$search%";
}

echo "<h1>Test User Search for '$search'</h1>";
echo "<p><strong>Search query:</strong> " . $where_clause . "</p>";
echo "<p><strong>Search parameters:</strong></p>";
echo "<pre>" . print_r($params, true) . "</pre>";

// Main query
$query = "SELECT id, username, email, full_name, role, created_at 
          FROM users 
          $where_clause 
          ORDER BY created_at DESC
          LIMIT 10";

echo "<p><strong>Full SQL:</strong></p>";
echo "<pre>" . $query . "</pre>";

$stmt = $db->prepare($query);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Results Found: " . count($users) . "</h2>";

if (count($users) > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Full Name</th><th>Role</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . htmlspecialchars($user['username']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['role']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No results found.</p>";
}

// Test API directly
echo "<h2>Test API Directly</h2>";
$_GET['search'] = $search;
$_GET['page'] = '1';
$_GET['limit'] = '10';

include __DIR__ . '/api/users.php';
?>
