<?php
// Check current database and comments
$pdo = new PDO("mysql:host=localhost;dbname=it_service_request", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== Database Check ===\n";
echo "Database: it_service_request\n";
echo "Host: localhost\n\n";

echo "=== All Comments in Database ===\n";
$stmt = $pdo->query("SELECT c.id, c.service_request_id, c.comment, c.user_id, c.created_at, u.username 
                    FROM comments c 
                    LEFT JOIN users u ON c.user_id = u.id 
                    ORDER BY c.created_at DESC LIMIT 10");

$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($comments)) {
    echo "No comments found in database!\n";
} else {
    echo "Found " . count($comments) . " comments:\n\n";
    foreach ($comments as $comment) {
        echo "ID: {$comment['id']}, Request ID: {$comment['service_request_id']}, User: {$comment['username']}\n";
        echo "Comment: {$comment['comment']}\n";
        echo "Created: {$comment['created_at']}\n\n";
    }
}

echo "=== Comments by Request ID ===\n";
$stmt = $pdo->query("SELECT service_request_id, COUNT(*) as count 
                    FROM comments 
                    GROUP BY service_request_id 
                    ORDER BY service_request_id");

$byRequest = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($byRequest as $req) {
    echo "Request #{$req['service_request_id']}: {$req['count']} comments\n";
}

echo "\n=== Check Complete ===\n";
?>
