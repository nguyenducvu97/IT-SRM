<?php
// Direct file include test
require_once 'api/comments.php';

echo "Comments API loaded successfully\n";

// Test if function exists
if (function_exists('commentsJsonResponse')) {
    echo "Function commentsJsonResponse exists\n";
    
    // Test the function
    ob_start();
    commentsJsonResponse(true, "Test message", ["test" => "data"]);
    $output = ob_get_clean();
    echo "Function output: $output\n";
} else {
    echo "Function commentsJsonResponse does NOT exist\n";
}

// Check for conflicts
if (function_exists('jsonResponse')) {
    echo "Function jsonResponse also exists (potential conflict)\n";
}
?>
