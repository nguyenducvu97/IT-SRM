<?php
// Advanced Notification Helper with Email Integration and Browser Push Support
// Suppress PHP warnings to prevent JSON corruption
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/PHPMailerEmailHelper.php';

class NotificationHelper {
    private $db;
    private $emailHelper;
    
    public function __construct($database = null) {
        if ($database) {
            $this->db = $database;
        } else {
            $this->db = getDatabaseConnection();
        }
        $this->emailHelper = new PHPMailerEmailHelper();
    }
    
    /**
     * Create notification with email integration
     */
    public function createNotification($userId, $title, $message, $type = 'info', $relatedId = null, $relatedType = null, $sendEmail = true) {
        try {
            // Create in-app notification
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, title, message, type, related_id, related_type)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $result = $stmt->execute([$userId, $title, $message, $type, $relatedId, $relatedType]);
            
            // Send email if requested
            if ($sendEmail && $result) {
                $this->sendNotificationEmail($userId, $title, $message, $type, $relatedId, $relatedType);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Failed to create notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send email notification to user
     */
    private function sendNotificationEmail($userId, $title, $message, $type, $relatedId, $relatedType) {
        try {
            // Use PHPMailerEmailHelper for consistent email templates
            require_once __DIR__ . '/PHPMailerEmailHelper.php';
            $emailHelper = new PHPMailerEmailHelper();
            
            // Get user details
            $stmt = $this->db->prepare("SELECT email, full_name FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($userData) {
                $subject = $title;
                $email_body = $title . "\n\n";
                $email_body .= "Nội dung: " . $message . "\n\n";
                
                // Add link based on related type
                if ($relatedType === 'request' && $relatedId) {
                    $email_body .= "Xem chi tiết: http://localhost/it-service-request/request-detail.html?id=" . $relatedId . "\n\n";
                }
                
                $email_body .= "Trân trọng,\n";
                $email_body .= "IT Service Request System";
                
                return $emailHelper->sendEmail($userData['email'], $userData['full_name'], $subject, $email_body);
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Failed to send notification email: " . $e->getMessage());
            return false;
        }
    }
            /*
            // Get user email
            $stmt = $this->db->prepare("SELECT email, full_name FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return false;
            }
            
            // Generate email template based on type
            $emailBody = $this->generateEmailTemplate($title, $message, $type, $relatedId, $relatedType, $user['full_name']);
            
            // Send email
            $subject = '=?UTF-8?B?' . base64_encode($title) . '?=';
            return $this->emailHelper->sendEmail($user['email'], $user['full_name'], $subject, $emailBody);
            
        } catch (Exception $e) {
            error_log("Failed to send notification email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate email template based on notification type
     */
    private function generateEmailTemplate($title, $message, $type, $relatedId, $relatedType, $userName) {
        $typeColors = [
            'info' => '#007bff',
            'success' => '#28a745', 
            'warning' => '#ffc107',
            'error' => '#dc3545'
        ];
        
        $typeIcons = [
            'info' => '🔵',
            'success' => '🟢',
            'warning' => '🟡', 
            'error' => '🔴'
        ];
        
        $color = $typeColors[$type] ?? '#007bff';
        $icon = $typeIcons[$type] ?? '🔵';
        
        $relatedLink = '';
        if ($relatedId && $relatedType) {
            switch($relatedType) {
                case 'request':
                    $relatedLink = "<p><a href='http://localhost/it-service-request/request-detail.html?id={$relatedId}' style='background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; display: inline-block;'>Xem chi tiết yêu cầu</a></p>";
                    break;
                case 'support_request':
                    // For support requests, we need to link to the original service request
                    // Get the service_request_id from the support request
                    try {
                        $pdo = require_once 'config/database.php';
                        if ($pdo) {
                            $stmt = $pdo->prepare("SELECT service_request_id FROM support_requests WHERE id = ?");
                            $stmt->execute([$relatedId]);
                            $result = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($result && $result['service_request_id']) {
                                $relatedLink = "<p><a href='http://localhost/it-service-request/request-detail.html?id={$result['service_request_id']}' style='background: #ffc107; color: black; padding: 8px 16px; text-decoration: none; border-radius: 4px; display: inline-block;'>Xem chi tiết yêu cầu</a></p>";
                            } else {
                                $relatedLink = "<p><a href='http://localhost/it-service-request/support-requests.html' style='background: #ffc107; color: black; padding: 8px 16px; text-decoration: none; border-radius: 4px; display: inline-block;'>Xem yêu cầu hỗ trợ</a></p>";
                            }
                        } else {
                            $relatedLink = "<p><a href='http://localhost/it-service-request/support-requests.html' style='background: #ffc107; color: black; padding: 8px 16px; text-decoration: none; border-radius: 4px; display: inline-block;'>Xem yêu cầu hỗ trợ</a></p>";
                        }
                    } catch (Exception $e) {
                        // Fallback to support requests list if database query fails
                        $relatedLink = "<p><a href='http://localhost/it-service-request/support-requests.html' style='background: #ffc107; color: black; padding: 8px 16px; text-decoration: none; border-radius: 4px; display: inline-block;'>Xem yêu cầu hỗ trợ</a></p>";
                    }
                    break;
                case 'reject_request':
                    // For reject requests, we need to link to the original service request
                    // Get the service_request_id from the reject request
                    try {
                        $pdo = require_once 'config/database.php';
                        if ($pdo) {
                            $stmt = $pdo->prepare("SELECT service_request_id FROM reject_requests WHERE id = ?");
                            $stmt->execute([$relatedId]);
                            $result = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($result && $result['service_request_id']) {
                                $relatedLink = "<p><a href='http://localhost/it-service-request/request-detail.html?id={$result['service_request_id']}' style='background: #dc3545; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; display: inline-block;'>Xem chi tiết yêu cầu</a></p>";
                            } else {
                                $relatedLink = "<p><a href='http://localhost/it-service-request/reject-requests.html' style='background: #dc3545; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; display: inline-block;'>Xem yêu cầu từ chối</a></p>";
                            }
                        } else {
                            $relatedLink = "<p><a href='http://localhost/it-service-request/reject-requests.html' style='background: #dc3545; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; display: inline-block;'>Xem yêu cầu từ chối</a></p>";
                        }
                    } catch (Exception $e) {
                        // Fallback to reject requests list if database query fails
                        $relatedLink = "<p><a href='http://localhost/it-service-request/reject-requests.html' style='background: #dc3545; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; display: inline-block;'>Xem yêu cầu từ chối</a></p>";
                    }
                    break;
            }
        }
        
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #dee2e6; border-radius: 8px; background-color: #f8f9fa;'>
            <div style='text-align: center; margin-bottom: 20px;'>
                <h1 style='color: #343a40; margin: 0;'>IT Service Request System</h1>
                <p style='color: #6c757d; margin: 5px 0 0 0;'>Hệ thống yêu cầu dịch vụ IT</p>
            </div>
            
            <div style='background: white; padding: 20px; border-radius: 6px; border-left: 4px solid {$color};'>
                <div style='display: flex; align-items: center; margin-bottom: 15px;'>
                    <span style='font-size: 24px; margin-right: 10px;'>{$icon}</span>
                    <h2 style='color: #343a40; margin: 0; font-size: 18px;'>{$title}</h2>
                </div>
                
                <div style='background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 10px 0;'>
                    <p style='color: #495057; margin: 0; line-height: 1.5;'>{$message}</p>
                </div>
                
                {$relatedLink}
            </div>
            
            <div style='margin-top: 20px; padding: 15px; background: #e9ecef; border-radius: 6px; text-align: center;'>
                <p style='color: #6c757d; margin: 0; font-size: 14px;'>
                    <strong>Chào {$userName},</strong><br>
                    Bạn nhận được thông báo này từ hệ thống IT Service Request.
                </p>
            </div>
            
            <div style='margin-top: 20px; text-align: center; border-top: 1px solid #dee2e6; padding-top: 15px;'>
                <p style='color: #6c757d; margin: 0; font-size: 12px;'>
                    Đây là email tự động, vui lòng không trả lời email này.<br>
                    Nếu cần hỗ trợ, vui lòng liên hệ IT Department.
                </p>
            </div>
        </div>";
    }
    
    /**
     * Notify multiple users
     */
    public function notifyUsers($userIds, $title, $message, $type = 'info', $relatedId = null, $relatedType = null, $sendEmail = true) {
        $successCount = 0;
        
        foreach ($userIds as $userId) {
            if ($this->createNotification($userId, $title, $message, $type, $relatedId, $relatedType, $sendEmail)) {
                $successCount++;
            }
        }
        
        return $successCount;
    }
    
    /**
     * Notify all users with specific role
     */
    public function notifyRole($role, $title, $message, $type = 'info', $relatedId = null, $relatedType = null, $sendEmail = true) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE role = ?");
            $stmt->execute([$role]);
            $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($users)) {
                return $this->notifyUsers($users, $title, $message, $type, $relatedId, $relatedType, $sendEmail);
            }
            
            return 0;
        } catch (Exception $e) {
            error_log("Failed to notify role {$role}: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Notify request participants (owner, assigned staff, admins)
     */
    public function notifyRequestParticipants($requestId, $title, $message, $type = 'info', $sendEmail = true) {
        try {
            // Get request owner and assigned staff
            $stmt = $this->db->prepare("
                SELECT user_id, assigned_to 
                FROM service_requests 
                WHERE id = ?
            ");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $notifyUsers = [];
            
            // Add request owner
            $notifyUsers[] = $request['user_id'];
            
            // Add assigned staff if exists
            if ($request['assigned_to']) {
                $notifyUsers[] = $request['assigned_to'];
            }
            
            // Add all admin users
            $stmt = $this->db->prepare("SELECT id FROM users WHERE role = 'admin'");
            $stmt->execute();
            $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($admins as $adminId) {
                $notifyUsers[] = $adminId;
            }
            
            // Remove duplicates
            $notifyUsers = array_unique($notifyUsers);
            
            if (!empty($notifyUsers)) {
                $this->notifyUsers($notifyUsers, $title, $message, $type, $requestId, 'request', $sendEmail);
            }
            
            return count($notifyUsers);
        } catch (Exception $e) {
            error_log("Failed to notify request participants: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get unread notification count for user
     */
    public function getUnreadCount($userId) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (Exception $e) {
            error_log("Failed to get unread count: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId, $userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE notifications 
                SET is_read = TRUE, read_at = NOW() 
                WHERE id = ? AND user_id = ?
            ");
            return $stmt->execute([$notificationId, $userId]);
        } catch (Exception $e) {
            error_log("Failed to mark notification as read: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead($userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE notifications 
                SET is_read = TRUE, read_at = NOW() 
                WHERE user_id = ? AND is_read = FALSE
            ");
            return $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("Failed to mark all notifications as read: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get notifications for user with pagination
     */
    public function getUserNotifications($userId, $limit = 20, $offset = 0) {
        try {
            // Use simple query without parameterized LIMIT/OFFSET for MySQL compatibility
            $limit = (int)$limit;
            $offset = (int)$offset;
            
            $stmt = $this->db->prepare("
                SELECT id, title, message, type, related_id, related_type, 
                       is_read, created_at, read_at
                FROM notifications 
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add time_ago calculation for each notification
            foreach ($result as &$notif) {
                $notif['time_ago'] = $this->getTimeAgo($notif['created_at']);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Failed to get user notifications: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calculate time ago string
     */
    private function getTimeAgo($datetime) {
        $time = strtotime($datetime);
        $now = time();
        $diff = $now - $time;
        
        if ($diff < 60) {
            return "Vài giây";
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . " phút";
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . " giờ";
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . " ngày";
        } else {
            return date('d/m/Y', $time);
        }
    }
}
