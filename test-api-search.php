<?php
// Test API search directly
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simulate API environment
session_start();
$_SESSION['user_id'] = 2;
$_SESSION['username'] = 'staff1';
$_SESSION['role'] = 'staff';
$_SESSION['full_name'] = 'John Smith';

$_GET['action'] = 'list';
$_GET['page'] = '1';
$_GET['search'] = 'test';
$_SERVER['REQUEST_METHOD'] = 'GET';

// Include required files
require_once 'config/database.php';

echo "<h2>API Search Test</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db === null) {
        throw new Exception("Database connection failed");
    }
    
    echo "<p>Database connection: SUCCESS</p>";
    
    // Set up variables like in API
    $page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
    $has_filters = isset($_GET['search']);
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    $search_filter = isset($_GET['search']) ? trim($_GET['search']) : '';
    $user_role = $_SESSION['role'];
    $user_id = $_SESSION['user_id'];
    
    echo "<p>Variables set up:</p>";
    echo "<p>- Search filter: '$search_filter'</p>";
    echo "<p>- Has filters: " . ($has_filters ? 'true' : 'false') . "</p>";
    echo "<p>- User role: $user_role</p>";
    echo "<p>- User ID: $user_id</p>";
    
    // Build WHERE clause
    $where_clause = "WHERE 1=1";
    $params = [];
    
    if ($user_role != 'admin' && $user_role != 'staff') {
        $where_clause .= " AND sr.user_id = :user_id";
        $params[':user_id'] = $user_id;
    }
    
    // Add search condition
    if (!empty($search_filter)) {
        $where_clause .= " AND (sr.title LIKE :search OR sr.description LIKE :search OR u.username LIKE :search)";
        $params[':search'] = '%' . $search_filter . '%';
    }
    
    echo "<h3>WHERE Clause:</h3>";
    echo "<p>" . htmlspecialchars($where_clause) . "</p>";
    
    echo "<h3>Parameters:</h3>";
    echo "<pre>" . json_encode($params, JSON_PRETTY_PRINT) . "</pre>";
    
    // Build the query
    $query = "SELECT sr.*, u.username as requester_name, c.name as category_name
              FROM service_requests sr 
              LEFT JOIN users u ON sr.user_id = u.id 
              LEFT JOIN categories c ON sr.category_id = c.id 
              $where_clause 
              ORDER BY sr.created_at DESC
              LIMIT 10";
    
    echo "<h3>Full Query:</h3>";
    echo "<p>" . htmlspecialchars($query) . "</p>";
    
    // Execute query
    $stmt = $db->prepare($query);
    
    echo "<h3>Binding Parameters:</h3>";
    foreach ($params as $key => $value) {
        echo "<p>Binding $key = " . htmlspecialchars($value) . "</p>";
        $stmt->bindValue($key, $value);
    }
    
    echo "<p>Executing query...</p>";
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Results:</h3>";
    echo "<p>Found " . count($results) . " results</p>";
    
    if (!empty($results)) {
        echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
        echo "<tr style='background-color: #f2f2f2;'><th>ID</th><th>Title</th><th>Description</th><th>Username</th></tr>";
        
        foreach ($results as $result) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($result['id']) . "</td>";
            echo "<td>" . htmlspecialchars($result['title']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($result['description'], 0, 50)) . "...</td>";
            echo "<td>" . htmlspecialchars($result['requester_name']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<p style='color: green;'>API search test successful!</p>";
    
} catch (Exception $e) {
    echo "<h3>Error:</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
}

?>
