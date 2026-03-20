// Initialize profile manager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.profileManager = new ProfileManager();
});

class ProfileManager {
    constructor() {
        this.currentUser = null;
        this.allUsers = []; // Store all users for filtering
        this.init();
    }

    init() {
        this.checkAuth();
        this.bindEvents();
        this.loadProfile();
    }

    checkAuth() {
        console.log('=== PROFILE.JS CHECK AUTH ===');
        console.log('window.currentUser:', window.currentUser);
        
        // Use user data passed from PHP instead of API call
        if (window.currentUser) {
            console.log('User data found, setting up profile');
            this.currentUser = window.currentUser;
            this.updateUserDisplay();
            this.showAdminElements();
        } else {
            console.log('No user data found - redirecting to index');
            this.showNotification('Phiên đăng nhập hết hạn', 'error');
            setTimeout(() => {
                window.location.href = 'index.html';
            }, 2000);
        }
    }

    bindEvents() {
        // Profile form
        const profileForm = document.getElementById('profileForm');
        if (profileForm) {
            profileForm.addEventListener('submit', (e) => this.handleProfileSubmit(e));
        }

        // Password form
        const passwordForm = document.getElementById('passwordForm');
        if (passwordForm) {
            passwordForm.addEventListener('submit', (e) => this.handlePasswordSubmit(e));
        }

        // User role form
        const userRoleForm = document.getElementById('userRoleForm');
        if (userRoleForm) {
            userRoleForm.addEventListener('submit', (e) => this.handleUserRoleSubmit(e));
        }

        // Reset password form
        const resetPasswordForm = document.getElementById('resetPasswordForm');
        if (resetPasswordForm) {
            resetPasswordForm.addEventListener('submit', (e) => this.handleResetPasswordSubmit(e));
        }

        // Logout
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => this.logout());
        }
        
        // Refresh profile
        const refreshBtn = document.getElementById('refreshProfileBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.loadProfile());
        }
        
        // User search and filter (admin only)
        const userSearch = document.getElementById('userSearch');
        const roleFilter = document.getElementById('roleFilter');
        
        if (userSearch) {
            userSearch.addEventListener('input', () => this.filterUsers());
        }
        
        if (roleFilter) {
            roleFilter.addEventListener('change', () => this.filterUsers());
        }
    }

    async loadProfile() {
        try {
            const response = await this.apiCall('api/profile.php?action=profile');
            
            if (response.success) {
                this.displayProfile(response.data);
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            console.error('Profile load error:', error);
            this.showNotification('Lỗi tải thông tin cá nhân', 'error');
        }
    }

    displayProfile(user) {
        console.log('Displaying profile data:', user);
        
        document.getElementById('username').value = user.username || '';
        document.getElementById('full_name').value = user.full_name || '';
        document.getElementById('email').value = user.email || '';
        document.getElementById('phone').value = user.phone || '';
        document.getElementById('role').value = this.getRoleText(user.role) || '';
        
        // Set department dropdown value
        const deptField = document.getElementById('department');
        if (deptField) {
            // Load departments first, then set the value
            this.loadDepartments().then(() => {
                deptField.value = user.department || '';
            });
        }
    }

    async loadDepartments() {
        try {
            const response = await this.apiCall('api/departments.php?action=dropdown');
            
            if (response.success && response.data) {
                const deptSelect = document.getElementById('department');
                if (deptSelect) {
                    // Clear existing options except the first one
                    deptSelect.innerHTML = '<option value="">Chọn phòng ban</option>';
                    
                    // Add department options
                    response.data.forEach(dept => {
                        const option = document.createElement('option');
                        option.value = dept;
                        option.textContent = dept;
                        deptSelect.appendChild(option);
                    });
                }
            }
        } catch (error) {
            console.error('Error loading departments:', error);
        }
    }

    async handleProfileSubmit(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        const profileData = {
            action: 'update_profile',
            full_name: formData.get('full_name'),
            email: formData.get('email'),
            phone: formData.get('phone'),
            department: formData.get('department')
        };

        try {
            const response = await this.apiCall('api/profile.php', {
                method: 'PUT',
                body: JSON.stringify(profileData)
            });

            if (response.success) {
                this.showNotification('Cập nhật thông tin thành công', 'success');
                this.loadProfile(); // Reload to show updated data
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            console.error('Profile update error:', error);
            this.showNotification('Lỗi cập nhật thông tin', 'error');
        }
    }

    async handlePasswordSubmit(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        const passwordData = {
            action: 'change_password',
            current_password: formData.get('current_password'),
            new_password: formData.get('new_password'),
            confirm_password: formData.get('confirm_password')
        };

        try {
            const response = await this.apiCall('api/profile.php', {
                method: 'PUT',
                body: JSON.stringify(passwordData)
            });

            if (response.success) {
                this.showNotification('Đổi mật khẩu thành công', 'success');
                e.target.reset(); // Clear form
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            console.error('Password change error:', error);
            this.showNotification('Lỗi đổi mật khẩu', 'error');
        }
    }

    async loadUsers() {
        if (this.currentUser.role !== 'admin') return;

        try {
            const response = await this.apiCall('api/profile.php?action=all_users');
            
            if (response.success) {
                this.allUsers = response.data; // Store for filtering
                this.displayUsers(response.data);
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            console.error('Users load error:', error);
            this.showNotification('Lỗi tải danh sách users', 'error');
        }
    }

    filterUsers() {
        if (!this.allUsers || this.allUsers.length === 0) return;
        
        const searchTerm = document.getElementById('userSearch')?.value.toLowerCase() || '';
        const roleFilter = document.getElementById('roleFilter')?.value || '';
        
        const filteredUsers = this.allUsers.filter(user => {
            const matchesSearch = !searchTerm || 
                user.username.toLowerCase().includes(searchTerm) ||
                user.full_name.toLowerCase().includes(searchTerm) ||
                user.email.toLowerCase().includes(searchTerm);
            
            const matchesRole = !roleFilter || user.role === roleFilter;
            
            return matchesSearch && matchesRole;
        });
        
        this.displayUsers(filteredUsers);
    }

    displayUsers(users) {
        const tbody = document.getElementById('usersTableBody');
        if (!tbody) return;

        tbody.innerHTML = users.map(user => `
            <tr>
                <td>${user.id}</td>
                <td>${user.username}</td>
                <td>${user.full_name}</td>
                <td>${user.email}</td>
                <td>
                    <span class="badge role-${user.role}">${this.getRoleText(user.role)}</span>
                </td>
                <td>${this.formatDate(user.created_at)}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="profileManager.showUserRoleModal(${user.id}, '${user.username}', '${user.role}')">
                        <i class="fas fa-edit"></i> Sửa vai trò
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="profileManager.showResetPasswordModal(${user.id}, '${user.username}')">
                        <i class="fas fa-key"></i> Đổi mật khẩu
                    </button>
                </td>
            </tr>
        `).join('');
    }

    showUserRoleModal(userId, username, currentRole) {
        document.getElementById('userRoleUserId').value = userId;
        document.getElementById('userRoleName').value = username;
        document.getElementById('userRoleSelect').value = currentRole;
        document.getElementById('userRoleModal').style.display = 'block';
    }

    async handleUserRoleSubmit(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        const roleData = {
            action: 'update_role',
            user_id: parseInt(document.getElementById('userRoleUserId').value),
            role: document.getElementById('userRoleSelect').value
        };

        try {
            const response = await this.apiCall('api/profile.php', {
                method: 'PUT',
                body: JSON.stringify(roleData)
            });

            if (response.success) {
                this.showNotification('Cập nhật vai trò thành công', 'success');
                this.closeUserRoleModal();
                this.loadUsers(); // Reload users list
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            console.error('Role update error:', error);
            this.showNotification('Lỗi cập nhật vai trò', 'error');
        }
    }

    closeUserRoleModal() {
        document.getElementById('userRoleModal').style.display = 'none';
    }

    showResetPasswordModal(userId, username) {
        document.getElementById('resetPasswordUserId').value = userId;
        document.getElementById('resetPasswordName').value = username;
        document.getElementById('resetPasswordModal').style.display = 'block';
        document.getElementById('resetPasswordNewPassword').value = '';
        document.getElementById('resetPasswordConfirmPassword').value = '';
    }

    closeResetPasswordModal() {
        document.getElementById('resetPasswordModal').style.display = 'none';
    }

    async handleResetPasswordSubmit(e) {
        e.preventDefault();
        
        const userId = parseInt(document.getElementById('resetPasswordUserId').value);
        const newPassword = document.getElementById('resetPasswordNewPassword').value;
        const confirmPassword = document.getElementById('resetPasswordConfirmPassword').value;
        
        if (newPassword !== confirmPassword) {
            this.showNotification('Mật khẩu xác nhận không khớp', 'error');
            return;
        }
        
        if (newPassword.length < 6) {
            this.showNotification('Mật khẩu phải có ít nhất 6 ký tự', 'error');
            return;
        }

        try {
            const response = await this.apiCall('api/profile.php', {
                method: 'PUT',
                body: JSON.stringify({
                    action: 'reset_password',
                    user_id: userId,
                    new_password: newPassword
                })
            });

            if (response.success) {
                this.showNotification('Đổi mật khẩu user thành công', 'success');
                this.closeResetPasswordModal();
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            console.error('Reset password error:', error);
            this.showNotification('Lỗi đổi mật khẩu', 'error');
        }
    }

    updateUserDisplay() {
        const userDisplay = document.getElementById('userDisplay');
        if (userDisplay && this.currentUser) {
            userDisplay.innerHTML = `
                <i class="fas fa-user"></i> 
                <span>${this.currentUser.full_name}</span>
                <span class="badge role-${this.currentUser.role}">${this.getRoleText(this.currentUser.role)}</span>
            `;
        }
    }

    showAdminElements() {
        if (this.currentUser && this.currentUser.role === 'admin') {
            // Show admin-only elements
            const adminElements = document.querySelectorAll('.admin-only');
            adminElements.forEach(el => el.style.display = 'block');
            
            // Load users for admin
            this.loadUsers();
        }
    }

    logout() {
        // Clear session (this would be handled by backend)
        window.location.href = 'index.html';
    }

    // Helper functions
    getRoleText(role) {
        const roles = {
            'admin': 'Admin',
            'staff': 'Staff',
            'user': 'User'
        };
        return roles[role] || role;
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('vi-VN');
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = 'notification';
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 5px;
            color: white;
            font-weight: bold;
            z-index: 1000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        `;

        // Set background color based on type
        const colors = {
            success: '#28a745',
            error: '#dc3545',
            warning: '#ffc107',
            info: '#17a2b8'
        };
        notification.style.backgroundColor = colors[type] || colors.info;

        document.body.appendChild(notification);

        // Show notification
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);

        // Hide notification after 3 seconds
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }

    async apiCall(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include' // Important for session cookies
        };

        const finalOptions = { ...defaultOptions, ...options };

        const response = await fetch(url, finalOptions);
        return await response.json();
    }
}

// Global functions for onclick handlers
window.closeUserRoleModal = () => profileManager.closeUserRoleModal();
window.closeResetPasswordModal = () => profileManager.closeResetPasswordModal();
