<?php
// Test Vietnamese character encoding
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment;filename="test_vietnamese.csv"');

$output = fopen('php://output', 'w');
fwrite($output, "\xEF\xBB\xBF");

// Test headers with Vietnamese characters
$headers = [
    'Mã NV',
    'Họ và tên', 
    'Email',
    'Phòng ban',
    'Tổng yêu cầu',
    'Đã hoàn thành',
    'Đang xử lý',
    'Chờ xử lý',
    'Thời gian phản hồi TB (phút)',
    'Thời gian hoàn thành TB (giờ)',
    'Tổng đánh giá',
    'Điểm đánh giá chung TB (1-5) - K3',
    'Đánh giá tích cực',
    'Đánh giá tiêu cực',
    'Chất lượng xử lý (%) - K4',
    'Tỷ lệ hoàn thành (%)',
    'Tỷ lệ phản hồi (%)',
    'Điểm K1 - Tốc độ phản hồi (1-5)',
    'Điểm K2 - Tiến độ hoàn thành (1-5)',
    'Điểm K3 - Đánh giá chung (1-5)',
    'Điểm K4 - Chất lượng xử lý (1-5)',
    'Điểm KPI Tổng hợp (1-5)'
];

// Write headers directly
foreach ($headers as $header) {
    fputcsv($output, [$header]);
}

// Test data row
$test_data = [
    'NV001',
    'Nguyễn Văn A',
    'nguyenvana@email.com',
    'IT',
    '10',
    '8',
    '1',
    '1',
    '15.5',
    '24.2',
    '5',
    '4.2',
    '3',
    '2',
    '85%',
    '80%',
    '90%',
    '4.5',
    '4.2',
    '4.8',
    '3.9',
    '4.1'
];

fputcsv($output, $test_data);
fclose($output);
?>
