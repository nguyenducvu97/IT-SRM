<?php
// Test file to verify reject requests pagination functionality
require_once 'config/database.php';

echo "<h2>Reject Requests Pagination Test</h2>";

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    die("Database connection failed");
}

// Test pagination parameters
$limit = 9;
$total_query = "SELECT COUNT(*) as total FROM reject_requests";
$total_stmt = $db->query($total_query);
$total = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total / $limit);

echo "<h3>Database Pagination Info:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Total Records</th><th>Limit</th><th>Total Pages</th></tr>";
echo "<tr><td>$total</td><td>$limit</td><td>$total_pages</td></tr>";
echo "</table>";

// Test each page
echo "<h3>Testing Each Page:</h3>";

for ($page = 1; $page <= $total_pages; $page++) {
    $offset = ($page - 1) * $limit;
    
    echo "<h4>Page $page (OFFSET: $offset, LIMIT: $limit)</h4>";
    
    $query = "SELECT id, status, created_at 
              FROM reject_requests 
              ORDER BY created_at DESC 
              LIMIT $limit OFFSET $offset";
    
    $stmt = $db->query($query);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found: " . count($requests) . " requests</p>";
    
    if (!empty($requests)) {
        echo "<table border='1' cellpadding='5' style='width: 100%; font-size: 12px;'>";
        echo "<tr><th>ID</th><th>Status</th><th>Created At</th></tr>";
        
        foreach ($requests as $request) {
            echo "<tr>";
            echo "<td>{$request['id']}</td>";
            echo "<td><span class='badge status-{$request['status']}'>{$request['status']}</span></td>";
            echo "<td>{$request['created_at']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    echo "<hr>";
}

// Test API pagination
echo "<h3>API Pagination Test:</h3>";

for ($page = 1; $page <= $total_pages; $page++) {
    echo "<h4>API Page $page:</h4>";
    
    $url = "http://localhost/it-service-request/api/reject_requests.php?action=list&page=$page&limit=9";
    echo "<p>URL: <code>$url</code></p>";
    
    // This would require proper session to work
    echo "<p><em>Note: API call requires admin/staff authentication</em></p>";
    
    // Simulate expected API response structure
    $offset = ($page - 1) * $limit;
    $query = "SELECT id, status, created_at 
              FROM reject_requests 
              ORDER BY created_at DESC 
              LIMIT $limit OFFSET $offset";
    
    $stmt = $db->query($query);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $api_response = [
        'success' => true,
        'message' => 'Lấy danh sách yêu cầu từ chối thành công',
        'data' => [
            'reject_requests' => $requests,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => $total_pages
            ]
        ]
    ];
    
    echo "<p>Expected API Response:</p>";
    echo "<pre>" . json_encode($api_response, JSON_PRETTY_PRINT) . "</pre>";
    
    echo "<hr>";
}

echo "<h3>Frontend Pagination Test:</h3>";
echo "<p>To test pagination in the frontend:</p>";
echo "<ol>";
echo "<li>Login as admin or staff user</li>";
echo "<li>Navigate to 'Yêu cầu từ chối' page</li>";
echo "<li>Check that pagination buttons appear at bottom</li>";
echo "<li>Click 'Next' or page numbers to navigate</li>";
echo "<li>Verify different requests appear on each page</li>";
echo "</ol>";

echo "<h3>Expected Pagination Structure:</h3>";
echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
echo "<button disabled>Previous</button> ";
for ($i = 1; $i <= $total_pages; $i++) {
    $active = $i == 1 ? 'style="background: #007bff; color: white;"' : '';
    echo "<button onclick='app.loadRejectRequests($i)' $active>$i</button> ";
}
echo "<button>Next</button>";
echo "</div>";

echo "<h3>Key Items to Verify:</h3>";
echo "<ul>";
echo "<li>✅ Page 1: Should show IDs 110-98 (9 items, all pending)</li>";
echo "<li>✅ Page 2: Should show IDs 97-76 (6 items, including approved/rejected)</li>";
echo "<li>✅ Pagination buttons should be clickable</li>";
echo "<li>✅ Current page should be highlighted</li>";
echo "<li>✅ Previous/Next buttons should work correctly</li>";
echo "</ul>";

echo "<p><strong>With pagination working, you'll be able to see the approved (ID: 97) and rejected (ID: 76) requests on page 2!</strong></p>";
?>
