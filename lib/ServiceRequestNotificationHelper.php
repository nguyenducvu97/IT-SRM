<?php
// Service Request Notification Helper - Role-based notification system
// Suppress PHP warnings to prevent JSON corruption
error_reporting(0);
ini_set('display_errors', 0);
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/NotificationHelper.php';

class ServiceRequestNotificationHelper {
    private $db;
    private $notificationHelper;
    
    public function __construct() {
        $this->db = getDatabaseConnection();
        $this->notificationHelper = new NotificationHelper();
    }
    
    /**
     * ========================================
     * USER NOTIFICATIONS (Người dùng/Yêu cầu)
     * ========================================
     */
    
    /**
     * Notify user when request status changes to In Progress
     */
    public function notifyUserRequestInProgress($requestId, $userId, $assignedStaffName = null) {
        $title = "Yêu cầu đang được xử lý";
        $message = "Yêu cầu #{$requestId} của bạn đã được nhân viên IT tiếp nhận và đang xử lý." . 
                   ($assignedStaffName ? " Nhân viên phụ trách: {$assignedStaffName}" : "");
        
        return $this->notificationHelper->createNotification(
            $userId, 
            $title, 
            $message, 
            'info', 
            $requestId, 
            'service_request'
        );
    }
    
    /**
     * Notify user when request status changes to Pending Approval
     */
    public function notifyUserRequestPendingApproval($requestId, $userId) {
        $title = "Yêu cầu đang chờ phê duyệt";
        $message = "Yêu cầu #{$requestId} của bạn đang chờ Admin xem xét và phê duyệt.";
        
        return $this->notificationHelper->createNotification(
            $userId, 
            $title, 
            $message, 
            'warning', 
            $requestId, 
            'service_request'
        );
    }
    
    /**
     * Notify user when request is resolved/completed
     */
    public function notifyUserRequestResolved($requestId, $userId, $resolutionDetails = null) {
        $title = "Yêu cầu đã hoàn thành";
        $message = "Yêu cầu #{$requestId} của bạn đã được xử lý thành công. " .
                   "Vui lòng kiểm tra kết quả và đưa ra đánh giá về chất lượng dịch vụ." .
                   ($resolutionDetails ? " Chi tiết: {$resolutionDetails}" : "");
        
        return $this->notificationHelper->createNotification(
            $userId, 
            $title, 
            $message, 
            'success', 
            $requestId, 
            'service_request'
        );
    }
    
    /**
     * Notify user when request is rejected
     */
    public function notifyUserRequestRejected($requestId, $userId, $rejectReason = null) {
        $title = "Yêu cầu đã bị từ chối";
        $message = "Yêu cầu #{$requestId} của bạn đã bị từ chối." .
                   ($rejectReason ? " Lý do: {$rejectReason}" : " Vui lòng liên hệ IT để biết thêm chi tiết.");
        
        return $this->notificationHelper->createNotification(
            $userId, 
            $title, 
            $message, 
            'error', 
            $requestId, 
            'service_request'
        );
    }
    
    /**
     * ========================================
     * STAFF NOTIFICATIONS (Nhân viên IT)
     * ========================================
     */
    
    /**
     * Notify staff when user creates new request
     */
    public function notifyStaffNewRequest($requestId, $requestTitle, $requesterName, $categoryName = null) {
        // Get all staff users
        $staffUsers = $this->getUsersByRole(['staff', 'admin']);
        $title = "Yêu cầu mới cần xử lý";
        $message = "Người dùng {$requesterName} đã tạo yêu cầu mới: #{$requestId} - {$requestTitle}" .
                   ($categoryName ? " (Danh mục: {$categoryName})" : "");
        
        $results = [];
        foreach ($staffUsers as $staff) {
            $results[] = $this->notificationHelper->createNotification(
                $staff['id'], 
                $title, 
                $message, 
                'info', 
                $requestId, 
                'service_request'
            );
        }
        
        return !in_array(false, $results);
    }
    
    /**
     * Notify staff when user provides feedback/rating
     */
    public function notifyStaffUserFeedback($requestId, $userId, $rating = null, $feedbackText = null, $requesterName = null) {
        // Get assigned staff and all admins
        $assignedStaff = $this->getAssignedStaff($requestId);
        $adminUsers = $this->getUsersByRole(['admin']);
        $notifyUsers = array_merge($assignedStaff, $adminUsers);
        
        // Remove duplicates
        $notifyUsers = array_unique($notifyUsers, SORT_REGULAR);
        
        $title = "Phản hồi từ người dùng";
        $message = "Người dùng {$requesterName} đã đưa ra đánh giá cho yêu cầu #{$requestId}" .
                   ($rating ? " với {$rating}/5 sao" : "") .
                   ($feedbackText ? ". Phản hồi: {$feedbackText}" : "");
        
        $results = [];
        foreach ($notifyUsers as $user) {
            $notificationType = $rating && $rating >= 4 ? 'success' : 'info';
            $results[] = $this->notificationHelper->createNotification(
                $user['id'], 
                $title, 
                $message, 
                $notificationType, 
                $requestId, 
                'service_request'
            );
        }
        
        return !in_array(false, $results);
    }
    
    /**
     * Notify staff when admin approves a request
     */
    public function notifyStaffAdminApproved($requestId, $requestTitle, $adminName = null) {
        $staffUsers = $this->getUsersByRole(['staff']);
        $title = "Yêu cầu được Admin phê duyệt";
        $message = "Admin đã phê duyệt yêu cầu #{$requestId} - {$requestTitle}" .
                   ($adminName ? " bởi {$adminName}" : "") . ". Vui lòng bắt đầu thực hiện kỹ thuật.";
        
        $results = [];
        foreach ($staffUsers as $staff) {
            $results[] = $this->notificationHelper->createNotification(
                $staff['id'], 
                $title, 
                $message, 
                'success', 
                $requestId, 
                'service_request'
            );
        }
        
        return !in_array(false, $results);
    }
    
    /**
     * Notify staff when admin rejects a request
     */
    public function notifyStaffAdminRejected($requestId, $requestTitle, $adminName = null, $rejectReason = null) {
        $assignedStaff = $this->getAssignedStaff($requestId);
        $title = "Yêu cầu bị Admin từ chối";
        $message = "Admin đã từ chối yêu cầu #{$requestId} - {$requestTitle}" .
                   ($adminName ? " bởi {$adminName}" : "") .
                   ($rejectReason ? ". Lý do: {$rejectReason}" : "") . 
                   ". Vui lòng dừng xử lý hoặc giải thích lại cho người dùng.";
        
        $results = [];
        foreach ($assignedStaff as $staff) {
            $results[] = $this->notificationHelper->createNotification(
                $staff['id'], 
                $title, 
                $message, 
                'warning', 
                $requestId, 
                'service_request'
            );
        }
        
        return !in_array(false, $results);
    }
    
    /**
     * ========================================
     * ADMIN NOTIFICATIONS (Quản trị viên)
     * ========================================
     */
    
    /**
     * Notify admin when user creates new request
     */
    public function notifyAdminNewRequest($requestId, $requestTitle, $requesterName, $categoryName = null) {
        $adminUsers = $this->getUsersByRole(['admin']);
        $title = "Yêu cầu mới trong hệ thống";
        $message = "Người dùng {$requesterName} đã tạo yêu cầu mới: #{$requestId} - {$requestTitle}" .
                   ($categoryName ? " (Danh mục: {$categoryName})" : "") . 
                   ". Tổng lượng yêu cầu đang đổ vào hệ thống.";
        
        $results = [];
        foreach ($adminUsers as $admin) {
            $results[] = $this->notificationHelper->createNotification(
                $admin['id'], 
                $title, 
                $message, 
                'info', 
                $requestId, 
                'service_request'
            );
        }
        
        return !in_array(false, $results);
    }
    
    /**
     * Notify admin when staff changes request status
     */
    public function notifyAdminStatusChange($requestId, $oldStatus, $newStatus, $staffName = null, $requestTitle = null) {
        $adminUsers = $this->getUsersByRole(['admin']);
        $title = "Thay đổi trạng thái yêu cầu";
        $message = "Nhân viên {$staffName} đã thay đổi trạng thái yêu cầu #{$requestId}" .
                   ($requestTitle ? " - {$requestTitle}" : "") . 
                   " từ '{$oldStatus}' thành '{$newStatus}'.";
        
        $results = [];
        foreach ($adminUsers as $admin) {
            $notificationType = in_array($newStatus, ['resolved', 'completed']) ? 'success' : 
                               (in_array($newStatus, ['rejected']) ? 'error' : 'info');
            $results[] = $this->notificationHelper->createNotification(
                $admin['id'], 
                $title, 
                $message, 
                $notificationType, 
                $requestId, 
                'service_request'
            );
        }
        
        return !in_array(false, $results);
    }
    
    /**
     * Notify admin when staff creates support request (escalation)
     */
    public function notifyAdminSupportRequest($requestId, $supportDetails, $staffName = null, $requestTitle = null) {
        $adminUsers = $this->getUsersByRole(['admin']);
        $title = "Yêu cầu hỗ trợ kỹ thuật (Escalation)";
        $message = "Nhân viên {$staffName} gặp vấn đề kỹ thuật khó và cần Admin can thiệp" .
                   ($requestTitle ? " cho yêu cầu #{$requestId} - {$requestTitle}" : "") . 
                   ". Chi tiết: {$supportDetails}";
        
        $results = [];
        foreach ($adminUsers as $admin) {
            $results[] = $this->notificationHelper->createNotification(
                $admin['id'], 
                $title, 
                $message, 
                'warning', 
                $requestId, 
                'support_request'
            );
        }
        
        return !in_array(false, $results);
    }
    
    /**
     * Notify admin when staff creates rejection request
     */
    public function notifyAdminRejectionRequest($requestId, $rejectReason, $staffName = null, $requestTitle = null) {
        $adminUsers = $this->getUsersByRole(['admin']);
        $title = "Yêu cầu từ chối cần xác nhận";
        $message = "Nhân viên {$staffName} nhận thấy yêu cầu #{$requestId}" .
                   ($requestTitle ? " - {$requestTitle}" : "") . 
                   " vi phạm chính sách hoặc không khả thi và cần Admin xác nhận trước khi hủy." .
                   " Lý do: {$rejectReason}";
        
        $results = [];
        foreach ($adminUsers as $admin) {
            $results[] = $this->notificationHelper->createNotification(
                $admin['id'], 
                $title, 
                $message, 
                'warning', 
                $requestId, 
                'reject_request'
            );
        }
        
        return !in_array(false, $results);
    }
    
    /**
     * ========================================
     * HELPER FUNCTIONS
     * ========================================
     */
    
    /**
     * Get users by role
     */
    public function getUsersByRole($roles) {
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        // Handle single role vs multiple roles
        if (count($roles) === 1) {
            $stmt = $this->db->prepare("
                SELECT id, username, full_name 
                FROM users 
                WHERE role = ?
            ");
            $stmt->execute([$roles[0]]);
        } else {
            $placeholders = str_repeat('?,', count($roles) - 1) . '?';
            $stmt = $this->db->prepare("
                SELECT id, username, full_name 
                FROM users 
                WHERE role IN ($placeholders)
            ");
            $stmt->execute($roles);
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get assigned staff for a request
     */
    private function getAssignedStaff($requestId) {
        $stmt = $this->db->prepare("
            SELECT assigned.id, assigned.username, assigned.full_name
            FROM service_requests sr
            LEFT JOIN users assigned ON sr.assigned_to = assigned.id
            WHERE sr.id = ? AND sr.assigned_to IS NOT NULL
        ");
        $stmt->execute([$requestId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get request details for notifications
     */
    public function getRequestDetails($requestId) {
        $stmt = $this->db->prepare("
            SELECT sr.*, u.full_name as requester_name, c.name as category_name,
                   assigned.full_name as assigned_name
            FROM service_requests sr
            LEFT JOIN users u ON sr.user_id = u.id
            LEFT JOIN categories c ON sr.category_id = c.id
            LEFT JOIN users assigned ON sr.assigned_to = assigned.id
            WHERE sr.id = ?
        ");
        $stmt->execute([$requestId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
