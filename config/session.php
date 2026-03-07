<?php
// Session management for IT Service Request System

// Function to start session if not already started
function startSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start([
            'cookie_lifetime' => 86400,
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'cookie_path' => '/',
            'cookie_domain' => 'localhost'
        ]);
    }
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to get current user data
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? '',
            'full_name' => $_SESSION['full_name'] ?? '',
            'role' => $_SESSION['role'] ?? '',
            'email' => $_SESSION['email'] ?? ''
        ];
    }
    return null;
}

// Function to get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Function to get current user role
function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}

// Function to check if user has specific role
function hasRole($role) {
    return getCurrentUserRole() === $role;
}

// Function to check if user is admin
function isAdmin() {
    return hasRole('admin');
}

// Function to check if user is staff
function isStaff() {
    return hasRole('staff');
}

// Function to check if user is regular user
function isUser() {
    return hasRole('user');
}

// Function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit();
    }
}

// Function to require specific role
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit();
    }
}

// Function to destroy session (logout)
function destroySession() {
    session_destroy();
    $_SESSION = array();
}

// Function to regenerate session ID
function regenerateSession() {
    session_regenerate_id(true);
}
?>
