<?php
// Background Email Queue Processor
// This script runs in background to process email queue

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/email.php';
require_once __DIR__ . '/../config/async_email.php';

// Set time limit to 5 minutes
set_time_limit(300);

// Process email queue
$processor = new AsyncEmailProcessor();
$result = $processor->processQueue();

error_log("Email queue processed: {$result['processed']} sent, {$result['failed']} failed");

// Exit silently
exit;
?>
