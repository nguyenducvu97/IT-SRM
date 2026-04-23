<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "🔍 Kiểm tra structure của table service_requests...\n\n";
    
    // Get table structure
    $stmt = $db->prepare("DESCRIBE service_requests");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📋 Các columns trong table service_requests:\n";
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    echo "\n🔍 Tìm column liên quan đến category...\n";
    $category_columns = array_filter($columns, function($col) {
        return stripos($col['Field'], 'categor') !== false || stripos($col['Field'], 'type') !== false;
    });
    
    if (!empty($category_columns)) {
        echo "✅ Tìm thấy columns liên quan:\n";
        foreach ($category_columns as $column) {
            echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
        }
    } else {
        echo "❌ Không tìm thấy column nào liên quan đến category\n";
    }
    
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
}
?>
