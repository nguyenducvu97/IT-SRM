<?php
// Check feedback data for requests
require_once 'config/database.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo '=== CHECKING FEEDBACK DATA FOR REQUEST #8 ===' . PHP_EOL;
    
    // Check service_requests table for feedback columns
    $stmt = $pdo->prepare('SELECT id, status, feedback_rating, feedback_text, feedback_created_at FROM service_requests WHERE id = ?');
    $stmt->execute([8]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($request) {
        echo 'Request found:' . PHP_EOL;
        echo '  ID: ' . $request['id'] . PHP_EOL;
        echo '  Status: ' . $request['status'] . PHP_EOL;
        echo '  Feedback Rating: ' . ($request['feedback_rating'] ?? 'NULL') . PHP_EOL;
        echo '  Feedback Text: ' . ($request['feedback_text'] ?? 'NULL') . PHP_EOL;
        echo '  Feedback Created At: ' . ($request['feedback_created_at'] ?? 'NULL') . PHP_EOL;
        
        if ($request['feedback_rating'] || $request['feedback_text']) {
            echo '✅ This request HAS feedback data - Admin should see it!' . PHP_EOL;
        } else {
            echo '❌ This request has NO feedback data' . PHP_EOL;
        }
    } else {
        echo '❌ Request #8 not found' . PHP_EOL;
    }
    
    echo PHP_EOL . '=== CHECKING ALL REQUESTS WITH FEEDBACK ===' . PHP_EOL;
    
    // Check all requests with feedback data
    $stmt = $pdo->prepare('SELECT id, title, status, feedback_rating, feedback_text FROM service_requests WHERE feedback_rating IS NOT NULL OR feedback_text IS NOT NULL');
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($requests) > 0) {
        echo 'Found ' . count($requests) . ' requests with feedback:' . PHP_EOL;
        foreach ($requests as $req) {
            echo '  Request #' . $req['id'] . ' - ' . $req['title'] . ' (Status: ' . $req['status'] . ')' . PHP_EOL;
            echo '    Rating: ' . ($req['feedback_rating'] ?? 'NULL') . PHP_EOL;
            echo '    Text: ' . (substr($req['feedback_text'] ?? '', 0, 50) . '...') . PHP_EOL;
        }
    } else {
        echo '❌ No requests found with feedback data' . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>
