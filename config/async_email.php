<?php
// Async Email Queue Processor
class AsyncEmailProcessor {
    private $queue_file;
    private $max_retries = 3;
    
    public function __construct() {
        $this->queue_file = __DIR__ . '/../logs/email_queue.json';
    }
    
    public function queueEmail($to_email, $to_name, $subject, $body, $template_data = []) {
        $email_data = [
            'id' => uniqid('email_', true),
            'to_email' => $to_email,
            'to_name' => $to_name,
            'subject' => $subject,
            'body' => $body,
            'template_data' => $template_data,
            'created_at' => date('Y-m-d H:i:s'),
            'retries' => 0,
            'status' => 'queued'
        ];
        
        $queue = $this->getQueue();
        $queue[] = $email_data;
        $this->saveQueue($queue);
        
        // Trigger async processing if not already running
        $this->triggerAsyncProcess();
        
        return $email_data['id'];
    }
    
    private function triggerAsyncProcess() {
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
            
            // Fallback to old method
            $this->fallbackToOldMethod();
        }
    }
    
    // Fallback method for compatibility
    private function fallbackToOldMethod() {
        $script_path = __DIR__ . '/../scripts/process_email_queue.php';
        if (file_exists($script_path)) {
            exec("php \"$script_path\" > /dev/null 2>&1 &");
        }
    }
    
    private function getQueue() {
        if (!file_exists($this->queue_file)) {
            return [];
        }
        
        $json = file_get_contents($this->queue_file);
        return json_decode($json, true) ?: [];
    }
    
    private function saveQueue($queue) {
        $json = json_encode($queue, JSON_PRETTY_PRINT);
        file_put_contents($this->queue_file, $json, LOCK_EX);
    }
    
    public function processQueue() {
        $queue = $this->getQueue();
        $processed = 0;
        $failed = 0;
        
        foreach ($queue as $key => $email) {
            if ($email['status'] === 'queued' && $email['retries'] < $this->max_retries) {
                try {
                    $emailHelper = new EmailHelper();
                    $success = $emailHelper->sendEmail($email['to_email'], $email['to_name'], $email['subject'], $email['body']);
                    
                    if ($success) {
                        $queue[$key]['status'] = 'sent';
                        $queue[$key]['sent_at'] = date('Y-m-d H:i:s');
                        $processed++;
                    } else {
                        $queue[$key]['retries']++;
                        $queue[$key]['last_error'] = 'Send failed';
                        $failed++;
                    }
                } catch (Exception $e) {
                    $queue[$key]['retries']++;
                    $queue[$key]['last_error'] = $e->getMessage();
                    $failed++;
                }
                
                // Remove old sent emails (keep only last 100)
                if ($email['status'] === 'sent') {
                    $this->cleanupOldSentEmails($queue);
                }
            }
        }
        
        $this->saveQueue($queue);
        
        // Clean up lock file
        $lock_file = __DIR__ . '/../logs/email_processor.lock';
        if (file_exists($lock_file)) {
            unlink($lock_file);
        }
        
        return ['processed' => $processed, 'failed' => $failed];
    }
    
    private function cleanupOldSentEmails(&$queue) {
        $sent_emails = array_filter($queue, function($email) {
            return $email['status'] === 'sent';
        });
        
        if (count($sent_emails) > 100) {
            // Sort by sent_at and keep only latest 100
            usort($sent_emails, function($a, $b) {
                return strtotime($b['sent_at'] ?? '0') - strtotime($a['sent_at'] ?? '0');
            });
            
            $sent_emails = array_slice($sent_emails, 0, 100);
            
            // Rebuild queue with only kept sent emails
            $queue = array_filter($queue, function($email) {
                return $email['status'] !== 'sent';
            });
            
            $queue = array_merge($queue, $sent_emails);
        }
    }
}

// Fast email sender with immediate response
class FastEmailSender {
    private $async_processor;
    
    public function __construct() {
        $this->async_processor = new AsyncEmailProcessor();
    }
    
    public function queueNewRequestNotification($email_data) {
        $subject = "Yêu cầu mới #" . $email_data['id'] . ": " . $email_data['title'];
        
        $body = "Yêu cầu mới đã được tạo:\n\n";
        $body .= "ID: #" . $email_data['id'] . "\n";
        $body .= "Tiêu đề: " . $email_data['title'] . "\n";
        $body .= "Người tạo: " . $email_data['requester_name'] . "\n";
        $body .= "Danh mục: " . $email_data['category'] . "\n";
        $body .= "Ưu tiên: " . $email_data['priority'] . "\n";
        $body .= "Mô tả: " . $email_data['description'] . "\n\n";
        $body .= "Xem chi tiết: http://localhost/it-service-request/request-detail.html?id=" . $email_data['id'] . "\n\n";
        $body .= "Trân trọng,\n";
        $body .= "IT Service Request System";
        
        // Queue for staff and admin
        $this->queueForRole('staff', $subject, $body);
        $this->queueForRole('admin', $subject, $body);
    }
    
    private function queueForRole($role, $subject, $body) {
        global $db;
        
        try {
            $stmt = $db->prepare("SELECT email, full_name FROM users WHERE role = ? AND email IS NOT NULL AND email != ''");
            $stmt->execute([$role]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($users as $user) {
                $this->async_processor->queueEmail($user['email'], $user['full_name'], $subject, $body);
            }
        } catch (Exception $e) {
            error_log("Failed to queue emails for role $role: " . $e->getMessage());
        }
    }
}
?>
