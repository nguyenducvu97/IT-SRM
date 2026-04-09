<?php
// Quick syntax check without including the file
$file = 'api/service_requests.php';
$content = file_get_contents($file);

// Count braces
$open_braces = substr_count($content, '{');
$close_braces = substr_count($content, '}');
echo "Open braces: $open_braces\n";
echo "Close braces: $close_braces\n";
echo "Difference: " . ($open_braces - $close_braces) . "\n";

// Count parentheses
$open_parens = substr_count($content, '(');
$close_parens = substr_count($content, ')');
echo "Open parens: $open_parens\n";
echo "Close parens: $close_parens\n";
echo "Difference: " . ($open_parens - $close_parens) . "\n";

// Check for common issues
if (strpos($content, 'if ($action ===') !== false && strpos($content, 'elseif ($action ===') === false) {
    echo "Found 'if' without 'elseif'\n";
}

// Look for the problematic line around where we made changes
$lines = explode("\n", $content);
for ($i = 1980; $i < 2000 && $i < count($lines); $i++) {
    if (strpos($lines[$i], 'if') !== false || strpos($lines[$i], 'elseif') !== false) {
        echo "Line " . ($i + 1) . ": " . trim($lines[$i]) . "\n";
    }
}
?>
