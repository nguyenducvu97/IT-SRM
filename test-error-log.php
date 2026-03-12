<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Try to include the API file
try {
    require_once 'api/comments.php';
    echo "API file loaded successfully\n";
} catch (Error $e) {
    echo "Error loading API: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Exception loading API: " . $e->getMessage() . "\n";
}

// Check function existence
$functions = get_defined_functions();
$user_functions = $functions['user'];

echo "\nUser-defined functions:\n";
foreach ($user_functions as $func) {
    if (strpos($func, 'json') !== false || strpos($func, 'comment') !== false) {
        echo "- $func\n";
    }
}
?>
