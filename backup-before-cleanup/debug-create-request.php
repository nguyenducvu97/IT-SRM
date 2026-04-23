<?php
// Enable all error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/debug_create.log');

require_once 'config/database.php';

echo "🔧 Debug Create Request API\n\n";

// Test data for creating request
$_POST = [
    'action' => 'create',
    'title' => 'Test Request Debug - ' . date('H:i:s'),
    'description' => 'Test description for debugging API create request',
    'category_id' => 1,
    'priority' => 'medium'
];

echo "📝 POST Data:\n";
print_r($_POST);

echo "\n🌐 Simulating API call...\n";

// Simulate the API call
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    echo "Action: $action\n";
    
    if ($action === 'create') {
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        $priority = isset($_POST['priority']) ? $_POST['priority'] : 'medium';
        
        echo "Title: $title\n";
        echo "Description: $description\n";
        echo "Category ID: $category_id\n";
        echo "Priority: $priority\n";
        
        // Test the query
        $query = "INSERT INTO service_requests 
                  (user_id, category_id, title, description, priority, status, created_at, updated_at)
                  VALUES (:user_id, :category_id, :title, :description, :priority, 'open', NOW(), NOW())";
        
        echo "Query: $query\n";
        
        $stmt = $db->prepare($query);
        
        // Mock user session
        $user_id = 4; // Test user ID
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':priority', $priority);
        
        echo "🚀 Executing query...\n";
        $result = $stmt->execute();
        
        if ($result) {
            $request_id = $db->lastInsertId();
            echo "✅ Request created successfully! ID: $request_id\n";
            
            // Test email notification
            echo "📧 Testing email notification...\n";
            require_once 'lib/EmailHelper.php';
            
            $emailHelper = new EmailHelper();
            $email_result = $emailHelper->sendNewRequestNotification([
                'id' => $request_id,
                'title' => $title,
                'requester_name' => 'Debug User',
                'category' => 'Test Category',
                'priority' => $priority,
                'description' => $description
            ]);
            
            if ($email_result) {
                echo "✅ Email sent successfully!\n";
            } else {
                echo "❌ Email failed!\n";
            }
            
        } else {
            echo "❌ Query failed!\n";
            $error_info = $stmt->errorInfo();
            echo "Error: " . print_r($error_info, true) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 DEBUG COMPLETE\n";
echo str_repeat("=", 50) . "\n";
?>
