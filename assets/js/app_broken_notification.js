// IT Service Request Management JavaScript

class ITServiceApp {
    constructor() {
        this.currentUser = null;
        this.currentPage = 'dashboard';
        this.currentRequests = null; // Store current requests for re-rendering
        this.currentRecentRequests = null; // Store recent requests
        this.init();
    }

    init() {
        this.bindEvents();
        this.checkAuth();
        this.selectedFiles = []; // Store selected files
        
        // Initialize translation support
        this.initTranslationSupport();
    }

    initTranslationSupport() {
        // Listen for language changes
        document.addEventListener('languageChanged', (e) => {
            this.updateUIWithTranslations();
        });
        
        // Update UI when translation system is ready
        if (window.translationSystem) {
            this.updateUIWithTranslations();
        } else {
            // Wait for translation system to be ready
            document.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => this.updateUIWithTranslations(), 100);
            });
        }
    }

    updateUIWithTranslations() {
        // Update dynamic content that might not have data-translate attributes
        this.updateStatusTexts();
        this.updatePriorityTexts();
        this.updateMessages();
        
        // Re-render stored data with new translations
        if (this.currentRequests) {
            this.displayRequests(this.currentRequests);
        }
        if (this.currentRecentRequests) {
            this.displayRecentRequests(this.currentRecentRequests);
        }
    }

    updateStatusTexts() {
        // This will be called when language changes to update status texts
        if (this.currentPage === 'requests' && this.currentRequests) {
            this.displayRequests(this.currentRequests);
        }
    }

    updatePriorityTexts() {
        // This will be called when language changes to update priority texts
        if (this.currentPage === 'requests' && this.currentRequests) {
            this.displayRequests(this.currentRequests);
        }
    }

    updateMessages() {
        // Update any current messages or notifications
        const notifications = document.querySelectorAll('.notification');
        notifications.forEach(notification => {
            // You might want to re-translate notification messages here
        });
    }

    bindEvents() {
        // Login/Register events
        const loginForm = document.getElementById('loginForm');
        if (loginForm) loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        
        const registerForm = document.getElementById('registerForm');
        if (registerForm) registerForm.addEventListener('submit', (e) => this.handleRegister(e));
        
        const showRegister = document.getElementById('showRegister');
        if (showRegister) showRegister.addEventListener('click', (e) => this.showRegister(e));
        
        const showLogin = document.getElementById('showLogin');
        if (showLogin) showLogin.addEventListener('click', (e) => this.showLogin(e));
        
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) logoutBtn.addEventListener('click', () => this.logout());

        // Profile events
        const profileForm = document.getElementById('profileForm');
        if (profileForm) profileForm.addEventListener('submit', (e) => this.handleProfileSubmit(e));
        
        const passwordForm = document.getElementById('passwordForm');
        if (passwordForm) passwordForm.addEventListener('submit', (e) => this.handlePasswordSubmit(e));
        
        const refreshProfileBtn = document.getElementById('refreshProfileBtn');
        if (refreshProfileBtn) refreshProfileBtn.addEventListener('click', () => this.loadProfile());
        
        const userRoleForm = document.getElementById('userRoleForm');
        if (userRoleForm) userRoleForm.addEventListener('submit', (e) => this.handleUserRoleSubmit(e));

        // Navigation events
        const navLinks = document.querySelectorAll('.nav-link');
        console.log('Found nav links:', navLinks.length);
        navLinks.forEach((link, index) => {
            console.log(`Nav link ${index}:`, link.dataset.page, link.textContent);
            link.addEventListener('click', (e) => this.handleNavigation(e));
        });

        // Request form events
        const newRequestForm = document.getElementById('newRequestForm');
        if (newRequestForm) newRequestForm.addEventListener('submit', (e) => this.handleNewRequest(e));
        
        const cancelRequest = document.getElementById('cancelRequest');
        if (cancelRequest) cancelRequest.addEventListener('click', () => this.showPage('dashboard'));

        // Filter events
        const statusFilter = document.getElementById('statusFilter');
        if (statusFilter) statusFilter.addEventListener('change', () => this.loadRequests());
        
        const priorityFilter = document.getElementById('priorityFilter');
        if (priorityFilter) priorityFilter.addEventListener('change', () => this.loadRequests());
        
        const categoryFilter = document.getElementById('categoryFilter');
        if (categoryFilter) categoryFilter.addEventListener('change', () => this.loadRequests());

        // Modal events
        document.querySelectorAll('.close').forEach(closeBtn => {
            closeBtn.addEventListener('click', (e) => this.closeModal(e.target.closest('.modal')));
        });

        // Category events
        const addCategoryBtn = document.getElementById('addCategoryBtn');
        if (addCategoryBtn) addCategoryBtn.addEventListener('click', () => this.showCategoryModal());
        
        const categoryForm = document.getElementById('categoryForm');
        if (categoryForm) categoryForm.addEventListener('submit', (e) => this.handleCategorySubmit(e));
        
        document.querySelectorAll('.cancel-category').forEach(btn => {
            btn.addEventListener('click', () => this.closeModal(document.getElementById('categoryModal')));
        });

        // Comment events
        const addCommentBtn = document.getElementById('addCommentBtn');
        if (addCommentBtn) addCommentBtn.addEventListener('click', () => this.addComment());

        // Resolve form events
        const resolveForm = document.getElementById('resolveForm');
        if (resolveForm) resolveForm.addEventListener('submit', (e) => this.handleResolveSubmit(e));

        // User management events
        const addUserBtn = document.getElementById('addUserBtn');
        if (addUserBtn) addUserBtn.addEventListener('click', () => this.showUserModal());
        
        const userForm = document.getElementById('userForm');
        if (userForm) userForm.addEventListener('submit', (e) => this.handleUserSubmit(e));
        
        document.querySelectorAll('.cancel-user').forEach(btn => {
            btn.addEventListener('click', () => this.closeUserModal());
        });
        
        // Password reset checkbox
        const resetPassword = document.getElementById('resetPassword');
        if (resetPassword) resetPassword.addEventListener('change', (e) => {
            const passwordField = document.getElementById('userPassword');
            if (e.target.checked) {
                passwordField.setAttribute('required', 'required');
                passwordField.placeholder = 'Nhập mật khẩu mới';
            } else {
                const userId = document.getElementById('userId').value;
                if (userId) {
                    passwordField.removeAttribute('required');
                    passwordField.placeholder = '';
                }
            }
        });

        // User search and filter events
        const userSearch = document.getElementById('userSearch');
        if (userSearch) {
            userSearch.addEventListener('input', () => this.loadUsers());
        }
        
        const roleFilter = document.getElementById('roleFilter');
        if (roleFilter) {
            roleFilter.addEventListener('change', () => this.loadUsers());
        }

        // Support request events
        const supportStatusFilter = document.getElementById('supportStatusFilter');
        if (supportStatusFilter) {
            supportStatusFilter.addEventListener('change', () => this.loadSupportRequests());
        }
        
        // Reject request events
        const rejectStatusFilter = document.getElementById('rejectStatusFilter');
        if (rejectStatusFilter) {
            rejectStatusFilter.addEventListener('change', () => this.loadRejectRequests());
        }
        
        const adminSupportForm = document.getElementById('adminSupportForm');
        if (adminSupportForm) {
            adminSupportForm.addEventListener('submit', (e) => this.handleAdminSupportSubmit(e));
        }
        
        const adminRejectForm = document.getElementById('adminRejectForm');
        if (adminRejectForm) {
            adminRejectForm.addEventListener('submit', (e) => this.handleAdminRejectSubmit(e));
        }

        // File upload events
        const fileInput = document.getElementById('requestAttachments');
        const uploadArea = document.getElementById('fileUploadArea');
        
        fileInput.addEventListener('change', (e) => this.handleFileSelect(e));
        
        // Drag and drop events
        uploadArea.addEventListener('dragover', (e) => this.handleDragOver(e));
        uploadArea.addEventListener('dragleave', (e) => this.handleDragLeave(e));
        uploadArea.addEventListener('drop', (e) => this.handleFileDrop(e));

        // Window click event for modals
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.closeModal(e.target);
            }
        });
    }

    async handleLogin(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        try {
            const response = await this.apiCall('api/auth.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'login',
                    username: formData.get('username'),
                    password: formData.get('password')
                })
            });

            if (response.success) {
                this.currentUser = response.data;
                this.showDashboard();
                this.showNotification('Đăng nhập thành công!', 'success');
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            this.showNotification('Lỗi kết nối', 'error');
        }
    }

    async handleRegister(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        try {
            const response = await this.apiCall('api/auth.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'register',
                    username: formData.get('username'),
                    email: formData.get('email'),
                    password: formData.get('password'),
                    full_name: formData.get('full_name'),
                    department: formData.get('department'),
                    phone: formData.get('phone')
                })
            });

            if (response.success) {
                this.showNotification('Đăng ký thành công! Vui lòng đăng nhập.', 'success');
                this.showLogin();
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            this.showNotification('Lỗi kết nối', 'error');
        }
    }

    showRegister(e) {
        e.preventDefault();
        document.getElementById('loginScreen').classList.remove('active');
        document.getElementById('registerScreen').classList.add('active');
    }

    showLogin(e) {
        if (e) e.preventDefault();
        document.getElementById('registerScreen').classList.remove('active');
        document.getElementById('loginScreen').classList.add('active');
    }

    async logout() {
        this.currentUser = null;
        
        // Call logout API
        this.apiCall('api/auth.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'logout' })
        }).then(() => {
            // Show login screen regardless of API response
            this.showLoginScreen();
        }).catch(() => {
            // Still show login screen even if API fails
            this.showLoginScreen();
        });
    }

    showLoginScreen() {
        // Hide loading screen first
        const loadingScreen = document.getElementById('loadingScreen');
        if (loadingScreen) {
            loadingScreen.classList.remove('active');
        }
        
        const dashboardScreen = document.getElementById('dashboardScreen');
        const loginScreen = document.getElementById('loginScreen');
        const loginForm = document.getElementById('loginForm');
        
        if (dashboardScreen) dashboardScreen.classList.remove('active');
        if (loginScreen) loginScreen.classList.add('active');
        if (loginForm) loginForm.reset();
    }

    async viewCategoryRequests(categoryId) {
        try {
            this.showNotification('Đang tải yêu cầu theo danh mục...', 'info');
            
            const response = await this.apiCall(`api/categories.php?action=requests&category_id=${categoryId}`);
            
            if (response.success) {
                this.displayCategoryRequests(response.data, categoryId);
            } else {
                this.showNotification('Không thể tải yêu cầu theo danh mục', 'error');
            }
        } catch (error) {
            this.showNotification('Lỗi kết nối', 'error');
        }
    }

    displayCategoryRequests(requests, categoryId) {
        const container = document.getElementById('categoriesList');
        
        if (requests.length === 0) {
            container.innerHTML = `
                <div class="category-requests-header">
                    <button class="btn btn-secondary" onclick="app.loadCategories()">
                        <i class="fas fa-arrow-left"></i> Quay lại danh mục
                    </button>
                    <h3>Yêu cầu theo danh mục</h3>
                </div>
                <p>Không có yêu cầu nào trong danh mục này.</p>
            `;
            return;
        }

        const requestsHtml = requests.map(request => this.createRequestCard(request)).join('');
        
        container.innerHTML = `
            <div class="category-requests-header">
                <button class="btn btn-secondary" onclick="app.loadCategories()">
                    <i class="fas fa-arrow-left"></i> Quay lại danh mục
                </button>
                <h3>Yêu cầu theo danh mục (${requests.length})</h3>
                <div class="status-filters">
                    <button class="btn btn-outline-primary active" onclick="app.filterCategoryRequests('all', this)">Tất cả</button>
                    <button class="btn btn-outline-primary" onclick="app.filterCategoryRequests('open', this)">Mới</button>
                    <button class="btn btn-outline-primary" onclick="app.filterCategoryRequests('in_progress', this)">Đang xử lý</button>
                    <button class="btn btn-outline-primary" onclick="app.filterCategoryRequests('resolved', this)">Đã giải quyết</button>
                    <button class="btn btn-outline-primary" onclick="app.filterCategoryRequests('closed', this)">Đã đóng</button>
                </div>
            </div>
            <div class="requests-grid">
                ${requestsHtml}
            </div>
        `;
        
        // Store current category and requests for filtering
        this.currentCategoryId = categoryId;
        this.currentCategoryRequests = requests;
    }

    async filterCategoryRequests(status, button) {
        // Update button states
        document.querySelectorAll('.status-filters button').forEach(btn => {
            btn.classList.remove('active');
        });
        button.classList.add('active');
        
        try {
            const response = await this.apiCall(`api/categories.php?action=requests&category_id=${this.currentCategoryId}&status=${status}`);
            
            if (response.success) {
                this.displayCategoryRequests(response.data, this.currentCategoryId);
            }
        } catch (error) {
            this.showNotification('Lỗi lọc yêu cầu', 'error');
        }
    }

    createRequestCard(request) {
        const statusColors = {
            'open': 'warning',
            'in_progress': 'info', 
            'resolved': 'success',
            'closed': 'secondary'
        };
        
        const statusTexts = {
            'open': 'Mới',
            'in_progress': 'Đang xử lý',
            'resolved': 'Đã giải quyết',
            'closed': 'Đã đóng'
        };

        return `
            <div class="request-card" onclick="app.viewRequestDetail(${request.id})">
                <div class="request-header">
                    <h4>${request.title}</h4>
                    <span class="badge badge-${statusColors[request.status]}">${statusTexts[request.status]}</span>
                </div>
                <div class="request-body">
                    <p class="request-description">${request.description.substring(0, 150)}...</p>
                    <div class="request-meta">
                        <span><i class="fas fa-user"></i> ${request.full_name || request.username}</span>
                        <span><i class="fas fa-calendar"></i> ${this.formatDate(request.created_at)}</span>
                        ${request.assigned_full_name ? `
                            <span><i class="fas fa-user-tie"></i> ${request.assigned_full_name}</span>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    }

    viewRequestDetail(requestId) {
        this.showRequestDetail(requestId);
    }

    showDashboard() {
        // Hide loading screen first
        const loadingScreen = document.getElementById('loadingScreen');
        if (loadingScreen) {
            loadingScreen.classList.remove('active');
        }
        
        const loginScreen = document.getElementById('loginScreen');
        const registerScreen = document.getElementById('registerScreen');
        const dashboardScreen = document.getElementById('dashboardScreen');
        
        if (loginScreen) loginScreen.classList.remove('active');
        if (registerScreen) registerScreen.classList.remove('active');
        if (dashboardScreen) dashboardScreen.classList.add('active');
        
        // Update user info
        document.getElementById('userInfo').textContent = this.currentUser.full_name;
        
        // Show/hide menu items based on role
        document.getElementById('adminMenu').style.display = 'none';
        document.getElementById('adminDepartmentMenu').style.display = 'none';
        document.getElementById('adminSupportMenu').style.display = 'none';
        document.getElementById('adminRejectMenu').style.display = 'none';
        document.getElementById('newRequestMenu').style.display = 'none';
        
        if (this.currentUser.role === 'admin') {
            document.getElementById('adminMenu').style.display = 'block';
            document.getElementById('adminDepartmentMenu').style.display = 'block';
            document.getElementById('adminSupportMenu').style.display = 'block';
            document.getElementById('adminRejectMenu').style.display = 'block';
            document.getElementById('newRequestMenu').style.display = 'none';
        } else if (this.currentUser.role === 'staff') {
            // Staff should see limited menus - NOT adminMenu
            document.getElementById('adminMenu').style.display = 'none'; // Staff can't see full admin menu
            document.getElementById('adminDepartmentMenu').style.display = 'none'; // Staff can't manage departments
            document.getElementById('adminSupportMenu').style.display = 'block'; // Staff can see support requests
            document.getElementById('adminRejectMenu').style.display = 'block'; // Staff can handle reject requests
            // Show new request menu for staff
            document.getElementById('newRequestMenu').style.display = 'none'; // Staff typically handles requests, not creates new ones
        } else {
            // Regular user - only new request menu
            document.getElementById('newRequestMenu').style.display = 'block';
        }
        
        // Load dashboard data
        this.loadDashboard();
        this.loadCategories();
    }

    handleNavigation(e) {
        console.log('=== handleNavigation called ===');
        
        // Prevent multiple calls
        if (this._navigating) {
            console.log('Already navigating, ignoring');
            return;
        }
        this._navigating = true;
        
        setTimeout(() => {
            this._navigating = false;
        }, 100);
        
        const navLink = e.target.closest('.nav-link');
        if (!navLink) {
            console.log('No nav-link found, ignoring');
            return;
        }
        
        const page = navLink.dataset.page;
        const href = navLink.href;
        
        console.log('Navigation data:', { page, href });
        
        // Check if this is an internal navigation (has data-page)
        if (page) {
            // Check if user is trying to access admin-only pages
            const adminPages = ['users', 'departments'];
            const staffPages = ['support-requests', 'reject-requests'];
            
            if (adminPages.includes(page) && this.currentUser.role !== 'admin') {
                console.log('❌ Non-admin user trying to access admin page:', page);
                this.showNotification('Chỉ admin mới có quyền truy cập trang này', 'error');
                return;
            }
            
            if (staffPages.includes(page) && !['admin', 'staff'].includes(this.currentUser.role)) {
                console.log('❌ Non-admin/staff user trying to access staff page:', page);
                this.showNotification('Chỉ admin và staff mới có quyền truy cập trang này', 'error');
                return;
            }
            
            console.log('Internal navigation detected, calling showPage with:', page);
            e.preventDefault(); // Only prevent default for internal navigation
            this.showPage(page);
        } else {
            console.log('External link detected, allowing navigation');
            // This is an external link, let browser handle it naturally
            // Don't prevent default - let browser navigate
            if (href && href !== '#') {
                console.log('Navigating to:', href);
                window.location.href = href;
            }
        }
    }

    showPage(page) {
        console.log('=== showPage called with:', page, '===');
        console.log('Current user:', this.currentUser);
        
        // Update navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        
        // Only update active state for internal pages
        const navPageElement = document.querySelector(`[data-page="${page}"]`);
        if (navPageElement) {
            navPageElement.classList.add('active');
        }

        // Update pages
        document.querySelectorAll('.page').forEach(p => {
            p.classList.remove('active');
        });
        
        const pageId = page === 'new-request' ? 'newRequestPage' : `${page}Page`;
        const pageElement = document.getElementById(pageId);
        
        console.log('Looking for page element:', pageId);
        console.log('Page element found:', !!pageElement);
        
        if (pageElement) {
            pageElement.classList.add('active');
        } else {
            console.error(`Page element not found: ${pageId}`);
            return;
        }

        this.currentPage = page;
        console.log('Current page set to:', this.currentPage);

        // Load page-specific data
        console.log('Loading page-specific data for:', page);
        switch(page) {
            case 'dashboard':
                this.loadDashboard();
                break;
            case 'profile':
                this.loadProfile();
                break;
            case 'requests':
                this.loadRequests();
                break;
            case 'new-request':
                this.loadCategories();
                break;
            case 'categories':
                this.loadCategories();
                break;
            case 'users':
                console.log('Users page detected, checking role...');
                if (this.currentUser.role === 'admin') {
                    console.log('User is admin, calling loadUsers()');
                    this.loadUsers();
                } else {
                    console.log('❌ User is not admin, denying access to users page');
                    this.showNotification('Chỉ admin mới có quyền truy cập quản lý người dùng', 'error');
                    // Redirect to dashboard
                    setTimeout(() => this.showPage('dashboard'), 1000);
                }
                break;
            case 'departments':
                if (this.currentUser.role === 'admin') {
                    if (this.departmentsManager) {
                        this.departmentsManager.loadDepartments();
                    }
                } else {
                    console.log('❌ User is not admin, denying access to departments page');
                    this.showNotification('Chỉ admin mới có quyền quản lý phòng ban', 'error');
                    setTimeout(() => this.showPage('dashboard'), 1000);
                }
                break;
            case 'support-requests':
                if (['admin', 'staff'].includes(this.currentUser.role)) {
                    this.loadSupportRequests();
                } else {
                    console.log('❌ User is not admin/staff, denying access to support requests page');
                    this.showNotification('Chỉ admin và staff mới có quyền xem yêu cầu hỗ trợ', 'error');
                    setTimeout(() => this.showPage('dashboard'), 1000);
                }
                break;
            case 'reject-requests':
                if (['admin', 'staff'].includes(this.currentUser.role)) {
                    this.loadRejectRequests();
                } else {
                    console.log('❌ User is not admin/staff, denying access to reject requests page');
                    this.showNotification('Chỉ admin và staff mới có quyền xem yêu cầu từ chối', 'error');
                    setTimeout(() => this.showPage('dashboard'), 1000);
                }
                break;
        }
    }

    async loadDashboard() {
        try {
            // Load all requests for accurate stats
            const statsResponse = await this.apiCall('api/service_requests.php?action=list');
            
            // Load recent requests (limited to 10 to have more data for prioritization)
            const recentResponse = await this.apiCall('api/service_requests.php?action=list&limit=10');
            
            if (statsResponse.success && recentResponse.success) {
                const allRequests = statsResponse.data.requests;
                let recentRequests = recentResponse.data.requests;
                
                // Prioritize support requests in recent requests
                const supportRequests = recentRequests.filter(r => r.status === 'request_support');
                const otherRequests = recentRequests.filter(r => r.status !== 'request_support');
                
                // Put support requests first, then other requests, limit to 5 total
                recentRequests = [...supportRequests, ...otherRequests].slice(0, 5);
                
                // Update stats from all requests
                const stats = {
                    total: allRequests.length,
                    open: allRequests.filter(r => r.status === 'open').length,
                    in_progress: allRequests.filter(r => r.status === 'in_progress').length,
                    resolved: allRequests.filter(r => r.status === 'resolved').length,
                    rejected: allRequests.filter(r => r.status === 'rejected').length,
                    request_support: allRequests.filter(r => r.status === 'request_support').length,
                    closed: allRequests.filter(r => r.status === 'closed').length
                };

                // Update stats from all requests
                const totalElement = document.getElementById('totalRequests');
                if (totalElement) totalElement.textContent = stats.total;
                
                const openElement = document.getElementById('openRequests');
                if (openElement) openElement.textContent = stats.open;
                
                const inProgressElement = document.getElementById('inProgressRequests');
                if (inProgressElement) inProgressElement.textContent = stats.in_progress;
                
                const resolvedElement = document.getElementById('resolvedRequests');
                if (resolvedElement) resolvedElement.textContent = stats.resolved;
                
                const rejectedElement = document.getElementById('rejectedRequests');
                if (rejectedElement) rejectedElement.textContent = stats.rejected;
                
                const requestSupportCount = document.getElementById('requestSupportCount');
                if (requestSupportCount) requestSupportCount.textContent = stats.request_support;
                
                const closedRequests = document.getElementById('closedRequests');
                if (closedRequests) closedRequests.textContent = stats.closed;

                // Load recent requests (limited)
                this.displayRecentRequests(recentRequests);
            }
        } catch (error) {
            console.error('Dashboard load error:', error);
        }
    }

    async loadSupportRequestsCount() {
        try {
            // Count all support requests (not just pending)
            const response = await this.apiCall('api/support_requests.php?action=list&limit=1');
            
            if (response.success) {
                const supportCount = response.data.pagination ? response.data.pagination.total : response.data.length;
                const supportElement = document.getElementById('supportRequests');
                if (supportElement) supportElement.textContent = supportCount;
            }
        } catch (error) {
            console.error('Support requests count error:', error);
            const supportElement = document.getElementById('supportRequests');
            if (supportElement) supportElement.textContent = '0';
        }
    }

    displayRecentRequests(requests) {
        // Store recent requests for language switching
        this.currentRecentRequests = requests;
        
        const container = document.getElementById('recentRequestsList');
        
        if (!container) {
            console.error('Recent requests container not found');
            return;
        }
        
        if (requests.length === 0) {
            container.innerHTML = '<p>Chưa có yêu cầu nào.</p>';
            return;
        }

        container.innerHTML = requests.map(request => `
            <div class="request-item" onclick="app.showRequestDetail(${request.id})">
                <div class="request-header">
                    <div class="request-title">
                        <span class="request-id">ID: ${request.id}</span>
                        ${request.title}
                    </div>
                    <span class="badge status-${request.status}">${this.getStatusText(request.status)}</span>
                </div>
                <div class="request-meta">
                    <span><i class="fas fa-user"></i> ${request.requester_name}</span>
                    <span><i class="fas fa-tag"></i> ${request.category_name}</span>
                    <span><i class="fas fa-clock"></i> ${this.formatDate(request.created_at)}</span>
                </div>
                <div class="request-description">${request.description.substring(0, 100)}...</div>
            </div>
        `).join('');
    }

    async loadRequests(page = 1) {
        try {
            const statusFilter = document.getElementById('statusFilter');
            const status = statusFilter ? statusFilter.value : '';
            
            const priorityFilter = document.getElementById('priorityFilter');
            const priority = priorityFilter ? priorityFilter.value : '';
            
            const categoryFilter = document.getElementById('categoryFilter');
            const category = categoryFilter ? categoryFilter.value : '';
            
            let url = `api/service_requests.php?action=list&page=${page}`;
            if (status) url += `&status=${status}`;
            if (priority) url += `&priority=${priority}`;
            if (category) url += `&category=${category}`;

            const response = await this.apiCall(url);
            
            if (response.success) {
                this.displayRequests(response.data.requests);
                this.displayPagination(response.data);
                this.updateStatusCounts(response.data.status_counts || {});
            }
        } catch (error) {
            console.error('Requests load error:', error);
        }
    }

    displayRequests(requests) {
        // Store requests for language switching
        this.currentRequests = requests;
        
        const container = document.getElementById('requestsList');
        
        if (!container) {
            console.error('Requests list container not found');
            return;
        }
        
        if (requests.length === 0) {
            container.innerHTML = '<p>Không tìm thấy yêu cầu nào.</p>';
            return;
        }

        container.innerHTML = requests.map(request => `
            <div class="request-item" onclick="app.showRequestDetail(${request.id})">
                <div class="request-header">
                    <div class="request-title">
                        <span class="request-id">ID: ${request.id}</span>
                        ${request.title}
                    </div>
                    <div>
                        <span class="badge priority-${request.priority}">${this.getPriorityText(request.priority)}</span>
                        <span class="badge status-${request.status}">${this.getStatusText(request.status)}</span>
                    </div>
                </div>
                <div class="request-meta">
                    <span><i class="fas fa-user"></i> ${request.requester_name}</span>
                    <span><i class="fas fa-tag"></i> ${request.category_name}</span>
                    <span><i class="fas fa-clock"></i> ${this.formatDate(request.created_at)}</span>
                    ${request.assigned_name ? `<span><i class="fas fa-user-check"></i> ${request.assigned_name}</span>` : ''}
                    ${request.accepted_at ? `<span><i class="fas fa-hand-paper"></i> Đã nhận: ${this.formatDate(request.accepted_at)}</span>` : ''}
                </div>
                <div class="request-description">${request.description.substring(0, 150)}...</div>
                ${this.currentUser && this.currentUser.role === 'admin' ? `
                <div class="request-actions" onclick="event.stopPropagation()">
                    <button class="btn btn-secondary btn-sm" onclick="app.editRequest(${request.id})">
                        <i class="fas fa-edit"></i> Sửa
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="app.deleteRequest(${request.id})">
                        <i class="fas fa-trash"></i> Xóa
                    </button>
                </div>
                ` : ''}
            </div>
        `).join('');
    }

    displayPagination(data) {
        const container = document.getElementById('pagination');
        const { page, total_pages } = data;
        
        if (total_pages <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '';
        
        // Previous button
        html += `<button onclick="app.loadRequests(${page - 1})" ${page === 1 ? 'disabled' : ''}>Previous</button>`;
        
        // Page numbers
        for (let i = 1; i <= total_pages; i++) {
            if (i === page || i === 1 || i === total_pages || (i >= page - 1 && i <= page + 1)) {
                html += `<button onclick="app.loadRequests(${i})" class="${i === page ? 'active' : ''}">${i}</button>`;
            } else if (i === page - 2 || i === page + 2) {
                html += '<span>...</span>';
            }
        }
        
        // Next button
        html += `<button onclick="app.loadRequests(${page + 1})" ${page === total_pages ? 'disabled' : ''}>Next</button>`;
        
        container.innerHTML = html;
    }

    updateStatusCounts(statusCounts) {
        // Update status count displays
        const counts = {
            open: statusCounts.open || 0,
            in_progress: statusCounts.in_progress || 0,
            resolved: statusCounts.resolved || 0,
            rejected: statusCounts.rejected || 0,
            request_support: statusCounts.request_support || 0,
            closed: statusCounts.closed || 0
        };

        // Update dropdown options with counts
        const openCount = document.getElementById('openCount');
        const inProgressCount = document.getElementById('inProgressCount');
        const resolvedCount = document.getElementById('resolvedCount');
        const rejectedCount = document.getElementById('rejectedCount');
        const requestSupportCount = document.getElementById('requestSupportCount');
        const closedCount = document.getElementById('closedCount');

        if (openCount) openCount.textContent = `(${counts.open})`;
        if (inProgressCount) inProgressCount.textContent = `(${counts.in_progress})`;
        if (resolvedCount) resolvedCount.textContent = `(${counts.resolved})`;
        if (rejectedCount) rejectedCount.textContent = `(${counts.rejected})`;
        if (requestSupportCount) requestSupportCount.textContent = `(${counts.request_support})`;
        if (closedCount) closedCount.textContent = `(${counts.closed})`;
    }

    async showRequestsByStatus(status) {
        console.log('=== SHOW REQUESTS BY STATUS ===');
        console.log('Status:', status);
        
        // Navigate to requests page with status filter
        this.showPage('requests');
        
        // Set status filter and load requests
        if (status === 'all') {
            // Clear status filter
            const statusFilter = document.getElementById('statusFilter');
            if (statusFilter) {
                statusFilter.value = '';
            }
            await this.loadRequests();
        } else if (status === 'support') {
            console.log('=== SHOWING SUPPORT PAGE ===');
            console.log('Current user role:', this.currentUser.role);
            
            // Load support requests (only for admin, staff can see their own)
            if (this.currentUser.role === 'admin' || this.currentUser.role === 'staff') {
                await this.loadSupportRequests();
            } else {
                this.showNotification('Bạn không có quyền xem yêu cầu hỗ trợ', 'error');
            }
        } else if (status === 'request_support') {
            // Set status filter for service requests
            const statusFilter = document.getElementById('statusFilter');
            if (statusFilter) {
                statusFilter.value = 'request_support';
            }
            await this.loadRequests();
        } else if (status === 'rejected') {
            // Load rejected requests
            const statusFilter = document.getElementById('statusFilter');
            if (statusFilter) {
                statusFilter.value = 'rejected';
            }
            await this.loadRequests();
        } else {
            // Set status filter for service requests
            const statusFilter = document.getElementById('statusFilter');
            if (statusFilter) {
                statusFilter.value = status;
            }
            await this.loadRequests();
        }
    }

    async loadSupportRequests() {
        try {
            console.log('=== LOADING SUPPORT REQUESTS ===');
            console.log('Current user:', this.currentUser);
            console.log('Filter element:', document.getElementById('supportStatusFilter'));
            
            const status = document.getElementById('supportStatusFilter').value || 'all';
            console.log('Loading with status:', status);
            
            // For admin, load all requests by default, for staff load pending
            if (this.currentUser.role === 'admin' && status === 'all') {
                const response = await this.apiCall('api/support_requests.php?action=list');
                console.log('API response (all):', response);
                
                if (response.success) {
                    const supportRequests = response.data;
                    console.log('Support requests loaded (all):', supportRequests);
                    this.displaySupportRequests(supportRequests);
                } else {
                    this.showNotification(response.message, 'error');
                }
            } else {
                const response = await this.apiCall(`api/support_requests.php?action=list&status=${status}`);
                console.log('API response (filtered):', response);
                
                if (response.success) {
                    const supportRequests = response.data;
                    console.log('Support requests loaded (filtered):', supportRequests);
                    this.displaySupportRequests(supportRequests);
                } else {
                    this.showNotification(response.message, 'error');
                }
            }
        } catch (error) {
            console.error('Support requests load error:', error);
            this.showNotification('Lỗi tải yêu cầu hỗ trợ', 'error');
        }
    }

    displaySupportRequests(supportRequests) {
        console.log('=== DISPLAY SUPPORT REQUESTS ===');
        console.log('Support requests to display:', supportRequests);
        console.log('Container element:', document.getElementById('supportRequestsList'));
        
        const container = document.getElementById('supportRequestsList');
        
        if (!container) {
            console.error('Support requests list container not found');
            return;
        }
        
        if (supportRequests.length === 0) {
            console.log('No support requests to display');
            container.innerHTML = '<p>Không có yêu cầu hỗ trợ nào.</p>';
            return;
        }

        console.log('Container found, rendering HTML...');
        container.innerHTML = supportRequests.map(support => `
            <div class="request-item support-request" data-support-id="${support.id}">
                <div class="request-header">
                    <h4>
                        <a href="request-detail.html?id=${support.service_request_id}" target="_blank">
                            #${support.service_request_id} - ${support.request_title}
                        </a>
                    </h4>
                    <div class="request-badges">
                        <span class="badge status-${support.status}">${this.getSupportStatusText(support.status)}</span>
                        <span class="badge support-type-${support.support_type}">${this.getSupportTypeText(support.support_type)}</span>
                    </div>
                </div>
                
                <div class="request-meta">
                    <div class="meta-item">
                        <strong>Người tạo:</strong> ${support.requester_name}
                    </div>
                    <div class="meta-item">
                        <strong>Ngày tạo:</strong> ${this.formatDate(support.created_at)}
                    </div>
                    <div class="meta-item">
                        <strong>Chi tiết:</strong> ${support.support_details}
                    </div>
                    <div class="meta-item">
                        <strong>Lý do:</strong> ${support.support_reason}
                    </div>
                    ${support.admin_reason ? `
                        <div class="meta-item">
                            <strong>Quyết định ADMIN:</strong> ${support.admin_reason}
                        </div>
                        <div class="meta-item">
                            <strong>Trạng thái yêu cầu:</strong> 
                            <span class="badge status-${support.service_request_status || 'unknown'}">${this.getStatusText(support.service_request_status || 'unknown')}</span>
                        </div>
                    ` : ''}
                </div>
                
                <div class="request-actions">
                    ${support.status === 'pending' && this.currentUser.role === 'admin' ? `
                        <button class="btn btn-primary" onclick="app.showAdminSupportModal(${support.id})">
                            <i class="fas fa-gavel"></i> Xử lý
                        </button>
                    ` : ''}
                    ${support.admin_reason ? `
                        <div class="admin-reason">
                            <strong>Quyết định ADMIN:</strong> ${support.admin_reason}
                        </div>
                    ` : ''}
                </div>
            </div>
        `).join('');
    }

    async showRequestDetail(id) {
        console.log('=== SHOW REQUEST DETAIL ===');
        console.log('Request ID:', id);
        
        // Check if user is authenticated before redirecting
        if (!this.currentUser || !this.currentUser.id) {
            console.log('User not authenticated, staying on current page');
            this.showNotification('Vui lòng đăng nhập để xem chi tiết', 'error');
            return;
        }
        
        // Navigate to detail page
        console.log('User authenticated, redirecting to detail page');
        window.location.href = `request-detail.html?id=${id}`;
    }

    async showSupportRequestDetail(supportId) {
        console.log('Showing support request detail:', supportId);
        // TODO: Implement support request detail view
        this.showNotification('Chi tiết yêu cầu hỗ trợ sẽ được hiển thị', 'info');
    }

    displayRequestDetail(request) {
        const container = document.getElementById('requestDetails');
        
        container.innerHTML = `
            <div class="request-detail" data-request-id="${request.id}">
                <h4>${request.title}</h4>
                <div class="request-meta">
                    <span><strong>Người tạo:</strong> ${request.requester_name}</span>
                    <span><strong>Email:</strong> ${request.requester_email}</span>
                    <span><strong>Điện thoại:</strong> ${request.requester_phone || 'N/A'}</span>
                </div>
                ${request.assigned_name ? `
                    <div class="request-meta">
                        <span><strong>Người nhận:</strong> ${request.assigned_name}</span>
                        <span><strong>Email người nhận:</strong> ${request.assigned_email || 'N/A'}</span>
                    </div>
                ` : ''}
                <div class="request-meta">
                    <span><strong>Danh mục:</strong> ${request.category_name}</span>
                    <span><strong>Ưu tiên:</strong> <span class="badge priority-${request.priority}">${this.getPriorityText(request.priority)}</span></span>
                    <span><strong>Trạng thái:</strong> <span class="badge status-${request.status}">${this.getStatusText(request.status)}</span></span>
                </div>
                <div class="request-description">
                    <strong>Mô tả:</strong>
                    <p>${request.description}</p>
                </div>
                ${request.attachments && request.attachments.length > 0 ? `
                    <div class="attachments-section">
                        <h5><i class="fas fa-paperclip"></i> Tệp đính kèm (${request.attachments.length})</h5>
                        <div class="attachments-list">
                            ${request.attachments.map(attachment => {
                                const isImage = attachment.mime_type.startsWith('image/');
                                console.log('Attachment:', attachment.original_name, 'MIME:', attachment.mime_type, 'Is Image:', isImage);
                                return `
                                    <div class="attachment-item">
                                        <div class="attachment-info">
                                            <i class="fas fa-file"></i>
                                            <span class="attachment-name">${attachment.original_name}</span>
                                            <span class="attachment-size">(${app.formatFileSize(attachment.file_size)})</span>
                                        </div>
                                        <div class="attachment-actions">
                                            ${isImage ? `
                                                <img src="uploads/requests/${attachment.filename}" 
                                                     alt="${attachment.original_name}" 
                                                     class="attachment-preview"
                                                     onclick="app.showImageModal('uploads/requests/${attachment.filename}', '${attachment.original_name}')">
                                                <div class="image-overlay">
                                                    <i class="fas fa-search-plus"></i>
                                                </div>
                                            ` : ''}
                                            <a href="uploads/requests/${attachment.filename}" 
                                               class="btn btn-sm btn-secondary" 
                                               target="_blank"
                                               download="${attachment.original_name}">
                                                <i class="fas fa-download"></i> Tải về
                                            </a>
                                        </div>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </div>
                ` : ''}
                <div class="request-meta">
                    <span><strong>Ngày tạo:</strong> ${this.formatDate(request.created_at)}</span>
                    ${request.accepted_at ? `<span><strong>Ngày nhận:</strong> ${this.formatDate(request.accepted_at)}</span>` : ''}
                    ${request.resolved_at ? `<span><strong>Ngày giải quyết:</strong> ${this.formatDate(request.resolved_at)}</span>` : ''}
                </div>
                ${request.resolution ? `
                    <div class="resolution-info">
                        <h4><i class="fas fa-check-circle"></i> Thông tin giải quyết</h4>
                        <div class="resolution-details">
                            <div class="resolution-item">
                                <strong>Người giải quyết:</strong> ${request.resolution.resolver_name}
                            </div>
                            <div class="resolution-item">
                                <strong>Ngày giải quyết:</strong> ${this.formatDate(request.resolution.resolved_at)}
                            </div>
                            <div class="resolution-item">
                                <strong>Mô tả lỗi:</strong> ${request.resolution.error_description}
                            </div>
                            <div class="resolution-item">
                                <strong>Loại lỗi:</strong> ${this.getErrorTypeText(request.resolution.error_type)}
                            </div>
                            ${request.resolution.replacement_materials ? `
                                <div class="resolution-item">
                                    <strong>Vật tư thay thế:</strong> ${request.resolution.replacement_materials}
                                </div>
                            ` : ''}
                            <div class="resolution-item">
                                <strong>Cách khắc phục:</strong> ${request.resolution.solution_method}
                            </div>
                        </div>
                    </div>
                ` : ''}
                ${this.currentUser.role === 'admin' ? `
                    <div class="request-actions">
                        ${request.status === 'open' && !request.assigned_to ? `
                            <button class="btn btn-success" onclick="app.acceptRequest(${request.id})">
                                <i class="fas fa-check"></i> Nhận yêu cầu
                            </button>
                        ` : ''}
                        <select id="statusUpdate" class="form-control">
                            <option value="">Cập nhật trạng thái</option>
                            <option value="open" ${request.status === 'open' ? 'selected' : ''}>Mở</option>
                            <option value="in_progress" ${request.status === 'in_progress' ? 'selected' : ''}>Đang xử lý</option>
                            <option value="resolved" ${request.status === 'resolved' ? 'selected' : ''}>Đã giải quyết</option>
                            <option value="closed" ${request.status === 'closed' ? 'selected' : ''}>Đã đóng</option>
                        </select>
                        <button class="btn btn-primary" onclick="app.updateRequestStatus(${request.id})">Cập nhật</button>
                    </div>
                ` : this.currentUser.role === 'staff' ? `
                    <div class="request-actions">
                        ${request.status === 'open' && !request.assigned_to ? `
                            <button class="btn btn-success" onclick="app.acceptRequest(${request.id})">
                                <i class="fas fa-check"></i> Nhận yêu cầu
                            </button>
                        ` : ''}
                        ${request.status === 'in_progress' && request.assigned_to == this.currentUser.id ? `
                            <button class="btn btn-primary" onclick="app.showResolveModal(${request.id})">
                                <i class="fas fa-check-circle"></i> Đã giải quyết
                            </button>
                        ` : ''}
                        <!-- Debug info -->
                        <div style="font-size: 12px; color: #666; margin-top: 10px;">
                            Debug: Status=${request.status}, Assigned=${request.assigned_to}, CurrentUser=${this.currentUser.id}
                        </div>
                    </div>
                ` : ''}
            </div>
        `;

        // Display comments
        this.displayComments(request.comments || []);
    }

    displayComments(comments) {
        const container = document.getElementById('commentsList');
        
        if (comments.length === 0) {
            container.innerHTML = '<p>Chưa có bình luận nào.</p>';
            return;
        }

        container.innerHTML = comments.map(comment => `
            <div class="comment">
                <div class="comment-header">
                    <span class="comment-author">${comment.user_name}</span>
                    <span class="comment-date">${this.formatDate(comment.created_at)}</span>
                </div>
                <div class="comment-text">${comment.comment}</div>
            </div>
        `).join('');
    }

    async acceptRequest(id) {
        try {
            const response = await this.apiCall('api/service_requests.php', {
                method: 'PUT',
                body: JSON.stringify({
                    id: id,
                    action: 'accept'
                })
            });

            if (response.success) {
                this.showNotification('Yêu cầu đã được nhận thành công', 'success');
                this.showRequestDetail(id);
                if (this.currentPage === 'requests') {
                    this.loadRequests();
                }
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            this.showNotification('Lỗi kết nối', 'error');
        }
    }

    async updateRequestStatus(id) {
        const status = document.getElementById('statusUpdate').value;
        
        if (!status) {
            this.showNotification('Vui lòng chọn trạng thái', 'error');
            return;
        }

        try {
            const response = await this.apiCall('api/service_requests.php', {
                method: 'PUT',
                body: JSON.stringify({
                    id: id,
                    status: status
                })
            });

            if (response.success) {
                this.showNotification('Cập nhật trạng thái thành công', 'success');
                this.showRequestDetail(id);
                if (this.currentPage === 'requests') {
                    this.loadRequests();
                }
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            this.showNotification('Lỗi kết nối', 'error');
        }
    }

    async addComment() {
        const commentText = document.getElementById('commentText').value.trim();
        const requestDetail = document.querySelector('#requestDetails .request-detail');
        const requestId = requestDetail ? requestDetail.dataset.requestId : null;
        
        if (!commentText) {
            this.showNotification('Vui lòng nhập bình luận', 'error');
            return;
        }
        
        if (!requestId) {
            this.showNotification('Không tìm thấy ID yêu cầu', 'error');
            return;
        }

        try {
            const response = await this.apiCall('api/comments.php', {
                method: 'POST',
                body: JSON.stringify({
                    service_request_id: requestId,
                    comment: commentText
                })
            });

            if (response.success) {
                document.getElementById('commentText').value = '';
                this.showRequestDetail(requestId);
                this.showNotification('Bình luận đã được thêm', 'success');
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            this.showNotification('Lỗi kết nối', 'error');
        }
    }

    async handleNewRequest(e) {
        e.preventDefault();
        console.log('Handling new request submission...');
        
        // Get buttons and add loading state IMMEDIATELY
        const submitBtn = e.target.querySelector('button[type="submit"]');
        const cancelBtn = e.target.querySelector('#cancelRequest');
        const originalSubmitText = submitBtn.innerHTML;
        
        // Show loading overlay immediately
        const loadingOverlay = document.getElementById('loadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.style.display = 'flex';
        }
        
        // Show loading state on button
        submitBtn.disabled = true;
        cancelBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
        
        // Add a small delay to ensure UI updates
        await new Promise(resolve => setTimeout(resolve, 100));
        
        const formData = new FormData(e.target);
        console.log('Form data:', Object.fromEntries(formData));
        
        try {
            // Create FormData for file upload
            const submitData = new FormData();
            submitData.append('action', 'create');
            submitData.append('title', formData.get('title'));
            submitData.append('description', formData.get('description'));
            submitData.append('category_id', formData.get('category_id'));
            submitData.append('priority', formData.get('priority'));
            
            // Add selected files
            this.selectedFiles.forEach((file) => {
                submitData.append('attachments[]', file);
            });
            
            // Update loading text
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi yêu cầu...';
            
            const response = await fetch('api/service_requests.php', {
                method: 'POST',
                body: submitData,
                credentials: 'include'
            });
            
            const result = await response.json();
            console.log('Create request response:', result);

            if (result.success) {
                this.showNotification('Yêu cầu đã được tạo thành công', 'success');
                e.target.reset();
                this.selectedFiles = [];
                this.updateFileList();
                this.showPage('requests');
            } else {
                this.showNotification(result.message, 'error');
            }
        } catch (error) {
            console.error('Create request error:', error);
            this.showNotification('Lỗi kết nối', 'error');
        } finally {
            // Hide loading overlay
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
            }
            
            // Restore button states
            submitBtn.disabled = false;
            cancelBtn.disabled = false;
            submitBtn.innerHTML = originalSubmitText;
        }
    }

    async loadCategories() {
        // Prevent multiple simultaneous calls
        if (this._loadingCategories) {
            return;
        }
        this._loadingCategories = true;
        
        try {
            const response = await this.apiCall('api/categories.php');
            
            if (response.success) {
                this.populateCategorySelects(response.data);
                
                if (this.currentPage === 'categories') {
                    this.displayCategories(response.data);
                }
            } else {
                this.showNotification('Không thể tải danh mục', 'error');
            }
        } catch (error) {
            this.showNotification('Lỗi khi tải danh mục', 'error');
        } finally {
            this._loadingCategories = false;
        }
    }

    populateCategorySelects(categories) {
        const selects = ['requestCategory', 'categoryFilter'];
        
        selects.forEach(selectId => {
            const select = document.getElementById(selectId);
            const currentValue = select.value;
            
            if (selectId === 'categoryFilter') {
                select.innerHTML = '<option value="">Tất cả danh mục</option>';
            } else {
                select.innerHTML = '<option value="">Chọn danh mục</option>';
            }
            
            categories.forEach(category => {
                const option = document.createElement('option');
                option.value = category.id;
                option.textContent = category.name;
                select.appendChild(option);
            });
            
            select.value = currentValue;
        });
    }

    displayCategories(categories) {
        const container = document.getElementById('categoriesList');
        
        if (categories.length === 0) {
            container.innerHTML = '<p>Không có danh mục nào.</p>';
            return;
        }

        container.innerHTML = categories.map(category => `
            <div class="category-item" onclick="app.viewCategoryRequests(${category.id})" style="cursor: pointer;">
                <div class="category-info">
                    <h4>${category.name}</h4>
                    <p>${category.description || 'Không có mô tả'}</p>
                    <div class="category-stats">
                        <div class="stat-item">
                            <span class="stat-number">${category.request_count || 0}</span>
                            <span class="stat-label">Tổng số</span>
                        </div>
                        <div class="stat-item open">
                            <span class="stat-number">${category.open_count || 0}</span>
                            <span class="stat-label">Mới</span>
                        </div>
                        <div class="stat-item progress">
                            <span class="stat-number">${category.in_progress_count || 0}</span>
                            <span class="stat-label">Đang xử lý</span>
                        </div>
                        <div class="stat-item resolved">
                            <span class="stat-number">${category.resolved_count || 0}</span>
                            <span class="stat-label">Đã giải quyết</span>
                        </div>
                        <div class="stat-item closed">
                            <span class="stat-number">${category.closed_count || 0}</span>
                            <span class="stat-label">Đã đóng</span>
                        </div>
                    </div>
                </div>
                ${this.currentUser.role === 'admin' ? `
                    <div class="category-actions" onclick="event.stopPropagation()">
                        <button class="btn btn-secondary" onclick="app.editCategory(${category.id}, '${category.name}', '${category.description || ''}')">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger" onclick="app.deleteCategory(${category.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                ` : ''}
            </div>
        `).join('');
    }

    showCategoryModal(category = null) {
        // Check if user is admin
        if (!this.currentUser || this.currentUser.role !== 'admin') {
            this.showNotification('Chỉ admin mới có quyền quản lý danh mục', 'error');
            return;
        }
        
        const modal = document.getElementById('categoryModal');
        const title = document.getElementById('categoryModalTitle');
        
        if (category) {
            title.textContent = 'Chỉnh sửa danh mục';
            document.getElementById('categoryId').value = category.id;
            document.getElementById('categoryName').value = category.name;
            document.getElementById('categoryDescription').value = category.description || '';
        } else {
            title.textContent = 'Thêm danh mục';
            document.getElementById('categoryForm').reset();
        }
        
        modal.style.display = 'block';
    }

    editCategory(id, name, description) {
        // Check if user is admin
        if (!this.currentUser || this.currentUser.role !== 'admin') {
            this.showNotification('Chỉ admin mới có quyền chỉnh sửa danh mục', 'error');
            return;
        }
        this.showCategoryModal({ id, name, description });
    }

    async handleCategorySubmit(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const categoryId = formData.get('id');
        
        try {
            const url = categoryId ? 'api/categories.php' : 'api/categories.php';
            const method = categoryId ? 'PUT' : 'POST';
            
            const response = await this.apiCall(url, {
                method: method,
                body: JSON.stringify({
                    id: categoryId,
                    name: formData.get('name'),
                    description: formData.get('description')
                })
            });

            if (response.success) {
                this.showNotification(categoryId ? 'Cập nhật danh mục thành công' : 'Thêm danh mục thành công', 'success');
                this.closeModal(document.getElementById('categoryModal'));
                this.loadCategories();
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            this.showNotification('Lỗi kết nối', 'error');
        }
    }

    async handleUserSubmit(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const userId = formData.get('id');
        const resetPassword = document.getElementById('resetPassword').checked;
        
        try {
            const userData = {
                id: userId,
                username: formData.get('username'),
                email: formData.get('email'),
                full_name: formData.get('full_name'),
                department: formData.get('department'),
                phone: formData.get('phone'),
                role: formData.get('role')
            };
            
            // Only include password if it's provided or reset is checked
            const password = formData.get('password');
            if (password && (resetPassword || !userId)) {
                userData.password = password;
            }
            
            const response = await this.apiCall('api/users.php', {
                method: userId ? 'PUT' : 'POST',
                body: JSON.stringify(userData)
            });

            if (response.success) {
                this.showNotification(userId ? 'Cập nhật người dùng thành công' : 'Thêm người dùng thành công', 'success');
                this.closeUserModal();
                this.loadUsers();
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            this.showNotification('Lỗi kết nối', 'error');
        }
    }

    async deleteCategory(id) {
        // Check if user is admin
        if (!this.currentUser || this.currentUser.role !== 'admin') {
            this.showNotification('Chỉ admin mới có quyền xóa danh mục', 'error');
            return;
        }
        
        if (!confirm('Bạn có chắc chắn muốn xóa danh mục này?')) {
            return;
        }

        try {
            const response = await this.apiCall(`api/categories.php?id=${id}`, {
                method: 'DELETE'
            });

            if (response.success) {
                this.showNotification('Xóa danh mục thành công', 'success');
                this.loadCategories();
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            this.showNotification('Lỗi kết nối', 'error');
        }
    }

    
    closeModal(modal) {
        if (modal) {
            modal.style.display = 'none';
        }
    }

    async checkAuth() {
        try {
            console.log('Checking authentication...');
            
            // Add small delay to ensure session is ready
            await new Promise(resolve => setTimeout(resolve, 100));
            
            // Check if user session exists on server
            const response = await this.apiCall('api/auth.php?action=check_session');
            console.log('Auth check response:', response);
            
            if (response.success && response.data) {
                // User is logged in, set current user and show dashboard
                this.currentUser = response.data;
                console.log('User is logged in:', this.currentUser);
                this.showDashboard();
            } else {
                // No active session, show login screen
                console.log('No active session, showing login');
                this.showLoginScreen();
            }
        } catch (error) {
            console.error('Auth check error:', error);
            this.showLoginScreen();
        }
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

    showNotification(message, type = 'info') {
        // Use NotificationManager if available
        if (window.notificationManager) {
            window.notificationManager.show(message, type);
        } else {
            // Fallback to simple alert
            console.log(`Notification [${type}]: ${message}`);
            alert(message);
        }
        }, 3000);
    }

    getStatusText(status) {
        if (window.t) {
            return t(`status_${status}`) || status;
        }
        
        // Fallback to hardcoded Vietnamese
        const statuses = {
            'open': 'Mở',
            'in_progress': 'Đang xử lý',
            'resolved': 'Đã giải quyết',
            'rejected': 'Đã từ chối',
            'closed': 'Đã đóng',
            'cancelled': 'Đã hủy',
            'request_support': 'Cần hỗ trợ'
        };
        return statuses[status] || status;
    }

    getPriorityText(priority) {
        if (window.t) {
            return t(`priority_${priority}`) || priority;
        }
        
        // Fallback to hardcoded Vietnamese
        const priorityMap = {
            'low': 'Thấp',
            'medium': 'Trung bình',
            'high': 'Cao',
            'critical': 'Khẩn cấp',
            'urgent': 'Khẩn cấp'
        };
        return priorityMap[priority] || priority;
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN');
    }

    // User Management Functions
    async loadUsers() {
        console.log('🚀 loadUsers() STARTED!');
        console.log('🔍 _loadingUsers flag:', this._loadingUsers);
        
        // Prevent multiple simultaneous calls
        if (this._loadingUsers) {
            console.log('⚠️ Already loading users, skipping');
            return;
        }
        this._loadingUsers = true;
        console.log('✅ Set _loadingUsers = true');
        
        try {
            console.log('🔍 Looking for usersList element...');
            const usersList = document.getElementById('usersList');
            
            if (!usersList) {
                console.error('❌ usersList element not found!');
                return;
            }
            console.log('✅ usersList element found');
            
            // Show loading message
            usersList.innerHTML = '<p>Đang tải danh sách người dùng...</p>';
            console.log('✅ Set loading message');
            
            const search = document.getElementById('userSearch')?.value || '';
            const role = document.getElementById('roleFilter')?.value || '';
            
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (role) params.append('role', role);
            
            console.log('🌐 Making API call...');
            // Use the real API (authentication is working)
            const response = await fetch(`api/users.php?${params.toString()}`);
            const data = await response.json();
            
            console.log('📦 API response:', data);
            
            if (data.success) {
                console.log(`✅ Successfully loaded ${data.data.length} users`);
                this.displayUsers(data.data);
            } else {
                console.error('❌ API error:', data.message);
                usersList.innerHTML = `<div class="error">Lỗi: ${data.message}</div>`;
                this.showNotification('Không thể tải danh sách người dùng', 'error');
            }
        } catch (error) {
            console.error('💥 Network error:', error);
            const usersList = document.getElementById('usersList');
            if (usersList) {
                usersList.innerHTML = `<div class="error">Lỗi kết nối</div>`;
            }
            this.showNotification('Lỗi khi tải người dùng', 'error');
        } finally {
            console.log('🏁 loadUsers() FINISHED');
            this._loadingUsers = false;
        }
    }

    displayUsers(users) {
        console.log('🎨 displayUsers() called with:', users.length, 'users');
        
        // Check which page is active to use the correct element
        const usersPage = document.getElementById('usersPage');
        const usersList = usersPage && usersPage.classList.contains('active') 
            ? document.getElementById('usersList')  // Users page
            : document.getElementById('profileUsersList');  // Profile page
            
        console.log('🔍 usersList element:', !!usersList);
        
        if (!usersList) {
            console.error('❌ usersList element not found!');
            return;
        }
        
        if (users.length === 0) {
            console.log('📭 No users to display');
            usersList.innerHTML = '<p class="no-data">Không có người dùng nào</p>';
            return;
        }

        console.log('🏗️ Creating HTML for', users.length, 'users');
        const usersHTML = users.map((user, index) => {
            console.log(`👤 User ${index}:`, user.full_name, user.role);
            return `
            <div class="user-card">
                <div class="user-info">
                    <h4>${user.full_name}</h4>
                    <p><i class="fas fa-user"></i> ${user.username}</p>
                    <p><i class="fas fa-envelope"></i> ${user.email}</p>
                    <p><i class="fas fa-building"></i> ${user.department || 'Chưa có'}</p>
                    <p><i class="fas fa-phone"></i> ${user.phone || 'Chưa có'}</p>
                    <span class="role-badge ${user.role}">${this.getRoleText(user.role)}</span>
                </div>
                <div class="user-actions">
                    <button class="btn btn-secondary" onclick="event.stopPropagation(); app.editUser(${user.id})">
                        <i class="fas fa-edit"></i> Sửa
                    </button>
                    <button class="btn btn-danger" onclick="event.stopPropagation(); app.deleteUser(${user.id})">
                        <i class="fas fa-trash"></i> Xóa
                    </button>
                </div>
            </div>
        `;
        }).join('');

        console.log('📝 Setting HTML to usersList');
        usersList.innerHTML = usersHTML;
        console.log('✅ displayUsers() completed successfully');
    }

    getRoleText(role) {
        const roleMap = {
            'admin': 'Admin',
            'staff': 'Staff',
            'user': 'User'
        };
        return roleMap[role] || role;
    }

    showUserModal(userId = null) {
        const modal = document.getElementById('userModal');
        const title = document.getElementById('userModalTitle');
        const form = document.getElementById('userForm');
        const passwordField = document.getElementById('userPassword');
        const passwordNote = document.getElementById('passwordNote');
        const resetCheckbox = document.getElementById('resetPassword');
        
        form.reset();
        resetCheckbox.checked = false;
        
        if (userId) {
            title.textContent = 'Chỉnh sửa người dùng';
            passwordField.removeAttribute('required');
            passwordNote.style.display = 'inline';
            this.loadUser(userId);
        } else {
            title.textContent = 'Thêm người dùng';
            passwordField.setAttribute('required', 'required');
            passwordNote.style.display = 'none';
            document.getElementById('userId').value = '';
        }
        
        modal.style.display = 'block';
    }

    async loadUser(userId) {
        try {
            const response = await fetch(`api/users.php?id=${userId}`);
            const data = await response.json();
            
            if (data.success) {
                const user = data.data;
                document.getElementById('userId').value = user.id;
                document.getElementById('userUsername').value = user.username;
                document.getElementById('userEmail').value = user.email;
                document.getElementById('userFullName').value = user.full_name;
                document.getElementById('userPhone').value = user.phone || '';
                document.getElementById('userRole').value = user.role;
                document.getElementById('userPassword').value = ''; // Don't show password
                
                // Set department dropdown value
                const deptSelect = document.getElementById('userDepartment');
                if (deptSelect && typeof DepartmentHelper !== 'undefined') {
                    DepartmentHelper.setDepartmentValue(deptSelect, user.department || '');
                } else if (deptSelect) {
                    deptSelect.value = user.department || '';
                }
                document.getElementById('userPassword').removeAttribute('required');
            }
        } catch (error) {
            this.showNotification('Lỗi khi tải thông tin người dùng', 'error');
        }
    }

    async saveUser(userData) {
        try {
            const method = userData.id ? 'PUT' : 'POST';
            const response = await fetch('api/users.php', {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(userData)
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification(userData.id ? 'Cập nhật người dùng thành công' : 'Thêm người dùng thành công', 'success');
                this.closeUserModal();
                this.loadUsers();
            } else {
                this.showNotification(data.message || 'Lỗi khi lưu người dùng', 'error');
            }
        } catch (error) {
            this.showNotification('Lỗi khi lưu người dùng', 'error');
        }
    }

    editUser(userId) {
        this.showUserModal(userId);
    }

    async deleteUser(userId) {
        if (!confirm('Bạn có chắc chắn muốn xóa người dùng này?')) {
            return;
        }
        
        try {
            const response = await fetch(`api/users.php?id=${userId}`, {
                method: 'DELETE'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('Xóa người dùng thành công', 'success');
                this.loadUsers();
            } else {
                this.showNotification(data.message || 'Lỗi khi xóa người dùng', 'error');
            }
        } catch (error) {
            this.showNotification('Lỗi khi xóa người dùng', 'error');
        }
    }

    async editRequest(requestId) {
        try {
            // Get request details
            const response = await this.apiCall(`api/service_requests.php?action=get&id=${requestId}`);
            
            if (response.success) {
                const request = response.data;
                this.showEditRequestModal(request);
            } else {
                this.showNotification(response.message || 'Lỗi khi tải thông tin yêu cầu', 'error');
            }
        } catch (error) {
            this.showNotification('Lỗi khi tải thông tin yêu cầu', 'error');
        }
    }

    async deleteRequest(requestId) {
        // Always show confirmation dialog first
        if (!confirm('Bạn có chắc chắn muốn xóa yêu cầu này?')) {
            return;
        }

        try {
            // First attempt to delete to check for related data
            const response = await fetch(`api/service_requests.php?id=${requestId}`, {
                method: 'DELETE'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('Xóa yêu cầu thành công', 'success');
                this.loadRequests();
            } else if (data.message && data.message.includes('Bạn có chắc chắn muốn tiếp tục?')) {
                // Show additional confirmation dialog for cascade delete
                if (confirm(data.message)) {
                    // Delete with force parameter
                    const forceResponse = await fetch(`api/service_requests.php?id=${requestId}&force=true`, {
                        method: 'DELETE'
                    });
                    
                    const forceData = await forceResponse.json();
                    
                    if (forceData.success) {
                        this.showNotification('Xóa yêu cầu thành công', 'success');
                        this.loadRequests();
                    } else {
                        this.showNotification(forceData.message || 'Lỗi khi xóa yêu cầu', 'error');
                    }
                }
            } else {
                this.showNotification(data.message || 'Lỗi khi xóa yêu cầu', 'error');
            }
        } catch (error) {
            this.showNotification('Lỗi khi xóa yêu cầu', 'error');
        }
    }

    showEditRequestModal(request) {
        // Create modal if it doesn't exist
        let modal = document.getElementById('editRequestModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'editRequestModal';
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Chỉnh sửa yêu cầu</h3>
                        <span class="close">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="editRequestForm">
                            <input type="hidden" id="editRequestId">
                            <div class="form-group">
                                <label for="editRequestTitle">Tiêu đề *</label>
                                <input type="text" id="editRequestTitle" required>
                            </div>
                            <div class="form-group">
                                <label for="editRequestCategory">Danh mục *</label>
                                <select id="editRequestCategory" required>
                                    <option value="">Chọn danh mục</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="editRequestPriority">Ưu tiên</label>
                                <select id="editRequestPriority">
                                    <option value="low">Thấp</option>
                                    <option value="medium">Trung bình</option>
                                    <option value="high">Cao</option>
                                    <option value="critical">Khẩn cấp</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="editRequestStatus">Trạng thái</label>
                                <select id="editRequestStatus">
                                    <option value="open">Mở</option>
                                    <option value="in_progress">Đang xử lý</option>
                                    <option value="resolved">Đã giải quyết</option>
                                    <option value="closed">Đã đóng</option>
                                    <option value="cancelled">Đã hủy</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="editRequestAssigned">Giao cho</label>
                                <select id="editRequestAssigned">
                                    <option value="">Chưa giao</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="editRequestDescription">Mô tả *</label>
                                <textarea id="editRequestDescription" rows="4" required></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                                <button type="button" class="btn btn-secondary cancel-edit-request">Hủy</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        // Populate form with request data
        document.getElementById('editRequestId').value = request.id;
        document.getElementById('editRequestTitle').value = request.title;
        document.getElementById('editRequestDescription').value = request.description;
        document.getElementById('editRequestPriority').value = request.priority;
        document.getElementById('editRequestStatus').value = request.status;

        // Load categories
        this.loadCategoriesForEdit(request.category_id);

        // Load staff for assignment
        this.loadStaffForEdit(request.assigned_to);

        // Show modal
        modal.style.display = 'flex';

        // Bind events
        const form = document.getElementById('editRequestForm');
        form.onsubmit = (e) => this.handleEditRequestSubmit(e);

        const cancelBtn = modal.querySelector('.cancel-edit-request');
        cancelBtn.onclick = () => this.closeEditRequestModal();

        const closeBtn = modal.querySelector('.close');
        closeBtn.onclick = () => this.closeEditRequestModal();
    }

    async loadCategoriesForEdit(selectedCategoryId = null) {
        try {
            const response = await this.apiCall('api/categories.php?action=list');
            if (response.success) {
                const select = document.getElementById('editRequestCategory');
                select.innerHTML = '<option value="">Chọn danh mục</option>';
                
                response.data.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.id;
                    option.textContent = category.name;
                    if (category.id == selectedCategoryId) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Failed to load categories:', error);
        }
    }

    async loadStaffForEdit(selectedStaffId = null) {
        try {
            const response = await this.apiCall('api/users.php?role=staff');
            if (response.success) {
                const select = document.getElementById('editRequestAssigned');
                select.innerHTML = '<option value="">Chưa giao</option>';
                
                response.data.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = user.full_name;
                    if (user.id == selectedStaffId) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Failed to load staff:', error);
        }
    }

    async handleEditRequestSubmit(e) {
        e.preventDefault();
        
        const formData = {
            action: 'update',
            id: document.getElementById('editRequestId').value,
            title: document.getElementById('editRequestTitle').value,
            description: document.getElementById('editRequestDescription').value,
            category_id: document.getElementById('editRequestCategory').value,
            priority: document.getElementById('editRequestPriority').value,
            status: document.getElementById('editRequestStatus').value,
            assigned_to: document.getElementById('editRequestAssigned').value || null
        };

        try {
            const response = await this.apiCall('api/service_requests.php', {
                method: 'PUT',
                body: JSON.stringify(formData)
            });

            if (response.success) {
                this.showNotification('Cập nhật yêu cầu thành công', 'success');
                this.closeEditRequestModal();
                this.loadRequests();
            } else {
                this.showNotification(response.message || 'Lỗi khi cập nhật yêu cầu', 'error');
            }
        } catch (error) {
            this.showNotification('Lỗi khi cập nhật yêu cầu', 'error');
        }
    }

    closeEditRequestModal() {
        const modal = document.getElementById('editRequestModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    showImageModal(imageSrc, imageName) {
        const modal = document.getElementById('imageModal');
        const modalImage = document.getElementById('modalImage');
        const modalTitle = document.getElementById('imageModalTitle');
        
        modalImage.src = imageSrc;
        modalImage.alt = imageName;
        modalTitle.textContent = imageName;
        modal.style.display = 'flex';
        
        // Close on background click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
        
        // Close on Escape key
        const handleEscape = (e) => {
            if (e.key === 'Escape') {
                modal.style.display = 'none';
                document.removeEventListener('keydown', handleEscape);
            }
        };
        document.addEventListener('keydown', handleEscape);
    }

    closeUserModal() {
        document.getElementById('userModal').style.display = 'none';
    }

    // File Upload Functions
    handleFileSelect(e) {
        const files = Array.from(e.target.files);
        this.addFiles(files);
    }

    handleDragOver(e) {
        e.preventDefault();
        e.stopPropagation();
        document.getElementById('fileUploadArea').classList.add('dragover');
    }

    handleDragLeave(e) {
        e.preventDefault();
        e.stopPropagation();
        document.getElementById('fileUploadArea').classList.remove('dragover');
    }

    handleFileDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        document.getElementById('fileUploadArea').classList.remove('dragover');
        
        const files = Array.from(e.dataTransfer.files);
        this.addFiles(files);
    }

    addFiles(files) {
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 
                           'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                           'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                           'text/plain'];

        files.forEach(file => {
            // Validate file size
            if (file.size > maxSize) {
                this.showNotification(`Tệp ${file.name} quá lớn (tối đa 5MB)`, 'error');
                return;
            }

            // Validate file type
            if (!allowedTypes.includes(file.type)) {
                this.showNotification(`Tệp ${file.name} không được hỗ trợ`, 'error');
                return;
            }

            // Check for duplicates
            if (this.selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                this.showNotification(`Tệp ${file.name} đã được chọn`, 'warning');
                return;
            }

            this.selectedFiles.push(file);
        });

        this.updateFileList();
    }

    removeFile(index) {
        this.selectedFiles.splice(index, 1);
        this.updateFileList();
    }

    updateFileList() {
        const fileList = document.getElementById('fileList');
        
        if (this.selectedFiles.length === 0) {
            fileList.innerHTML = '';
            return;
        }

        fileList.innerHTML = this.selectedFiles.map((file, index) => `
            <div class="file-item">
                <div class="file-item-info">
                    <i class="fas fa-file"></i>
                    <span class="file-item-name">${file.name}</span>
                    <span class="file-item-size">${this.formatFileSize(file.size)}</span>
                </div>
                <button type="button" class="file-item-remove" onclick="app.removeFile(${index})">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `).join('');
    }

    // Resolution Functions
    showResolveModal(requestId) {
        document.getElementById('resolveRequestId').value = requestId;
        document.getElementById('resolveForm').reset();
        document.getElementById('resolveModal').style.display = 'block';
    }

    closeResolveModal() {
        document.getElementById('resolveModal').style.display = 'none';
    }

    async handleResolveSubmit(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const requestId = document.getElementById('resolveRequestId').value;
        
        try {
            const response = await this.apiCall('api/service_requests.php', {
                method: 'PUT',
                body: JSON.stringify({
                    id: requestId,
                    action: 'resolve',
                    error_description: formData.get('error_description'),
                    error_type: formData.get('error_type'),
                    replacement_materials: formData.get('replacement_materials'),
                    solution_method: formData.get('solution_method')
                })
            });

            if (response.success) {
                this.showNotification('Yêu cầu đã được giải quyết thành công', 'success');
                this.closeResolveModal();
                this.showRequestDetail(requestId);
                if (this.currentPage === 'requests') {
                    this.loadRequests();
                }
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            this.showNotification('Lỗi kết nối', 'error');
        }
    }

    getErrorTypeText(errorType) {
        const types = {
            'hardware': 'Lỗi phần cứng',
            'software': 'Lỗi phần mềm',
            'network': 'Lỗi mạng',
            'security': 'Lỗi bảo mật',
            'user_error': 'Lỗi người dùng',
            'configuration': 'Lỗi cấu hình',
            'other': 'Khác'
        };
        return types[errorType] || errorType;
    }

    // Reject Request Functions
    async loadRejectRequests() {
        try {
            const status = document.getElementById('rejectStatusFilter').value || 'pending';
            const response = await this.apiCall(`api/reject_requests.php?action=list&status=${status}`);
            
            if (response.success) {
                this.displayRejectRequests(response.data);
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            this.showNotification('Lỗi tải yêu cầu từ chối', 'error');
        }
    }

    displayRejectRequests(rejectRequests) {
        const container = document.getElementById('rejectRequestsList');
        
        if (rejectRequests.length === 0) {
            container.innerHTML = '<p>Không có yêu cầu từ chối nào.</p>';
            return;
        }

        container.innerHTML = rejectRequests.map(reject => `
            <div class="request-item reject-request" data-reject-id="${reject.id}">
                <div class="request-header">
                    <h4>
                        <a href="request-detail.html?id=${reject.service_request_id}" target="_blank">
                            #${reject.service_request_id} - ${reject.request_title}
                        </a>
                    </h4>
                    <div class="request-badges">
                        <span class="badge status-${reject.status}">${this.getRejectStatusText(reject.status)}</span>
                    </div>
                </div>
                
                <div class="request-meta">
                    <div class="meta-item">
                        <strong>Người từ chối:</strong> ${reject.requester_name}
                    </div>
                    <div class="meta-item">
                        <strong>Ngày tạo:</strong> ${this.formatDate(reject.created_at)}
                    </div>
                    <div class="meta-item">
                        <strong>Lý do từ chối:</strong> ${reject.reject_reason}
                    </div>
                    ${reject.reject_details ? `
                        <div class="meta-item">
                            <strong>Chi tiết:</strong> ${reject.reject_details}
                        </div>
                    ` : ''}
                </div>
                
                <div class="request-actions">
                    ${reject.status === 'pending' && this.currentUser.role === 'admin' ? `
                        <button class="btn btn-primary" onclick="app.showAdminRejectModal(${reject.id})">
                            <i class="fas fa-gavel"></i> Xử lý
                        </button>
                    ` : ''}
                    ${reject.admin_reason ? `
                        <div class="admin-reason">
                            <strong>Quyết định ADMIN:</strong> ${reject.admin_reason}
                        </div>
                    ` : ''}
                </div>
            </div>
        `).join('');
    }

    showAdminRejectModal(rejectId) {
        document.getElementById('adminRejectId').value = rejectId;
        document.getElementById('adminRejectForm').reset();
        this.loadRejectRequestDetails(rejectId);
        document.getElementById('adminRejectModal').style.display = 'block';
    }

    closeAdminRejectModal() {
        document.getElementById('adminRejectModal').style.display = 'none';
    }

    async loadRejectRequestDetails(rejectId) {
        try {
            const response = await this.apiCall(`api/reject_requests.php?action=get&id=${rejectId}`);
            
            if (response.success) {
                const reject = response.data;
                const container = document.getElementById('adminRejectRequestDetails');
                
                container.innerHTML = `
                    <div class="reject-request-info">
                        <h4><i class="fas fa-info-circle"></i> Chi tiết yêu cầu từ chối</h4>
                        <div class="reject-details">
                            <div class="reject-item">
                                <strong>ID yêu cầu gốc:</strong> #${reject.service_request_id}
                            </div>
                            <div class="reject-item">
                                <strong>Người từ chối:</strong> ${reject.requester_name}
                            </div>
                            <div class="reject-item">
                                <strong>Lý do từ chối:</strong> ${reject.reject_reason}
                            </div>
                            ${reject.reject_details ? `
                                <div class="reject-item">
                                    <strong>Chi tiết bổ sung:</strong> ${reject.reject_details}
                                </div>
                            ` : ''}
                            <div class="reject-item">
                                <strong>Trạng thái:</strong> <span class="badge status-${reject.status}">${this.getRejectStatusText(reject.status)}</span>
                            </div>
                            <div class="reject-item">
                                <strong>Ngày tạo:</strong> ${this.formatDate(reject.created_at)}
                            </div>
                        </div>
                    </div>
                `;
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            this.showNotification('Lỗi tải chi tiết yêu cầu từ chối', 'error');
        }
    }

    async handleAdminRejectSubmit(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const rejectId = document.getElementById('adminRejectId').value;
        
        try {
            const response = await this.apiCall('api/reject_requests.php', {
                method: 'PUT',
                body: JSON.stringify({
                    reject_id: rejectId,
                    decision: formData.get('decision'),
                    admin_reason: formData.get('reason')
                })
            });

            if (response.success) {
                this.showNotification(response.message, 'success');
                this.closeAdminRejectModal();
                // Reload reject requests list
                this.loadRejectRequests();
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            this.showNotification('Lỗi xử lý yêu cầu từ chối', 'error');
        }
    }

    getRejectStatusText(status) {
        const statuses = {
            'pending': 'Chờ duyệt',
            'approved': 'Đã duyệt',
            'rejected': 'Đã từ chối'
        };
        return statuses[status] || status;
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('vi-VN');
    }

    // Support Request Functions
    displaySupportRequests(supportRequests) {
        const container = document.getElementById('supportRequestsList');
        
        if (supportRequests.length === 0) {
            console.log('Container found, rendering HTML...');
            container.innerHTML = '<p>Không có yêu cầu hỗ trợ nào.</p>';
            return;
        }

        container.innerHTML = supportRequests.map(support => `
            <div class="request-item support-request" data-support-id="${support.id}">
                <div class="request-header">
                    <h4>
                        <a href="request-detail.html?id=${support.service_request_id}" target="_blank">
                            #${support.service_request_id} - ${support.request_title}
                        </a>
                    </h4>
                    <div class="request-badges">
                        <span class="badge status-${support.status}">${this.getSupportStatusText(support.status)}</span>
                        <span class="badge support-type-${support.support_type}">${this.getSupportTypeText(support.support_type)}</span>
                    </div>
                </div>
                
                <div class="request-meta">
                    <div class="meta-item">
                        <strong>Người tạo:</strong> ${support.requester_name}
                    </div>
                    <div class="meta-item">
                        <strong>Ngày tạo:</strong> ${this.formatDate(support.created_at)}
                    </div>
                    <div class="meta-item">
                        <strong>Chi tiết:</strong> ${support.support_details}
                    </div>
                    <div class="meta-item">
                        <strong>Lý do:</strong> ${support.support_reason}
                    </div>
                    ${support.admin_reason ? `
                        <div class="meta-item">
                            <strong>Quyết định ADMIN:</strong> ${support.admin_reason}
                        </div>
                        <div class="meta-item">
                            <strong>Trạng thái yêu cầu:</strong> 
                            <span class="badge status-${support.service_request_status || 'unknown'}">${this.getStatusText(support.service_request_status || 'unknown')}</span>
                        </div>
                    ` : ''}
                </div>
                
                <div class="request-actions">
                    ${support.status === 'pending' && this.currentUser.role === 'admin' ? `
                        <button class="btn btn-primary" onclick="app.showAdminSupportModal(${support.id})">
                            <i class="fas fa-gavel"></i> Xử lý
                        </button>
                    ` : ''}
                    ${support.admin_reason ? `
                        <div class="admin-reason">
                            <strong>Quyết định ADMIN:</strong> ${support.admin_reason}
                        </div>
                    ` : ''}
                </div>
            </div>
        `).join('');
    }

    showAdminSupportModal(supportId) {
        document.getElementById('adminSupportId').value = supportId;
        document.getElementById('adminSupportForm').reset();
        this.loadSupportRequestDetails(supportId);
        document.getElementById('adminSupportModal').style.display = 'block';
    }

    closeAdminSupportModal() {
        document.getElementById('adminSupportModal').style.display = 'none';
    }

    async loadSupportRequestDetails(supportId) {
        try {
            const response = await this.apiCall(`api/support_requests.php?action=get&id=${supportId}`);
            
            if (response.success) {
                const support = response.data;
                const container = document.getElementById('supportRequestDetails');
                
                container.innerHTML = `
                    <div class="support-request-info">
                        <h4><i class="fas fa-info-circle"></i> Chi tiết yêu cầu hỗ trợ</h4>
                        <div class="support-details">
                            <div class="support-item">
                                <strong>ID yêu cầu gốc:</strong> #${support.service_request_id}
                            </div>
                            <div class="support-item">
                                <strong>Người tạo:</strong> ${support.requester_name}
                            </div>
                            <div class="support-item">
                                <strong>Loại hỗ trợ:</strong> ${this.getSupportTypeText(support.support_type)}
                            </div>
                            <div class="support-item">
                                <strong>Chi tiết hỗ trợ:</strong> ${support.support_details}
                            </div>
                            <div class="support-item">
                                <strong>Lý do:</strong> ${support.support_reason}
                            </div>
                            <div class="support-item">
                                <strong>Ngày tạo:</strong> ${this.formatDate(support.created_at)}
                            </div>
                            <div class="support-item">
                                <strong>Trạng thái:</strong> <span class="badge status-${support.status}">${this.getSupportStatusText(support.status)}</span>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            this.showNotification('Lỗi tải chi tiết yêu cầu hỗ trợ', 'error');
        }
    }

    async handleAdminSupportSubmit(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const supportId = document.getElementById('adminSupportId').value;
        
        try {
            const response = await this.apiCall('api/support_requests.php', {
                method: 'PUT',
                body: JSON.stringify({
                    id: supportId,
                    action: 'process',
                    decision: formData.get('decision'),
                    reason: formData.get('reason')
                })
            });

            if (response.success) {
                this.showNotification('Đã xử lý yêu cầu hỗ trợ thành công', 'success');
                this.closeAdminSupportModal();
                this.loadSupportRequests();
                
                // Also reload service request detail if on request detail page
                if (window.location.pathname.includes('request-detail.html')) {
                    const currentRequestId = this.getRequestIdFromURL();
                    if (currentRequestId) {
                        // Check if this support request belongs to current service request
                        if (response.data && response.data.service_request_id == currentRequestId) {
                            // Reload the service request detail
                            this.loadRequestDetail();
                        }
                    }
                }
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            this.showNotification('Lỗi kết nối', 'error');
        }
    }

    getSupportTypeText(supportType) {
        const types = {
            'equipment': 'Thiết bị',
            'person': 'Nhân sự',
            'department': 'Bộ phận khác'
        };
        return types[supportType] || supportType;
    }

    getSupportStatusText(status) {
        const statuses = {
            'pending': 'Chờ xử lý',
            'approved': 'Đã phê duyệt',
            'rejected': 'Đã từ chối'
        };
        return statuses[status] || status;
    }

    // Profile Management Functions
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
                this.loadProfile(); // Reload profile data
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
        
        const currentPassword = formData.get('current_password');
        const newPassword = formData.get('new_password');
        const confirmPassword = formData.get('confirm_password');

        console.log('=== PASSWORD SUBMIT DEBUG ===');
        console.log('Current password:', currentPassword);
        console.log('New password:', newPassword);
        console.log('Confirm password:', confirmPassword);

        if (newPassword !== confirmPassword) {
            this.showNotification('Mật khẩu xác nhận không khớp', 'error');
            return;
        }

        const passwordData = {
            action: 'change_password',
            current_password: currentPassword,
            new_password: newPassword,
            confirm_password: confirmPassword
        };

        console.log('Password data being sent:', passwordData);

        try {
            const response = await this.apiCall('api/profile.php', {
                method: 'PUT',
                body: JSON.stringify(passwordData)
            });

            console.log('Password change response:', response);

            if (response.success) {
                this.showNotification('Đổi mật khẩu thành công', 'success');
                e.target.reset();
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            console.error('Password change error:', error);
            this.showNotification('Lỗi đổi mật khẩu', 'error');
        }
    }

    async handleUserRoleSubmit(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        const roleData = {
            action: 'update_role',
            user_id: formData.get('user_id'),
            role: formData.get('role')
        };

        try {
            const response = await this.apiCall('api/profile.php', {
                method: 'PUT',
                body: JSON.stringify(roleData)
            });

            if (response.success) {
                this.showNotification('Cập nhật vai trò thành công', 'success');
                this.loadUsers(); // Reload users list
                this.closeUserRoleModal();
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            console.error('Role update error:', error);
            this.showNotification('Lỗi cập nhật vai trò', 'error');
        }
    }

    getRoleText(role) {
        const roles = {
            'admin': 'Admin',
            'staff': 'Staff',
            'user': 'User'
        };
        return roles[role] || role;
    }

    closeUserRoleModal() {
        document.getElementById('userRoleModal').style.display = 'none';
    }
}

// Initialize app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.app = new ITServiceApp();
    
    // Handle URL parameter for page navigation
    const urlParams = new URLSearchParams(window.location.search);
    const pageParam = urlParams.get('page');
    
    if (pageParam && window.app) {
        console.log('URL parameter found, showing page:', pageParam);
        // Wait a bit for app to fully initialize
        setTimeout(() => {
            window.app.showPage(pageParam);
        }, 500);
    }
});

// Global functions for onclick handlers
window.closeUserRoleModal = () => app.closeUserRoleModal();
