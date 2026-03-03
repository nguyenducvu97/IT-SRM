<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/session.php';
require_once 'config/database.php';

// Debug session
error_log("=== PROFILE.PHP DEBUG ===");
error_log("Session data: " . json_encode($_SESSION));

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("No user_id in session - redirecting to index");
    header('Location: index.html');
    exit;
}

$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['role']; // Fix: use 'role' not 'user_role'

error_log("User logged in: ID=$current_user_id, Role=$current_user_role");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin cá nhân - IT Service Request</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="header-content">
                <h1><i class="fas fa-laptop-code"></i> IT Service Request</h1>
                <div class="user-menu">
                    <span id="userDisplay"></span>
                    <button id="logoutBtn" class="btn btn-secondary"><i class="fas fa-sign-out-alt"></i> Đăng xuất</button>
                </div>
            </div>
        </header>

        <!-- Sidebar -->
        <aside class="sidebar">
            <nav class="nav-menu">
                <ul>
                    <li><a href="index.html" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="profile.php" class="nav-link active"><i class="fas fa-user"></i> Thông tin cá nhân</a></li>
                    <li><a href="index.html#requestsPage" class="nav-link"><i class="fas fa-list"></i> Yêu cầu</a></li>
                    <li id="newRequestMenu" style="display: none;"><a href="index.html#new-request" class="nav-link"><i class="fas fa-plus"></i> Tạo yêu cầu</a></li>
                    <li><a href="index.html#categoriesPage" class="nav-link"><i class="fas fa-tags"></i> Danh mục</a></li>
                    <li id="adminMenu" style="display: none;"><a href="index.html#usersPage" class="nav-link"><i class="fas fa-users"></i> Người dùng</a></li>
                    <li id="adminSupportMenu" style="display: none;"><a href="index.html#support-requests" class="nav-link"><i class="fas fa-hands-helping"></i> Yêu cầu hỗ trợ</a></li>
                    <li id="adminRejectMenu" style="display: none;"><a href="index.html#reject-requests" class="nav-link"><i class="fas fa-times-circle"></i> Yêu cầu từ chối</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Profile Page -->
            <div class="page">
                <div class="page-header">
                    <h2><i class="fas fa-user"></i> Thông tin cá nhân</h2>
                    <div class="page-actions">
                        <button id="refreshProfileBtn" class="btn btn-secondary">
                            <i class="fas fa-sync"></i> Làm mới
                        </button>
                    </div>
                </div>
                
                <!-- Profile Information -->
                <div class="profile-section">
                    <h3>Thông tin cá nhân</h3>
                    <form id="profileForm" class="form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">Tên đăng nhập</label>
                                <input type="text" id="username" class="form-control" readonly>
                            </div>
                            <div class="form-group">
                                <label for="full_name">Họ và tên</label>
                                <input type="text" id="full_name" name="full_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Điện thoại</label>
                                <input type="tel" id="phone" name="phone" class="form-control">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="role">Vai trò</label>
                                <input type="text" id="role" class="form-control" readonly>
                            </div>
                            <div class="form-group">
                                <label for="department">Phòng ban</label>
                                <input type="text" id="department" name="department" class="form-control">
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Cập nhật thông tin</button>
                        </div>
                    </form>
                </div>

                <!-- Password Change -->
                <div class="profile-section">
                    <h3>Đổi mật khẩu</h3>
                    <form id="passwordForm" class="form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="current_password">Mật khẩu hiện tại</label>
                                <input type="password" id="current_password" name="current_password" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="new_password">Mật khẩu mới</label>
                                <input type="password" id="new_password" name="new_password" class="form-control" required minlength="6">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="confirm_password">Xác nhận mật khẩu mới</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="6">
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-warning"><i class="fas fa-key"></i> Đổi mật khẩu</button>
                        </div>
                    </form>
                </div>

                <!-- User Management (Admin Only) -->
                <div class="profile-section admin-only" id="userManagementSection" style="display: none;">
                    <h3>Quản lý Users</h3>
                    <div class="search-filter">
                        <input type="text" id="userSearch" placeholder="Tìm kiếm người dùng..." class="form-control">
                        <select id="roleFilter" class="form-control">
                            <option value="">Tất cả vai trò</option>
                            <option value="admin">Admin</option>
                            <option value="staff">Staff</option>
                            <option value="user">User</option>
                        </select>
                    </div>
                    
                    <div id="usersList" class="user-list">
                        <!-- Users will be loaded here -->
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- User Role Modal -->
    <div id="userRoleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Chỉnh sửa vai trò</h3>
                <span class="close" onclick="closeUserRoleModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="userRoleForm" class="form">
                    <input type="hidden" id="userRoleUserId">
                    <div class="form-group">
                        <label for="userRoleName">Tên user</label>
                        <input type="text" id="userRoleName" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label for="userRoleSelect">Vai trò</label>
                        <select id="userRoleSelect" class="form-control" required>
                            <option value="admin">Admin</option>
                            <option value="staff">Staff</option>
                            <option value="user">User</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Cập nhật</button>
                        <button type="button" class="btn btn-secondary" onclick="closeUserRoleModal()">Hủy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/profile.js"></script>
    <script>
        // Pass current user data to JavaScript
        window.currentUser = {
            id: <?php echo $current_user_id; ?>,
            role: '<?php echo $current_user_role; ?>'
        };
        
        console.log('=== PROFILE.PHP USER DATA ===');
        console.log('window.currentUser:', window.currentUser);
        
        // Show/hide menu items based on role
        document.addEventListener('DOMContentLoaded', function() {
            const currentUser = window.currentUser;
            
            if (currentUser) {
                // Show new request menu for users
                if (currentUser.role === 'user' || currentUser.role === 'staff') {
                    const newRequestMenu = document.getElementById('newRequestMenu');
                    if (newRequestMenu) {
                        newRequestMenu.style.display = 'block';
                    }
                }
                
                // Show admin menus for admin
                if (currentUser.role === 'admin') {
                    const adminMenu = document.getElementById('adminMenu');
                    const adminSupportMenu = document.getElementById('adminSupportMenu');
                    const adminRejectMenu = document.getElementById('adminRejectMenu');
                    
                    if (adminMenu) adminMenu.style.display = 'block';
                    if (adminSupportMenu) adminSupportMenu.style.display = 'block';
                    if (adminRejectMenu) adminRejectMenu.style.display = 'block';
                }
            }
        });
    </script>
</body>
</html>
