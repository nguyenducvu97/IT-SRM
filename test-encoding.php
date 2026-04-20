<?php
// Test UTF-8 encoding for Vietnamese characters
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment;filename="test_encoding.csv"');

$output = fopen('php://output', 'w');
fwrite($output, "\xEF\xBB\xBF");

// Test headers with Vietnamese characters
$headers = [
    'Mã NV',
    'Hô và tên', 
    'Phòng ban',
    'Tông yêu càu',
    'Tiêuêu yêu càu',
    'Thòi gian phan hòi',
    'Dièm KPI Tông hòp'
];

// Apply encoding function
function ensureUTF8($text) {
    if (is_string($text)) {
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'auto');
        }
        $text = iconv('UTF-8', 'UTF-8//IGNORE', $text);
    }
    return $text;
}

$encoded_headers = array_map('ensureUTF8', $headers);
fputcsv($output, $encoded_headers);

// Test data
$test_data = [
    [1, 'Nguyễn Văn A', 'Phòng IT', 'Yêu cầu test', 'Tiêu đề test'],
    [2, 'Trần Thị B', 'Phòng Kế toán', 'Yêu cầu test 2', 'Tiêu đề test 2']
];

foreach ($test_data as $row) {
    $encoded_row = array_map('ensureUTF8', $row);
    fputcsv($output, $encoded_row);
}

fclose($output);
?>
