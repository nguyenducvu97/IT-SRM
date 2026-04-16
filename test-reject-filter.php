<?php
// Test file to verify reject requests filter functionality
require_once 'config/database.php';

echo "<h2>Reject Requests Filter Test</h2>";

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    die("Database connection failed");
}

// Test different filter values
$filters = ['all', 'pending', 'approved', 'rejected'];

echo "<h3>Testing API with different filters:</h3>";

foreach ($filters as $filter) {
    echo "<h4>Filter: '$filter'</h4>";
    
    // Build API URL
    $url = "http://localhost/it-service-request/api/reject_requests.php?action=list";
    if ($filter !== 'all') {
        $url .= "&status=$filter";
    }
    $url .= "&page=1&limit=9";
    
    echo "<p>API URL: <code>$url</code></p>";
    
    // Make API call
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Cookie: ' . ($_SERVER['HTTP_COOKIE'] ?? '')
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    
    if ($response) {
        $data = json_decode($response, true);
        
        if ($data && $data['success']) {
            $total = $data['data']['total'] ?? 0;
            $count = count($data['data']['requests'] ?? []);
            
            echo "<p style='color: green;'>✅ SUCCESS: Total=$total, Showing=$count</p>";
            
            if ($count > 0) {
                echo "<table border='1' cellpadding='5' style='width: 100%; font-size: 12px;'>";
                echo "<tr><th>ID</th><th>Service Request</th><th>Status</th><th>Created</th></tr>";
                
                foreach ($data['data']['requests'] as $request) {
                    echo "<tr>";
                    echo "<td>{$request['id']}</td>";
                    echo "<td>{$request['service_request_title']}</td>";
                    echo "<td><span class='badge status-{$request['status']}'>{$request['status']}</span></td>";
                    echo "<td>{$request['created_at']}</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            }
        } else {
            echo "<p style='color: red;'>❌ API ERROR: " . ($data['message'] ?? 'Unknown error') . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ FAILED TO CALL API</p>";
    }
    
    echo "<hr>";
}

// Database verification
echo "<h3>Database Verification:</h3>";

$counts = [];
foreach (['pending', 'approved', 'rejected'] as $status) {
    $query = "SELECT COUNT(*) as count FROM reject_requests WHERE status = :status";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->execute();
    $counts[$status] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

$total_query = "SELECT COUNT(*) as count FROM reject_requests";
$total_stmt = $db->query($total_query);
$total_count = $total_stmt->fetch(PDO::FETCH_ASSOC)['count'];

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Status</th><th>Database Count</th><th>Expected API Count</th></tr>";
echo "<tr><td>pending</td><td>{$counts['pending']}</td><td>Should show when filter='pending'</td></tr>";
echo "<tr><td>approved</td><td>{$counts['approved']}</td><td>Should show when filter='approved'</td></tr>";
echo "<tr><td>rejected</td><td>{$counts['rejected']}</td><td>Should show when filter='rejected'</td></tr>";
echo "<tr><th>ALL</th><th>$total_count</th><td>Should show when filter='all' (default)</td></tr>";
echo "</table>";

echo "<h3>Frontend Test Instructions:</h3>";
echo "<ol>";
echo "<li>Open the application and navigate to 'Yêu cầu từ chối' page</li>";
echo "<li>Check that 'Tất cả' is selected by default in the dropdown</li>";
echo "<li>Verify that all reject requests are displayed</li>";
echo "<li>Change filter to 'Chờ duyệt' - should only show pending requests</li>";
echo "<li>Change filter to 'Đã duyệt' - should only show approved requests</li>";
echo "<li>Change filter to 'Đã từ chối' - should only show rejected requests</li>";
echo "<li>Check browser console for any errors</li>";
echo "</ol>";

echo "<h3>Expected Behavior:</h3>";
echo "<ul>";
echo "<li>✅ Default filter should be 'Tất cả' (all)</li>";
echo "<li>✅ API should return all requests when status='all' or no status parameter</li>";
echo "<li>✅ API should filter by status when specific status is provided</li>";
echo "<li>✅ Frontend should display correct number of results</li>";
echo "</ul>";

echo "<p><strong>If the filter is still not working correctly, check the browser console for JavaScript errors and verify the API responses above.</strong></p>";
?>
