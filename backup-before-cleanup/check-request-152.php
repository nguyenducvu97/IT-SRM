<?php
require_once 'config/database.php';

echo "=== CHECK REQUEST #152 ===\n";

$database = new Database();
$db = $database->getConnection();

// Check request #152
$stmt = $db->prepare("SELECT id, status, assigned_to, user_id FROM service_requests WHERE id = 152");
$stmt->execute();
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if ($request) {
    echo "Request #152 found:\n";
    print_r($request);
    
    echo "\nStatus: " . $request['status'] . "\n";
    echo "Assigned to: " . ($request['assigned_to'] ?? 'NULL') . "\n";
    echo "User ID: " . $request['user_id'] . "\n";
    
    // Check if available for assignment
    $available_statuses = ['open', 'request_support'];
    if (in_array($request['status'], $available_statuses) && ($request['assigned_to'] == null || $request['assigned_to'] == 0)) {
        echo "\nRequest is AVAILABLE for assignment\n";
    } else {
        echo "\nRequest is NOT available for assignment\n";
        echo "Required status: " . implode(' OR ', $available_statuses) . "\n";
        echo "Current status: " . $request['status'] . "\n";
        echo "Assigned to: " . ($request['assigned_to'] ?? 'NULL') . "\n";
    }
} else {
    echo "Request #152 NOT found\n";
}

echo "\n=== CHECK COMPLETE ===\n";
?>
