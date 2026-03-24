<?php
// Performance Testing Script for Request Creation
// This script helps measure the improvement in request creation time

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

// Mock user session for testing
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'user';

function runPerformanceTest() {
    echo "=== Performance Test for Request Creation ===\n\n";
    
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db === null) {
        echo "❌ Database connection failed\n";
        return;
    }
    
    echo "✅ Database connected\n\n";
    
    // Test data
    $test_requests = [
        [
            'title' => 'Test Request ' . time(),
            'description' => 'This is a performance test request for measuring optimization improvements.',
            'category_id' => 1,
            'priority' => 'medium'
        ],
        [
            'title' => 'High Priority Test ' . time(),
            'description' => 'High priority performance test with longer description to simulate real-world usage.',
            'category_id' => 2,
            'priority' => 'high'
        ],
        [
            'title' => 'Low Priority Test ' . time(),
            'description' => 'Low priority test request.',
            'category_id' => 3,
            'priority' => 'low'
        ]
    ];
    
    $results = [];
    
    foreach ($test_requests as $index => $request_data) {
        echo "🔄 Test " . ($index + 1) . ": Creating request - '{$request_data['title']}'\n";
        
        $start_time = microtime(true);
        $start_memory = memory_get_usage(true);
        
        try {
            // Simulate the optimized request creation process
            $user_id = 1;
            
            // Start transaction
            $db->beginTransaction();
            
            // Insert request
            $query = "INSERT INTO service_requests 
                     (user_id, category_id, title, description, priority, status, created_at, updated_at) 
                     VALUES (?, ?, ?, ?, ?, 'open', NOW(), NOW())";
            
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                $user_id,
                $request_data['category_id'],
                $request_data['title'],
                $request_data['description'],
                $request_data['priority']
            ]);
            
            $request_id = $db->lastInsertId();
            
            // Simulate notification creation (optimized)
            $notification_start = microtime(true);
            
            // Get staff and admin users
            $stmt = $db->prepare("SELECT id FROM users WHERE role IN ('staff', 'admin')");
            $stmt->execute();
            $staff_admin_users = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Batch insert notifications
            if (!empty($staff_admin_users)) {
                $title = "Yêu cầu mới #" . $request_id;
                $message = "Test user tạo yêu cầu: " . $request_data['title'];
                $current_time = date('Y-m-d H:i:s');
                
                $values = [];
                $params = [];
                
                foreach ($staff_admin_users as $user_id) {
                    $values[] = "(?, ?, ?, ?, ?, ?, ?)";
                    $params = array_merge($params, [
                        $user_id,
                        $title,
                        $message,
                        'info',
                        $request_id,
                        'request',
                        $current_time
                    ]);
                }
                
                $notification_query = "INSERT INTO notifications (user_id, title, message, type, related_id, related_type, created_at) 
                                     VALUES " . implode(',', $values);
                
                $notif_stmt = $db->prepare($notification_query);
                $notif_stmt->execute($params);
            }
            
            $notification_time = (microtime(true) - $notification_start) * 1000;
            
            // Commit transaction
            $db->commit();
            
            $end_time = microtime(true);
            $end_memory = memory_get_usage(true);
            
            $execution_time = round(($end_time - $start_time) * 1000, 2);
            $memory_used = round(($end_memory - $start_memory) / 1024, 2);
            
            $results[] = [
                'request_id' => $request_id,
                'execution_time' => $execution_time,
                'notification_time' => $notification_time,
                'memory_used' => $memory_used,
                'notifications_created' => count($staff_admin_users)
            ];
            
            echo "✅ Request #{$request_id} created in {$execution_time}ms\n";
            echo "   📧 Notifications: {$notification_time}ms for " . count($staff_admin_users) . " users\n";
            echo "   💾 Memory used: {$memory_used}KB\n\n";
            
        } catch (Exception $e) {
            $db->rollBack();
            echo "❌ Test failed: " . $e->getMessage() . "\n\n";
        }
    }
    
    // Summary
    if (!empty($results)) {
        echo "📊 Performance Summary:\n";
        echo "========================\n";
        
        $total_time = array_sum(array_column($results, 'execution_time'));
        $avg_time = round($total_time / count($results), 2);
        $min_time = min(array_column($results, 'execution_time'));
        $max_time = max(array_column($results, 'execution_time'));
        
        $total_notifications = array_sum(array_column($results, 'notification_time'));
        $avg_notifications = round($total_notifications / count($results), 2);
        
        $total_memory = array_sum(array_column($results, 'memory_used'));
        $avg_memory = round($total_memory / count($results), 2);
        
        echo "📈 Request Creation:\n";
        echo "   Total requests: " . count($results) . "\n";
        echo "   Average time: {$avg_time}ms\n";
        echo "   Min time: {$min_time}ms\n";
        echo "   Max time: {$max_time}ms\n";
        echo "   Total time: {$total_time}ms\n\n";
        
        echo "📧 Notification Processing:\n";
        echo "   Average notification time: {$avg_notifications}ms\n";
        echo "   Total notification time: {$total_notifications}ms\n\n";
        
        echo "💾 Memory Usage:\n";
        echo "   Average memory per request: {$avg_memory}KB\n";
        echo "   Total memory used: {$total_memory}KB\n\n";
        
        // Performance classification
        if ($avg_time < 200) {
            echo "🚀 Performance: EXCELLENT (< 200ms average)\n";
        } elseif ($avg_time < 500) {
            echo "⚡ Performance: GOOD (200-500ms average)\n";
        } elseif ($avg_time < 1000) {
            echo "🐌 Performance: ACCEPTABLE (500-1000ms average)\n";
        } else {
            echo "⚠️  Performance: NEEDS OPTIMIZATION (> 1000ms average)\n";
        }
        
        echo "\n💡 Optimization Tips:\n";
        echo "- Target < 200ms for excellent user experience\n";
        echo "- Async email processing should reduce main thread blocking\n";
        echo "- Batch notifications should reduce database round trips\n";
        echo "- File upload optimization should handle multiple files efficiently\n";
    }
}

// Clean up test data
function cleanupTestData() {
    echo "\n🧹 Cleaning up test data...\n";
    
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        try {
            // Delete test notifications
            $stmt = $db->prepare("DELETE FROM notifications WHERE title LIKE '%Test Request%' OR title LIKE '%Test user%'");
            $stmt->execute();
            $deleted_notifications = $stmt->rowCount();
            
            // Delete test requests
            $stmt = $db->prepare("DELETE FROM service_requests WHERE title LIKE '%Test Request%' OR title LIKE '%High Priority Test%' OR title LIKE '%Low Priority Test%'");
            $stmt->execute();
            $deleted_requests = $stmt->rowCount();
            
            echo "✅ Deleted {$deleted_requests} test requests and {$deleted_notifications} notifications\n";
        } catch (Exception $e) {
            echo "❌ Cleanup failed: " . $e->getMessage() . "\n";
        }
    }
}

// Run tests
if (php_sapi_name() === 'cli') {
    echo "IT Service Request - Performance Test\n";
    echo "====================================\n\n";
    
    runPerformanceTest();
    
    // Ask for cleanup
    echo "\nClean up test data? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);
    
    if (trim(strtolower($line)) === 'y') {
        cleanupTestData();
    }
    
    echo "\n✨ Performance test completed!\n";
} else {
    echo "<pre>";
    runPerformanceTest();
    echo "</pre>";
}
?>
