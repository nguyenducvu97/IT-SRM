<?php
/**
 * Notification Helper Functions
 * Creates notifications for different user types based on actions
 */

require_once '../config/database.php';

/**
 * Create notification for multiple users based on action and user roles
 */
function createNotifications($action, $requestId, $requestData, $performedBy) {
    $db = getDatabaseConnection();
    
    // Get request details including requester and assigned staff
    $stmt = $db->prepare("
        SELECT sr.*, u.full_name as requester_name, u.email as requester_email,
               staff.full_name as staff_name, staff.email as staff_email
        FROM service_requests sr
        LEFT JOIN users u ON sr.user_id = u.id
        LEFT JOIN users staff ON sr.assigned_to = staff.id
        WHERE sr.id = ?
    ");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        return false;
    }
    
    // Get all admin users
    $adminStmt = $db->prepare("SELECT id, full_name FROM users WHERE role = 'admin'");
    $adminStmt->execute();
    $admins = $adminStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all staff users
    $staffStmt = $db->prepare("SELECT id, full_name FROM users WHERE role = 'staff'");
    $staffStmt->execute();
    $staffUsers = $staffStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $performedByName = $performedBy['full_name'] ?? 'Unknown';
    $performedByRole = $performedBy['role'] ?? 'unknown';
    
    $notifications = [];
    
    switch ($action) {
        case 'status_update':
            $statusText = getStatusText($requestData['new_status']);
            $title = "📝 Trạng thái yêu cầu #{$requestId} đã cập nhật";
            $message = "Yêu cầu #{$requestId} đã được {$performedByName} ({$performedByRole}) cập nhật trạng thái thành '{$statusText}'";
            
            // Staff updates -> Admin and Requester get notified
            if ($performedByRole === 'staff') {
                // Notify admins
                foreach ($admins as $admin) {
                    if ($admin['id'] != $performedBy['id']) {
                        $notifications[] = [
                            'user_id' => $admin['id'],
                            'title' => $title,
                            'message' => $message,
                            'type' => 'info',
                            'related_id' => $requestId,
                            'related_type' => 'request'
                        ];
                    }
                }
                
                // Notify requester
                if ($request['user_id'] && $request['user_id'] != $performedBy['id']) {
                    $notifications[] = [
                        'user_id' => $request['user_id'],
                        'title' => $title,
                        'message' => $message,
                        'type' => 'info',
                        'related_id' => $requestId,
                        'related_type' => 'request'
                    ];
                }
            }
            // Admin updates -> Staff get notified
            elseif ($performedByRole === 'admin') {
                foreach ($staffUsers as $staff) {
                    if ($staff['id'] != $performedBy['id']) {
                        $notifications[] = [
                            'user_id' => $staff['id'],
                            'title' => $title,
                            'message' => $message,
                            'type' => 'info',
                            'related_id' => $requestId,
                            'related_type' => 'request'
                        ];
                    }
                }
            }
            break;
            
        case 'request_created':
            $title = "🆕 Yêu cầu mới #{$requestId}";
            $message = "Yêu cầu mới #{$requestId} đã được tạo bởi {$request['requester_name']}: '{$request['title']}'";
            
            // Notify admins and staff
            foreach ($admins as $admin) {
                $notifications[] = [
                    'user_id' => $admin['id'],
                    'title' => $title,
                    'message' => $message,
                    'type' => 'info',
                    'related_id' => $requestId,
                    'related_type' => 'request'
                ];
            }
            
            foreach ($staffUsers as $staff) {
                $notifications[] = [
                    'user_id' => $staff['id'],
                    'title' => $title,
                    'message' => $message,
                    'type' => 'info',
                    'related_id' => $requestId,
                    'related_type' => 'request'
                ];
            }
            break;
            
        case 'request_assigned':
            $title = "✅ Yêu cầu #{$requestId} đã được phân công";
            $message = "Yêu cầu #{$requestId} đã được phân công cho {$request['staff_name']}";
            
            // Notify assigned staff
            if ($request['assigned_to']) {
                $notifications[] = [
                    'user_id' => $request['assigned_to'],
                    'title' => $title,
                    'message' => $message,
                    'type' => 'success',
                    'related_id' => $requestId,
                    'related_type' => 'request'
                ];
            }
            
            // Notify requester
            if ($request['user_id']) {
                $notifications[] = [
                    'user_id' => $request['user_id'],
                    'title' => $title,
                    'message' => $message,
                    'type' => 'success',
                    'related_id' => $requestId,
                    'related_type' => 'request'
                ];
            }
            break;
            
        case 'request_resolved':
            $title = "✅ Yêu cầu #{$requestId} đã được giải quyết";
            $message = "Yêu cầu #{$requestId} đã được giải quyết bởi {$performedByName}";
            
            // Notify requester
            if ($request['user_id'] && $request['user_id'] != $performedBy['id']) {
                $notifications[] = [
                    'user_id' => $request['user_id'],
                    'title' => $title,
                    'message' => $message,
                    'type' => 'success',
                    'related_id' => $requestId,
                    'related_type' => 'request'
                ];
            }
            
            // Notify admins (if staff resolved)
            if ($performedByRole === 'staff') {
                foreach ($admins as $admin) {
                    if ($admin['id'] != $performedBy['id']) {
                        $notifications[] = [
                            'user_id' => $admin['id'],
                            'title' => $title,
                            'message' => $message,
                            'type' => 'success',
                            'related_id' => $requestId,
                            'related_type' => 'request'
                        ];
                    }
                }
            }
            break;
            
        case 'request_closed':
            $title = "🔒 Yêu cầu #{$requestId} đã được đóng";
            $message = "Yêu cầu #{$requestId} đã được đóng bởi {$performedByName}";
            
            // Notify admins and staff (if user closed)
            if ($performedByRole === 'user') {
                foreach ($admins as $admin) {
                    $notifications[] = [
                        'user_id' => $admin['id'],
                        'title' => $title,
                        'message' => $message,
                        'type' => 'info',
                        'related_id' => $requestId,
                        'related_type' => 'request'
                    ];
                }
                
                foreach ($staffUsers as $staff) {
                    $notifications[] = [
                        'user_id' => $staff['id'],
                        'title' => $title,
                        'message' => $message,
                        'type' => 'info',
                        'related_id' => $requestId,
                        'related_type' => 'request'
                    ];
                }
            }
            break;
            
        case 'request_rated':
            $rating = $requestData['rating'] ?? 0;
            $feedback = $requestData['feedback'] ?? '';
            $hasFeedback = $requestData['has_feedback'] ?? false;
            
            $stars = str_repeat('⭐', $rating) . str_repeat('☆', 5 - $rating);
            $title = "⭐ Yêu cầu #{$requestId} đã được đánh giá";
            
            if ($hasFeedback) {
                $message = "Yêu cầu #{$requestId} đã được {$performedByName} đánh giá {$stars} với phản hồi: \"" . substr($feedback, 0, 100) . (strlen($feedback) > 100 ? '...' : '') . "\"";
            } else {
                $message = "Yêu cầu #{$requestId} đã được {$performedByName} đánh giá {$stars}";
            }
            
            // Notify admins and staff about rating
            foreach ($admins as $admin) {
                if ($admin['id'] != $performedBy['id']) {
                    $notifications[] = [
                        'user_id' => $admin['id'],
                        'title' => $title,
                        'message' => $message,
                        'type' => 'success',
                        'related_id' => $requestId,
                        'related_type' => 'request'
                    ];
                }
            }
            
            foreach ($staffUsers as $staff) {
                if ($staff['id'] != $performedBy['id']) {
                    $notifications[] = [
                        'user_id' => $staff['id'],
                        'title' => $title,
                        'message' => $message,
                        'type' => 'success',
                        'related_id' => $requestId,
                        'related_type' => 'request'
                    ];
                }
            }
            break;
    }
    
    // Insert all notifications
    foreach ($notifications as $notification) {
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, title, message, type, related_id, related_type, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            $notification['user_id'],
            $notification['title'],
            $notification['message'],
            $notification['type'],
            $notification['related_id'],
            $notification['related_type']
        ]);
    }
    
    return true;
}

/**
 * Get status text in Vietnamese
 */
function getStatusText($status) {
    $statuses = [
        'open' => 'Mở',
        'in_progress' => 'Đang xử lý',
        'resolved' => 'Đã giải quyết',
        'rejected' => 'Đã từ chối',
        'closed' => 'Đã đóng',
        'cancelled' => 'Đã hủy'
    ];
    return $statuses[$status] ?? $status;
}

/**
 * Get current user info from session
 */
function getCurrentUserFromHelper() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT id, full_name, role, email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
