<?php
require_once 'config/database.php';

echo "=== FIND OPEN REQUEST ===\n";

$database = new Database();
$db = $database->getConnection();

// Find open requests
$stmt = $db->prepare("SELECT id, title, status, assigned_to FROM service_requests 
                      WHERE status = 'open' AND (assigned_to IS NULL OR assigned_to = 0) 
                      ORDER BY id DESC LIMIT 5");
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($requests) > 0) {
    echo "Found " . count($requests) . " open requests:\n";
    foreach ($requests as $request) {
        echo "- ID: {$request['id']}, Title: {$request['title']}, Status: {$request['status']}, Assigned to: " . ($request['assigned_to'] ?? 'NULL') . "\n";
    }
    
    // Use first open request
    $test_request_id = $requests[0]['id'];
    echo "\nUsing request #$test_request_id for test\n";
    
    // Update test file
    $test_content = "<?php
// Test accept request with open request
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo \"=== TEST ACCEPT REQUEST (OPEN) ===\n\";

// Simulate staff session
\$_SESSION['user_id'] = 2;
\$_SESSION['username'] = 'staff';
\$_SESSION['full_name'] = 'Staff User';
\$_SESSION['role'] = 'staff';

// Simulate POST request
\$_SERVER['REQUEST_METHOD'] = 'POST';
\$_SERVER['CONTENT_TYPE'] = 'multipart/form-data; boundary=----WebKitFormBoundary';

\$_POST['action'] = 'accept_request';
\$_POST['request_id'] = $test_request_id;

echo \"POST data:\n\";
print_r(\$_POST);

echo \"\nTesting accept request API...\n\";

try {
    ob_start();
    require 'api/service_requests.php';
    \$output = ob_get_clean();
    
    echo \"API Output: \$output\n\";
    
    \$response_data = json_decode(\$output, true);
    if (\$response_data) {
        echo \"\nParsed JSON response:\n\";
        print_r(\$response_data);
    } else {
        echo \"\nFailed to parse JSON response\n\";
    }
    
} catch (Exception \$e) {
    echo \"Exception: \" . \$e->getMessage() . \"\n\";
}

echo \"\n=== TEST COMPLETE ===\n\";
?>";
    
    file_put_contents('test-accept-request-open.php', $test_content);
    echo "\nCreated test-accept-request-open.php\n";
    
} else {
    echo "No open requests found\n";
    
    // Find any request
    $stmt = $db->prepare("SELECT id, title, status, assigned_to FROM service_requests ORDER BY id DESC LIMIT 5");
    $stmt->execute();
    $all_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nLast 5 requests:\n";
    foreach ($all_requests as $request) {
        echo "- ID: {$request['id']}, Title: {$request['title']}, Status: {$request['status']}, Assigned to: " . ($request['assigned_to'] ?? 'NULL') . "\n";
    }
}

echo "\n=== FIND COMPLETE ===\n";
?>
