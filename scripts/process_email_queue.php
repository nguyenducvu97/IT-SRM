mail <?php
// Background Email Queue Processor
// Chạy independently để xử lý email async

// Prevent direct browser access - only allow POST/CLI
$request_method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
if ($request_method === 'GET' && php_sapi_name() !== 'cli') {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Direct access not allowed',
        'message' => 'This script can only be accessed via POST requests or CLI'
    ]);
    exit;
}

// Set execution time
set_time_limit(300); // 5 minutes

// Include required files
require_once __DIR__ . '/../api/async_email_queue.php';

// Ignore user abort
ignore_user_abort(true);

// Process the queue
$queue = new AsyncEmailQueue();
$result = $queue->processQueue();

// Log results
error_log("Email queue processed: " . json_encode($result));

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'result' => $result,
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $request_method,
    'trigger' => 'background_processing'
]);
?>
