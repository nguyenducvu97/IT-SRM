<?php
// Email Queue Configuration
// Email processing mode: 'inline', 'background', 'disabled'

// 'inline' - Gửi email ngay lập tức (chậm cho user)
// 'background' - Lưu vào queue và xử lý ngầm (nhanh cho user) 
// 'disabled' - Không gửi email (chỉ lưu để debug)
define('EMAIL_PROCESSING_MODE', 'background');

// Background processing settings
define('BACKGROUND_PROCESSING_METHOD', 'exec'); // 'exec', 'curl', 'popen'
define('BACKGROUND_TIMEOUT', 1); // seconds
define('ENABLE_BACKGROUND_LOGGING', true);

// Log file for debugging
define('EMAIL_QUEUE_LOG', __DIR__ . '/../logs/email_queue_debug.log');

// Helper function to check if background processing is enabled
function isBackgroundProcessingEnabled() {
    return defined('EMAIL_PROCESSING_MODE') && EMAIL_PROCESSING_MODE === 'background';
}

// Helper function to check if email processing is disabled
function isEmailProcessingDisabled() {
    return defined('EMAIL_PROCESSING_MODE') && EMAIL_PROCESSING_MODE === 'disabled';
}
?>
