<?php
echo "=== SIMPLE FIX FOR SERVICE_REQUESTS.PHP ===" . PHP_EOL;

$file = 'api/service_requests.php';
$content = file_get_contents($file);

echo "Original file size: " . strlen($content) . " bytes" . PHP_EOL;

// Find and remove the specific problematic catch block
$lines = explode("\n", $content);
$fixed_lines = [];

for ($i = 0; $i < count($lines); $i++) {
    $line = $lines[$i];
    
    // Skip the orphaned catch block at line 13916
    if (trim($line) === '} catch (Exception $e) {' && $i >= 13910 && $i <= 13920) {
        echo "Skipping orphaned catch block at line " . ($i + 1) . PHP_EOL;
        // Skip this line and the next few lines
        $i += 8; // Skip this line and next 7 lines
        continue;
    }
    
    $fixed_lines[] = $line;
}

// Write fixed content
$fixed_content = implode("\n", $fixed_lines);
file_put_contents($file, $fixed_content);

echo "Fixed file size: " . strlen($fixed_content) . " bytes" . PHP_EOL;
echo "Removed orphaned catch block" . PHP_EOL;

// Test syntax
$php_path = 'C:\xampp\php\php.exe';
$command = "\"{$php_path}\" -l {$file}";
$output = shell_exec($command);

if (strpos($output, 'No syntax errors') !== false) {
    echo "SUCCESS: Syntax check passed!" . PHP_EOL;
} else {
    echo "ERROR: Syntax check failed:" . PHP_EOL;
    echo $output . PHP_EOL;
}

echo PHP_EOL . "=== FIX COMPLETE ===" . PHP_EOL;
?>
