<?php
/**
 * Test pagination functionality for all pages
 */

require_once 'config/database.php';
require_once 'config/session.php';

// Start session
startSession();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die("Access denied. Admin access required.");
}

echo "<h1>Pagination Test Results</h1>";

// Test 1: Service Requests API
echo "<h2>1. Service Requests API</h2>";
$api_url = "http://localhost/it-service-request/api/service_requests.php?action=list&page=1&limit=9";
$response = file_get_contents($api_url);
$data = json_decode($response, true);

if ($data && $data['success']) {
    echo "<p>Service Requests: <strong>" . count($data['data']['requests']) . "</strong> items returned</p>";
    echo "<p>Total pages: <strong>" . $data['data']['pagination']['total_pages'] . "</strong></p>";
    echo "<p>Current page: <strong>" . $data['data']['pagination']['page'] . "</strong></p>";
    echo "<p>Items per page: <strong>" . $data['data']['pagination']['limit'] . "</strong></p>";
} else {
    echo "<p style='color: red;'>Service Requests API failed</p>";
}

// Test 2: Departments API
echo "<h2>2. Departments API</h2>";
$api_url = "http://localhost/it-service-request/api/departments.php?action=get&page=1&limit=9";
$response = file_get_contents($api_url);
$data = json_decode($response, true);

if ($data && $data['success']) {
    echo "<p>Departments: <strong>" . count($data['data']) . "</strong> items returned</p>";
    echo "<p>Total pages: <strong>" . $data['total_pages'] . "</strong></p>";
    echo "<p>Current page: <strong>" . $data['page'] . "</strong></p>";
    echo "<p>Items per page: <strong>" . $data['limit'] . "</strong></p>";
} else {
    echo "<p style='color: red;'>Departments API failed</p>";
}

// Test 3: Users API
echo "<h2>3. Users API</h2>";
$api_url = "http://localhost/it-service-request/api/users.php?page=1&limit=9";
$response = file_get_contents($api_url);
$data = json_decode($response, true);

if ($data && $data['success']) {
    echo "<p>Users: <strong>" . count($data['data']) . "</strong> items returned</p>";
    echo "<p>Total pages: <strong>" . $data['pagination']['total_pages'] . "</strong></p>";
    echo "<p>Current page: <strong>" . $data['pagination']['page'] . "</strong></p>";
    echo "<p>Items per page: <strong>" . $data['pagination']['limit'] . "</strong></p>";
} else {
    echo "<p style='color: red;'>Users API failed</p>";
}

// Test 4: Support Requests API
echo "<h2>4. Support Requests API</h2>";
$api_url = "http://localhost/it-service-request/api/support_requests.php?action=list&page=1&limit=9";
$response = file_get_contents($api_url);
$data = json_decode($response, true);

if ($data && $data['success']) {
    echo "<p>Support Requests: <strong>" . count($data['data']) . "</strong> items returned</p>";
    echo "<p>Total pages: <strong>" . $data['pagination']['pages'] . "</strong></p>";
    echo "<p>Current page: <strong>" . $data['pagination']['page'] . "</strong></p>";
    echo "<p>Items per page: <strong>" . $data['pagination']['limit'] . "</strong></p>";
} else {
    echo "<p style='color: red;'>Support Requests API failed</p>";
}

// Test 5: Reject Requests API
echo "<h2>5. Reject Requests API</h2>";
$api_url = "http://localhost/it-service-request/api/reject_requests.php?action=list&page=1&limit=9";
$response = file_get_contents($api_url);
$data = json_decode($response, true);

if ($data && $data['success']) {
    echo "<p>Reject Requests: <strong>" . count($data['data']) . "</strong> items returned</p>";
    echo "<p>Total pages: <strong>" . $data['pagination']['total_pages'] . "</strong></p>";
    echo "<p>Current page: <strong>" . $data['pagination']['page'] . "</strong></p>";
    echo "<p>Items per page: <strong>" . $data['pagination']['limit'] . "</strong></p>";
} else {
    echo "<p style='color: red;'>Reject Requests API failed</p>";
}

echo "<h2>6. Pagination Container Check</h2>";
echo "<p>Checking if all pagination containers exist in index.html...</p>";

$html_content = file_get_contents('index.html');
$containers = [
    'pagination' => 'Requests Page',
    'usersPagination' => 'Users Page', 
    'departmentsPagination' => 'Departments Page',
    'supportPagination' => 'Support Requests Page',
    'rejectPagination' => 'Reject Requests Page',
    'categoryPagination' => 'Category Requests Page'
];

foreach ($containers as $container_id => $page_name) {
    if (strpos($html_content, 'id="' . $container_id . '"') !== false) {
        echo "<p style='color: green;'>$page_name: <strong>$container_id</strong> - FOUND</p>";
    } else {
        echo "<p style='color: red;'>$page_name: <strong>$container_id</strong> - MISSING</p>";
    }
}

echo "<h2>7. Test Results Summary</h2>";
echo "<p>All APIs should return exactly 9 items per page (or fewer if less data available)</p>";
echo "<p>All pagination containers should be found in index.html</p>";
echo "<p>Pagination should always be visible, with disabled buttons when only 1 page</p>";

?>
