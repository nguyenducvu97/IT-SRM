<?php
// TRUE Background Email Processor
// Chạy hoàn toàn ngầm mà không cần mở file mới

class BackgroundEmailProcessor {
    private $queueFile;
    private $lockFile;
    
    public function __construct() {
        $this->queueFile = __DIR__ . '/../logs/email_queue.json';
        $this->lockFile = __DIR__ . '/../logs/email_processor.lock';
    }
    
    // Process emails directly without opening new files
    public function processEmailsInBackground() {
        // Check if already processing
        if ($this->isAlreadyProcessing()) {
            return ['status' => 'already_running', 'message' => 'Email processor already running'];
        }
        
        // Create lock file
        $this->createLock();
        
        try {
            // Process queue directly
            $result = $this->processQueue();
            
            // Clean up lock
            $this->removeLock();
            
            return [
                'status' => 'success',
                'processed' => $result['processed'],
                'failed' => $result['failed'],
                'message' => 'Emails processed in background'
            ];
            
        } catch (Exception $e) {
            $this->removeLock();
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    // Check if processor is already running
    private function isAlreadyProcessing() {
        if (!file_exists($this->lockFile)) {
            return false;
        }
        
        $lockTime = filemtime($this->lockFile);
        $maxAge = 300; // 5 minutes max
        
        return (time() - $lockTime) < $maxAge;
    }
    
    // Create lock file
    private function createLock() {
        touch($this->lockFile);
    }
    
    // Remove lock file
    private function removeLock() {
        if (file_exists($this->lockFile)) {
            unlink($this->lockFile);
        }
    }
    
    // Process email queue directly
    private function processQueue() {
        if (!file_exists($this->queueFile)) {
            return ['processed' => 0, 'failed' => 0];
        }
        
        $queueData = json_decode(file_get_contents($this->queueFile), true);
        if (!$queueData) {
            return ['processed' => 0, 'failed' => 0];
        }
        
        $processed = 0;
        $failed = 0;
        $updatedQueue = [];
        
        foreach ($queueData as $email) {
            if ($email['status'] === 'processed') {
                $updatedQueue[] = $email;
                continue;
            }
            
            if ($email['status'] === 'queued' && $email['attempts'] < 3) {
                try {
                    // Send email directly
                    if ($this->sendEmailDirectly($email)) {
                        $email['status'] = 'processed';
                        $email['processed_at'] = date('Y-m-d H:i:s');
                        $processed++;
                        
                        if (defined('ENABLE_BACKGROUND_LOGGING') && ENABLE_BACKGROUND_LOGGING) {
                            error_log("Background email sent: {$email['id']} to {$email['to']}");
                        }
                    } else {
                        $email['attempts']++;
                        $failed++;
                    }
                } catch (Exception $e) {
                    $email['attempts']++;
                    $failed++;
                    
                    if (defined('ENABLE_BACKGROUND_LOGGING') && ENABLE_BACKGROUND_LOGGING) {
                        error_log("Background email failed: {$email['id']} - " . $e->getMessage());
                    }
                }
            }
            
            $updatedQueue[] = $email;
        }
        
        // Save updated queue
        file_put_contents($this->queueFile, json_encode($updatedQueue, JSON_PRETTY_PRINT), LOCK_EX);
        
        return ['processed' => $processed, 'failed' => $failed];
    }
    
    // Send email directly without external files
    private function sendEmailDirectly($email) {
        try {
            // Include EmailHelper
            require_once __DIR__ . '/../lib/EmailHelper.php';
            $emailHelper = new EmailHelper();
            
            return $emailHelper->sendEmail(
                $email['to'], 
                $email['toName'], 
                $email['subject'], 
                $email['body']
            );
            
        } catch (Exception $e) {
            if (defined('ENABLE_BACKGROUND_LOGGING') && ENABLE_BACKGROUND_LOGGING) {
                error_log("Direct email send error: " . $e->getMessage());
            }
            return false;
        }
    }
}

// Global processor instance
$backgroundProcessor = new BackgroundEmailProcessor();

// Process emails when called
function processEmailsInBackground() {
    if (!isset($backgroundProcessor)) {
        $backgroundProcessor = new BackgroundEmailProcessor();
    }
    return $backgroundProcessor->processEmailsInBackground();
}
?>
