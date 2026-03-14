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
        // Only load profile if auth is successful
        if (this.currentUser) {
            this.loadProfile();
        }
    }

    checkAuth() {
        console.log('=== PROFILE.JS CHECK AUTH ===');
        console.log('window.currentUser:', window.currentUser);
        
        // Use user data passed from PHP instead of API call
        if (window.currentUser && window.currentUser.id) {
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
            console.log('📋 Loading profile data...');
            console.log('📋 Current user:', this.currentUser);
            
            // Show loading state if available
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) {
                console.log('📋 Showing loading overlay');
                loadingOverlay.style.display = 'flex';
            } else {
                console.log('⚠️ Loading overlay not found');
            }
            
            console.log('📋 Making API call to: api/profile.php?action=profile');
            const response = await this.apiCall('api/profile.php?action=profile');
            console.log('📋 Profile API response:', response);
            console.log('📋 Response type:', typeof response);
            console.log('📋 Response success:', response?.success);
            console.log('📋 Response data:', response?.data);
            
            if (response && response.success) {
                console.log('✅ Profile API call successful');
                this.displayProfile(response.data);
                console.log('✅ Profile displayed successfully');
            } else {
                console.error('❌ Profile API call failed:', response?.message || 'Unknown error');
                this.showNotification(response?.message || 'Lỗi tải thông tin cá nhân', 'error');
            }
        } catch (error) {
            console.error('❌ Profile load error:', error);
            console.error('❌ Error stack:', error.stack);
            this.showNotification('Lỗi tải thông tin cá nhân', 'error');
        } finally {
            // Hide loading state
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) {
                console.log('📋 Hiding loading overlay');
                loadingOverlay.style.display = 'none';
            }
        }
    }

    displayProfile(user) {
        console.log('=== DISPLAY PROFILE START ===');
        console.log('Displaying profile data:', user);
        
        try {
            // Check if elements exist before trying to set values
            const usernameEl = document.getElementById('username');
            const fullNameEl = document.getElementById('full_name');
            const emailEl = document.getElementById('email');
            const phoneEl = document.getElementById('phone');
            const roleEl = document.getElementById('role');
            const deptEl = document.getElementById('department');
            
            console.log('Elements found:', {
                username: !!usernameEl,
                full_name: !!fullNameEl,
                email: !!emailEl,
                phone: !!phoneEl,
                role: !!roleEl,
                department: !!deptEl
            });
            
            if (usernameEl) usernameEl.value = user.username || '';
            if (fullNameEl) fullNameEl.value = user.full_name || '';
            if (emailEl) emailEl.value = user.email || '';
            if (phoneEl) phoneEl.value = user.phone || '';
            if (roleEl) roleEl.value = this.getRoleText(user.role) || '';
            
            // Set department value (readonly field)
            if (deptEl) {
                deptEl.value = user.department || '';
            }
            
            console.log('=== DISPLAY PROFILE END ===');
        } catch (error) {
            console.error('❌ Error in displayProfile:', error);
            this.showNotification('Lỗi hiển thị thông tin cá nhân', 'error');
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
            console.log('📋 Updating profile...');
            
            // Show loading state
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) {
                loadingOverlay.style.display = 'flex';
            }
            
            const response = await this.apiCall('api/profile.php', {
                method: 'PUT',
                body: JSON.stringify(profileData)
            });

            if (response.success) {
                this.showNotification('Cập nhật thông tin thành công', 'success');
                console.log('✅ Profile updated successfully');
                
                // Update current user data without full reload
                if (this.currentUser) {
                    this.currentUser.full_name = profileData.full_name;
                    this.currentUser.email = profileData.email;
                    this.currentUser.phone = profileData.phone;
                    this.currentUser.department = profileData.department;
                    this.updateUserDisplay();
                }
                
                // Just update display instead of full reload
                this.displayProfile({
                    ...this.currentUser,
                    ...profileData
                });
            } else {
                console.error('❌ Profile update failed:', response.message);
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            console.error('❌ Profile update error:', error);
            this.showNotification('Lỗi cập nhật thông tin', 'error');
        } finally {
            // Hide loading state
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
            }
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
            console.log('👥 Loading users for admin...');
            
            const response = await this.apiCall('api/profile.php?action=all_users');
            console.log('👥 Users API response:', response);
            
            if (response.success) {
                this.displayUsers(response.data);
                console.log('✅ Users loaded successfully');
            } else {
                console.error('❌ Users load failed:', response.message);
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            console.error('❌ Users load error:', error);
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
        try {
            console.log('🌐 API Call:', url, options);
            
            const defaultOptions = {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                }
            };

            const finalOptions = { ...defaultOptions, ...options };
            console.log('🌐 Final options:', finalOptions);

            const response = await fetch(url, finalOptions);
            console.log('🌐 Response status:', response.status);
            console.log('🌐 Response ok:', response.ok);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('🌐 Response data:', data);
            
            return data;
        } catch (error) {
            console.error('🌐 API Call error:', error);
            console.error('🌐 Error details:', error.message);
            throw error;
        }
    }
}

// Global functions for onclick handlers
window.closeUserRoleModal = () => profileManager.closeUserRoleModal();
