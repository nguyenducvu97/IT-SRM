<?php
echo "=== FIXING SERVICE_REQUESTS.PHP SYNTAX ERROR ===" . PHP_EOL;

// Read the original file
$original_file = 'api/service_requests.php';
$backup_file = 'api/service_requests.php.backup';
$fixed_file = 'api/service_requests_fixed.php';

// Create backup
if (!file_exists($backup_file)) {
    copy($original_file, $backup_file);
    echo "Created backup: {$backup_file}" . PHP_EOL;
}

// Read file content
$content = file_get_contents($original_file);
$lines = explode("\n", $content);

echo "Original file has " . count($lines) . " lines" . PHP_EOL;

// Find and remove the orphaned catch block
$fixed_lines = [];
$remove_next_lines = 0;

for ($i = 0; $i < count($lines); $i++) {
    $line = $lines[$i];
    
    // Check if this is the start of the orphaned catch block
    if (trim($line) === '} catch (Exception $e) {' && $remove_next_lines == 0) {
        echo "Found orphaned catch block at line " . ($i + 1) . PHP_EOL;
        $remove_next_lines = 8; // Remove this line and next 7 lines
        continue;
    }
    
    if ($remove_next_lines > 0) {
        echo "Removing line " . ($i + 1) . ": " . trim($line) . PHP_EOL;
        $remove_next_lines--;
        continue;
    }
    
    $fixed_lines[] = $line;
}

// Write fixed content
$fixed_content = implode("\n", $fixed_lines);
file_put_contents($fixed_file, $fixed_content);

echo "Fixed file written: {$fixed_file}" . PHP_EOL;
echo "Fixed file has " . count($fixed_lines) . " lines" . PHP_EOL;

// Test syntax
$output = [];
$return_code = 0;
exec("php -l {$fixed_file}", $output, $return_code);

if ($return_code === 0) {
    echo "SUCCESS: Syntax check passed!" . PHP_EOL;
    
    // Replace original with fixed
    copy($fixed_file, $original_file);
    echo "Replaced original file with fixed version" . PHP_EOL;
    
    // Clean up
    unlink($fixed_file);
    echo "Cleaned up temporary file" . PHP_EOL;
} else {
    echo "ERROR: Syntax check failed:" . PHP_EOL;
    foreach ($output as $line) {
        echo "  " . $line . PHP_EOL;
    }
}

echo PHP_EOL . "=== FIX COMPLETE ===" . PHP_EOL;
?>
