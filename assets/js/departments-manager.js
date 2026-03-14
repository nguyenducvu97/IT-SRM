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

    async loadDepartments() {
        console.log('🏢 Loading departments for admin...');
        console.log('🏢 App object available:', !!this.app);
        console.log('🏢 App.apiCall method available:', typeof this.app.apiCall);
        
        try {
            // Check if app and apiCall are available
            if (!this.app || typeof this.app.apiCall !== 'function') {
                throw new Error('App object or apiCall method not available');
            }
            
            // Explicitly call with action=get to get full department objects
            const response = await this.app.apiCall('api/departments.php?action=get');
            console.log('📡 Full API response:', response);
            console.log('📡 Response data type:', typeof response.data);
            console.log('📡 Response data length:', response.data ? response.data.length : 'N/A');
            
            if (response.success) {
                console.log('✅ Departments loaded successfully:', response.data.length);
                this.departments = response.data;
                
                // Log each department to debug the structure
                this.departments.forEach((dept, index) => {
                    console.log(`🏢 Department ${index}:`, dept);
                    console.log(`🏢 Department ${index} name:`, dept.name);
                    console.log(`🏢 Department ${index} keys:`, Object.keys(dept));
                });
                
                await this.displayDepartments();
            } else {
                console.error('❌ Failed to load departments:', response.message);
                this.app.showNotification('Không thể tải danh sách phòng ban: ' + response.message, 'error');
            }
        } catch (error) {
            console.error('💥 Error loading departments:', error);
            console.error('💥 Error stack:', error.stack);
            this.app.showNotification('Lỗi khi tải phòng ban: ' + error.message, 'error');
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

        // Get all users and requests once and count by department locally
        let userCounts = {};
        let requestCounts = {};
        
        try {
            console.log('👥 Loading all users for department counts...');
            const usersResponse = await this.app.apiCall('api/users.php');
            if (usersResponse.success && usersResponse.data) {
                // Count users by department
                userCounts = {};
                usersResponse.data.forEach(user => {
                    const dept = user.department || 'Unknown';
                    userCounts[dept] = (userCounts[dept] || 0) + 1;
                });
                console.log('👥 User counts by department:', userCounts);
            }
        } catch (error) {
            console.log('Could not load users for department counts:', error);
        }
        
        try {
            console.log('📋 Loading all requests for department counts...');
            const requestsResponse = await this.app.apiCall('api/service_requests.php?action=list&limit=1000');
            console.log('📋 Full requests API response:', requestsResponse);
            
            if (requestsResponse.success && requestsResponse.data) {
                // Count requests by department
                requestCounts = {};
                const requests = requestsResponse.data.requests || requestsResponse.data || [];
                console.log('📋 Total requests loaded:', requests.length);
                
                // Load all users to get department info
                let users = [];
                try {
                    console.log('� Loading users for department mapping...');
                    const usersResponse = await this.app.apiCall('api/users.php');
                    if (usersResponse.success && usersResponse.data) {
                        users = usersResponse.data;
                        console.log('� Users loaded:', users.length);
                    }
                } catch (error) {
                    console.error('� Error loading users:', error);
                }
                
                // Create user department mapping
                const userDepartmentMap = {};
                users.forEach(user => {
                    if (user.id && user.department) {
                        userDepartmentMap[user.id] = user.department;
                    }
                });
                console.log('👤 User department mapping:', userDepartmentMap);
                
                requests.forEach((request, index) => {
                    console.log(`📋 Request ${index}:`, request);
                    console.log(`📋 Request ${index} - ALL KEYS:`, Object.keys(request));
                    console.log(`📋 Request ${index} - user_id:`, request.user_id);
                    
                    // Get department from user mapping
                    const dept = userDepartmentMap[request.user_id] || 'Unknown';
                    console.log(`📋 Using department field: "${dept}" for request ${request.id} (user_id: ${request.user_id})`);
                    
                    if (!requestCounts[dept]) {
                        requestCounts[dept] = {
                            total: 0,
                            open: 0,
                            in_progress: 0,
                            resolved: 0,
                            rejected: 0
                        };
                    }
                    
                    requestCounts[dept].total++;
                    
                    switch(request.status) {
                        case 'open':
                            requestCounts[dept].open++;
                            break;
                        case 'in_progress':
                            requestCounts[dept].in_progress++;
                            break;
                        case 'resolved':
                            requestCounts[dept].resolved++;
                            break;
                        case 'rejected':
                            requestCounts[dept].rejected++;
                            break;
                    }
                });
                
                console.log('📋 Final request counts by department:', requestCounts);
            } else {
                console.error('📋 Failed to load requests:', requestsResponse.message);
            }
        } catch (error) {
            console.error('📋 Error loading requests for department counts:', error);
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
            const deptRequestCounts = requestCounts[deptName] || { total: 0, open: 0, in_progress: 0, resolved: 0, rejected: 0 };
            
            console.log('🏢 Rendering department:', { 
                index, 
                deptId, 
                deptName, 
                isActive,
                description,
                userCount,
                requestCounts: deptRequestCounts,
                originalData: department 
            });
            
            return `
            <div class="department-card clickable" data-department-id="${deptId}" data-department-name="${deptName}">
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
                <div class="department-requests">
                    <div class="request-stats">
                        <span class="stat-item total">
                            <i class="fas fa-tasks"></i>
                            <span class="stat-number">${deptRequestCounts.total}</span>
                            <span class="stat-label">Tổng</span>
                        </span>
                        <span class="stat-item open">
                            <i class="fas fa-clock"></i>
                            <span class="stat-number">${deptRequestCounts.open}</span>
                            <span class="stat-label">Mới</span>
                        </span>
                        <span class="stat-item in-progress">
                            <i class="fas fa-spinner"></i>
                            <span class="stat-number">${deptRequestCounts.in_progress}</span>
                            <span class="stat-label">Đang xử lý</span>
                        </span>
                        <span class="stat-item resolved">
                            <i class="fas fa-check-circle"></i>
                            <span class="stat-number">${deptRequestCounts.resolved}</span>
                            <span class="stat-label">Hoàn thành</span>
                        </span>
                    </div>
                    <div class="view-requests-hint">
                        <i class="fas fa-arrow-right"></i> Click để xem yêu cầu
                    </div>
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
        
        // Add click event listener for department cards (excluding action buttons)
        container.querySelectorAll('.department-card.clickable').forEach(card => {
            card.addEventListener('click', (e) => {
                // Don't trigger if clicking on action buttons
                if (e.target.closest('.department-actions')) {
                    return;
                }
                
                const deptId = card.dataset.departmentId;
                const deptName = card.dataset.departmentName;
                console.log('🏢 Department card clicked:', { deptId, deptName });
                this.showDepartmentRequests(deptId, deptName);
            });
        });
        
        // Hide loading state after departments are displayed
        if (this.app && typeof this.app.hideLoadingState === 'function') {
            this.app.hideLoadingState();
            console.log('🏢 Loading state hidden after displaying departments');
        }
    }
    
    showDepartmentRequests(deptId, deptName) {
        console.log('🏢 Showing department requests:', { deptId, deptName });
        
        // Store department info in session storage
        sessionStorage.setItem('currentDepartmentId', deptId);
        sessionStorage.setItem('currentDepartmentName', deptName);
        
        // Navigate to department requests page
        this.app.showPage('department-requests');
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
