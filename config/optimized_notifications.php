<?php
// Optimized Notification Helper with Batch Processing
class OptimizedNotificationHelper {
    private $db;
    private $batch_size = 50;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Create notifications for multiple users in a single query
     */
    public function notifyUsersBatch($user_ids, $title, $message, $type = 'info', $related_id = null, $related_type = null, $send_email = false) {
        if (empty($user_ids)) {
            return;
        }
        
        $start_time = microtime(true);
        
        try {
            // Prepare batch insert data
            $batch_data = [];
            $current_time = date('Y-m-d H:i:s');
            
            foreach ($user_ids as $user_id) {
                $batch_data[] = [
                    'user_id' => $user_id,
                    'title' => $title,
                    'message' => $message,
                    'type' => $type,
                    'related_id' => $related_id,
                    'related_type' => $related_type,
                    'created_at' => $current_time
                ];
            }
            
            // Insert in batches for better performance
            $this->batchInsertNotifications($batch_data);
            
            error_log("Batch notifications created in " . round((microtime(true) - $start_time) * 1000, 2) . "ms for " . count($user_ids) . " users");
            
            // Handle email asynchronously if needed
            if ($send_email) {
                $this->queueEmailNotifications($user_ids, $title, $message, $type, $related_id, $related_type);
            }
            
        } catch (Exception $e) {
            error_log("Failed to create batch notifications: " . $e->getMessage());
        }
    }
    
    private function batchInsertNotifications($batch_data) {
        // Split into smaller batches to avoid memory issues
        $chunks = array_chunk($batch_data, $this->batch_size);
        
        foreach ($chunks as $chunk) {
            $values = [];
            $params = [];
            
            foreach ($chunk as $data) {
                $values[] = "(?, ?, ?, ?, ?, ?, ?)";
                $params = array_merge($params, [
                    $data['user_id'],
                    $data['title'],
                    $data['message'],
                    $data['type'],
                    $data['related_id'],
                    $data['related_type'],
                    $data['created_at']
                ]);
            }
            
            $query = "INSERT INTO notifications (user_id, title, message, type, related_id, related_type, created_at) 
                     VALUES " . implode(',', $values);
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
        }
    }
    
    private function queueEmailNotifications($user_ids, $title, $message, $type, $related_id, $related_type) {
        // Get user emails
        $placeholders = str_repeat('?,', count($user_ids) - 1) . '?';
        $query = "SELECT id, email, full_name FROM users WHERE id IN ($placeholders) AND email IS NOT NULL AND email != ''";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($user_ids);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Queue emails for async processing
        foreach ($users as $user) {
            $subject = "Thông báo: " . $title;
            $body = "Chào {$user['full_name']},\n\n";
            $body .= $message . "\n\n";
            
            if ($related_id && $related_type) {
                $body .= "Xem chi tiết: http://localhost/it-service-request/";
                if ($related_type === 'request') {
                    $body .= "request-detail.html?id=" . $related_id;
                }
                $body .= "\n";
            }
            
            $body .= "\nTrân trọng,\nIT Service Request System";
            
            // Queue for async email processing
            $this->queueEmail($user['email'], $user['full_name'], $subject, $body);
        }
    }
    
    private function queueEmail($to_email, $to_name, $subject, $body) {
        $queue_file = __DIR__ . '/../logs/email_queue.json';
        $email_data = [
            'id' => uniqid('email_', true),
            'to_email' => $to_email,
            'to_name' => $to_name,
            'subject' => $subject,
            'body' => $body,
            'created_at' => date('Y-m-d H:i:s'),
            'retries' => 0,
            'status' => 'queued'
        ];
        
        $queue = [];
        if (file_exists($queue_file)) {
            $json = file_get_contents($queue_file);
            $queue = json_decode($json, true) ?: [];
        }
        
        $queue[] = $email_data;
        file_put_contents($queue_file, json_encode($queue, JSON_PRETTY_PRINT), LOCK_EX);
    }
    
    /**
     * Create single notification (fallback method)
     */
    public function createNotification($user_id, $title, $message, $type = 'info', $related_id = null, $related_type = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, title, message, type, related_id, related_type, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            return $stmt->execute([$user_id, $title, $message, $type, $related_id, $related_type]);
        } catch (Exception $e) {
            error_log("Failed to create notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Notify all users with a specific role
     */
    public function notifyRole($role, $title, $message, $type = 'info', $related_id = null, $related_type = null, $send_email = false) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE role = ?");
            $stmt->execute([$role]);
            $user_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($user_ids)) {
                $this->notifyUsersBatch($user_ids, $title, $message, $type, $related_id, $related_type, $send_email);
            }
        } catch (Exception $e) {
            error_log("Failed to notify role $role: " . $e->getMessage());
        }
    }
}

// Legacy compatibility function
function createNotification($pdo, $userId, $title, $message, $type = 'info', $relatedId = null, $relatedType = null) {
    $helper = new OptimizedNotificationHelper($pdo);
    return $helper->createNotification($userId, $title, $message, $type, $relatedId, $relatedType);
}

function notifyRole($pdo, $role, $title, $message, $type = 'info', $relatedId = null, $relatedType = null) {
    $helper = new OptimizedNotificationHelper($pdo);
    $helper->notifyRole($role, $title, $message, $type, $relatedId, $relatedType, false);
}
?>
