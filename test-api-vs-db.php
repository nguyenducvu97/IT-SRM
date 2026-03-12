<?php
// Test API directly to see what it returns
session_start();
$_SESSION['user_id'] = 1; // Admin
$_SESSION['role'] = 'admin';

echo "=== Testing API for Request #34 ===\n";

$ch = curl_init("http://localhost/it-service-request/api/service_requests.php?action=get&id=34");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n\n";

if ($httpCode == 200) {
    $data = json_decode($response, true);
    if ($data && $data['success']) {
        $comments = $data['data']['comments'] ?? [];
        echo "✅ API Success!\n";
        echo "Request ID: {$data['data']['id']}\n";
        echo "Comments from API: " . count($comments) . "\n\n";
        
        echo "API Comments:\n";
        foreach ($comments as $comment) {
            echo "  ID: {$comment['id']}, Author: {$comment['user_name']}, Text: {$comment['comment']}\n";
        }
    } else {
        echo "❌ API Error: " . ($data['message'] ?? 'Unknown') . "\n";
        echo "Full Response: $response\n";
    }
} else {
    echo "❌ HTTP Error: $httpCode\n";
    echo "Response: $response\n";
}

echo "\n=== Database vs API Comparison ===\n";

// Check database directly
$pdo = new PDO("mysql:host=localhost;dbname=it_service_request", "root", "");
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE service_request_id = 34");
$stmt->execute();
$dbCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

echo "Database comments count: $dbCount\n";
echo "API comments count: " . count($comments ?? []) . "\n";

if ($dbCount != count($comments ?? [])) {
    echo "❌ MISMATCH! API and Database have different counts!\n";
    echo "This means API is not reading from the same database.\n";
} else {
    echo "✅ Counts match - API is reading from correct database\n";
}
?>
