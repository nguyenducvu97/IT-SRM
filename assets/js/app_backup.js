// Clean version of app.js with reduced console logs
class ITServiceApp {
    constructor() {
        this.currentUser = null;
        this.currentPage = 'dashboard';
        this.selectedFiles = []; // Store selected files
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.checkAuth();
        
        // Initialize translation support
        this.initTranslationSupport();
    }

    bindEvents() {
        // Login/Register events
        document.getElementById('loginForm').addEventListener('submit', (e) => this.handleLogin(e));
        document.getElementById('registerForm').addEventListener('submit', (e) => this.handleRegister(e));
        document.getElementById('showRegister').addEventListener('click', (e) => this.showRegister(e));
        document.getElementById('showLogin').addEventListener('click', (e) => this.showLogin(e));
        
        // Logout button
        document.getElementById('logoutBtn').addEventListener('click', () => this.logout());

        // Navigation events
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach((link, index) => {
            link.addEventListener('click', (e) => this.handleNavigation(e));
        });

        // Request form events
        const newRequestForm = document.getElementById('newRequestForm');
        if (newRequestForm) newRequestForm.addEventListener('submit', (e) => this.handleNewRequest(e));
        
        const cancelRequest = document.getElementById('cancelRequest');
        if (cancelRequest) cancelRequest.addEventListener('click', () => this.showPage('dashboard'));

        // File upload events
        const fileInput = document.getElementById('requestAttachments');
        const uploadArea = document.getElementById('fileUploadArea');
        
        if (fileInput) fileInput.addEventListener('change', (e) => this.handleFileSelect(e));
        if (uploadArea) {
            uploadArea.addEventListener('dragover', (e) => this.handleDragOver(e));
            uploadArea.addEventListener('dragleave', (e) => this.handleDragLeave(e));
            uploadArea.addEventListener('drop', (e) => this.handleFileDrop(e));
        }
    }

    // Keep essential methods but reduce console logs
    handleNavigation(e) {
        if (this._navigating) return;
        this._navigating = true;
        
        const navLink = e.target.closest('.nav-link');
        if (!navLink) {
            this._navigating = false;
            return;
        }
        
        const page = navLink.dataset.page;
        const href = navLink.href;
        
        if (page) {
            e.preventDefault();
            this.showPage(page);
        } else {
            if (href && href !== '#') {
                window.location.href = href;
            }
        }
        
        this._navigating = false;
    }

    showPage(page) {
        // Update navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        
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
        
        if (pageElement) {
            pageElement.classList.add('active');
        }

        this.currentPage = page;

        // Load page-specific data
        switch(page) {
            case 'dashboard':
                this.loadDashboard();
                break;
            case 'requests':
                this.loadRequests();
                break;
            case 'categories':
                this.loadCategories();
                break;
            case 'users':
                if (this.currentUser.role === 'admin') {
                    this.loadUsers();
                } else {
                    this.showNotification('Chỉ admin mới có quyền truy cập quản lý người dùng', 'error');
                    setTimeout(() => this.showPage('dashboard'), 1000);
                }
                break;
            case 'departments':
                if (this.currentUser.role === 'admin') {
                    this.departmentsManager.loadDepartments();
                } else {
                    this.showNotification('Chỉ admin mới có quyền quản lý phòng ban', 'error');
                    setTimeout(() => this.showPage('dashboard'), 1000);
                }
                break;
            case 'support-requests':
                if (['admin', 'staff'].includes(this.currentUser.role)) {
                    this.loadSupportRequests();
                } else {
                    this.showNotification('Chỉ admin và staff mới có quyền xem yêu cầu hỗ trợ', 'error');
                    setTimeout(() => this.showPage('dashboard'), 1000);
                }
                break;
            case 'reject-requests':
                if (['admin', 'staff'].includes(this.currentUser.role)) {
                    this.loadRejectRequests();
                } else {
                    this.showNotification('Chỉ admin và staff mới có quyền xem yêu cầu từ chối', 'error');
                    setTimeout(() => this.showPage('dashboard'), 1000);
                }
                break;
            case 'profile':
                this.loadProfile();
                break;
        }
    }

    // Keep other essential methods but without excessive logging
    async checkAuth() {
        try {
            const response = await this.apiCall('api/auth.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'check' })
            });
            
            if (response.success) {
                this.currentUser = response.data;
                this.showDashboard();
            } else {
                this.showLoginScreen();
            }
        } catch (error) {
            this.showLoginScreen();
        }
    }

    showDashboard() {
        // Hide loading screen first
        const loadingScreen = document.getElementById('loadingScreen');
        if (loadingScreen) loadingScreen.classList.remove('active');
        
        const loginScreen = document.getElementById('loginScreen');
        const registerScreen = document.getElementById('registerScreen');
        const dashboardScreen = document.getElementById('dashboardScreen');
        
        if (loginScreen) loginScreen.classList.remove('active');
        if (registerScreen) registerScreen.classList.remove('active');
        if (dashboardScreen) dashboardScreen.classList.add('active');
        
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
            document.getElementById('adminMenu').style.display = 'none';
            document.getElementById('adminDepartmentMenu').style.display = 'none';
            document.getElementById('adminSupportMenu').style.display = 'block';
            document.getElementById('adminRejectMenu').style.display = 'block';
            document.getElementById('newRequestMenu').style.display = 'none';
        } else {
            document.getElementById('newRequestMenu').style.display = 'block';
        }
        
        // Load dashboard data
        this.loadDashboard();
        this.loadCategories();
    }

    showLoginScreen() {
        const loadingScreen = document.getElementById('loadingScreen');
        const loginScreen = document.getElementById('loginScreen');
        const dashboardScreen = document.getElementById('dashboardScreen');
        
        if (loadingScreen) loadingScreen.classList.remove('active');
        if (dashboardScreen) dashboardScreen.classList.remove('active');
        if (loginScreen) loginScreen.classList.add('active');
    }

    showRegister(e) {
        if (e) e.preventDefault();
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
        
        try {
            const response = await this.apiCall('api/auth.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'logout' })
            });
            
            if (response.success) {
                window.location.href = 'index.html';
            }
        } catch (error) {
            window.location.href = 'index.html';
        }
    }

    // File Upload Functions (reduced logging)
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
            if (file.size > maxSize) {
                this.showNotification(`Tệp ${file.name} quá lớn (tối đa 5MB)`, 'error');
                return;
            }

            if (!allowedTypes.includes(file.type)) {
                this.showNotification(`Tệp ${file.name} không được hỗ trợ`, 'error');
                return;
            }

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
                    <span class="file-name">${file.name}</span>
                    <span class="file-size">(${this.formatFileSize(file.size)})</span>
                </div>
                <button type="button" class="file-remove" onclick="app.removeFile(${index})">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `).join('');
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // API Helper
    async apiCall(url, options = {}) {
        const defaultOptions = {
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
            }
        };

        const response = await fetch(url, { ...defaultOptions, ...options });
        return await response.json();
    }

    // Notification Helper
    showNotification(message, type = 'info') {
        if (window.NotificationManager) {
            NotificationManager.show(message, type);
        } else {
            alert(message);
        }
    }

    // Translation Support
    initTranslationSupport() {
        if (window.TranslationSystem) {
            TranslationSystem.init();
        }
    }

    // Keep other essential methods but implement them minimally
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
            this.showNotification('Lỗi đăng nhập', 'error');
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
                this.showNotification('Đăng ký thành công!', 'success');
                this.showLogin();
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            this.showNotification('Lỗi đăng ký', 'error');
        }
    }

    async handleNewRequest(e) {
        e.preventDefault();
        
        const submitBtn = document.querySelector('#newRequestForm button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
        
        try {
            const formData = new FormData(e.target);
            
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
            
            const response = await fetch('api/service_requests.php', {
                method: 'POST',
                body: submitData,
                credentials: 'include'
            });
            
            const result = await response.json();

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
            this.showNotification('Lỗi khi tạo yêu cầu', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    // Placeholder methods for other functionality
    loadDashboard() { /* Implement as needed */ }
    loadRequests() { /* Implement as needed */ }
    loadCategories() { /* Implement as needed */ }
    loadUsers() { /* Implement as needed */ }
    loadSupportRequests() { /* Implement as needed */ }
    loadRejectRequests() { /* Implement as needed */ }
    loadProfile() { /* Implement as needed */ }
}

// Initialize app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.app = new ITServiceApp();
    
    // Handle URL parameter for page navigation
    const urlParams = new URLSearchParams(window.location.search);
    const pageParam = urlParams.get('page');
    
    if (pageParam && window.app) {
        setTimeout(() => {
            window.app.showPage(pageParam);
        }, 500);
    }
});

// Global functions for onclick handlers
window.closeUserRoleModal = () => app.closeUserRoleModal();
