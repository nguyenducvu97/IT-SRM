// Departments Management Class
class DepartmentsManager {
    constructor(app) {
        this.app = app;
        this.departments = [];
        this.init();
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        // Add department button
        const addBtn = document.getElementById('addDepartmentBtn');
        if (addBtn) {
            addBtn.addEventListener('click', () => this.showAddDepartmentModal());
        }

        // Department form submit
        const form = document.getElementById('departmentForm');
        if (form) {
            form.addEventListener('submit', (e) => this.handleDepartmentSubmit(e));
        }

        // Cancel department button
        const cancelBtn = document.querySelector('.cancel-department');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => this.closeDepartmentModal());
        }

        // Modal close button
        const modal = document.getElementById('departmentModal');
        if (modal) {
            const closeBtn = modal.querySelector('.close');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => this.closeDepartmentModal());
            }
        }
    }

    async loadDepartments(page = 1) {
        console.log(`🏢 Loading departments for admin... page: ${page}`);
        
        try {
            // Explicitly call with action=get and pagination parameters
            const response = await this.app.apiCall(`api/departments.php?action=get&page=${page}&limit=9`);
            console.log('📡 Full API response:', response);
            console.log('📡 Response data type:', typeof response.data);
            console.log('📡 Response data length:', response.data ? response.data.length : 'N/A');
            
            if (response.success) {
                console.log('✅ Departments loaded successfully:', response.data.length);
                this.departments = response.data;
                
                // Call displayPagination for departments
                if (this.app.displayPagination && typeof this.app.displayPagination === 'function') {
                    console.log('🎨 Calling displayPagination for departments with data:', {
                        page: response.page || 1,
                        total_pages: response.total_pages || 1
                    });
                    this.app.displayPagination({
                        page: response.page || 1,
                        total_pages: response.total_pages || 1
                    });
                }
                
                // Log each department to debug the structure
                this.departments.forEach((dept, index) => {
                    console.log(`🏢 Department ${index}:`, dept);
                    console.log(`🏢 Department ${index} name:`, dept.name);
                    console.log(`🏢 Department ${index} keys:`, Object.keys(dept));
                });
                
                await this.displayDepartments();
            } else {
                console.error('❌ Failed to load departments:', response.message);
                this.app.showNotification(response.message || 'Lỗi khi tải danh sách phòng ban', 'error');
            }
        } catch (error) {
            console.error('❌ Error in loadDepartments:', error);
            this.app.showNotification('Lỗi khi tải danh sách phòng ban', 'error');
        }
    }

    async displayDepartments() {
        const container = document.getElementById('departmentsList');
        if (!container) return;

        if (this.departments.length === 0) {
            container.innerHTML = `
                <div class="empty-departments">
                    <i class="fas fa-building"></i>
                    <h3>Chưa có phòng ban nào</h3>
                    <p>Nhấn nút "Thêm phòng ban" để tạo phòng ban đầu tiên</p>
                </div>
            `;
            return;
        }

        // Get all users once and count by department locally
        let userCounts = {};
        try {
            console.log('👥 Loading all users for department counts...');
            const response = await this.app.apiCall('api/users.php');
            if (response.success && response.data) {
                // Count users by department
                userCounts = {};
                const users = response.data.users || response.data || [];
                users.forEach(user => {
                    const dept = user.department || 'Unknown';
                    userCounts[dept] = (userCounts[dept] || 0) + 1;
                });
                console.log('👥 User counts by department:', userCounts);
            }
        } catch (error) {
            console.log('Could not load users for department counts:', error);
            // Continue with empty counts
        }

        container.innerHTML = this.departments.map((department, index) => {
            // Handle both string and object formats
            let deptId, deptName, isActive, description, createdAt;
            
            if (typeof department === 'string') {
                // String format (dropdown data)
                deptId = index + 1;
                deptName = department;
                isActive = true;
                description = '';
                createdAt = new Date().toISOString();
            } else {
                // Object format (full department data)
                deptId = department.id || (index + 1);
                deptName = department.name || 'Unknown Department';
                isActive = department.is_active !== false;
                description = department.description || '';
                createdAt = department.created_at;
            }
            
            const userCount = userCounts[deptName] || 0;
            
            console.log('🏢 Rendering department:', { 
                index, 
                deptId, 
                deptName, 
                isActive,
                description,
                userCount,
                originalData: department 
            });
            
            return `
            <div class="department-card" data-department-id="${deptId}">
                <div class="department-header">
                    <h3 class="department-name">${deptName}</h3>
                    <span class="department-status ${isActive ? 'active' : 'inactive'}">
                        ${isActive ? 'Hoạt động' : 'Không hoạt động'}
                    </span>
                </div>
                <div class="department-description">
                    ${description || 'Không có mô tả'}
                </div>
                <div class="department-meta">
                    <span><i class="fas fa-calendar"></i> ${this.formatDate(createdAt)}</span>
                    <span><i class="fas fa-users"></i> ${userCount} người dùng</span>
                </div>
                <div class="department-actions">
                    <button class="btn btn-secondary btn-sm edit-department-btn">
                        <i class="fas fa-edit"></i> Sửa
                    </button>
                    <button class="btn btn-danger btn-sm delete-department-btn">
                        <i class="fas fa-trash"></i> Xóa
                    </button>
                </div>
            </div>
        `;
        }).join('');

        // Add event listeners to buttons
        container.querySelectorAll('.edit-department-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const deptId = e.target.closest('.department-card').dataset.departmentId;
                console.log('🏢 Edit button clicked, department ID:', deptId);
                this.editDepartment(parseInt(deptId));
            });
        });

        container.querySelectorAll('.delete-department-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const deptId = e.target.closest('.department-card').dataset.departmentId;
                console.log('🏢 Delete button clicked, department ID:', deptId);
                this.deleteDepartment(parseInt(deptId));
            });
        });

        // Add click event to department cards (but not on buttons)
        container.querySelectorAll('.department-card').forEach(card => {
            card.addEventListener('click', (e) => {
                // Don't trigger if clicking on buttons
                if (e.target.closest('.department-actions')) {
                    return;
                }
                
                const deptId = card.dataset.departmentId;
                const deptName = card.querySelector('.department-name').textContent;
                console.log('🏢 Department card clicked, ID:', deptId, 'Name:', deptName);
                this.navigateToUsersWithDepartment(deptName);
            });
            
            // Add cursor pointer to indicate clickable
            card.style.cursor = 'pointer';
        });
    }

    navigateToUsersWithDepartment(deptName) {
        console.log('🔄 Navigating to users page with department filter:', deptName);
        
        // Navigate to users page
        this.app.showPage('users');
        
        // Wait for page to load, then set department filter
        setTimeout(() => {
            const roleFilter = document.getElementById('roleFilter');
            const userSearch = document.getElementById('userSearch');
            
            // Clear search and set department filter via search parameter
            if (userSearch) {
                userSearch.value = '';
                // Trigger search with department filter
                const event = new Event('input', { bubbles: true });
                userSearch.dispatchEvent(event);
            }
            
            // Set department filter by adding it to URL parameters
            setTimeout(() => {
                // We'll modify the loadUsers function to handle department parameter
                this.app.loadUsersWithDepartment(1, deptName);
            }, 100);
        }, 500);
    }

    async showDepartmentUsers(deptId, deptName) {
        console.log(`👥 Loading users for department: ${deptName} (ID: ${deptId})`);
        
        try {
            // Show loading modal
            this.showDepartmentUsersModal(deptName, '<div class="loading">Đang tải danh sách người dùng...</div>');
            
            // Load users by department
            const response = await this.app.apiCall(`api/users.php?department=${encodeURIComponent(deptName)}`);
            
            if (response.success) {
                const users = response.data.users || response.data || [];
                console.log(`👥 Found ${users.length} users in department: ${deptName}`);
                
                if (users.length === 0) {
                    this.showDepartmentUsersModal(deptName, `
                        <div class="empty-users">
                            <i class="fas fa-users"></i>
                            <h3>Không có người dùng nào</h3>
                            <p>Phòng ban "${deptName}" hiện chưa có người dùng nào</p>
                        </div>
                    `);
                } else {
                    const usersHtml = users.map(user => `
                        <div class="user-item">
                            <div class="user-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="user-info">
                                <h4 class="user-name">${user.full_name || 'N/A'}</h4>
                                <p class="user-username">@${user.username}</p>
                                <p class="user-email">${user.email}</p>
                                <p class="user-role">
                                    <span class="badge badge-${user.role}">${this.getRoleText(user.role)}</span>
                                </p>
                            </div>
                            <div class="user-actions">
                                <button class="btn btn-sm btn-secondary" onclick="app.viewUserDetail(${user.id})">
                                    <i class="fas fa-eye"></i> Xem chi tiết
                                </button>
                            </div>
                        </div>
                    `).join('');
                    
                    this.showDepartmentUsersModal(deptName, `
                        <div class="users-list">
                            <div class="users-header">
                                <h3><i class="fas fa-users"></i> ${users.length} người dùng</h3>
                                <p class="department-name">${deptName}</p>
                            </div>
                            <div class="users-container">
                                ${usersHtml}
                            </div>
                        </div>
                    `);
                }
            } else {
                console.error('❌ Failed to load department users:', response.message);
                this.showDepartmentUsersModal(deptName, `
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Lỗi</h3>
                        <p>${response.message || 'Không thể tải danh sách người dùng'}</p>
                    </div>
                `);
            }
        } catch (error) {
            console.error('❌ Error loading department users:', error);
            this.showDepartmentUsersModal(deptName, `
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Lỗi kết nối</h3>
                    <p>Không thể tải danh sách người dùng. Vui lòng thử lại.</p>
                </div>
            `);
        }
    }

    showDepartmentUsersModal(deptName, content) {
        // Create modal if it doesn't exist
        let modal = document.getElementById('departmentUsersModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'departmentUsersModal';
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content modal-large">
                    <div class="modal-header">
                        <h3 class="modal-title">Người dùng phòng ban</h3>
                        <span class="close">&times;</span>
                    </div>
                    <div class="modal-body">
                        ${content}
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary close-modal">Đóng</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            // Add close event listeners
            modal.querySelector('.close').addEventListener('click', () => this.closeDepartmentUsersModal());
            modal.querySelector('.close-modal').addEventListener('click', () => this.closeDepartmentUsersModal());
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeDepartmentUsersModal();
                }
            });
        }
        
        // Update modal title and content
        modal.querySelector('.modal-title').innerHTML = `<i class="fas fa-building"></i> ${deptName}`;
        modal.querySelector('.modal-body').innerHTML = content;
        
        // Show modal
        modal.style.display = 'block';
    }

    closeDepartmentUsersModal() {
        const modal = document.getElementById('departmentUsersModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    getRoleText(role) {
        const roleMap = {
            'admin': 'Quản trị viên',
            'staff': 'Nhân viên',
            'user': 'Người dùng'
        };
        return roleMap[role] || role;
    }

    showAddDepartmentModal() {
        const modal = document.getElementById('departmentModal');
        const title = document.getElementById('departmentModalTitle');
        const form = document.getElementById('departmentForm');
        
        title.textContent = 'Thêm phòng ban';
        form.reset();
        document.getElementById('departmentId').value = '';
        document.getElementById('departmentIsActive').checked = true;
        
        modal.style.display = 'flex';
    }

    async editDepartment(departmentId) {
        console.log('🏢 Editing department:', departmentId);
        
        if (!departmentId || departmentId === 'undefined' || departmentId === 'null' || isNaN(departmentId)) {
            console.error('❌ Invalid department ID:', departmentId);
            this.app.showNotification('ID phòng ban không hợp lệ', 'error');
            return;
        }
        
        // Convert to number and check if it's a fallback ID (index + 1)
        const id = parseInt(departmentId);
        const isFallbackId = id > 0 && id <= this.departments.length && !this.departments[id - 1].id;
        
        if (isFallbackId) {
            // This is a fallback ID, get the department from array
            const department = this.departments[id - 1];
            console.log('🏢 Using fallback department:', department);
            
            // Create a mock department object with the fallback ID
            const mockDepartment = {
                id: id,
                name: department.name,
                description: department.description || '',
                is_active: department.is_active !== false,
                created_at: department.created_at
            };
            
            this.showEditDepartmentModal(mockDepartment);
        } else {
            // Try to fetch from API first
            try {
                const response = await this.app.apiCall(`api/departments.php?action=get&id=${id}`);
                
                if (response.success && response.data) {
                    this.showEditDepartmentModal(response.data);
                } else {
                    console.error('❌ Department not found in API:', response.message);
                    // Fallback to local data
                    const localDepartment = this.departments.find(dept => dept.id == id);
                    if (localDepartment) {
                        this.showEditDepartmentModal(localDepartment);
                    } else {
                        this.app.showNotification('Không tìm thấy phòng ban', 'error');
                    }
                }
            } catch (error) {
                console.error('❌ Error fetching department:', error);
                // Fallback to local data
                const localDepartment = this.departments.find(dept => dept.id == id);
                if (localDepartment) {
                    this.showEditDepartmentModal(localDepartment);
                } else {
                    this.app.showNotification('Lỗi khi tải thông tin phòng ban', 'error');
                }
            }
        }
    }

    showEditDepartmentModal(department) {
        const modal = document.getElementById('departmentModal');
        const title = document.getElementById('departmentModalTitle');
        
        title.textContent = 'Chỉnh sửa phòng ban';
        
        // Populate form
        document.getElementById('departmentId').value = department.id;
        document.getElementById('departmentName').value = department.name;
        document.getElementById('departmentDescription').value = department.description || '';
        document.getElementById('departmentIsActive').checked = department.is_active;
        
        modal.style.display = 'flex';
    }

    async handleDepartmentSubmit(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const departmentId = document.getElementById('departmentId').value;
        
        const departmentData = {
            name: formData.get('name').trim(),
            description: formData.get('description').trim(),
            is_active: formData.get('is_active') === 'on'
        };

        if (!departmentData.name) {
            this.app.showNotification('Tên phòng ban là bắt buộc', 'error');
            return;
        }

        try {
            let response;
            
            if (departmentId) {
                // Update existing department
                departmentData.id = parseInt(departmentId);
                response = await this.app.apiCall('api/departments.php?action=update', {
                    method: 'PUT',
                    body: JSON.stringify(departmentData)
                });
            } else {
                // Create new department
                response = await this.app.apiCall('api/departments.php?action=create', {
                    method: 'POST',
                    body: JSON.stringify(departmentData)
                });
            }

            if (response.success) {
                this.app.showNotification(
                    departmentId ? 'Cập nhật phòng ban thành công' : 'Thêm phòng ban thành công',
                    'success'
                );
                this.closeDepartmentModal();
                this.loadDepartments();
            } else {
                this.app.showNotification(response.message || 'Lỗi khi lưu phòng ban', 'error');
            }
        } catch (error) {
            console.error('❌ Error in handleDepartmentSubmit:', error);
            this.app.showNotification('Lỗi khi lưu phòng ban', 'error');
        }
    }

    async deleteDepartment(departmentId) {
        console.log('🏢 Deleting department:', departmentId);
        
        if (!departmentId || departmentId === 'undefined' || departmentId === 'null' || isNaN(departmentId)) {
            console.error('❌ Invalid department ID:', departmentId);
            this.app.showNotification('ID phòng ban không hợp lệ', 'error');
            return;
        }
        
        const id = parseInt(departmentId);
        const isFallbackId = id > 0 && id <= this.departments.length && !this.departments[id - 1].id;
        
        let departmentName = 'Phòng ban này';
        
        if (isFallbackId) {
            // Get department name from array
            const department = this.departments[id - 1];
            departmentName = department ? department.name : 'Phòng ban này';
        } else {
            // Get department name from array or API
            const department = this.departments.find(dept => dept.id == id);
            departmentName = department ? department.name : 'Phòng ban này';
        }
        
        if (confirm(`Bạn có chắc chắn muốn xóa phòng ban "${departmentName}"?`)) {
            this.performDelete(id, isFallbackId);
        }
    }

    async performDelete(departmentId, isFallbackId = false) {
        try {
            if (isFallbackId) {
                // For fallback IDs, we can't actually delete since they don't exist in database
                this.app.showNotification('Không thể xóa phòng ban mặc định. Vui lòng tạo phòng ban mới trong database.', 'warning');
                return;
            }
            
            const response = await this.app.apiCall(`api/departments.php?action=delete&id=${departmentId}`, {
                method: 'DELETE'
            });
            
            if (response.success) {
                this.app.showNotification('Xóa phòng ban thành công', 'success');
                this.loadDepartments();
            } else {
                this.app.showNotification(response.message || 'Lỗi khi xóa phòng ban', 'error');
            }
        } catch (error) {
            console.error('❌ Error in performDelete:', error);
            this.app.showNotification('Lỗi khi xóa phòng ban', 'error');
        }
    }

    closeDepartmentModal() {
        const modal = document.getElementById('departmentModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('vi-VN');
    }
}

// Initialize departments manager when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('🏢 Initializing Departments Manager...');
    
    if (typeof app !== 'undefined') {
        console.log('✅ App found, creating departments manager...');
        app.departmentsManager = new DepartmentsManager(app);
        
        // Show/hide departments menu for admin
        if (app.currentUser && app.currentUser.role === 'admin') {
            console.log('✅ Admin user detected, showing departments menu');
            const adminDepartmentMenu = document.getElementById('adminDepartmentMenu');
            if (adminDepartmentMenu) {
                adminDepartmentMenu.style.display = 'block';
                console.log('✅ Departments menu shown');
            } else {
                console.log('❌ Departments menu element not found');
            }
        } else {
            console.log('❌ Not admin user or user not loaded');
        }
        
        // Set global reference
        window.departmentsManager = app.departmentsManager;
        console.log('✅ Departments Manager initialized');
    } else {
        console.log('❌ App not found, departments manager not initialized');
    }
});

// Global function for onclick handlers
window.departmentsManager = null;
document.addEventListener('DOMContentLoaded', function() {
    if (window.app && window.app.departmentsManager) {
        window.departmentsManager = window.app.departmentsManager;
        console.log('✅ Global departments manager reference set');
    }
});
