<?php
require_once 'config/database.php';
$db = getDatabaseConnection();

// Check latest requests
$query = "SELECT id, title, created_at FROM service_requests 
         WHERE user_id = 4 AND title LIKE '%Test%'
         ORDER BY created_at DESC 
         LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Recent test requests:\n";
foreach ($results as $row) {
    echo "ID: {$row['id']} - {$row['title']} - {$row['created_at']}\n";
}

// Count total test requests today
$count_query = "SELECT COUNT(*) as total FROM service_requests 
                 WHERE user_id = 4 AND title LIKE '%Test%' 
                 AND DATE(created_at) = CURDATE()";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute();
$count = $count_stmt->fetch(PDO::FETCH_ASSOC);

echo "\nTotal test requests today: {$count['total']}\n";

// Check if quick fix requests are created quickly
$quick_fix_query = "SELECT id, title, created_at FROM service_requests 
                     WHERE user_id = 4 
                     AND (title LIKE '%Quick Fix%' OR title LIKE '%Performance%')
                     ORDER BY created_at DESC 
                     LIMIT 3";
$quick_fix_stmt = $db->prepare($quick_fix_query);
$quick_fix_stmt->execute();
$quick_fix_results = $quick_fix_stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\nQuick fix requests:\n";
foreach ($quick_fix_results as $row) {
    echo "ID: {$row['id']} - {$row['title']} - {$row['created_at']}\n";
}
?>
