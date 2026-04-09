<?php
require_once 'config/database.php';
$db = getDatabaseConnection();

// Check attachments table structure
echo "Attachments table structure:\n";
try {
    $stmt = $db->prepare("DESCRIBE attachments");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Check if there are any attachments
echo "\nTotal attachments: ";
try {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM attachments");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo $result['count'] . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Check recent service_requests to see what columns they have
echo "\nService requests table structure (relevant columns):\n";
try {
    $stmt = $db->prepare("DESCRIBE service_requests");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        if (strpos($column['Field'], 'id') !== false || strpos($column['Field'], 'request') !== false) {
            echo "- {$column['Field']} ({$column['Type']})\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
