<?php
// Check syntax of reject_requests.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Checking reject_requests.php Syntax</h2>";

$file = __DIR__ . '/api/reject_requests.php';

if (!file_exists($file)) {
    echo "<p style='color: red;'>❌ File not found: $file</p>";
    exit;
}

echo "<p>Checking file: $file</p>";

// Check syntax
$output = [];
$return_var = 0;
exec("php -l \"$file\" 2>&1", $output, $return_var);

if ($return_var === 0) {
    echo "<p style='color: green;'>✅ No syntax errors found</p>";
} else {
    echo "<p style='color: red;'>❌ Syntax errors found:</p>";
    echo "<pre>" . htmlspecialchars(implode("\n", $output)) . "</p>";
}

// Try to include without executing main logic
echo "<h3>Attempting to include file:</h3>";

try {
    // Capture output
    ob_start();
    
    // Include without executing main logic
    include_once $file;
    
    $output = ob_get_clean();
    
    if (empty($output)) {
        echo "<p style='color: green;'>✅ File included successfully</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ File produced output:</p>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
    }
    
} catch (ParseError $e) {
    echo "<p style='color: red;'>❌ Parse Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
} catch (Error $e) {
    echo "<p style='color: red;'>❌ Fatal Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
}

echo "<h3>File contents check:</h3>";

// Read and check for common issues
$content = file_get_contents($file);

// Check for BOM
if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
    echo "<p style='color: orange;'>⚠️ File contains BOM</p>";
} else {
    echo "<p style='color: green;'>✅ No BOM detected</p>";
}

// Check for short tags
if (strpos($content, '<?') !== 0) {
    echo "<p style='color: orange;'>⚠️ File may have content before PHP tag</p>";
} else {
    echo "<p style='color: green;'>✅ PHP tag is at start</p>";
}

// Check for extra whitespace at end
if (substr($content, -1) === '>') {
    echo "<p style='color: orange;'>⚠️ File ends with > (possible extra content)</p>";
} else {
    echo "<p style='color: green;'>✅ File ends properly</p>";
}

// Check for function definitions
if (strpos($content, 'function handleGet') !== false) {
    echo "<p style='color: green;'>✅ handleGet function found</p>";
} else {
    echo "<p style='color: red;'>❌ handleGet function not found</p>";
}

if (strpos($content, 'function handlePut') !== false) {
    echo "<p style='color: green;'>✅ handlePut function found</p>";
} else {
    echo "<p style='color: red;'>❌ handlePut function not found</p>";
}

echo "<p><a href='debug-reject-detailed.php'>Run Detailed Debug</a></p>";
?>
