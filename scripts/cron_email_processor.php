<?php
// Cron Job Email Processor - Chạy định kỳ để xử lý email queue
// Có thể thiết lập cron job mỗi 1-5 phút

// Set execution time
set_time_limit(300); // 5 minutes

// Include required files
require_once __DIR__ . '/../api/async_email_queue.php';

// Ignore user abort
ignore_user_abort(true);

// Process the queue
$queue = new AsyncEmailQueue();
$result = $queue->processQueue();

// Log results with timestamp
$log_message = "[" . date('Y-m-d H:i:s') . "] Email queue processed: " . json_encode($result);
error_log($log_message);

// Optional: Send health check if there are too many failed emails
if ($result['failed'] > 10) {
    error_log("WARNING: High email failure rate detected - {$result['failed']} failed emails");
}

// Return JSON for web access
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'result' => $result,
    'timestamp' => date('Y-m-d H:i:s'),
    'message' => 'Email queue processed successfully'
]);
?>
