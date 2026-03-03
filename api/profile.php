<?php
require_once '../config/database.php';
require_once '../config/session.php';

header('Content-Type: application/json');

// Check authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['user_role'];

try {
    $pdo = getDatabaseConnection();
    
    if ($method == 'GET') {
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        
        if ($action == 'profile') {
            // Get current user profile
            $stmt = $pdo->prepare("
                SELECT id, username, full_name, email, phone, role, created_at, updated_at
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$current_user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Remove sensitive data
                unset($user['password']);
                echo json_encode(['success' => true, 'data' => $user]);
            } else {
                echo json_encode(['success' => false, 'message' => 'User not found']);
            }
        }
        
        elseif ($action == 'all_users' && $current_user_role === 'admin') {
            // Admin only: Get all users for role management
            $stmt = $pdo->prepare("
                SELECT id, username, full_name, email, phone, role, created_at, updated_at
                FROM users 
                ORDER BY created_at DESC
            ");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Remove sensitive data
            foreach ($users as &$user) {
                unset($user['password']);
            }
            
            echo json_encode(['success' => true, 'data' => $users]);
        }
        
        else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    }
    
    elseif ($method == 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        if ($action == 'update_profile') {
            // Update user profile
            $allowed_fields = ['full_name', 'email', 'phone'];
            $updates = [];
            $params = [];
            
            foreach ($allowed_fields as $field) {
                if (isset($input[$field]) && !empty($input[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $input[$field];
                }
            }
            
            if (empty($updates)) {
                echo json_encode(['success' => false, 'message' => 'No fields to update']);
                exit;
            }
            
            $params[] = $current_user_id; // WHERE clause
            
            $sql = "UPDATE users SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute($params)) {
                echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
            }
        }
        
        elseif ($action == 'change_password') {
            // Change password
            $current_password = $input['current_password'] ?? '';
            $new_password = $input['new_password'] ?? '';
            $confirm_password = $input['confirm_password'] ?? '';
            
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                echo json_encode(['success' => false, 'message' => 'All password fields are required']);
                exit;
            }
            
            if ($new_password !== $confirm_password) {
                echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
                exit;
            }
            
            if (strlen($new_password) < 6) {
                echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters']);
                exit;
            }
            
            // Verify current password
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$current_user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($current_password, $user['password_hash'])) {
                echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                exit;
            }
            
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            
            if ($stmt->execute([$hashed_password, $current_user_id])) {
                echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to change password']);
            }
        }
        
        elseif ($action == 'update_role' && $current_user_role === 'admin') {
            // Admin only: Update user role
            $target_user_id = $input['user_id'] ?? 0;
            $new_role = $input['role'] ?? '';
            
            if ($target_user_id <= 0 || empty($new_role)) {
                echo json_encode(['success' => false, 'message' => 'User ID and role are required']);
                exit;
            }
            
            $valid_roles = ['admin', 'staff', 'user'];
            if (!in_array($new_role, $valid_roles)) {
                echo json_encode(['success' => false, 'message' => 'Invalid role']);
                exit;
            }
            
            // Prevent admin from changing their own role
            if ($target_user_id == $current_user_id) {
                echo json_encode(['success' => false, 'message' => 'Cannot change your own role']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE users SET role = ?, updated_at = NOW() WHERE id = ?");
            
            if ($stmt->execute([$new_role, $target_user_id])) {
                echo json_encode(['success' => true, 'message' => 'User role updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update user role']);
            }
        }
        
        else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
