<?php
// Simple test file to debug the issue
error_log("=== DEBUG TEST START ===");
error_log("PHP Version: " . phpversion());
error_log("Current working directory: " . getcwd());
error_log("Document root: " . $_SERVER['DOCUMENT_ROOT'] ?? 'not set');
error_log("Request URI: " . $_SERVER['REQUEST_URI'] ?? 'not set');
error_log("=== DEBUG TEST END ===");

echo "Debug test completed. Check error logs.";
?>
