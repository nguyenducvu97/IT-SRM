<?php
echo "=== FIXING FINAL CATCH BLOCK ===" . PHP_EOL;

// Read the file
$file = 'api/service_requests.php';
$content = file_get_contents($file);

// Find the specific orphaned catch block
$pattern = '/^\s*\} catch \(Exception \$e\) \{\s*\n\s*\n\s*serviceJsonResponse\(false, "Database error: " \. \$e\-\>getMessage\(\)\);\s*\n\s*\n\s*\}/m';

// Remove the orphaned catch block
$fixed_content = preg_replace($pattern, '}', $content);

// Write back
file_put_contents($file, $fixed_content);

echo "Fixed final catch block" . PHP_EOL;

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
