<?php
echo "=== FIXING UNMATCHED BRACE ===" . PHP_EOL;

// Read the file
$file = 'api/service_requests.php';
$content = file_get_contents($file);

// Remove the extra closing brace
$pattern = '/^\s*\}\s*\n\s*\n\s*\}/m';
$fixed_content = preg_replace($pattern, '}', $content);

// Write back
file_put_contents($file, $fixed_content);

echo "Fixed unmatched brace" . PHP_EOL;

// Test syntax
$output = [];
$return_code = 0;
exec("php -l {$file}", $output, $return_code);

if ($return_code === 0) {
    echo "SUCCESS: Syntax check passed!" . PHP_EOL;
} else {
    echo "ERROR: Syntax still failed:" . PHP_EOL;
    foreach ($output as $line) {
        echo "  " . $line . PHP_EOL;
    }
}

echo PHP_EOL . "=== FIX COMPLETE ===" . PHP_EOL;
?>
