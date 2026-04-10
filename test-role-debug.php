<?php
// Debug role-based search
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<h2>Role-Based Search Debug</h2>";

// Check current session
echo "<h3>Current Session Data:</h3>";
echo "<pre>" . json_encode($_SESSION, JSON_PRETTY_PRINT) . "</pre>";

// Test role detection
echo "<h3>Role Detection:</h3>";
echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'null') . "</p>";
echo "<p>Username: " . ($_SESSION['username'] ?? 'null') . "</p>";
echo "<p>Role: " . ($_SESSION['role'] ?? 'null') . "</p>";
echo "<p>Full Name: " . ($_SESSION['full_name'] ?? 'null') . "</p>";

// Test role logic
$user_role = $_SESSION['role'] ?? 'unknown';
echo "<h3>Role Logic Test:</h3>";
echo "<p>Current role: $user_role</p>";
echo "<p>Is admin? " . ($user_role == 'admin' ? 'YES' : 'NO') . "</p>";
echo "<p>Is staff? " . ($user_role == 'staff' ? 'YES' : 'NO') . "</p>";
echo "<p>Is user? " . ($user_role == 'user' ? 'YES' : 'NO') . "</p>";

// Test filter condition
$should_filter = ($user_role != 'admin' && $user_role != 'staff');
echo "<p>Should apply user_id filter? " . ($should_filter ? 'YES' : 'NO') . "</p>";

if ($should_filter) {
    echo "<p>User ID filter will be applied: " . $_SESSION['user_id'] . "</p>";
} else {
    echo "<p>No user_id filter - full access</p>";
}

// Test search API directly
echo "<h3>Search API Test:</h3>";

// Set up test parameters
$_GET['search'] = 'test';
$_GET['page'] = '1';
$_SERVER['REQUEST_METHOD'] = 'GET';

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db === null) {
        echo "<p style='color: red;'>Database connection failed</p>";
        exit();
    }
    
    // Simulate search logic
    $search = 'test';
    $page = 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'];
    
    echo "<p>Search parameters:</p>";
    echo "<ul>";
    echo "<li>Search: '$search'</li>";
    echo "<li>User ID: $user_id</li>";
    echo "<li>User Role: $user_role</li>";
    echo "<li>Should filter: " . ($should_filter ? 'YES' : 'NO') . "</li>";
    echo "</ul>";
    
    // Build WHERE clause
    $where_clause = "WHERE 1=1";
    $params = [];
    
    // Add user filter for non-admin/non-staff
    if ($user_role != 'admin' && $user_role != 'staff') {
        $where_clause .= " AND sr.user_id = :user_id";
        $params[':user_id'] = $user_id;
        echo "<p>Added user_id filter: sr.user_id = $user_id</p>";
    } else {
        echo "<p>No user_id filter - admin/staff access</p>";
    }
    
    // Add search condition
    if (!empty($search)) {
        $where_clause .= " AND (sr.title LIKE :search OR sr.description LIKE :search OR u.username LIKE :search OR sr.id LIKE :search)";
        $params[':search'] = '%' . $search . '%';
        echo "<p>Added search condition</p>";
    }
    
    echo "<h4>Final WHERE Clause:</h4>";
    echo "<code>" . htmlspecialchars($where_clause) . "</code>";
    
    echo "<h4>Parameters:</h4>";
    echo "<pre>" . json_encode($params, JSON_PRETTY_PRINT) . "</pre>";
    
    // Execute query
    $query = "SELECT sr.*, u.username as requester_name, c.name as category_name
              FROM service_requests sr 
              LEFT JOIN users u ON sr.user_id = u.id 
              LEFT JOIN categories c ON sr.category_id = c.id 
              $where_clause 
              ORDER BY sr.created_at DESC
              LIMIT 5";
    
    echo "<h4>Query:</h4>";
    echo "<code>" . htmlspecialchars($query) . "</code>";
    
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Results (" . count($results) . " found):</h4>";
    if (!empty($results)) {
        echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
        echo "<tr style='background-color: #f2f2f2;'><th>ID</th><th>Title</th><th>Requester</th><th>User ID</th></tr>";
        
        foreach ($results as $result) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($result['id']) . "</td>";
            echo "<td>" . htmlspecialchars($result['title']) . "</td>";
            echo "<td>" . htmlspecialchars($result['requester_name']) . "</td>";
            echo "<td>" . htmlspecialchars($result['user_id']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No results found</p>";
    }
    
} catch (Exception $e) {
    echo "<h3>Error:</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}

?>
