<?php
// Test search for "130"
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';

startSession();

// Simulate admin user for testing
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

$database = new Database();
$db = $database->getConnection();

$search = '130';
$where_clause = "WHERE 1=1";
$params = [];

// Add search condition
if (!empty($search)) {
    $where_clause .= " AND (sr.title LIKE :search OR sr.description LIKE :search OR u.username LIKE :search OR sr.id LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

echo "<h1>Test Search for '130'</h1>";
echo "<p><strong>Search query:</strong> " . $where_clause . "</p>";
echo "<p><strong>Search parameter:</strong> " . $params[':search'] . "</p>";

// Main query
$query = "SELECT sr.*, u.username as requester_name, c.name as category_name
          FROM service_requests sr 
          LEFT JOIN users u ON sr.user_id = u.id 
          LEFT JOIN categories c ON sr.category_id = c.id 
          $where_clause 
          ORDER BY sr.created_at DESC
          LIMIT 10";

echo "<p><strong>Full SQL:</strong></p>";
echo "<pre>" . $query . "</pre>";

$stmt = $db->prepare($query);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Results Found: " . count($requests) . "</h2>";

if (count($requests) > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Title</th><th>Description</th><th>Username</th></tr>";
    foreach ($requests as $request) {
        echo "<tr>";
        echo "<td>" . $request['id'] . "</td>";
        echo "<td>" . htmlspecialchars($request['title']) . "</td>";
        echo "<td>" . htmlspecialchars($request['description']) . "</td>";
        echo "<td>" . htmlspecialchars($request['requester_name']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No results found.</p>";
}

// Also check if request ID 130 exists
echo "<h2>Check if ID 130 exists in database</h2>";
$check_query = "SELECT * FROM service_requests WHERE id = 130";
$check_stmt = $db->prepare($check_query);
$check_stmt->execute();
$check_result = $check_stmt->fetch(PDO::FETCH_ASSOC);

if ($check_result) {
    echo "<p>✅ Request ID 130 EXISTS:</p>";
    echo "<pre>" . print_r($check_result, true) . "</pre>";
} else {
    echo "<p>❌ Request ID 130 DOES NOT EXIST</p>";
}

// Check if any requests contain "130" in title or description
echo "<h2>Check if any requests contain '130' in title or description</h2>";
$check_130_query = "SELECT id, title, description FROM service_requests WHERE title LIKE '%130%' OR description LIKE '%130%'";
$check_130_stmt = $db->prepare($check_130_query);
$check_130_stmt->execute();
$check_130_results = $check_130_stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($check_130_results) > 0) {
    echo "<p>✅ Found " . count($check_130_results) . " requests containing '130':</p>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Title</th><th>Description</th></tr>";
    foreach ($check_130_results as $request) {
        echo "<tr>";
        echo "<td>" . $request['id'] . "</td>";
        echo "<td>" . htmlspecialchars($request['title']) . "</td>";
        echo "<td>" . htmlspecialchars($request['description']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ No requests contain '130' in title or description</p>";
}

// Check if any usernames contain "130"
echo "<h2>Check if any usernames contain '130'</h2>";
$check_user_query = "SELECT id, username FROM users WHERE username LIKE '%130%'";
$check_user_stmt = $db->prepare($check_user_query);
$check_user_stmt->execute();
$check_user_results = $check_user_stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($check_user_results) > 0) {
    echo "<p>✅ Found " . count($check_user_results) . " users containing '130':</p>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Username</th></tr>";
    foreach ($check_user_results as $user) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . htmlspecialchars($user['username']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ No users contain '130'</p>";
}
?>
