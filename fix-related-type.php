<?php
// Fix related_type column to include service_request
require_once 'config/database.php';

echo "<h2>Fix related_type Column</h2>";

try {
    $db = getDatabaseConnection();
    
    // Check current enum values
    echo "<h3>Current related_type enum values:</h3>";
    $stmt = $db->prepare("SHOW COLUMNS FROM notifications WHERE Field = 'related_type'");
    $stmt->execute();
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column) {
        echo "<p>Current enum: " . $column['Type'] . "</p>";
        
        // Extract enum values
        $enumStr = $column['Type'];
        preg_match("/^enum\((.*)\)$/", $enumStr, $matches);
        $enumValues = str_getcsv($matches[1], ",", "'");
        
        echo "<p>Current values: " . implode(', ', $enumValues) . "</p>";
        
        if (!in_array('service_request', $enumValues)) {
            echo "<p style='color: orange;'>⚠️ 'service_request' not found in enum values</p>";
            
            // Add service_request to enum
            $newEnumValues = array_merge($enumValues, ['service_request']);
            $newEnumStr = "'" . implode("','", $newEnumValues) . "'";
            
            echo "<h4>Adding 'service_request' to enum...</h4>";
            echo "<p>New enum: " . $newEnumStr . "</p>";
            
            // Alter table
            $alterStmt = $db->prepare("
                ALTER TABLE notifications 
                MODIFY COLUMN related_type ENUM(" . $newEnumStr . ") 
                CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
            ");
            
            if ($alterStmt->execute()) {
                echo "<p style='color: green;'>✅ Successfully added 'service_request' to related_type enum</p>";
                
                // Verify the change
                echo "<h4>Verifying change...</h4>";
                $verifyStmt = $db->prepare("SHOW COLUMNS FROM notifications WHERE Field = 'related_type'");
                $verifyStmt->execute();
                $newColumn = $verifyStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($newColumn) {
                    echo "<p>Updated enum: " . $newColumn['Type'] . "</p>";
                    
                    // Test creating a notification
                    echo "<h4>Test notification creation...</h4>";
                    require_once 'lib/ServiceRequestNotificationHelper.php';
                    $notificationHelper = new ServiceRequestNotificationHelper();
                    
                    $result = $notificationHelper->notifyAdminRejectionRequest(
                        28, 
                        "Test reason", 
                        "Test Staff", 
                        "Test Request"
                    );
                    
                    echo "<p>Test creation: " . ($result ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
                    
                    if ($result) {
                        // Check the created notification
                        $checkStmt = $db->prepare("SELECT id, related_id, related_type FROM notifications WHERE user_id = 1 ORDER BY created_at DESC LIMIT 1");
                        $checkStmt->execute();
                        $notification = $checkStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($notification) {
                            echo "<p>✅ Created notification - ID: {$notification['id']}, Related ID: {$notification['related_id']}, Type: {$notification['related_type']}</p>";
                        }
                    }
                } else {
                    echo "<p style='color: red;'>❌ Failed to verify change</p>";
                }
            } else {
                echo "<p style='color: red;'>❌ Failed to alter table</p>";
            }
        } else {
            echo "<p style='color: green;'>✅ 'service_request' already exists in enum values</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Could not find related_type column</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
}
?>
