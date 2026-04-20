<?php
// Test clean export without any includes
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Clean any previous output
if (ob_get_length()) ob_clean();

// Set headers for CSV download
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment;filename="test_clean.csv"');
header('Cache-Control: max-age=0');
header('Expires: 0');
header('Pragma: public');

// Open output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel display
fwrite($output, "\xEF\xBB\xBF");

// Add headers
$headers = ['Test', 'Data', 'Number'];
fputcsv($output, $headers);

// Add sample data
fputcsv($output, ['Row1', 'Value1', 123]);
fputcsv($output, ['Row2', 'Value2', 456]);

fclose($output);
exit();
?>
