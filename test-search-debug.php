<?php
// Simple test to isolate the search issue
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Search Debug Test</h2>";

try {
    // Test basic functionality
    echo "<p>1. Testing basic PHP functionality...</p>";
    
    // Test GET parameter
    $search_filter = isset($_GET['search']) ? trim($_GET['search']) : '';
    echo "<p>Search parameter: '$search_filter'</p>";
    
    // Test database connection
    require_once 'config/database.php';
    echo "<p>2. Database config loaded...</p>";
    
    $database = new Database();
    echo "<p>3. Database object created...</p>";
    
    $db = $database->getConnection();
    echo "<p>4. Database connection: " . ($db ? "SUCCESS" : "FAILED") . "</p>";
    
    if ($db) {
        // Test basic query
        echo "<p>5. Testing basic query...</p>";
        $query = "SELECT COUNT(*) as count FROM service_requests";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Found " . $result['count'] . " requests</p>";
        
        // Test search query
        if (!empty($search_filter)) {
            echo "<p>6. Testing search query for '$search_filter'...</p>";
            
            $search_query = "SELECT COUNT(*) as count FROM service_requests sr 
                           LEFT JOIN users u ON sr.user_id = u.id 
                           WHERE sr.title LIKE :search OR sr.description LIKE :search OR u.username LIKE :search";
            
            $stmt = $db->prepare($search_query);
            $stmt->bindValue(':search', '%' . $search_filter . '%');
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p>Search found " . $result['count'] . " results</p>";
            
            // Test the full query structure
            echo "<p>7. Testing full query structure...</p>";
            $where_clause = "WHERE 1=1 AND (sr.title LIKE :search OR sr.description LIKE :search OR u.username LIKE :search)";
            $params = [':search' => '%' . $search_filter . '%'];
            
            $full_query = "SELECT sr.*, u.username as requester_name, c.name as category_name
                          FROM service_requests sr 
                          LEFT JOIN users u ON sr.user_id = u.id 
                          LEFT JOIN categories c ON sr.category_id = c.id 
                          $where_clause 
                          ORDER BY sr.created_at DESC
                          LIMIT 10";
            
            echo "<p>Full query: " . htmlspecialchars($full_query) . "</p>";
            echo "<p>Params: " . json_encode($params) . "</p>";
            
            $stmt = $db->prepare($full_query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<p>Full query executed, found " . count($results) . " results</p>";
        }
    }
    
    // Test API inclusion
    echo "<p>8. Testing API inclusion...</p>";
    
    // Set up environment
    $_SESSION['user_id'] = 2;
    $_SESSION['username'] = 'staff1';
    $_SESSION['role'] = 'staff';
    $_SESSION['full_name'] = 'John Smith';
    
    $_GET['action'] = 'list';
    $_GET['page'] = '1';
    $_GET['search'] = $search_filter;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    echo "<p>Environment set up, including API...</p>";
    
    ob_start();
    include 'api/service_requests.php';
    $output = ob_get_clean();
    
    echo "<h3>API Output:</h3>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    
} catch (Exception $e) {
    echo "<h3>Error:</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
} catch (Error $e) {
    echo "<h3>Fatal Error:</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
}

?>
