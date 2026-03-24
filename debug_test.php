<?php
// Debug script to check for syntax errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing service_requests.php syntax...\n";

// Test syntax
$output = shell_exec('php -l api/service_requests.php 2>&1');
echo "Syntax check: $output\n";

// Test loading basic classes
echo "Testing class loading...\n";

try {
    require_once __DIR__ . '/config/session.php';
    echo "✅ session.php loaded\n";
} catch (Exception $e) {
    echo "❌ session.php error: " . $e->getMessage() . "\n";
}

try {
    require_once __DIR__ . '/config/database.php';
    echo "✅ database.php loaded\n";
} catch (Exception $e) {
    echo "❌ database.php error: " . $e->getMessage() . "\n";
}

// Test optimization files
$optimization_files = [
    'config/async_email.php',
    'config/optimized_notifications.php', 
    'config/optimized_file_upload.php',
    'config/database_optimizer.php'
];

foreach ($optimization_files as $file) {
    try {
        require_once __DIR__ . '/' . $file;
        echo "✅ $file loaded\n";
    } catch (Exception $e) {
        echo "❌ $file error: " . $e->getMessage() . "\n";
    } catch (Error $e) {
        echo "❌ $file fatal error: " . $e->getMessage() . "\n";
    }
}

echo "\nTesting complete.\n";
?>
