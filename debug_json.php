<?php
// Debug JSON parsing
header("Content-Type: application/json; charset=UTF-8");

echo "=== DEBUG JSON PARSING ===\n";

// Get raw input
$raw_input = file_get_contents("php://input");
echo "Raw input: " . $raw_input . "\n";

// Try to parse
$data = json_decode($raw_input);
echo "Parsed data: ";
print_r($data);

// Check for JSON errors
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON error: " . json_last_error_msg() . "\n";
}

// Check method
echo "Request method: " . $_SERVER['REQUEST_METHOD'] . "\n";

// Check headers
echo "Headers:\n";
foreach (getallheaders() as $name => $value) {
    echo "$name: $value\n";
}
?>
