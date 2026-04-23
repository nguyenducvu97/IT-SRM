<?php
// Async Email Queue - Giảm thời gian response từ 5-8s xuống 1-2s
class AsyncEmailQueue {
    private $queueFile;
    private $maxRetries = 3;
    
    public function __construct() {
        $this->queueFile = __DIR__ . '/../logs/email_queue.json';
    }
    
    // Add email to queue (non-blocking)
    public function queueEmail($to, $toName, $subject, $body, $priority = 'normal') {
        $email = [
            'id' => uniqid('email_', true),
            'to' => $to,
            'toName' => $toName,
            'subject' => $subject,
            'body' => $body,
            'priority' => $priority,
            'attempts' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'status' => 'queued'
        ];
        
        $queue = $this->getQueue();
        $queue[] = $email;
        $this->saveQueue($queue);
        
        // Trigger background processing (non-blocking)
        $this->triggerBackgroundProcess();
        
        return $email['id'];
    }
    
    // Get current queue
    private function getQueue() {
        if (!file_exists($this->queueFile)) {
            return [];
        }
        
        $json = file_get_contents($this->queueFile);
        return json_decode($json, true) ?: [];
    }
    
    // Save queue to file
    private function saveQueue($queue) {
        file_put_contents($this->queueFile, json_encode($queue, JSON_PRETTY_PRINT), LOCK_EX);
    }
    
    // Trigger background email processing (completely non-blocking)
    private function triggerBackgroundProcess() {
        // Check email processing mode
        if (function_exists('isEmailProcessingDisabled') && isEmailProcessingDisabled()) {
            error_log("Email processing is DISABLED - emails queued but not sent");
            return;
        }
        
        // For inline mode, process immediately
        if (function_exists('isBackgroundProcessingEnabled') && !isBackgroundProcessingEnabled()) {
            error_log("Processing emails inline (background disabled)");
            $this->processQueue();
            return;
        }
        
        // TRUE BACKGROUND MODE - Process directly without opening files
        try {
            require_once __DIR__ . '/../scripts/background_email_processor.php';
            
            // Process emails in true background (no file opening)
            $result = processEmailsInBackground();
            
            if (defined('ENABLE_BACKGROUND_LOGGING') && ENABLE_BACKGROUND_LOGGING) {
                error_log("Background email processing: " . json_encode($result));
            }
            
        } catch (Exception $e) {
            error_log("Background processing failed: " . $e->getMessage());
            
            // Fallback to old method if needed
            $this->fallbackToOldMethod();
        }
    }
    
    // Fallback method for compatibility
    private function fallbackToOldMethod() {
        $script = __DIR__ . '/../scripts/process_email_queue.php';
        
        if (function_exists('exec')) {
            $php_path = $this->findPhpPath();
            if ($php_path) {
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    $command = 'start /B "' . $php_path . '" "' . $script . '"';
                    @exec($command);
                } else {
                    $command = '"' . $php_path . '" "' . $script . '" > /dev/null 2>&1 &';
                    @exec($command);
                }
            }
        }
    }
    
    // Find PHP executable path
    private function findPhpPath() {
        // Method 1: Use PHP_BINARY constant but validate it's actually PHP
        if (defined('PHP_BINARY') && PHP_BINARY !== '') {
            // Check if it's actually PHP executable, not Apache
            if (strpos(basename(PHP_BINARY), 'php') !== false) {
                return PHP_BINARY;
            }
        }
        
        // Method 2: Try common Windows PHP paths
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $common_paths = [
                'C:\xampp\php\php.exe',
                'C:\wamp\bin\php\php8.2.0\php.exe',
                'C:\wamp64\bin\php\php8.2.0\php.exe',
                'C:\php\php.exe',
                'C:\Program Files\PHP\php.exe',
                'C:\Program Files (x86)\PHP\php.exe'
            ];
            
            foreach ($common_paths as $path) {
                if (file_exists($path)) {
                    return $path;
                }
            }
        }
        
        // Method 3: Try 'php' command (might be in PATH)
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            // Linux/Mac - try 'which php'
            if (function_exists('shell_exec')) {
                $php_path = @shell_exec('which php 2>/dev/null');
                if ($php_path && trim($php_path) !== '') {
                    return trim($php_path);
                }
            }
        }
        
        // Method 4: Use current PHP executable
        if (isset($_SERVER['_']) && $_SERVER_ !== '') {
            return $_SERVER_['_'];
        }
        
        // Method 5: Try to find PHP in same directory as current script
        $current_dir = dirname(__DIR__);
        $php_exe = $current_dir . DIRECTORY_SEPARATOR . 'php.exe';
        if (file_exists($php_exe)) {
            return $php_exe;
        }
        
        // Method 6: Last resort - try 'php' anyway
        error_log("Warning: Could not find reliable PHP path, falling back to 'php' command");
        return 'php'; // This might fail but at least we tried
    }
    
    // Process queue (called by background script)
    public function processQueue() {
        $queue = $this->getQueue();
        $processed = [];
        $failed = [];
        
        foreach ($queue as $email) {
            // Ensure email structure has all required keys
            $email = array_merge([
                'id' => '',
                'to' => '',
                'toName' => '',
                'subject' => '',
                'body' => '',
                'status' => 'queued',
                'attempts' => 0,
                'created_at' => '',
                'last_attempt' => '',
                'processed_at' => ''
            ], $email);
            
            if ($email['status'] === 'processed') {
                $processed[] = $email;
                continue;
            }
            
            $attempts = $email['attempts'] ?? 0;
            if ($attempts >= $this->maxRetries) {
                $email['status'] = 'failed';
                $failed[] = $email;
                continue;
            }
            
            // Try to send email
            $email['attempts']++;
            $email['last_attempt'] = date('Y-m-d H:i:s');
            
            if ($this->sendEmailNow($email)) {
                $email['status'] = 'processed';
                $email['processed_at'] = date('Y-m-d H:i:s');
                error_log("Email sent successfully: {$email['id']} to {$email['to']}");
            } else {
                $email['status'] = 'failed';
                error_log("Email failed: {$email['id']} to {$email['to']} after {$email['attempts']} attempts");
            }
            
            $processed[] = $email;
        }
        
        // Save updated queue
        $this->saveQueue($processed);
        
        // Clean old processed emails (older than 24 hours)
        $this->cleanOldEmails();
        
        return [
            'processed' => count(array_filter($processed, fn($e) => $e['status'] === 'processed')),
            'failed' => count($failed),
            'remaining' => count(array_filter($processed, fn($e) => $e['status'] === 'queued'))
        ];
    }
    
    // Send email immediately
    private function sendEmailNow($email) {
        try {
            // Check if EmailHelper exists
            $emailHelperPath = __DIR__ . '/../lib/EmailHelper.php';
            if (!file_exists($emailHelperPath)) {
                error_log("EmailHelper not found at: {$emailHelperPath}");
                return false;
            }
            
            require_once $emailHelperPath;
            $emailHelper = new EmailHelper();
            return $emailHelper->sendEmail($email['to'], $email['toName'], $email['subject'], $email['body']);
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }
    
    // Clean old processed emails
    private function cleanOldEmails() {
        $queue = $this->getQueue();
        $filtered = array_filter($queue, function($email) {
            if ($email['status'] === 'processed' && isset($email['processed_at'])) {
                $processedTime = strtotime($email['processed_at']);
                return (time() - $processedTime) < 24 * 3600; // Keep less than 24 hours
            }
            return true;
        });
        
        $this->saveQueue(array_values($filtered));
    }
    
    // Get queue statistics
    public function getQueueStats() {
        $queue = $this->getQueue();
        $stats = [
            'total' => count($queue),
            'queued' => 0,
            'processed' => 0,
            'failed' => 0
        ];
        
        foreach ($queue as $email) {
            $stats[$email['status']]++;
        }
        
        return $stats;
    }
}

// Global instance
$asyncEmailQueue = new AsyncEmailQueue();

// Helper function for easy use
function queueEmailAsync($to, $toName, $subject, $body, $priority = 'normal') {
    global $asyncEmailQueue;
    return $asyncEmailQueue->queueEmail($to, $toName, $subject, $body, $priority);
}
?>
