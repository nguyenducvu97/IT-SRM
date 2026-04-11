<?php
require_once 'config/database.php';

echo "=== CHECK NOTIFICATION TRIGGERS ===" . PHP_EOL;

try {
    $pdo = getDatabaseConnection();
    
    // Check for triggers on notifications table
    $trigger_query = "
        SELECT TRIGGER_NAME, EVENT_MANIPULATION, ACTION_TIMING, ACTION_ORIENTATION, ACTION_CONDITION, ACTION_STATEMENT
        FROM INFORMATION_SCHEMA.TRIGGERS 
        WHERE EVENT_OBJECT_TABLE = 'notifications'
        ORDER BY TRIGGER_NAME
    ";
    
    $stmt = $pdo->query($trigger_query);
    $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Triggers on notifications table: " . count($triggers) . PHP_EOL;
    foreach ($triggers as $trigger) {
        echo "- {$trigger['TRIGGER_NAME']}: {$trigger['ACTION_TIMING']} {$trigger['ACTION_ORIENTATION']} {$trigger['ACTION_STATEMENT']}" . PHP_EOL;
    }
    
    echo PHP_EOL;
    
    // Check for duplicate prevention
    $duplicate_query = "
        SELECT CONSTRAINT_NAME, COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE TABLE_NAME = 'notifications'
        AND REFERENCED_TABLE_NAME IS NULL
    ";
    
    $stmt = $pdo->query($duplicate_query);
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Unique constraints on notifications table: " . count($constraints) . PHP_EOL;
    foreach ($constraints as $constraint) {
        echo "- {$constraint['CONSTRAINT_NAME']}: {$constraint['COLUMN_NAME']}" . PHP_EOL;
    }
    
    echo PHP_EOL;
    
    // Check table structure
    $table_query = "
        SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'notifications'
        ORDER BY ORDINAL_POSITION
    ";
    
    $stmt = $pdo->query($table_query);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Notifications table structure:" . PHP_EOL;
    foreach ($columns as $column) {
        echo "- {$column['COLUMN_NAME']}: {$column['IS_NULLABLE']}, Default: {$column['COLUMN_DEFAULT']}, Extra: {$column['EXTRA']}" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== CHECK COMPLETE ===" . PHP_EOL;
?>
