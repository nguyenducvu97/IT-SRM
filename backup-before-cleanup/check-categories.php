<?php
require_once 'config/database.php';

echo "🔧 Kiểm tra table categories\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if categories table exists
    $stmt = $db->prepare("SHOW TABLES LIKE 'categories'");
    $stmt->execute();
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables found: " . print_r($tables, true) . "\n";
    
    if (in_array('categories', $tables)) {
        echo "✅ Categories table exists\n";
        
        // Get all categories
        $stmt = $db->prepare("SELECT id, name FROM categories ORDER BY id");
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Available categories:\n";
        foreach ($categories as $category) {
            echo "- ID {$category['id']}: {$category['name']}\n";
        }
        
        // Check if category_id = 1 exists
        $category_1_exists = false;
        foreach ($categories as $category) {
            if ($category['id'] == 1) {
                $category_1_exists = true;
                break;
            }
        }
        
        if ($category_1_exists) {
            echo "✅ Category ID 1 exists: {$categories[0]['name']}\n";
        } else {
            echo "❌ Category ID 1 does not exist. Creating...\n";
            
            // Insert category ID 1
            $insert = $db->prepare("INSERT INTO categories (id, name) VALUES (?, ?)");
            $result = $insert->execute([1, 'General']);
            
            if ($result) {
                echo "✅ Created category ID 1: General\n";
            } else {
                echo "❌ Failed to create category ID 1\n";
            }
        }
        
    } else {
        echo "❌ Categories table does not exist. Creating...\n";
        
        // Create categories table
        $create = $db->prepare("CREATE TABLE categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL
        )");
        
        $result = $create->execute();
        if ($result) {
            echo "✅ Created categories table\n";
            
            // Insert default categories
            $categories = [
                [1, 'Hardware'],
                [2, 'Software'],
                [3, 'Network'],
                [4, 'Other']
            ];
            
            foreach ($categories as $id => $name) {
                $insert = $db->prepare("INSERT INTO categories (id, name) VALUES (?, ?)");
                $insert->execute([$id, $name]);
                echo "✅ Created category: ID $id - $name\n";
            }
        } else {
            echo "❌ Failed to create categories table\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 CATEGORIES CHECK COMPLETE\n";
echo str_repeat("=", 50) . "\n";
?>
