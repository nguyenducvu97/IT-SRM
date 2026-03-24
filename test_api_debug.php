<?php
// Debug API test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing API loading...\n";

// Try to include the API file
try {
    include_once 'api/service_requests.php';
    echo "✅ API file loaded successfully\n";
} catch (Exception $e) {
    echo "❌ Error loading API: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "❌ Fatal error loading API: " . $e->getMessage() . "\n";
}

echo "Test completed.\n";
?>
