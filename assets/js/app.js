// IT Service Request Management JavaScript

// Global error handler for undefined script errors
window.addEventListener('error', function(e) {
    if (e.filename && e.filename.includes('onboarding.js')) {
        console.warn('Ignoring onboarding.js error - file not found');
        e.preventDefault();
        return false;
    }
});

// Global unhandled promise rejection handler
window.addEventListener('unhandledrejection', function(e) {
    if (e.reason && e.reason.toString().includes('onboarding')) {
        console.warn('Ignoring onboarding promise rejection');
        e.preventDefault();
        return false;
    }
});

class ITServiceApp {
    constructor() {
        this.currentUser = null;
        this.currentPage = 'dashboard';
        this.autoReloadInterval = null;
        this.autoReloadEnabled = true;
        this.init();
    }

    init() {
        this.bindEvents();
        this.checkAuth();
        this.selectedFiles = []; // Store selected files
        this.initAutoReload();
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
        
        // Notification button event
        const notificationBtn = document.getElementById('notificationBtn');
        if (notificationBtn) {
            notificationBtn.addEventListener('click', () => {
                this.toggleNotificationDropdown();
            });
        }
        
        // Mark all read button in dropdown
        const markAllReadBtn = document.getElementById('markAllReadBtn');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', () => {
                this.markAllNotificationsAsRead();
            });
        }

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
        
        // Search functionality
        const requestSearch = document.getElementById('requestSearch');
        if (requestSearch) {
            let searchTimeout;
            
            // Search on input with debounce
            requestSearch.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.loadRequests();
                }, 500); // Wait 500ms after user stops typing
            });
        }
        
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
                console.log('=== LOGIN SUCCESS DEBUG ===');
                console.log('Login response:', response);
                console.log('Document cookies after login:', document.cookie);
                console.log('Current user set to:', this.currentUser);
                
                this.showDashboard();
                
                // Use toast notification for login success
                if (window.toastManager) {
                    window.toastManager.loginSuccess(this.currentUser.full_name || this.currentUser.username);
                } else {
                    this.showNotification('Đăng nhập thành công!', 'success');
                }
            } else {
                if (window.toastManager) {
                    window.toastManager.error(response.message, 'Đăng nhập thất bại');
                } else {
                    this.showNotification(response.message, 'error');
                }
            }
        } catch (error) {
            if (window.toastManager) {
                window.toastManager.error('Lỗi kết nối máy chủ', 'Lỗi kết nối');
            } else {
                this.showNotification('Lỗi kết nối', 'error');
            }
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
                if (window.toastManager) {
                    window.toastManager.success('Đăng ký thành công! Vui lòng đăng nhập.', 'Đăng ký thành công');
                } else {
                    this.showNotification('Đăng ký thành công! Vui lòng đăng nhập.', 'success');
                }
                this.showLogin();
            } else {
                if (window.toastManager) {
                    window.toastManager.error(response.message, 'Đăng ký thất bại');
                } else {
                    this.showNotification(response.message, 'error');
                }
            }
        } catch (error) {
            if (window.toastManager) {
                window.toastManager.error('Lỗi kết nối máy chủ', 'Lỗi kết nối');
            } else {
                this.showNotification('Lỗi kết nối', 'error');
            }
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
        
        // Show logout notification first
        if (window.toastManager) {
            window.toastManager.logoutSuccess();
        }
        
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
        console.log('User role:', this.currentUser.role);
        
        // Hide all menus first
        document.getElementById('adminMenu').style.display = 'none';
        document.getElementById('adminDepartmentMenu').style.display = 'none';
        document.getElementById('adminSupportMenu').style.display = 'none';
        document.getElementById('adminRejectMenu').style.display = 'none';
        const adminKPIMenu = document.getElementById('adminKPIMenu');
        if (adminKPIMenu) adminKPIMenu.style.display = 'none';
        document.getElementById('newRequestMenu').style.display = 'none';
        
        if (this.currentUser.role === 'admin') {
            console.log('✅ Showing admin menus');
            document.getElementById('adminMenu').style.display = 'block';
            document.getElementById('adminDepartmentMenu').style.display = 'block';
            document.getElementById('adminSupportMenu').style.display = 'block';
            document.getElementById('adminRejectMenu').style.display = 'block';
            if (adminKPIMenu) adminKPIMenu.style.display = 'block';
            // Hide new request menu for admin
            document.getElementById('newRequestMenu').style.display = 'none';
            // Show add category button for admin
            const addCategoryBtn = document.getElementById('addCategoryBtn');
            if (addCategoryBtn) {
                addCategoryBtn.style.display = 'block';
            }
            console.log('Admin user - hiding new request menu, showing add category button');
        } else if (this.currentUser.role === 'staff') {
            console.log('✅ Showing staff menus');
            // Staff should see limited menus - NOT adminMenu
            document.getElementById('adminMenu').style.display = 'none'; // Staff can't see full admin menu
            document.getElementById('adminDepartmentMenu').style.display = 'none'; // Staff can't manage departments
            document.getElementById('adminSupportMenu').style.display = 'block'; // Staff can handle support requests
            document.getElementById('adminRejectMenu').style.display = 'block'; // Staff can handle reject requests
            // Show new request menu for staff
            document.getElementById('newRequestMenu').style.display = 'none'; // Staff typically handles requests, not creates new ones
            // Hide add category button for staff
            const addCategoryBtn = document.getElementById('addCategoryBtn');
            if (addCategoryBtn) {
                addCategoryBtn.style.display = 'none';
            }
            console.log('Staff user - hiding admin, new request menus, and add category button');
        } else {
            console.log('✅ Showing user menus');
            // Regular user - only new request menu
            document.getElementById('newRequestMenu').style.display = 'block';
            // Hide add category button for regular user
            const addCategoryBtn = document.getElementById('addCategoryBtn');
            if (addCategoryBtn) {
                addCategoryBtn.style.display = 'none';
            }
            console.log('Regular user - showing new request menu, hiding add category button');
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
        console.log('Current page before:', this.currentPage);
        
        // Check if loading overlay is already shown
        const loadingOverlay = document.getElementById('loadingOverlay');
        const hasLoading = loadingOverlay && loadingOverlay.style.display === 'flex';
        
        if (!hasLoading) {
            // Show loading state for better UX
            this.showLoadingState('Đang tải trang...');
        }
        
        // Update navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        
        // Only update active state for internal pages
        const navPageElement = document.querySelector(`[data-page="${page}"]`);
        if (navPageElement) {
            navPageElement.classList.add('active');
            console.log('Updated navigation active state for:', page);
        } else {
            console.log('No navigation element found for:', page);
        }

        // Update pages
        document.querySelectorAll('.page').forEach(p => {
            p.classList.remove('active');
        });
        
        const pageId = page === 'new-request' ? 'newRequestPage' : `${page}Page`;
        const pageElement = document.getElementById(pageId);
        
        console.log('Looking for page element:', pageId);
        console.log('Page element found:', !!pageElement);
        
        // Set current page BEFORE checking element existence
        this.currentPage = page;
        console.log('Current page set to:', this.currentPage);
        
        if (pageElement) {
            pageElement.classList.add('active');
            console.log('Successfully activated page:', page);
        } else {
            console.error(`Page element not found: ${pageId}`);
            this.hideLoadingState();
            this.showNotification(`Trang ${page} không tồn tại`, 'error');
            // Don't return here - continue with loadPageData which will handle the error
        }

        // Load page-specific data
        console.log('Loading page-specific data for:', page);
        this.loadPageData(page);
    }

    showLoadingState(message = 'Đang tải...') {
        // Create or update loading overlay
        let loadingOverlay = document.getElementById('loadingOverlay');
        if (!loadingOverlay) {
            loadingOverlay = document.createElement('div');
            loadingOverlay.id = 'loadingOverlay';
            loadingOverlay.className = 'loading-overlay';
            loadingOverlay.innerHTML = `
                <div class="loading-overlay-content">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p class="loading-text">${message}</p>
                </div>
            `;
            document.body.appendChild(loadingOverlay);
        } else {
            // Safely update loading text
            const loadingText = loadingOverlay.querySelector('.loading-text');
            if (loadingText) {
                loadingText.textContent = message;
            }
        }
        loadingOverlay.style.display = 'flex';
    }

    hideLoadingState() {
        const loadingOverlay = document.getElementById('loadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }
    }

    loadPageData(page) {
        console.log('=== LOAD PAGE DATA FOR:', page, '===');
        
        // Use setTimeout to ensure DOM is updated before loading data
        setTimeout(() => {
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
                case 'category-requests':
                    this.loadCategoryRequestsPage();
                    break;
                case 'users':
                    console.log('Users page detected, checking role...');
                    if (this.currentUser.role === 'admin') {
                        console.log('User is admin, calling loadUsers()');
                        this.loadUsers();
                    } else {
                        console.log('❌ User is not admin, denying access to users page');
                        this.showNotification('Chỉ admin mới có quyền truy cập quản lý người dùng', 'error');
                        // Don't redirect to dashboard - just hide loading and show error
                        this.hideLoadingState();
                    }
                    // Always hide loading for users page
                    setTimeout(() => this.hideLoadingState(), 500);
                    break;
                case 'departments':
                    if (this.currentUser.role === 'admin') {
                        if (this.departmentsManager) {
                            this.departmentsManager.loadDepartments();
                        } else {
                            console.log('❌ Departments manager not available');
                            this.showNotification('Quản lý phòng ban không khả dụng', 'error');
                        }
                    } else {
                        console.log('❌ User is not admin, denying access to departments page');
                        this.showNotification('Chỉ admin mới có quyền quản lý phòng ban', 'error');
                        // Don't redirect to dashboard - just hide loading and show error
                        this.hideLoadingState();
                    }
                    // Always hide loading for departments page
                    setTimeout(() => this.hideLoadingState(), 500);
                    break;
                case 'support-requests':
                    if (this.currentUser && ['admin', 'staff'].includes(this.currentUser.role)) {
                        this.loadSupportRequests();
                    } else {
                        console.log('❌ User is not admin/staff or user not loaded, denying access to support requests page');
                        if (this.currentUser) {
                            this.showNotification('Chỉ admin và staff mới có quyền xem yêu cầu hỗ trợ', 'error');
                        }
                        // Don't redirect to dashboard - just hide loading and show error
                        this.hideLoadingState();
                    }
                    // Always hide loading for support requests page
                    setTimeout(() => this.hideLoadingState(), 500);
                    break;
                case 'reject-requests':
                    console.log('=== REJECT REQUESTS ACCESS DEBUG ===');
                    console.log('Current user:', this.currentUser);
                    console.log('User role:', this.currentUser ? this.currentUser.role : 'NO USER');
                    console.log('Is admin/staff?', this.currentUser && ['admin', 'staff'].includes(this.currentUser.role));
                    
                    if (this.currentUser && ['admin', 'staff'].includes(this.currentUser.role)) {
                        console.log('✅ Access granted, loading reject requests');
                        this.loadRejectRequests();
                    } else {
                        console.log('❌ User is not admin/staff or user not loaded, denying access to reject requests page');
                        if (this.currentUser) {
                            this.showNotification('Chỉ admin và staff mới có quyền xem yêu cầu từ chối', 'error');
                        } else {
                            this.showNotification('Vui lòng đăng nhập lại', 'error');
                            // Redirect to login after a delay
                            setTimeout(() => {
                                this.showLoginScreen();
                            }, 2000);
                        }
                        // Don't redirect to dashboard - just hide loading and show error
                        this.hideLoadingState();
                    }
                    // Always hide loading for reject requests page
                    setTimeout(() => this.hideLoadingState(), 500);
                    break;
                case 'kpi-export':
                    if (this.currentUser && this.currentUser.role === 'admin') {
                        this.loadKPIExport();
                    } else {
                        console.log('❌ User is not admin, denying access to KPI export page');
                        if (this.currentUser) {
                            this.showNotification('Chỉ admin mới có quyền xuất KPI', 'error');
                        }
                        // Don't redirect to dashboard - just hide loading and show error
                        this.hideLoadingState();
                    }
                    // Always hide loading for KPI export page
                    setTimeout(() => this.hideLoadingState(), 500);
                    break;
                default:
                    console.log('Unknown page:', page);
                    this.hideLoadingState();
            }
        }, 100); // Small delay to ensure DOM is updated
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
                
                // Use status_counts from API for accurate dashboard stats
                const apiStats = statsResponse.data.status_counts || {};
                console.log('Dashboard API stats:', apiStats);
                console.log('Dashboard all requests count:', allRequests.length);
                
                // Prioritize support requests in recent requests
                const supportRequests = recentRequests.filter(r => r.status === 'request_support');
                const otherRequests = recentRequests.filter(r => r.status !== 'request_support');
                
                // Put support requests first, then other requests, limit to 5 total
                recentRequests = [...supportRequests, ...otherRequests].slice(0, 5);
                
                // Use API stats for dashboard (more accurate than client-side calculation)
                const stats = {
                    total: apiStats.pagination?.total || allRequests.length,
                    open: apiStats.open || allRequests.filter(r => r.status === 'open').length,
                    in_progress: apiStats.in_progress || allRequests.filter(r => r.status === 'in_progress').length,
                    resolved: apiStats.resolved || allRequests.filter(r => r.status === 'resolved').length,
                    rejected: apiStats.rejected || allRequests.filter(r => r.status === 'rejected').length,
                    request_support: apiStats.request_support || allRequests.filter(r => r.status === 'request_support').length,
                    closed: apiStats.closed || allRequests.filter(r => r.status === 'closed').length
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
                
                // Update notification count when dashboard loads
                await this.updateNotificationCount();
                
                const requestSupportCount = document.getElementById('requestSupportCount');
                if (requestSupportCount) requestSupportCount.textContent = stats.request_support;
                
                const closedRequests = document.getElementById('closedRequests');
                if (closedRequests) closedRequests.textContent = stats.closed;

                // Load recent requests (limited)
                this.displayRecentRequests(recentRequests);
            }
        } catch (error) {
            console.error('Dashboard load error:', error);
        } finally {
            this.hideLoadingState();
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
            <div class="request-item status-${request.status} priority-${request.priority}" onclick="app.showRequestDetail(${request.id})">
                <div class="request-header">
                    <div class="request-title">
                        <span class="request-id">ID: ${request.id}</span> - ${request.title}
                    </div>
                    <div class="request-badges">
                        <span class="badge status-${request.status}">${this.getStatusText(request.status)}</span>
                        <span class="badge priority-${request.priority}">${this.getPriorityText(request.priority)}</span>
                        ${request.reject_status && request.status !== 'rejected' ? `<span class="badge badge-reject">Có yêu cầu từ chối</span>` : ''}
                        ${request.support_status ? `<span class="badge badge-support">Có yêu cầu hỗ trợ</span>` : ''}
                        ${request.feedback_rating ? `<span class="badge badge-feedback"><i class="fas fa-star"></i> Đã đánh giá</span>` : ''}
                    </div>
                </div>
                <div class="request-meta">
                    <span><i class="fas fa-user"></i> ${request.requester_name}</span>
                    <span><i class="fas fa-tag"></i> ${request.category_name}</span>
                    <span><i class="fas fa-clock"></i> ${this.formatDate(request.created_at)}</span>
                </div>
                <div class="request-description">
                    <p>${request.description.substring(0, 150)}${request.description.length > 150 ? '...' : ''}</p>
                </div>
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
            
            // Add search functionality
            const requestSearch = document.getElementById('requestSearch');
            const search = requestSearch ? requestSearch.value.trim() : '';
            
            // Use search API if search parameter exists, otherwise use normal API
            let url;
            if (search) {
                url = `api/search_requests.php?page=${page}&search=${encodeURIComponent(search)}`;
                if (status) url += `&status=${status}`;
                if (priority) url += `&priority=${priority}`;
                if (category) url += `&category=${category}`;
            } else {
                url = `api/service_requests.php?action=list&page=${page}`;
                if (status) url += `&status=${status}`;
                if (priority) url += `&priority=${priority}`;
                if (category) url += `&category=${category}`;
            }

            const response = await this.apiCall(url);
            
            if (response.success) {
                this.displayRequests(response.data.requests);
                this.displayPagination(response.data);
                this.updateStatusCounts(response.data.status_counts || {});
            }
        } catch (error) {
            console.error('Requests load error:', error);
        } finally {
            this.hideLoadingState();
        }
    }

    displayRequests(requests) {
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
            <div class="request-item status-${request.status} priority-${request.priority}" onclick="app.showRequestDetail(${request.id})">
                <div class="request-header">
                    <div class="request-title">
                        <span class="request-id">ID: ${request.id}</span> - ${request.title}
                    </div>
                    <div class="request-badges">
                        <span class="badge status-${request.status}">${this.getStatusText(request.status)}</span>
                        <span class="badge priority-${request.priority}">${this.getPriorityText(request.priority)}</span>
                        ${request.reject_status && request.status !== 'rejected' ? `<span class="badge badge-reject">Có yêu cầu từ chối</span>` : ''}
                        ${request.support_status ? `<span class="badge badge-support">Có yêu cầu hỗ trợ</span>` : ''}
                        ${request.feedback_rating ? `<span class="badge badge-feedback"><i class="fas fa-star"></i> Đã đánh giá</span>` : ''}
                    </div>
                </div>
                <div class="request-meta">
                    <span><i class="fas fa-user"></i> ${request.requester_name}</span>
                    <span><i class="fas fa-tag"></i> ${request.category_name}</span>
                    <span><i class="fas fa-clock"></i> ${this.formatDate(request.created_at)}</span>
                    ${request.assigned_name ? `<span><i class="fas fa-user-check"></i> ${request.assigned_name}</span>` : ''}
                    ${request.accepted_at ? `<span><i class="fas fa-hand-paper"></i> Đã nhận: ${this.formatDate(request.accepted_at)}</span>` : ''}
                </div>
                <div class="request-description">
                    <p>${request.description.substring(0, 150)}${request.description.length > 150 ? '...' : ''}</p>
                </div>
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
        
        if (status === 'support') {
            console.log('=== SHOWING SUPPORT PAGE ===');
            console.log('Current user role:', this.currentUser.role);
            
            // Load support requests (only for admin, staff can see their own)
            if (this.currentUser.role === 'admin' || this.currentUser.role === 'staff') {
                await this.loadSupportRequests();
            } else {
                this.showNotification('Bạn không có quyền xem yêu cầu hỗ trợ', 'error');
            }
        } else {
            // For all other statuses, navigate to requests page
            // Only showPage if we're not already on requests page
            if (this.currentPage !== 'requests') {
                this.showPage('requests');
                
                // Set status filter after page loads
                setTimeout(() => {
                    const statusFilter = document.getElementById('statusFilter');
                    if (statusFilter) {
                        statusFilter.value = status === 'all' ? '' : status;
                    }
                    this.loadRequests();
                }, 200);
            } else {
                // Already on requests page, just update filter and reload
                const statusFilter = document.getElementById('statusFilter');
                if (statusFilter) {
                    statusFilter.value = status === 'all' ? '' : status;
                }
                this.loadRequests();
            }
        }
    }

    async loadSupportRequests() {
        try {
            console.log('=== LOADING SUPPORT REQUESTS ===');
            console.log('Current user:', this.currentUser);
            console.log('Filter element:', document.getElementById('supportStatusFilter'));
            
            const status = document.getElementById('supportStatusFilter').value || 'all';
            console.log('Loading with status:', status);
            
            // For both admin and staff, load all requests when status is 'all', otherwise filter by status
            if (status === 'all') {
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
        } finally {
            this.hideLoadingState();
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
            <div class="request-item support-request clickable-card status-${support.status}" data-support-id="${support.id}" data-service-request-id="${support.service_request_id}">
                <div class="request-header">
                    <div class="request-title">
                        <span class="request-link">
                            <span class="request-id">ID: ${support.service_request_id}</span> - ${support.request_title}
                        </span>
                    </div>
                    <div class="request-badges">
                        <span class="badge status-${support.status}">${this.getSupportStatusText(support.status)}</span>
                        <span class="badge support-type-${support.support_type}">${this.getSupportTypeText(support.support_type)}</span>
                    </div>
                </div>
                
                <div class="request-meta">
                    <span><i class="fas fa-user"></i> ${support.requester_name}</span>
                    <span><i class="fas fa-clock"></i> ${this.formatDate(support.created_at)}</span>
                </div>
                
                <div class="request-description">
                    <p>${support.support_details ? support.support_details.substring(0, 150) + (support.support_details.length > 150 ? '...' : '') : 'Không có chi tiết'}</p>
                </div>
            </div>
        `).join('');
        
        // Add click event listeners for clickable support request cards
        const clickableCards = container.querySelectorAll('.clickable-card');
        console.log('Found clickable support request cards:', clickableCards.length);
        
        clickableCards.forEach((card, index) => {
            console.log(`Binding clickable support card ${index}:`, card.dataset.serviceRequestId);
            card.addEventListener('click', (e) => {
                // Don't navigate if clicking on buttons or links
                if (e.target.closest('button') || e.target.closest('a')) {
                    return;
                }
                
                console.log('Support request card clicked:', card.dataset.serviceRequestId);
                const serviceRequestId = card.dataset.serviceRequestId;
                
                // Navigate to original request detail page
                window.location.href = `request-detail.html?id=${serviceRequestId}`;
            });
        });
    }

    async showRequestDetail(id) {
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
                    ${request.assigned_at ? `<span><strong>Ngày nhận:</strong> ${this.formatDate(request.assigned_at)}</span>` : ''}
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
                
                // Check if we're currently on the detail page
                if (window.location.pathname.includes('request-detail.html')) {
                    // Reload the page to refresh data
                    window.location.reload();
                } else {
                    // Navigate to detail page
                    this.showRequestDetail(id);
                }
                
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
                
                // Check if we're currently on the detail page
                if (window.location.pathname.includes('request-detail.html')) {
                    // Reload the page to refresh data
                    window.location.reload();
                } else {
                    // Navigate to detail page
                    this.showRequestDetail(id);
                }
                
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
                
                // Check if we're currently on the detail page
                if (window.location.pathname.includes('request-detail.html')) {
                    // Reload the page to refresh data
                    window.location.reload();
                } else {
                    // Navigate to detail page
                    this.showRequestDetail(requestId);
                }
                
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
        
        // Add a very small delay to ensure UI updates
        await new Promise(resolve => setTimeout(resolve, 50));
        
        const formData = new FormData(e.target);
        console.log('Form data:', Object.fromEntries(formData));
        
        try {
            // Create FormData for file upload
            const submitData = new FormData();
            submitData.append('action', 'create'); // Add action parameter for quick fix
            submitData.append('title', formData.get('title'));
            submitData.append('description', formData.get('description'));
            submitData.append('category_id', formData.get('category_id'));
            submitData.append('priority', formData.get('priority'));
            
            // Add selected files
            this.selectedFiles.forEach((file, index) => {
                submitData.append(`attachments[${index}]`, file);
            });
            
            console.log('Submitting with files:', this.selectedFiles);
            
            // Update loading text - Creating request
            submitBtn.innerHTML = '<i class="fas fa-plus-circle"></i> Đang tạo yêu cầu...';
            
            // Create request with timeout protection
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 15000); // 15s timeout for large files
            
            const response = await fetch('api/service_requests.php', {
                method: 'POST',
                body: submitData,
                credentials: 'include',
                signal: controller.signal
            });
            
            clearTimeout(timeoutId);
            
            const result = await response.json();
            console.log('Create request response:', result);

            if (result.success) {
                // Update loading text - Success
                submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Thành công!';
                
                // Hiển thị thông báo thành công tương tự như chức năng gửi yêu cầu hỗ trợ
                if (window.notificationManager) {
                    window.notificationManager.success(
                        `Yêu cầu #${result.data?.id || 'mới'} đã được tạo thành công!`,
                        'Tạo yêu cầu thành công',
                        3000
                    );
                } else {
                    this.showNotification('Yêu cầu đã được tạo thành công!', 'success');
                }
                
                e.target.reset();
                this.selectedFiles = [];
                this.updateFileList();
                
                // Very small delay before redirect
                await new Promise(resolve => setTimeout(resolve, 50));
                this.showPage('requests');
            } else {
                // Update loading text - Error
                submitBtn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Lỗi!';
                
                if (window.notificationManager) {
                    window.notificationManager.error(result.message, 'Lỗi tạo yêu cầu');
                } else {
                    this.showNotification(result.message, 'error');
                }
            }
        } catch (error) {
            console.error('Create request error:', error);
            
            if (error.name === 'AbortError') {
                this.showNotification('Yêu cầu hết thời gian chờ (15s). File có thể quá lớn (>10MB) hoặc mạng chậm. Vui lòng thử lại với file nhỏ hơn hoặc kiểm tra kết nối.', 'error');
            } else if (error.message && error.message.includes('NetworkError')) {
                this.showNotification('Lỗi kết nối mạng. Vui lòng kiểm tra kết nối và thử lại.', 'error');
            } else {
                // Update loading text - Connection Error
                submitBtn.innerHTML = '<i class="fas fa-wifi"></i> Lỗi kết nối!';
                this.showNotification('Lỗi kết nối: ' + (error.message || 'Không xác định'), 'error');
            }
        } finally {
            // Hide loading overlay
            this.hideLoadingState();
            
            // Restore button states with shorter delay
            setTimeout(() => {
                submitBtn.disabled = false;
                cancelBtn.disabled = false;
                submitBtn.innerHTML = originalSubmitText;
            }, 200);
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
            this.hideLoadingState();
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
            <div class="category-item" data-category-id="${category.id}" onclick="app.showCategoryRequests(${category.id}, '${category.name}')">
                <div class="category-info">
                    <h4>${category.name}</h4>
                    <p>${category.description || 'Không có mô tả'}</p>
                    <div class="category-stats" id="categoryStats_${category.id}">
                        <span class="stat-badge">
                            <i class="fas fa-list"></i>
                            <span class="request-count" data-category-id="${category.id}">0</span> yêu cầu
                        </span>
                        <div class="status-breakdown" id="statusBreakdown_${category.id}">
                            <span class="status-badge open">0 mở</span>
                            <span class="status-badge in_progress">0 đang xử lý</span>
                            <span class="status-badge resolved">0 đã giải quyết</span>
                            <span class="status-badge closed">0 đã đóng</span>
                        </div>
                    </div>
                </div>
                ${this.currentUser.role === 'admin' ? `
                    <div class="category-actions">
                        <button class="btn btn-secondary" onclick="app.editCategory(${category.id}, '${category.name}', '${category.description || ''}')" onclick="event.stopPropagation()">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger" onclick="app.deleteCategory(${category.id})" onclick="event.stopPropagation()">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                ` : ''}
            </div>
        `).join('');
        
        // Load request counts for each category
        this.loadCategoryRequestCounts(categories);
    }

    // Load request counts for each category
    async loadCategoryRequestCounts(categories) {
        console.log('=== LOAD CATEGORY REQUEST COUNTS ===');
        console.log('Categories to load counts for:', categories);
        
        try {
            // Get request counts for all categories
            const response = await this.apiCall('api/service_requests.php?action=category_stats');
            
            console.log('Category stats API response:', response);
            
            if (response.success) {
                const stats = response.data;
                console.log('Category stats data:', stats);
                
                // Update count for each category
                categories.forEach(category => {
                    const categoryStats = stats[category.id] || {
                        total: 0,
                        open: 0,
                        in_progress: 0,
                        resolved: 0,
                        closed: 0
                    };
                    
                    // Update total count
                    const countElement = document.querySelector(`.request-count[data-category-id="${category.id}"]`);
                    if (countElement) {
                        countElement.textContent = categoryStats.total;
                        console.log(`Updated category ${category.id} (${category.name}) total count: ${categoryStats.total}`);
                    } else {
                        console.log(`Count element not found for category ${category.id}`);
                    }
                    
                    // Update status breakdown (always visible now)
                    const statusBreakdown = document.getElementById(`statusBreakdown_${category.id}`);
                    if (statusBreakdown) {
                        statusBreakdown.innerHTML = `
                            <span class="status-badge open">${categoryStats.open} mở</span>
                            <span class="status-badge in_progress">${categoryStats.in_progress} đang xử lý</span>
                            <span class="status-badge resolved">${categoryStats.resolved} đã giải quyết</span>
                            <span class="status-badge closed">${categoryStats.closed} đã đóng</span>
                        `;
                        // Ensure status breakdown is always visible
                        statusBreakdown.style.display = 'flex';
                    }
                });
            } else {
                console.error('Category stats API failed:', response.message);
            }
        } catch (error) {
            console.error('Error loading category request counts:', error);
        }
    }

    // Load category requests page
    async loadCategoryRequestsPage() {
        console.log('=== LOAD CATEGORY REQUESTS PAGE ===');
        
        // Get category info from session storage
        const categoryId = sessionStorage.getItem('currentCategoryId');
        const categoryName = sessionStorage.getItem('currentCategoryName');
        
        console.log('Category ID from session:', categoryId);
        console.log('Category Name from session:', categoryName);
        
        if (!categoryId) {
            console.error('No category ID found in session storage');
            this.showNotification('Không tìm thấy thông tin danh mục', 'error');
            // Don't redirect to categories - just show error and hide loading
            this.hideLoadingState();
            return;
        }
        
        // Update page header with category name
        const pageHeader = document.querySelector('#category-requestsPage .page-header h2');
        if (pageHeader && categoryName) {
            pageHeader.textContent = `Yêu cầu - ${categoryName}`;
        }
        
        // Load requests for this category
        await this.loadCategoryRequestsList(categoryId);
    }

    // Load requests list for category page
    async loadCategoryRequestsList(categoryId) {
        const container = document.getElementById('categoryRequestsList');
        
        if (!container) {
            console.error('Category requests list container not found');
            return;
        }
        
        // Show loading state
        container.innerHTML = '<div class="loading">Đang tải yêu cầu...</div>';
        
        try {
            const response = await this.apiCall(`api/service_requests.php?action=list&category_id=${categoryId}`);
            
            console.log('Category requests API response:', response);
            
            if (response.success) {
                // Handle different data structures
                let requests = response.data;
                
                console.log('Initial requests data:', requests);
                console.log('Type of requests:', typeof requests);
                console.log('Is array?', Array.isArray(requests));
                
                // Early validation with try-catch
                try {
                    // Check if data has pagination structure
                    if (requests && requests.requests && Array.isArray(requests.requests)) {
                        requests = requests.requests;
                        console.log('Using paginated data.requests:', requests);
                    } else if (requests && requests.data && Array.isArray(requests.data)) {
                        requests = requests.data;
                        console.log('Using paginated data.data:', requests);
                    } else if (!Array.isArray(requests)) {
                        console.error('Invalid data structure:', requests);
                        container.innerHTML = '<p class="error">Dữ liệu không hợp lệ từ server.</p>';
                        return;
                    }
                    
                    console.log('Final requests array:', requests);
                    console.log('Requests length:', requests.length);
                    
                    // Additional validation before map
                    if (!requests || !Array.isArray(requests)) {
                        console.error('Requests is not an array before map:', requests);
                        container.innerHTML = '<p class="error">Dữ liệu yêu cầu không hợp lệ.</p>';
                        return;
                    }
                    
                } catch (validationError) {
                    console.error('Error during validation:', validationError);
                    container.innerHTML = '<p class="error">Lỗi khi xác thực dữ liệu.</p>';
                    return;
                }
                
                if (requests.length === 0) {
                    container.innerHTML = '<p class="no-requests">Không có yêu cầu nào trong danh mục này.</p>';
                } else {
                    try {
                        container.innerHTML = requests.map(request => `
                            <div class="request-item status-${request.status} priority-${request.priority}" onclick="app.showRequestDetail(${request.id})">
                                <div class="request-header">
                                    <div class="request-title">
                                        <span class="request-id">ID: ${request.id}</span> - ${request.title}
                                    </div>
                                    <div class="request-badges">
                                        <span class="badge status-${request.status}">${this.getStatusText(request.status)}</span>
                                        <span class="badge priority-${request.priority}">${this.getPriorityText(request.priority)}</span>
                                    </div>
                                </div>
                                <div class="request-meta">
                                    <span><i class="fas fa-user"></i> ${request.requester_name}</span>
                                    <span><i class="fas fa-tag"></i> ${request.category_name || 'N/A'}</span>
                                    <span><i class="fas fa-clock"></i> ${this.formatDate(request.created_at)}</span>
                                </div>
                                <div class="request-description">
                                    <p>${request.description.substring(0, 150)}${request.description.length > 150 ? '...' : ''}</p>
                                </div>
                            </div>
                        `).join('');
                    } catch (mapError) {
                        console.error('Error during map operation:', mapError);
                        console.error('Requests data during map:', requests);
                        container.innerHTML = '<p class="error">Lỗi khi hiển thị danh sách yêu cầu.</p>';
                    }
                }
            } else {
                container.innerHTML = '<p class="error">Không thể tải yêu cầu: ' + response.message + '</p>';
            }
        } catch (error) {
            console.error('Error loading category requests:', error);
            container.innerHTML = '<p class="error">Lỗi khi tải yêu cầu. Vui lòng thử lại.</p>';
        } finally {
            // Hide the main loading overlay if it exists
            this.hideLoadingState();
        }
    }

    // Show category requests in a new page
    showCategoryRequests(categoryId, categoryName) {
        console.log('=== SHOW CATEGORY REQUESTS ===');
        console.log('Category ID:', categoryId);
        console.log('Category Name:', categoryName);
        
        // Store category info in session storage for the new page
        sessionStorage.setItem('currentCategoryId', categoryId);
        sessionStorage.setItem('currentCategoryName', categoryName);
        
        // Global error handler for undefined script errors
        window.addEventListener('error', function(e) {
            if (e.filename && e.filename.includes('onboarding.js')) {
                console.warn('Ignoring onboarding.js error - file not found');
                e.preventDefault();
                return false;
            }
        });

        // Global unhandled promise rejection handler
        window.addEventListener('unhandledrejection', function(e) {
            if (e.reason && e.reason.toString().includes('onboarding')) {
                console.warn('Ignoring onboarding promise rejection');
                e.preventDefault();
                return false;
            }
        });

        // Use internal navigation instead of full page reload
        this.showPage('category-requests');
    }

    // Toggle category requests list (kept for backward compatibility)
    async toggleCategoryRequests(categoryId) {
        const requestsList = document.getElementById(`categoryRequests_${categoryId}`);
        const statusBreakdown = document.getElementById(`statusBreakdown_${categoryId}`);
        
        if (requestsList && requestsList.style.display === 'none') {
            // Show requests
            requestsList.style.display = 'block';
            statusBreakdown.style.display = 'block';
            
            // Load requests for this category
            await this.loadCategoryRequests(categoryId);
        } else {
            // Hide requests
            if (requestsList) requestsList.style.display = 'none';
            if (statusBreakdown) statusBreakdown.style.display = 'none';
        }
    }

    // Load requests for a specific category
    async loadCategoryRequests(categoryId) {
        const requestsList = document.getElementById(`categoryRequests_${categoryId}`);
        
        try {
            const response = await this.apiCall(`api/service_requests.php?action=list&category_id=${categoryId}`);
            
            console.log('Category requests API response:', response);
            
            if (response.success) {
                // Handle different data structures
                let requests = response.data;
                
                console.log('Initial requests data:', requests);
                console.log('Type of requests:', typeof requests);
                console.log('Is array?', Array.isArray(requests));
                
                // Early validation with try-catch
                try {
                    // Check if data has pagination structure
                    if (requests && requests.requests && Array.isArray(requests.requests)) {
                        requests = requests.requests;
                        console.log('Using paginated data.requests:', requests);
                    } else if (requests && requests.data && Array.isArray(requests.data)) {
                        requests = requests.data;
                        console.log('Using paginated data.data:', requests);
                    } else if (!Array.isArray(requests)) {
                        console.error('Invalid data structure:', requests);
                        requestsList.innerHTML = '<p class="error">Dữ liệu không hợp lệ từ server.</p>';
                        return;
                    }
                    
                    console.log('Final requests array:', requests);
                    console.log('Requests length:', requests.length);
                    
                    // Additional validation before map
                    if (!requests || !Array.isArray(requests)) {
                        console.error('Requests is not an array before map:', requests);
                        requestsList.innerHTML = '<p class="error">Dữ liệu yêu cầu không hợp lệ.</p>';
                        return;
                    }
                    
                } catch (validationError) {
                    console.error('Error during validation:', validationError);
                    requestsList.innerHTML = '<p class="error">Lỗi khi xác thực dữ liệu.</p>';
                    return;
                }
                
                if (requests.length === 0) {
                    requestsList.innerHTML = '<p class="no-requests">Không có yêu cầu nào trong danh mục này.</p>';
                } else {
                    try {
                        requestsList.innerHTML = requests.map(request => `
                            <div class="request-item compact" onclick="app.showRequestDetail(${request.id})">
                                <div class="request-header">
                                    <div class="request-title">
                                        <span class="request-id">ID: ${request.id}</span> - ${request.title}
                                    </div>
                                    <span class="badge status-${request.status}">${this.getStatusText(request.status)}</span>
                                </div>
                                <div class="request-meta">
                                    <span><i class="fas fa-user"></i> ${request.requester_name}</span>
                                    <span><i class="fas fa-clock"></i> ${this.formatDate(request.created_at)}</span>
                                </div>
                            </div>
                        `).join('');
                    } catch (mapError) {
                        console.error('Error during map operation:', mapError);
                        console.error('Requests data during map:', requests);
                        requestsList.innerHTML = '<p class="error">Lỗi khi hiển thị danh sách yêu cầu.</p>';
                    }
                }
            } else {
                requestsList.innerHTML = '<p class="error">Không thể tải yêu cầu: ' + response.message + '</p>';
            }
        } catch (error) {
            console.error('Error loading category requests:', error);
            requestsList.innerHTML = '<p class="error">Lỗi khi tải yêu cầu. Vui lòng thử lại.</p>';
        }
    }

    showCategoryModal(category = null) {
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
            console.log('=== CHECK AUTH DEBUG ===');
            console.log('Document cookies:', document.cookie);
            
            // Add small delay to ensure session is ready
            await new Promise(resolve => setTimeout(resolve, 100));
            
            // Check if user session exists on server
            const response = await this.apiCall('api/auth.php?action=check_session');
            console.log('=== AUTH CHECK DEBUG ===');
            console.log('Auth check response:', response);
            console.log('Response success:', response.success);
            console.log('Response data:', response.data);
            
            if (response.success && response.data) {
                // User is logged in, set current user and show dashboard
                this.currentUser = response.data;
                console.log('✅ User is logged in:', this.currentUser);
                console.log('User role:', this.currentUser.role);
                console.log('User ID:', this.currentUser.id);
                this.showDashboard();
                // Start auto-reload after successful login
                this.startAutoReload();
            } else {
                // No active session, show login screen
                console.log('❌ No active session, showing login');
                console.log('Response message:', response.message);
                this.showLoginScreen();
            }
        } catch (error) {
            console.error('Auth check error:', error);
            this.showLoginScreen();
        }
    }

    async apiCall(url, options = {}) {
        const baseUrl = window.location.origin + '/it-service-request';
        const fullUrl = url.startsWith('http') ? url : baseUrl + '/' + url;
        
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include' // Important for session cookies
        };

        const finalOptions = { ...defaultOptions, ...options };
        
        console.log('=== API CALL DEBUG ===');
        console.log('URL:', fullUrl);
        console.log('Options:', finalOptions);
        console.log('Origin:', window.location.origin);
        
        try {
            const response = await fetch(fullUrl, finalOptions);
            
            // Check if response is OK
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            // Get response text first
            const responseText = await response.text();
            
            // Try to parse as JSON
            try {
                return JSON.parse(responseText);
            } catch (parseError) {
                console.error('JSON Parse Error:', parseError);
                console.error('Response Text:', responseText);
                throw new Error(`Invalid JSON response: ${parseError.message}`);
            }
        } catch (error) {
            console.error('API Call Error:', error);
            throw error;
        }
    }

    showNotification(message, type = 'info') {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.notification');
        existingNotifications.forEach(notification => notification.remove());
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 5px;
            color: white;
            font-weight: 500;
            z-index: 10000;
            max-width: 300px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        `;
        
        // Set background gradient based on type (matching request detail page)
        switch (type) {
            case 'success':
                notification.style.background = 'linear-gradient(135deg, #28a745, #20c997)';
                break;
            case 'error':
                notification.style.background = 'linear-gradient(135deg, #dc3545, #c82333)';
                break;
            case 'warning':
                notification.style.background = 'linear-gradient(135deg, #ffc107, #e0a800)';
                notification.style.color = '#212529';
                break;
            default:
                notification.style.background = 'linear-gradient(135deg, #17a2b8, #138496)';
        }
        
        notification.textContent = message;
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }

    getStatusText(status) {
        const statuses = {
            'open': 'Mở',
            'in_progress': 'Đang xử lý',
            'resolved': 'Đã giải quyết',
            'rejected': this.currentUser && ['admin', 'staff'].includes(this.currentUser.role) ? 'Đã từ chối' : 'Đã xử lý',
            'closed': 'Đã đóng',
            'cancelled': 'Đã hủy',
            'request_support': 'Cần hỗ trợ'
        };
        return statuses[status] || status;
    }

    getPriorityText(priority) {
        const priorityMap = {
            'low': 'Thấp',
            'medium': 'Trung bình',
            'high': 'Cao',
            'critical': 'Khẩn cấp'
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
            this.hideLoadingState();
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
            console.log('=== LOAD REJECT REQUESTS DEBUG ===');
            
            const statusFilter = document.getElementById('rejectStatusFilter');
            const status = statusFilter ? statusFilter.value : 'pending';
            
            console.log('Status filter:', status);
            console.log('Status filter element:', statusFilter);
            
            // If "all" is selected, don't pass status parameter to get all requests
            const url = status === 'all' 
                ? 'api/reject_requests.php?action=list'
                : `api/reject_requests.php?action=list&status=${status}`;
            
            console.log('API URL:', url);
            console.log('Current user before API call:', this.currentUser);
            
            const response = await this.apiCall(url);
            
            console.log('=== API RESPONSE DEBUG ===');
            console.log('API Response:', response);
            console.log('Response success:', response?.success);
            console.log('Response message:', response?.message);
            console.log('Response data:', response?.data);
            
            if (response.success) {
                console.log('✅ API call successful, displaying requests');
                this.displayRejectRequests(response.data.reject_requests || response.data);
            } else {
                console.log('❌ API call failed:', response.message);
                this.showNotification(response.message, 'error');
            }
            
        } catch (error) {
            console.error('=== LOAD REJECT REQUESTS ERROR ===');
            console.error('Error:', error);
            console.error('Error message:', error.message);
            console.error('Error stack:', error.stack);
            console.error('Current user:', this.currentUser);
            
            this.showNotification('Lỗi tải yêu cầu từ chối', 'error');
        } finally {
            this.hideLoadingState();
        }
    }

    displayRejectRequests(rejectRequests) {
        const container = document.getElementById('rejectRequestsList');
        
        if (rejectRequests.length === 0) {
            container.innerHTML = '<p>Không có yêu cầu từ chối nào.</p>';
            return;
        }

        container.innerHTML = rejectRequests.map(reject => `
            <div class="request-item reject-request clickable-card" data-reject-id="${reject.id}" data-service-request-id="${reject.service_request_id}">
                <div class="request-header">
                    <h4>
                        <span class="request-link">ID: ${reject.service_request_id} - ${reject.service_request_title}</span>
                    </h4>
                    <div class="request-badges">
                        <span class="badge status-${reject.status}">${this.getRejectStatusText(reject.status)}</span>
                    </div>
                </div>
                
                <div class="request-meta">
                    <div class="meta-item">
                        <strong>Người từ chối:</strong> ${reject.rejecter_name}
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
                        <button class="btn btn-primary reject-process-btn" data-reject-id="${reject.id}">
                            <i class="fas fa-gavel"></i> Xử lý
                        </button>
                    ` : ''}
                    ${reject.admin_reason && ['admin', 'staff'].includes(this.currentUser.role) ? `
                        <div class="admin-reason">
                            <strong>Quyết định ADMIN:</strong> ${reject.admin_reason}
                        </div>
                    ` : ''}
                </div>
            </div>
        `).join('');
        
        // Add event listeners to reject process buttons
        const rejectButtons = container.querySelectorAll('.reject-process-btn');
        console.log('Found reject buttons:', rejectButtons.length);
        
        rejectButtons.forEach((btn, index) => {
            console.log(`Binding reject button ${index}:`, btn.dataset.rejectId);
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation(); // Prevent card click when button is clicked
                console.log('Reject button clicked:', btn.dataset.rejectId);
                const rejectId = btn.dataset.rejectId;
                this.showAdminRejectModal(rejectId);
            });
        });
        
        // Add click event listeners for clickable cards
        const clickableCards = container.querySelectorAll('.clickable-card');
        console.log('Found clickable cards:', clickableCards.length);
        
        clickableCards.forEach((card, index) => {
            console.log(`Binding clickable card ${index}:`, card.dataset.serviceRequestId);
            card.addEventListener('click', (e) => {
                // Don't navigate if clicking on buttons or links
                if (e.target.closest('button') || e.target.closest('a')) {
                    return;
                }
                
                console.log('Reject request card clicked:', card.dataset.serviceRequestId);
                const serviceRequestId = card.dataset.serviceRequestId;
                
                // Navigate to original request detail page
                window.location.href = `request-detail.html?id=${serviceRequestId}`;
            });
        });
    }

    showAdminRejectModal(rejectId) {
        console.log('=== showAdminRejectModal called ===');
        console.log('Reject ID:', rejectId);
        
        const modal = document.getElementById('adminRejectModal');
        const idInput = document.getElementById('adminRejectId');
        const form = document.getElementById('adminRejectForm');
        
        console.log('Modal found:', !!modal);
        console.log('ID input found:', !!idInput);
        console.log('Form found:', !!form);
        
        if (!modal || !idInput || !form) {
            console.error('Missing modal elements:', { modal: !!modal, idInput: !!idInput, form: !!form });
            this.showNotification('Lỗi: Không tìm thấy modal xử lý yêu cầu từ chối', 'error');
            return;
        }
        
        idInput.value = rejectId;
        form.reset();
        this.loadRejectRequestDetails(rejectId);
        modal.style.display = 'block';
        
        console.log('Modal displayed');
    }

    closeAdminRejectModal() {
        this.closeModal(document.getElementById('adminRejectModal'));
    }

    async loadRejectRequestDetails(rejectId) {
        try {
            const response = await this.apiCall(`api/reject_requests.php?action=get&id=${rejectId}`);
            
            if (response.success) {
                const reject = response.data;
                const container = document.getElementById('adminRejectRequestDetails');
                
                // Clear container before adding new content to prevent duplication
                container.innerHTML = '';
                
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
                            ${reject.admin_reason ? `
                                <div class="reject-item admin-decision">
                                    <strong><i class="fas fa-gavel"></i> Quyết định của Admin:</strong>
                                    <div class="admin-reason-text">${reject.admin_reason}</div>
                                    ${reject.processed_at ? `
                                        <div class="processed-info">
                                            <small><i class="fas fa-clock"></i> Thời gian xử lý: ${this.formatDate(reject.processed_at)}</small>
                                        </div>
                                    ` : ''}
                                </div>
                            ` : ''}
                            ${reject.status !== 'pending' && !reject.admin_reason ? `
                                <div class="reject-item">
                                    <strong><i class="fas fa-info-circle"></i> Trạng thái:</strong> Đã được xử lý
                                </div>
                            ` : ''}
                        </div>
                        
                        ${reject.attachments && reject.attachments.length > 0 ? `
                            <div class="reject-attachments">
                                <h4><i class="fas fa-paperclip"></i> Tệp đính kèm (${reject.attachments.length})</h4>
                                <div class="attachments-list">
                                    ${reject.attachments.map(attachment => {
                                        const isImage = attachment.mime_type.startsWith('image/');
                                        const fileExt = attachment.filename.split('.').pop().toLowerCase();
                                        const isPDF = fileExt === 'pdf';
                                        const isWord = ['doc', 'docx'].includes(fileExt);
                                        const isExcel = ['xls', 'xlsx'].includes(fileExt);
                                        const isPowerPoint = ['ppt', 'pptx'].includes(fileExt);
                                        const isText = ['txt', 'md'].includes(fileExt);
                                        const isViewable = isPDF || isWord || isExcel || isPowerPoint || isText;
                                        
                                        return `
                                            <div class="attachment-item">
                                                <div class="attachment-info">
                                                    <i class="fas fa-${isImage ? 'image' : isPDF ? 'file-pdf' : isWord ? 'file-word' : isExcel ? 'file-excel' : isPowerPoint ? 'file-powerpoint' : 'file'}"></i>
                                                    <span class="attachment-name">${attachment.original_name}</span>
                                                    <span class="attachment-size">(${this.formatFileSize(attachment.file_size)})</span>
                                                </div>
                                                <div class="attachment-actions">
                                                    ${isImage ? `
                                                        <img src="api/reject_request_attachment.php?file=${attachment.filename}&action=view" 
                                                             alt="${attachment.original_name}" 
                                                             class="attachment-preview"
                                                             onclick="app.showImageModal('api/reject_request_attachment.php?file=${attachment.filename}&action=view', '${attachment.original_name}')"
                                                             style="cursor: pointer;"
                                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                                        <div class="image-error" style="display: none; padding: 10px; background: #f8d7da; color: #721c24; border-radius: 4px; text-align: center;">
                                                            <i class="fas fa-exclamation-triangle"></i> Không hiển thị được hình ảnh
                                                            </div>
                                                        <div class="image-overlay">
                                                            <i class="fas fa-search-plus"></i>
                                                        </div>
                                                    ` : ''}
                                                    ${isViewable ? `
                                                        <button class="btn btn-sm btn-primary" 
                                                                onclick="app.viewDocument('api/reject_request_attachment.php?file=${attachment.filename}&action=view', '${attachment.original_name}', '${fileExt}')">
                                                            <i class="fas fa-eye"></i> Xem
                                                        </button>
                                                    ` : ''}
                                                    <a href="api/reject_request_attachment.php?file=${attachment.filename}&action=download" 
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
        
        // Show loading state
        const submitBtn = e.target.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Đang xử lý...';
        submitBtn.disabled = true;
        this.showLoadingState('Đang xử lý yêu cầu từ chối...');
        
        try {
            const response = await this.apiCall('api/reject_requests.php', {
                method: 'PUT',
                body: JSON.stringify({
                    action: 'update',
                    reject_id: rejectId,
                    decision: formData.get('decision'),
                    admin_reason: formData.get('reason')
                })
            });

            if (response.success) {
                const decision = formData.get('decision');
                const message = decision === 'approved' ? 'Yêu cầu từ chối đã được phê duyệt thành công!' : 'Yêu cầu từ chối đã bị từ chối!';
                this.showNotification(message, 'success');
                this.closeAdminRejectModal();
                // Reload reject requests list
                this.loadRejectRequests();
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            this.showNotification('Lỗi xử lý yêu cầu từ chối', 'error');
        } finally {
            // Hide loading state
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
            this.hideLoadingState();
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
            <div class="request-item support-request clickable-card" data-support-id="${support.id}" data-service-request-id="${support.service_request_id}">
                <div class="request-header">
                    <h4>
                        <span class="request-link">ID: ${support.service_request_id} - ${support.request_title}</span>
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
                    ${support.admin_reason && ['admin', 'staff'].includes(this.currentUser.role) ? `
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
                        <button class="btn btn-primary" onclick="app.showAdminSupportModal(${support.id}, event)">
                            <i class="fas fa-gavel"></i> Xử lý
                        </button>
                    ` : ''}
                    ${support.admin_reason && ['admin', 'staff'].includes(this.currentUser.role) ? `
                        <div class="admin-reason">
                            <strong>Quyết định ADMIN:</strong> ${support.admin_reason}
                        </div>
                    ` : ''}
                </div>
            </div>
        `).join('');
        
        // Add click handlers to support request cards
        this.addSupportRequestCardHandlers();
    }

    showAdminSupportModal(supportId, event) {
        // Prevent card click when clicking button
        if (event) {
            event.stopPropagation();
        }
        
        document.getElementById('adminSupportId').value = supportId;
        document.getElementById('adminSupportForm').reset();
        this.loadSupportRequestDetails(supportId);
        document.getElementById('adminSupportModal').style.display = 'block';
    }

    addSupportRequestCardHandlers() {
        const cards = document.querySelectorAll('.clickable-card');
        cards.forEach(card => {
            card.addEventListener('click', (e) => {
                // Don't navigate if clicking on buttons or links
                if (e.target.closest('button') || e.target.closest('a')) {
                    return;
                }
                
                const serviceRequestId = card.dataset.serviceRequestId;
                if (serviceRequestId) {
                    // Open request detail in same tab
                    window.location.href = `request-detail.html?id=${serviceRequestId}`;
                }
            });
            
            // Add cursor pointer for better UX
            card.style.cursor = 'pointer';
        });
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
                        
                        ${support.attachments && support.attachments.length > 0 ? `
                            <div class="support-attachments">
                                <h4><i class="fas fa-paperclip"></i> Tệp đính kèm (${support.attachments.length})</h4>
                                <div class="attachments-list">
                                    ${support.attachments.map(attachment => {
                                        const isImage = attachment.mime_type.startsWith('image/');
                                        const fileExt = attachment.filename.split('.').pop().toLowerCase();
                                        const isPDF = fileExt === 'pdf';
                                        const isWord = ['doc', 'docx'].includes(fileExt);
                                        const isExcel = ['xls', 'xlsx'].includes(fileExt);
                                        const isPowerPoint = ['ppt', 'pptx'].includes(fileExt);
                                        const isText = ['txt', 'md'].includes(fileExt);
                                        const isViewable = isPDF || isWord || isExcel || isPowerPoint || isText;
                                        
                                        return `
                                            <div class="attachment-item">
                                                <div class="attachment-info">
                                                    <i class="fas fa-${isImage ? 'image' : isPDF ? 'file-pdf' : isWord ? 'file-word' : isExcel ? 'file-excel' : isPowerPoint ? 'file-powerpoint' : 'file'}"></i>
                                                    <span class="attachment-name">${attachment.original_name}</span>
                                                    <span class="attachment-size">(${this.formatFileSize(attachment.file_size)})</span>
                                                </div>
                                                <div class="attachment-actions">
                                                    ${isImage ? `
                                                        <img src="api/support_request_attachment.php?file=${attachment.filename}&action=view" 
                                                             alt="${attachment.original_name}" 
                                                             class="attachment-preview"
                                                             onclick="app.showImageModal('api/support_request_attachment.php?file=${attachment.filename}&action=view', '${attachment.original_name}')"
                                                             style="cursor: pointer;">
                                                        <div class="image-overlay">
                                                            <i class="fas fa-search-plus"></i>
                                                        </div>
                                                    ` : ''}
                                                    ${isViewable ? `
                                                        <button class="btn btn-sm btn-primary" 
                                                                onclick="app.viewDocument('api/support_request_attachment.php?file=${attachment.filename}&action=view', '${attachment.original_name}', '${fileExt}')">
                                                            <i class="fas fa-eye"></i> Xem
                                                        </button>
                                                    ` : ''}
                                                    <a href="api/support_request_attachment.php?file=${attachment.filename}&action=download" 
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
        
        // Show loading state
        const submitBtn = e.target.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Đang xử lý...';
        submitBtn.disabled = true;
        this.showLoadingState('Đang xử lý yêu cầu hỗ trợ...');
        
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
                const decision = formData.get('decision');
                const message = decision === 'approved' ? 'Yêu cầu hỗ trợ đã được phê duyệt thành công!' : 'Yêu cầu hỗ trợ đã bị từ chối!';
                this.showNotification(message, 'success');
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
        } finally {
            // Hide loading state
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
            this.hideLoadingState();
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
        } finally {
            // Hide loading state regardless of success or error
            this.hideLoadingState();
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
    
    // Check for URL parameter first
    const urlParams = new URLSearchParams(window.location.search);
    const pageParam = urlParams.get('page');
    
    if (pageParam && window.app) {
        console.log('URL parameter found, showing loading and navigating to:', pageParam);
        
        // Show loading immediately to hide dashboard flash
        window.app.showLoadingState('Đang tải trang...');
        
        // Hide default page content
        document.querySelectorAll('.page').forEach(p => {
            p.classList.remove('active');
        });
        
        // Navigate to target page after short delay
        setTimeout(() => {
            window.app.showPage(pageParam);
        }, 100);
    } else {
        // No URL parameter, load default page (dashboard)
        console.log('No URL parameter, loading default page');
        setTimeout(() => {
            window.app.showPage('dashboard');
        }, 100);
    }
    
    // Listen for navigation requests from detail window
    window.addEventListener('storage', (e) => {
        console.log('=== Storage Event Triggered ===');
        console.log('Key:', e.key);
        console.log('New Value:', e.newValue);
        console.log('Old Value:', e.oldValue);
        
        if (e.key === 'navigationRequest') {
            try {
                const request = JSON.parse(e.newValue);
                console.log('Navigation request received:', request);
                
                // Check if this is a valid recent request (within last 5 seconds)
                if (request.timestamp && (Date.now() - request.timestamp) < 5000) {
                    console.log('Request is recent, processing...');
                    if (request.action === 'showPage' && request.page) {
                        console.log('Executing navigation request:', request.page);
                        window.app.showPage(request.page);
                        
                        // Clear the request after processing
                        localStorage.removeItem('navigationRequest');
                        console.log('Navigation request cleared from localStorage');
                    }
                } else {
                    console.log('Request is too old, ignoring');
                }
            } catch (error) {
                console.error('Error parsing navigation request:', error);
            }
        }
    });
    
    // Listen for postMessage from detail window
    window.addEventListener('message', (e) => {
        console.log('=== Message Event Triggered ===');
        console.log('Origin:', e.origin);
        console.log('Data:', e.data);
        
        // Check if this is a navigation request from detail window
        if (e.data && e.data.action === 'showPage' && e.data.page) {
            console.log('PostMessage navigation request received:', e.data);
            
            // Check if this is a valid recent request (within last 5 seconds)
            if (e.data.timestamp && (Date.now() - e.data.timestamp) < 5000) {
                console.log('PostMessage request is recent, processing...');
                console.log('Executing postMessage navigation request:', e.data.page);
                window.app.showPage(e.data.page);
            } else {
                console.log('PostMessage request is too old, ignoring');
            }
        }
    });
});

// Global functions for onclick handlers
window.closeUserRoleModal = () => app.closeUserRoleModal();

// KPI Export Methods
ITServiceApp.prototype.loadKPIExport = function() {
    console.log('Loading KPI Export page...');
    
    // Set default date range
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    
    if (startDateInput) {
        startDateInput.value = firstDay.toISOString().split('T')[0];
    }
    
    if (endDateInput) {
        endDateInput.value = lastDay.toISOString().split('T')[0];
    }
    
    // Bind export button event
    const exportBtn = document.getElementById('exportKPIBtn');
    if (exportBtn) {
        exportBtn.removeEventListener('click', this.exportKPI.bind(this));
        exportBtn.addEventListener('click', this.exportKPI.bind(this));
    }
    
    // Bind date change events to refresh data
    const startInput = document.getElementById('startDate');
    const endInput = document.getElementById('endDate');
    
    if (startInput) {
        startInput.removeEventListener('change', this.loadKPIData.bind(this));
        startInput.addEventListener('change', this.loadKPIData.bind(this));
    }
    
    if (endInput) {
        endInput.removeEventListener('change', this.loadKPIData.bind(this));
        endInput.addEventListener('change', this.loadKPIData.bind(this));
    }
    
    // Load initial KPI data
    this.loadKPIData();
};

ITServiceApp.prototype.loadKPIData = async function() {
    try {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        
        if (!startDate || !endDate) {
            this.showNotification('Vui lòng chọn khoảng thời gian', 'error');
            return;
        }
        
        this.showLoadingState('Đang tải dữ liệu KPI...');
        
        const response = await this.apiCall(`api/kpi_export.php?action=get_kpi_data&start_date=${startDate}&end_date=${endDate}`);
        
        if (response.success) {
            this.displayKPIData(response.data);
            this.displayKPISummary(response.data);
        } else {
            this.showNotification('Không thể tải dữ liệu KPI: ' + response.message, 'error');
        }
    } catch (error) {
        console.error('Error loading KPI data:', error);
        this.showNotification('Lỗi khi tải dữ liệu KPI', 'error');
    } finally {
        this.hideLoadingState();
    }
};

ITServiceApp.prototype.displayKPIData = function(kpiData) {
    const tableBody = document.getElementById('kpiTableBody');
    if (!tableBody) return;
    
    tableBody.innerHTML = '';
    
    if (kpiData.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="12" class="text-center">Không có dữ liệu KPI trong khoảng thời gian đã chọn</td></tr>';
        return;
    }
    
    kpiData.forEach(staff => {
        const row = document.createElement('tr');
        
        // Determine performance class for total KPI score
        const kpiScoreClass = staff.total_kpi_score >= 90 ? 'performance-excellent' : 
                             staff.total_kpi_score >= 80 ? 'performance-good' : 
                             staff.total_kpi_score >= 70 ? 'performance-medium' : 'performance-poor';
        
        // Determine performance class for individual scores
        const artScoreClass = staff.art_score >= 80 ? 'performance-good' : 
                             staff.art_score >= 60 ? 'performance-medium' : 'performance-poor';
        const actScoreClass = staff.act_score >= 80 ? 'performance-good' : 
                             staff.act_score >= 60 ? 'performance-medium' : 'performance-poor';
        const arScoreClass = staff.ar_score >= 80 ? 'performance-good' : 
                            staff.ar_score >= 60 ? 'performance-medium' : 'performance-poor';
        const awrScoreClass = staff.awr_score >= 80 ? 'performance-good' : 
                            staff.awr_score >= 60 ? 'performance-medium' : 'performance-poor';
        
        row.innerHTML = `
            <td>${staff.id}</td>
            <td>${staff.full_name}</td>
            <td>${staff.department || 'N/A'}</td>
            <td class="numeric">${staff.total_requests}</td>
            <td class="numeric">${staff.completed_requests}</td>
            <td class="numeric">${typeof staff.avg_response_time_minutes === 'number' ? staff.avg_response_time_minutes.toFixed(1) : 'N/A'}</td>
            <td class="numeric">${typeof staff.avg_completion_time_hours === 'number' ? staff.avg_completion_time_hours.toFixed(1) : 'N/A'}</td>
            <td class="numeric">${typeof staff.avg_rating === 'number' ? staff.avg_rating.toFixed(1) : 'N/A'}</td>
            <td class="numeric">${staff.total_feedback}</td>
            <td class="numeric">${typeof staff.recommendation_rate === 'number' ? staff.recommendation_rate.toFixed(1) : '0.0'}%</td>
            <td class="numeric ${kpiScoreClass}">${typeof staff.total_kpi_score === 'number' ? staff.total_kpi_score.toFixed(1) : 'N/A'}</td>
            <td class="numeric ${artScoreClass}">${typeof staff.art_score === 'number' ? staff.art_score.toFixed(1) : 'N/A'}</td>
            <td class="numeric ${actScoreClass}">${typeof staff.act_score === 'number' ? staff.act_score.toFixed(1) : 'N/A'}</td>
            <td class="numeric ${arScoreClass}">${typeof staff.ar_score === 'number' ? staff.ar_score.toFixed(1) : 'N/A'}</td>
            <td class="numeric ${awrScoreClass}">${typeof staff.awr_score === 'number' ? staff.awr_score.toFixed(1) : 'N/A'}</td>
        `;
        
        tableBody.appendChild(row);
    });
};

ITServiceApp.prototype.displayKPISummary = function(kpiData) {
    const summaryContent = document.getElementById('kpiSummaryContent');
    if (!summaryContent) return;
    
    // Calculate summary statistics
    const totalStaff = kpiData.length;
    const totalRequests = kpiData.reduce((sum, staff) => sum + staff.total_requests, 0);
    const totalCompleted = kpiData.reduce((sum, staff) => sum + staff.completed_requests, 0);
    const totalFeedback = kpiData.reduce((sum, staff) => sum + staff.total_feedback, 0);
    const avgRating = kpiData.reduce((sum, staff) => sum + staff.avg_rating, 0) / totalStaff || 0;
    const avgResponseTime = kpiData.reduce((sum, staff) => sum + staff.avg_response_time_minutes, 0) / totalStaff || 0;
    const avgCompletionTime = kpiData.reduce((sum, staff) => sum + staff.avg_completion_time_hours, 0) / totalStaff || 0;
    const avgKPIScore = kpiData.reduce((sum, staff) => sum + staff.total_kpi_score, 0) / totalStaff || 0;
    
    summaryContent.innerHTML = `
        <div class="kpi-summary-item">
            <div class="value">${totalStaff}</div>
            <div class="label">Tổng số Staff</div>
        </div>
        <div class="kpi-summary-item">
            <div class="value">${totalRequests}</div>
            <div class="label">Tổng yêu cầu</div>
        </div>
        <div class="kpi-summary-item">
            <div class="value">${totalCompleted}</div>
            <div class="label">Đã hoàn thành</div>
        </div>
        <div class="kpi-summary-item">
            <div class="value">${typeof avgResponseTime === 'number' ? avgResponseTime.toFixed(1) : 'N/A'}</div>
            <div class="label">Phản hồi TB (phút)</div>
        </div>
        <div class="kpi-summary-item">
            <div class="value">${typeof avgCompletionTime === 'number' ? avgCompletionTime.toFixed(1) : 'N/A'}</div>
            <div class="label">Hoàn thành TB (giờ)</div>
        </div>
        <div class="kpi-summary-item">
            <div class="value">${typeof avgRating === 'number' ? avgRating.toFixed(1) : '0.0'}</div>
            <div class="label">Đánh giá TB</div>
        </div>
        <div class="kpi-summary-item">
            <div class="value">${totalFeedback}</div>
            <div class="label">Tổng đánh giá</div>
        </div>
        <div class="kpi-summary-item">
            <div class="value">${typeof avgKPIScore === 'number' ? avgKPIScore.toFixed(1) : 'N/A'}</div>
            <div class="label">Điểm KPI TB</div>
        </div>
    `;
};

ITServiceApp.prototype.exportKPI = async function() {
    try {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        
        if (!startDate || !endDate) {
            this.showNotification('Vui lòng chọn khoảng thời gian', 'error');
            return;
        }
        
        this.showLoadingState('Đang xuất file Excel...');
        
        // Create download link
        const downloadUrl = `api/kpi_export.php?action=export_kpi&start_date=${startDate}&end_date=${endDate}`;
        
        // Create temporary link and trigger download
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        this.showNotification('File Excel đã được xuất thành công', 'success');
        
    } catch (error) {
        console.error('Error exporting KPI:', error);
        this.showNotification('Lỗi khi xuất file Excel', 'error');
    } finally {
        this.hideLoadingState();
    }
};

// ========================================
// AUTO-RELOAD SYSTEM (3-second refresh)
// ========================================

ITServiceApp.prototype.initAutoReload = function() {
    console.log('Initializing auto-reload system...');
    // Auto-reload will be started after successful login
};

ITServiceApp.prototype.startAutoReload = function() {
    if (!this.autoReloadEnabled) {
        console.log('Auto-reload is disabled');
        return;
    }

    // Clear existing interval
    this.stopAutoReload();

    console.log('Starting 3-second auto-reload for all roles...');
    
    // Set interval for 3 seconds
    this.autoReloadInterval = setInterval(() => {
        this.performAutoReload();
    }, 3000);

    // Add visual indicator
    this.addAutoReloadIndicator();
};

ITServiceApp.prototype.stopAutoReload = function() {
    if (this.autoReloadInterval) {
        clearInterval(this.autoReloadInterval);
        this.autoReloadInterval = null;
        console.log('Auto-reload stopped');
    }
};

ITServiceApp.prototype.performAutoReload = async function() {
    if (!this.currentUser || !this.autoReloadEnabled) {
        return;
    }

    try {
        console.log('Auto-reloading data...');
        
        // Reload based on current page
        switch (this.currentPage) {
            case 'dashboard':
                await this.loadDashboardData();
                break;
            case 'requests':
                await this.loadRequests();
                break;
            case 'notifications':
                await this.loadNotifications();
                break;
            default:
                // For other pages, just refresh notifications
                await this.refreshNotifications();
        }

        // Always refresh notification count
        await this.updateNotificationCount();

    } catch (error) {
        console.error('Auto-reload error:', error);
    }
};

ITServiceApp.prototype.refreshNotifications = async function() {
    try {
        const response = await this.apiCall('api/notifications.php?action=list&limit=5');
        if (response.success && response.data) {
            // Update notification count
            await this.updateNotificationCount();
        }
    } catch (error) {
        console.error('Error refreshing notifications:', error);
    }
};

ITServiceApp.prototype.updateNotificationCount = async function() {
    try {
        const response = await this.apiCall('api/notifications.php?action=count');
        if (response.success) {
            const count = response.data.unread_count || 0;
            this.updateNotificationCountDisplay(count);
        }
    } catch (error) {
        console.error('Error updating notification count:', error);
    }
};


ITServiceApp.prototype.updateNotificationCountDisplay = function(count) {
    // Update notification count in header
    const countElement = document.getElementById('notificationCount');
    if (countElement) {
        if (count > 0) {
            countElement.textContent = count > 99 ? '99+' : count;
            countElement.classList.remove('empty');
        } else {
            countElement.textContent = '0';
            countElement.classList.add('empty');
        }
    }
};

ITServiceApp.prototype.addAutoReloadIndicator = function() {
    // Remove existing indicator
    const existing = document.querySelector('.auto-reload-indicator');
    if (existing) {
        existing.remove();
    }

    // Create indicator
    const indicator = document.createElement('div');
    indicator.className = 'auto-reload-indicator';
    indicator.innerHTML = `
        <div class="reload-pulse"></div>
        <span>Auto-refresh: 3s</span>
    `;
    
    // Add to header
    const header = document.querySelector('.app-header');
    if (header) {
        header.appendChild(indicator);
    }
};

ITServiceApp.prototype.toggleAutoReload = function() {
    this.autoReloadEnabled = !this.autoReloadEnabled;
    
    if (this.autoReloadEnabled) {
        this.startAutoReload();
        this.showNotification('Auto-reload enabled (3s)', 'success');
    } else {
        this.stopAutoReload();
        this.showNotification('Auto-reload disabled', 'info');
        
        // Remove indicator
        const indicator = document.querySelector('.auto-reload-indicator');
        if (indicator) {
            indicator.remove();
        }
    }
};

// Load notifications page
ITServiceApp.prototype.loadNotifications = async function() {
    try {
        const response = await this.apiCall('api/notifications.php?action=list&limit=50');
        
        if (response.success && response.data) {
            this.displayNotifications(response.data);
            // Update notification count when notifications page loads
            await this.updateNotificationCount();
        } else {
            this.showNotification('Failed to load notifications', 'error');
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
        this.showNotification('Error loading notifications', 'error');
    }
};

// Toggle notification dropdown
ITServiceApp.prototype.toggleNotificationDropdown = async function() {
    const dropdown = document.getElementById('notificationDropdown');
    if (!dropdown) return;
    
    if (dropdown.style.display === 'block') {
        dropdown.style.display = 'none';
    } else {
        // Load notifications before showing dropdown
        await this.loadNotificationsForDropdown();
        dropdown.style.display = 'block';
        
        // Close dropdown when clicking outside
        document.addEventListener('click', this.handleNotificationDropdownClickOutside);
    }
};

// Load notifications for dropdown
ITServiceApp.prototype.loadNotificationsForDropdown = async function() {
    try {
        const response = await this.apiCall('api/notifications.php?action=list&limit=10');
        
        if (response.success && response.data) {
            this.displayNotificationsInDropdown(response.data);
            // Update notification count
            await this.updateNotificationCount();
        } else {
            this.displayNotificationsInDropdown([]);
        }
    } catch (error) {
        console.error('Error loading notifications for dropdown:', error);
        this.displayNotificationsInDropdown([]);
    }
};

// Display notifications in dropdown
ITServiceApp.prototype.displayNotificationsInDropdown = function(notifications) {
    const container = document.getElementById('notificationList');
    if (!container) return;
    
    if (notifications.length === 0) {
        container.innerHTML = `
            <div class="notification-empty">
                <i class="fas fa-bell-slash"></i>
                <p>Không có thông báo mới</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = notifications.map(notif => {
        const readClass = notif.is_read ? 'read' : 'unread';
        const typeClass = `notification-${notif.type}`;
        const typeIcon = this.getNotificationIcon(notif.type);
        
        return `
            <div class="notification-item ${readClass} ${typeClass} clickable-notification" data-id="${notif.id}" onclick="app.handleNotificationClick(${notif.id}, '${notif.message.replace(/'/g, "\\'")}')">
                <div class="notification-icon">${typeIcon}</div>
                <div class="notification-content">
                    <h4 class="notification-title">${notif.title}</h4>
                    <p class="notification-message">${notif.message}</p>
                    <div class="notification-meta">
                        <span class="notification-time">${notif.time_ago}</span>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    // Add click event listeners after rendering
    setTimeout(() => {
        const notificationItems = container.querySelectorAll('.clickable-notification');
        notificationItems.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const notificationId = item.dataset.id;
                const message = item.dataset.message;
                this.handleNotificationClick(notificationId, message);
            });
        });
    }, 100);
};

ITServiceApp.prototype.handleNotificationDropdownClickOutside = function(event) {
    const dropdown = document.getElementById('notificationDropdown');
    const notificationBtn = document.getElementById('notificationBtn');
    
    if (dropdown && !dropdown.contains(event.target) && !notificationBtn.contains(event.target)) {
        dropdown.style.display = 'none';
        document.removeEventListener('click', app.handleNotificationDropdownClickOutside);
    }
};

// Display notifications (for full page)
ITServiceApp.prototype.displayNotifications = function(notifications) {
    const container = document.getElementById('notificationsList');
    if (!container) return;
    
    if (notifications.length === 0) {
        container.innerHTML = '<p class="no-notifications">No notifications found</p>';
        return;
    }
    
    container.innerHTML = notifications.map(notif => {
        const typeClass = 'notification-' + notif.type;
        const readClass = notif.is_read ? 'read' : 'unread';
        const typeIcon = this.getNotificationIcon(notif.type);
        
        return `
            <div class="notification-item ${readClass} ${typeClass} clickable-notification" data-id="${notif.id}" onclick="app.handleNotificationClick(${notif.id}, '${notif.message.replace(/'/g, "\\'")}')">
                <div class="notification-icon">${typeIcon}</div>
                <div class="notification-content">
                    <h4 class="notification-title">${notif.title}</h4>
                    <p class="notification-message">${notif.message}</p>
                    <div class="notification-meta">
                        <span class="notification-time">${notif.time_ago}</span>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    // Add click event listeners after rendering
    setTimeout(() => {
        const notificationItems = container.querySelectorAll('.clickable-notification');
        notificationItems.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const notificationId = item.dataset.id;
                const message = item.dataset.message;
                this.handleNotificationClick(notificationId, message);
            });
        });
    }, 100);
};

// Handle notification click - mark as read and navigate to request
ITServiceApp.prototype.handleNotificationClick = async function(notificationId, message) {
    try {
        // Mark notification as read first
        const response = await this.apiCall('api/notifications.php?action=mark_read', {
            method: 'PUT',
            body: JSON.stringify({
                notification_id: notificationId
            })
        });
        
        if (response.success) {
            // Extract request ID from message using regex
            const requestIdMatch = message.match(/#(\d+)/);
            if (requestIdMatch) {
                const requestId = requestIdMatch[1];
                // Navigate to request detail page
                window.location.href = `request-detail.html?id=${requestId}`;
            } else {
                // If no request ID found, just refresh notifications
                await this.loadNotificationsForDropdown();
                await this.updateNotificationCount();
            }
        } else {
            console.error('Failed to mark notification as read');
        }
    } catch (error) {
        console.error('Error handling notification click:', error);
    }
};

// Get notification icon based on type
ITServiceApp.prototype.getNotificationIcon = function(type) {
    const icons = {
        'info': '<i class="fas fa-info-circle"></i>',
        'success': '<i class="fas fa-check-circle"></i>',
        'warning': '<i class="fas fa-exclamation-triangle"></i>',
        'error': '<i class="fas fa-times-circle"></i>'
    };
    return icons[type] || icons['info'];
};

// Mark notification as read
ITServiceApp.prototype.markNotificationAsRead = async function(notificationId) {
    try {
        const response = await this.apiCall('api/notifications.php?action=mark_read', {
            method: 'PUT',
            body: JSON.stringify({
                notification_id: notificationId
            })
        });
        
        if (response.success) {
            // Reload dropdown to update UI
            await this.loadNotificationsForDropdown();
            await this.updateNotificationCount();
            this.showNotification('Đã đánh dấu thông báo là đã đọc', 'success');
        } else {
            this.showNotification('Không thể đánh dấu thông báo là đã đọc', 'error');
        }
    } catch (error) {
        console.error('Error marking notification as read:', error);
        this.showNotification('Lỗi khi đánh dấu thông báo là đã đọc', 'error');
    }
};

// Mark all notifications as read
ITServiceApp.prototype.markAllNotificationsAsRead = async function() {
    try {
        const response = await this.apiCall('api/notifications.php?action=mark_all_read', {
            method: 'PUT'
        });
        
        if (response.success) {
            // Reload dropdown to update UI
            await this.loadNotificationsForDropdown();
            await this.updateNotificationCount();
            this.showNotification('Đã đánh dấu tất cả thông báo là đã đọc', 'success');
        } else {
            this.showNotification('Không thể đánh dấu tất cả thông báo là đã đọc', 'error');
        }
    } catch (error) {
        console.error('Error marking all notifications as read:', error);
        this.showNotification('Error marking all notifications as read', 'error');
    }
};

// Load dashboard data for auto-reload
ITServiceApp.prototype.loadDashboardData = async function() {
    try {
        await this.loadDashboard();
    } catch (error) {
        console.error('Error loading dashboard data:', error);
    }
};

// Override logout to stop auto-reload
ITServiceApp.prototype.logout = function() {
    this.stopAutoReload();
    
    // Original logout logic
    const response = fetch('api/auth.php?action=logout', {
        method: 'POST',
        credentials: 'include'
    });
    
    this.currentUser = null;
    this.showLoginScreen();
    this.showNotification('Logged out successfully', 'success');
};
