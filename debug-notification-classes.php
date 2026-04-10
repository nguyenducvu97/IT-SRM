<?php
echo "<h2>🔍 DEBUG NOTIFICATION CLASSES</h2>";

// Check if files exist and can be loaded
echo "<h3>📁 File Existence Check:</h3>";

$files = [
    'lib/NotificationHelper.php' => 'NotificationHelper',
    'lib/ServiceRequestNotificationHelper.php' => 'ServiceRequestNotificationHelper'
];

foreach ($files as $file => $class) {
    if (file_exists($file)) {
        echo "<p>✅ File '$file': EXISTS</p>";
        
        // Try to include and check class
        try {
            require_once $file;
            echo "<p>✅ File '$file': LOADED successfully</p>";
            
            if (class_exists($class)) {
                echo "<p>✅ Class '$class': EXISTS</p>";
                
                // Get all methods
                $methods = get_class_methods($class);
                echo "<p><strong>Methods found (" . count($methods) . "):</strong></p>";
                echo "<ul>";
                foreach ($methods as $method) {
                    echo "<li>$method</li>";
                }
                echo "</ul>";
                
            } else {
                echo "<p>❌ Class '$class': NOT FOUND after loading</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Error loading '$file': " . $e->getMessage() . "</p>";
        } catch (Error $e) {
            echo "<p>❌ Fatal error loading '$file': " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>❌ File '$file': NOT FOUND</p>";
    }
}

// Check database connection
echo "<h3>🗄️ Database Connection:</h3>";

try {
    require_once 'config/database.php';
    $db = getDatabaseConnection();
    echo "<p>✅ Database connection: SUCCESS</p>";
    
    // Test creating notification helper
    if (class_exists('NotificationHelper')) {
        try {
            $helper = new NotificationHelper($db);
            echo "<p>✅ NotificationHelper instantiation: SUCCESS</p>";
            
            // Test creating a notification
            $result = $helper->createNotification(
                1, // user_id
                'Test Notification',
                'This is a test notification',
                'info',
                null,
                null,
                false // don't send email
            );
            
            if ($result) {
                echo "<p>✅ createNotification test: SUCCESS</p>";
            } else {
                echo "<p>❌ createNotification test: FAILED</p>";
            }
            
        } catch (Exception $e) {
            echo "<p>❌ NotificationHelper instantiation failed: " . $e->getMessage() . "</p>";
        }
    }
    
    // Test service request notification helper
    if (class_exists('ServiceRequestNotificationHelper')) {
        try {
            $serviceHelper = new ServiceRequestNotificationHelper($db);
            echo "<p>✅ ServiceRequestNotificationHelper instantiation: SUCCESS</p>";
            
        } catch (Exception $e) {
            echo "<p>❌ ServiceRequestNotificationHelper instantiation failed: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

// Check PHP errors
echo "<h3>⚠️ PHP Error Reporting:</h3>";
echo "<p>error_reporting: " . error_reporting() . "</p>";
echo "<p>display_errors: " . (ini_get('display_errors') ? 'ON' : 'OFF') . "</p>";

// Check included files
echo "<h3>📚 Included Files:</h3>";
$included_files = get_included_files();
foreach ($included_files as $file) {
    if (strpos($file, 'NotificationHelper') !== false || strpos($file, 'ServiceRequestNotificationHelper') !== false) {
        echo "<p>📄 $file</p>";
    }
}

echo "<h3>🔧 Fix Recommendations:</h3>";
echo "<ol>";
echo "<li>Check for PHP syntax errors in notification files</li>";
echo "<li>Verify database.php is working correctly</li>";
echo "<li>Check for missing dependencies in notification classes</li>";
echo "<li>Ensure proper file permissions</li>";
echo "</ol>";

// Quick syntax check
echo "<h3>🔍 Syntax Check:</h3>";
foreach ($files as $file => $class) {
    if (file_exists($file)) {
        $output = [];
        $return_var = 0;
        exec("php -l \"$file\" 2>&1", $output, $return_var);
        
        if ($return_var === 0) {
            echo "<p>✅ $file: Syntax OK</p>";
        } else {
            echo "<p>❌ $file: Syntax Error</p>";
            echo "<pre>" . implode("\n", $output) . "</pre>";
        }
    }
}
?>
