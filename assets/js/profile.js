// Initialize profile manager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.profileManager = new ProfileManager();
});

class ProfileManager {
    constructor() {
        this.currentUser = null;
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

        // Logout
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => this.logout());
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
        
        // Set department value (readonly field)
        const deptField = document.getElementById('department');
        if (deptField) {
            deptField.value = user.department || '';
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
                this.displayUsers(response.data);
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            console.error('Users load error:', error);
            this.showNotification('Lỗi tải danh sách users', 'error');
        }
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
                </td>
            </tr>
        `).join('');
    }

    showUserRoleModal(userId, username, currentRole) {
        document.getElementById('userRoleUserId').value = userId;
        document.getElementById('userRoleName').value = username;
        document.getElementById('userRole').value = currentRole;
        document.getElementById('userRoleModal').style.display = 'block';
    }

    async handleUserRoleSubmit(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        const roleData = {
            action: 'update_role',
            user_id: parseInt(formData.get('userRoleUserId')),
            role: formData.get('role')
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
            }
        };

        const finalOptions = { ...defaultOptions, ...options };

        const response = await fetch(url, finalOptions);
        return await response.json();
    }
}

// Global functions for onclick handlers
window.closeUserRoleModal = () => profileManager.closeUserRoleModal();
