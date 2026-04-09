<?php
// Check PHP syntax for service_requests.php
echo "<h1>PHP Syntax Check</h1>";

$file = 'api/service_requests.php';
$content = file_get_contents($file);

// Check for common syntax issues
$issues = [];

// Check for unmatched braces
$open_braces = substr_count($content, '{');
$close_braces = substr_count($content, '}');
if ($open_braces != $close_braces) {
    $issues[] = "Unmatched braces: Open = $open_braces, Close = $close_braces";
}

// Check for unmatched parentheses
$open_parens = substr_count($content, '(');
$close_parens = substr_count($content, ')');
if ($open_parens != $close_parens) {
    $issues[] = "Unmatched parentheses: Open = $open_parens, Close = $close_parens";
}

// Check for PHP tags
if (substr_count($content, '<?php') != substr_count($content, '?>')) {
    $issues[] = "Unmatched PHP tags";
}

// Check for common syntax errors
if (strpos($content, 'serviceJsonResponse(true, $response_data[\'message\'], $response_data);') !== false) {
    $issues[] = "Found serviceJsonResponse call without proper closing brace";
}

if (empty($issues)) {
    echo "<p style='color: green;'>No obvious syntax issues found</p>";
} else {
    echo "<p style='color: red;'>Syntax issues found:</p>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
}

// Try to include the file to check for runtime errors
echo "<h2>Runtime Check</h2>";
try {
    // This will cause a parse error if there's a syntax issue
    $result = @include $file;
    echo "<p style='color: green;'>File included successfully (no syntax errors)</p>";
} catch (ParseError $e) {
    echo "<p style='color: red;'>Parse error: " . $e->getMessage() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
} catch (Error $e) {
    echo "<p style='color: orange;'>Runtime error: " . $e->getMessage() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: orange;'>Exception: " . $e->getMessage() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
}
?>
