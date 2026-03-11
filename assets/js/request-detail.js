// IT Service Request Detail JavaScript

// Function removed - only keeping download functionality

// Function to show notifications
function showNotification(message, type = 'info') {
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
    
    // Set background color based on type
    switch (type) {
        case 'success':
            notification.style.backgroundColor = '#28a745';
            break;
        case 'error':
            notification.style.backgroundColor = '#dc3545';
            break;
        case 'warning':
            notification.style.backgroundColor = '#ffc107';
            notification.style.color = '#212529';
            break;
        default:
            notification.style.backgroundColor = '#17a2b8';
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

class RequestDetailApp {
    constructor() {
        this.currentUser = null;
        this.requestId = null;
        this.request = null;
        this.rejectRequestStatus = null;
        this.supportRequestStatus = null;
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.checkAuth();
        this.getRequestIdFromURL();
    }

    bindEvents() {
        // Login/Register events
        document.getElementById('loginForm').addEventListener('submit', (e) => this.handleLogin(e));
        document.getElementById('registerForm').addEventListener('submit', (e) => this.handleRegister(e));
        document.getElementById('showRegister').addEventListener('click', (e) => this.showRegister(e));
        document.getElementById('showLogin').addEventListener('click', (e) => this.showLogin(e));
        document.getElementById('logoutBtn').addEventListener('click', () => this.logout());

        // Comment events
        document.getElementById('addCommentBtn').addEventListener('click', () => this.addComment());

        // Resolve form events
        document.getElementById('resolveForm').addEventListener('submit', (e) => this.handleResolveSubmit(e));

        // Need Support form events
        document.getElementById('needSupportForm').addEventListener('submit', (e) => this.handleNeedSupportSubmit(e));

        // Admin Support form events
        document.getElementById('adminSupportForm').addEventListener('submit', (e) => this.handleAdminSupportSubmit(e));

        // Close Request form events
        document.getElementById('closeRequestForm').addEventListener('submit', (e) => this.handleCloseRequestSubmit(e));

        // Reject Request form events
        document.getElementById('rejectForm').addEventListener('submit', (e) => this.handleRejectSubmit(e));

        // Modal close events
        document.querySelectorAll('.close').forEach(closeBtn => {
            closeBtn.addEventListener('click', (e) => this.closeModal(e.target.closest('.modal')));
        });

        // Window click event for modals
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.closeModal(e.target);
            }
        });
    }

    getRequestIdFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        this.requestId = urlParams.get('id');
        
        if (this.requestId) {
            document.getElementById('requestId').textContent = this.requestId;
            // Don't load request details here - wait for authentication
            // loadRequestDetail() will be called after checkAuth() succeeds
        } else {
            this.showNotification('Không tìm thấy ID yêu cầu', 'error');
            setTimeout(() => {
                window.location.href = 'index.html';
            }, 2000);
        }
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
                // Load request details after successful login
                if (this.requestId) {
                    this.loadRequestDetail();
                }
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
        console.log('=== SHOW LOGIN SCREEN CALLED ===');
        console.log('Current user:', this.currentUser);
        
        // Hide loading screen first
        document.getElementById('loadingScreen').classList.remove('active');
        
        document.getElementById('dashboardScreen').classList.remove('active');
        document.getElementById('loginScreen').classList.add('active');
        document.getElementById('loginForm').reset();
    }

    showDashboard() {
        // Hide loading screen first
        document.getElementById('loadingScreen').classList.remove('active');
        
        document.getElementById('loginScreen').classList.remove('active');
        document.getElementById('registerScreen').classList.remove('active');
        document.getElementById('dashboardScreen').classList.add('active');
        
        // Update user info
        document.getElementById('userInfo').textContent = this.currentUser.full_name;
        
        // Show/hide menu items based on role
        if (this.currentUser.role === 'admin') {
            document.getElementById('adminMenu').style.display = 'block';
            document.getElementById('newRequestMenu').style.display = 'none';
        } else {
            document.getElementById('newRequestMenu').style.display = 'block';
        }
    }

    async checkAuth() {
        console.log('=== DEBUG CHECK AUTH ===');
        
        // Add delay to ensure session is ready
        await new Promise(resolve => setTimeout(resolve, 500));
        
        try {
            const response = await this.apiCall('api/auth.php?action=check_session');
            console.log('Auth response:', response);
            
            if (response.success) {
                this.currentUser = response.data;
                console.log('User authenticated:', this.currentUser);
                this.showDashboard();
                // Load request details after authentication
                if (this.requestId) {
                    this.loadRequestDetail();
                }
            } else {
                console.log('Auth failed - showing login screen');
                this.showLoginScreen();
                // Don't load request details if auth failed
                return;
            }
        } catch (error) {
            console.error('Auth check error:', error);
            this.showLoginScreen();
            // Don't load request details if auth failed
            return;
        }
    }

    async loadRequestDetail() {
        console.log('=== DEBUG LOAD REQUEST DETAIL ===');
        console.log('Request ID:', this.requestId);
        console.log('Current user:', this.currentUser);
        
        try {
            const response = await this.apiCall(`api/service_requests.php?action=get&id=${this.requestId}`);
            
            if (response.success) {
                this.request = response.data;
                
                // Load reject request status for staff BEFORE displaying
                if (this.currentUser && this.currentUser.role === 'staff') {
                    await this.checkRejectRequestStatus();
                }
                
                this.displayRequestDetail(this.request);
                
                // Show notification for reject request decision
                if (this.request.reject_request && this.request.reject_request.status !== 'pending') {
                    const decision = this.request.reject_request.status === 'approved' ? 'đã được phê duyệt' : 'đã bị từ chối';
                    const message = `📢 Yêu cầu từ chối ${decision} bởi admin!`;
                    this.showNotification(message, this.request.reject_request.status === 'approved' ? 'success' : 'warning');
                }
            } else {
                console.log('Request detail failed:', response.message);
                this.showNotification(response.message, 'error');
                setTimeout(() => {
                    window.location.href = 'index.html';
                }, 2000);
            }
        } catch (error) {
            console.error('Request detail error:', error);
            this.showNotification('Lỗi tải chi tiết yêu cầu', 'error');
        }
    }

    displayRequestDetail(request) {
        const container = document.getElementById('requestDetails');
        
        // Extract all values before template to avoid 'this' context issues
        const priorityText = this.getPriorityText(request.priority);
        const statusText = this.getStatusText(request.status);
        const formatDate = this.formatDate.bind(this);
        const getErrorTypeText = this.getErrorTypeText.bind(this);
        const getSupportTypeText = this.getSupportTypeText.bind(this);
        const getSupportStatusText = this.getSupportStatusText.bind(this);
        const formatFileSize = this.formatFileSize.bind(this);
        const currentUser = this.currentUser;
        
        container.innerHTML = `
            <div class="request-detail" data-request-id="${request.id}">
                <div class="request-header-info">
                    <h3>${request.title}</h3>
                    <div class="request-badges">
                        <span class="badge priority-${request.priority}">${priorityText}</span>
                        <span class="badge status-${request.status}">${statusText}</span>
                    </div>
                </div>
                
                <div class="request-meta-grid">
                    <div class="meta-item">
                        <strong>ID yêu cầu:</strong> #${request.id}
                    </div>
                    <div class="meta-item">
                        <strong>Người tạo:</strong> ${request.requester_name}
                    </div>
                    <div class="meta-item">
                        <strong>Email:</strong> ${request.requester_email}
                    </div>
                    <div class="meta-item">
                        <strong>Điện thoại:</strong> ${request.requester_phone || 'N/A'}
                    </div>
                    ${request.assigned_name ? `
                        <div class="meta-item">
                            <strong>Người nhận:</strong> ${request.assigned_name}
                        </div>
                        <div class="meta-item">
                            <strong>Email người nhận:</strong> ${request.assigned_email || 'N/A'}
                        </div>
                    ` : ''}
                    <div class="meta-item">
                        <strong>Danh mục:</strong> ${request.category_name}
                    </div>
                    <div class="meta-item">
                        <strong>Ưu tiên:</strong> <span class="badge priority-${request.priority}">${priorityText}</span>
                    </div>
                    <div class="meta-item">
                        <strong>Trạng thái:</strong> <span class="badge status-${request.status}">${statusText}</span>
                    </div>
                    <div class="meta-item">
                        <strong>Ngày tạo:</strong> ${formatDate(request.created_at)}
                    </div>
                    ${request.accepted_at ? `
                        <div class="meta-item">
                            <strong>Ngày nhận:</strong> ${formatDate(request.accepted_at)}
                        </div>
                    ` : ''}
                    ${request.resolved_at ? `
                        <div class="meta-item">
                            <strong>Ngày giải quyết:</strong> ${formatDate(request.resolved_at)}
                        </div>
                    ` : ''}
                </div>
                
                <div class="request-description">
                    <h4><i class="fas fa-file-alt"></i> Mô tả yêu cầu</h4>
                    <p>${request.description}</p>
                </div>
                
                ${request.attachments && request.attachments.length > 0 ? `
                    <div class="attachments-section">
                        <h4><i class="fas fa-paperclip"></i> Tệp đính kèm (${request.attachments.length})</h4>
                        <div class="attachments-list">
                            ${request.attachments.map(attachment => {
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
                                            <span class="attachment-size">(${formatFileSize(attachment.file_size)})</span>
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
                                            ${isViewable ? `
                                                <button class="btn btn-sm btn-primary" 
                                                        onclick="app.viewDocument('uploads/requests/${attachment.filename}', '${attachment.original_name}', '${fileExt}')">
                                                    <i class="fas fa-eye"></i> Xem
                                                </button>
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
                
                ${request.resolution ? `
                    <div class="resolution-info">
                        <h4><i class="fas fa-check-circle"></i> Thông tin giải quyết</h4>
                        <div class="resolution-details">
                            <div class="resolution-item">
                                <strong>Người giải quyết:</strong> ${request.resolution.resolver_name}
                            </div>
                            <div class="resolution-item">
                                <strong>Ngày giải quyết:</strong> ${formatDate(request.resolution.resolved_at)}
                            </div>
                            <div class="resolution-item">
                                <strong>Mô tả lỗi:</strong> ${request.resolution.error_description}
                            </div>
                            <div class="resolution-item">
                                <strong>Loại lỗi:</strong> ${getErrorTypeText(request.resolution.error_type)}
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
                
                ${request.reject_request ? `
                    <div class="reject-request-info">
                        <h4><i class="fas fa-times-circle"></i> Yêu cầu từ chối từ Staff</h4>
                        <div class="reject-details">
                            <div class="reject-item">
                                <strong>Người từ chối:</strong> ${request.reject_request.requester_name}
                            </div>
                            <div class="reject-item">
                                <strong>Lý do từ chối:</strong> ${request.reject_request.reject_reason}
                            </div>
                            ${request.reject_request.reject_details ? `
                                <div class="reject-item">
                                    <strong>Chi tiết bổ sung:</strong> ${request.reject_request.reject_details}
                                </div>
                            ` : ''}
                            <div class="reject-item">
                                <strong>Trạng thái:</strong> <span class="badge status-${request.reject_request.status}">${this.getRejectStatusText(request.reject_request.status)}</span>
                            </div>
                            <div class="reject-item">
                                <strong>Ngày tạo:</strong> ${formatDate(request.reject_request.created_at)}
                            </div>
                            ${request.reject_request.status !== 'pending' ? `
                            <div class="reject-item">
                                <strong>Người xử lý:</strong> ${request.reject_request.admin_name || 'Admin'}
                            </div>
                            <div class="reject-item">
                                <strong>Quyết định admin:</strong> ${request.reject_request.admin_reason}
                            </div>
                            <div class="reject-item">
                                <strong>Thời gian xử lý:</strong> ${formatDate(request.reject_request.processed_at)}
                            </div>
                        ` : ''}
                        </div>
                    </div>
                ` : ''}
                
                ${request.support_request ? `
                    <div class="support-request-info">
                        <h4><i class="fas fa-hands-helping"></i> Yêu cầu hỗ trợ từ Staff</h4>
                        <div class="support-details">
                            <div class="support-item">
                                <strong>Loại hỗ trợ:</strong> ${getSupportTypeText(request.support_request.support_type)}
                            </div>
                            <div class="support-item">
                                <strong>Chi tiết:</strong> ${request.support_request.support_details}
                            </div>
                            <div class="support-item">
                                <strong>Lý do:</strong> ${request.support_request.support_reason}
                            </div>
                            <div class="support-item">
                                <strong>Trạng thái:</strong> <span class="badge status-${request.support_request.status}">${getSupportStatusText(request.support_request.status)}</span>
                            </div>
                            <div class="support-item">
                                <strong>Ngày tạo:</strong> ${formatDate(request.support_request.created_at)}
                            </div>
                            ${request.support_request.status !== 'pending' ? `
                                <div class="support-item">
                                    <strong>Người xử lý:</strong> ${request.support_request.admin_name || 'Admin'}
                                </div>
                                <div class="support-item">
                                    <strong>Quyết định admin:</strong> ${request.support_request.admin_reason}
                                </div>
                                <div class="support-item">
                                    <strong>Thời gian xử lý:</strong> ${formatDate(request.support_request.processed_at)}
                                </div>
                            ` : ''}
                        </div>
                    </div>
                ` : ''}
                
                <div class="request-actions">
                    ${currentUser && currentUser.role === 'admin' ? `
                        ${request.status === 'open' && !request.assigned_to ? `
                            <button class="btn btn-success" onclick="app.acceptRequest(${request.id})">
                                <i class="fas fa-check"></i> Nhận yêu cầu
                            </button>
                        ` : ''}
                        ${request.status === 'in_progress' && request.assigned_to == currentUser.id ? `
                            <button class="btn btn-primary" onclick="app.showResolveModal(${request.id})">
                                <i class="fas fa-check-circle"></i> Đã giải quyết
                            </button>
                            <button class="btn btn-warning" onclick="app.showNeedSupportModal(${request.id})">
                                <i class="fas fa-hands-helping"></i> Cần hỗ trợ
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
                    ` : currentUser && currentUser.role === 'staff' ? `
                        ${request.status === 'open' && !request.assigned_to ? `
                            <button class="btn btn-success" onclick="app.acceptRequest(${request.id})">
                                <i class="fas fa-check"></i> Nhận yêu cầu
                            </button>
                        ` : ''}
                        ${request.status === 'in_progress' && request.assigned_to == currentUser.id ? `
                            <button class="btn btn-primary" onclick="app.showResolveModal(${request.id})">
                                <i class="fas fa-check-circle"></i> Đã giải quyết
                            </button>
                            <button class="btn btn-warning" onclick="app.showNeedSupportModal(${request.id})">
                                <i class="fas fa-hands-helping"></i> Cần hỗ trợ
                            </button>
                            <button class="btn btn-danger" onclick="app.showRejectModal(${request.id})">
                                <i class="fas fa-times"></i> Từ chối
                            </button>
                        ` : ''}
                    ` : ''}
                    
                    <!-- Show Close Request button for requesters when request is resolved -->
                    ${currentUser && currentUser.role === 'user' && request.status === 'resolved' && (request.user_id == currentUser.id || request.requester_id == currentUser.id) ? `
                        <button class="btn btn-danger" onclick="app.showCloseRequestModal(${request.id})">
                            <i class="fas fa-times-circle"></i> Đóng lại yêu cầu
                        </button>
                    ` : ''}
                    
                    <!-- Show closed status when request is closed -->
                    ${request.status === 'closed' ? `
                        <div class="closed-status">
                            <i class="fas fa-check-circle"></i> Yêu cầu đã được đóng
                        </div>
                    ` : ''}
                </div>
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
                    action: 'accept_request',
                    request_id: id
                })
            });

            if (response.success) {
                this.showNotification('Yêu cầu đã được nhận thành công', 'success');
                this.loadRequestDetail();
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
                this.loadRequestDetail();
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            this.showNotification('Lỗi kết nối', 'error');
        }
    }

    async addComment() {
        const commentText = document.getElementById('commentText').value.trim();
        
        if (!commentText) {
            this.showNotification('Vui lòng nhập bình luận', 'error');
            return;
        }

        try {
            const response = await this.apiCall('api/comments.php', {
                method: 'POST',
                body: JSON.stringify({
                    service_request_id: this.requestId,
                    comment: commentText
                })
            });

            if (response.success) {
                document.getElementById('commentText').value = '';
                this.loadRequestDetail();
                this.showNotification('Bình luận đã được thêm', 'success');
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            this.showNotification('Lỗi kết nối', 'error');
        }
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

    // Need Support Functions
    showNeedSupportModal(requestId) {
        document.getElementById('supportRequestId').value = requestId;
        document.getElementById('needSupportForm').reset();
        document.getElementById('needSupportModal').style.display = 'block';
    }

    closeNeedSupportModal() {
        document.getElementById('needSupportModal').style.display = 'none';
    }

    // Admin Support Functions
    showAdminSupportModal(supportId) {
        document.getElementById('adminSupportId').value = supportId;
        document.getElementById('adminSupportForm').reset();
        this.loadSupportRequestDetails(supportId);
        document.getElementById('adminSupportModal').style.display = 'block';
    }

    closeAdminSupportModal() {
        document.getElementById('adminSupportModal').style.display = 'none';
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
                this.loadRequestDetail();
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            this.showNotification('Lỗi kết nối', 'error');
        }
    }

    async handleNeedSupportSubmit(e) {
        e.preventDefault();
        const form = document.getElementById('needSupportForm');
        const formData = new FormData(form);
        const requestId = document.getElementById('supportRequestId').value;
        
        try {
            const response = await this.apiCall('api/support_requests.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'create',
                    service_request_id: requestId,
                    support_type: formData.get('support_type'),
                    support_details: formData.get('support_details'),
                    support_reason: formData.get('support_reason')
                })
            });
            
            if (response.success) {
                this.showNotification('Yêu cầu hỗ trợ đã được gửi thành công', 'success');
                this.closeNeedSupportModal();
                // Reload request detail to show updated status
                await this.loadRequestDetail();
            } else {
                this.showNotification(response.message || 'Lỗi khi gửi yêu cầu hỗ trợ', 'error');
            }
        } catch (error) {
            console.error('Support request error:', error);
            this.showNotification('Lỗi kết nối', 'error');
        }
    }

    showRejectModal(requestId) {
        const modal = document.getElementById('rejectModal');
        if (modal) {
            modal.style.display = 'block';
            // Store request ID for form submission
            this.rejectRequestId = requestId;
        }
    }

    closeRejectModal() {
        const modal = document.getElementById('rejectModal');
        if (modal) {
            modal.style.display = 'none';
            // Reset form
            document.getElementById('rejectForm').reset();
            this.rejectRequestId = null;
        }
    }

    async handleRejectSubmit(event) {
        event.preventDefault();
        
        console.log('=== DEBUG REJECT REQUEST ===');
        console.log('Request ID:', this.rejectRequestId);
        
        const form = document.getElementById('rejectForm');
        const formData = new FormData(form);
        
        const rejectData = {
            request_id: this.rejectRequestId,
            reject_reason: formData.get('reject_reason'),
            reject_details: formData.get('reject_details')
        };
        
        console.log('Reject data:', rejectData);
        
        try {
            const response = await this.apiCall('api/service_requests.php', {
                method: 'PUT',
                body: JSON.stringify({
                    action: 'reject_request',
                    ...rejectData
                })
            });
            
            console.log('Reject API Response:', response);
            
            if (response.success) {
                this.showNotification('Yêu cầu từ chối đã được gửi đến admin để duyệt', 'success');
                this.closeRejectModal();
                // Reload request detail to show updated status
                await this.loadRequestDetail();
            } else {
                this.showNotification(response.message || 'Lỗi khi gửi yêu cầu từ chối', 'error');
            }
        } catch (error) {
            console.error('Reject request error:', error);
            this.showNotification('Lỗi khi gửi yêu cầu từ chối', 'error');
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
                console.log('=== ADMIN SUPPORT SUBMIT RESPONSE ===');
                console.log('Response:', response);
                console.log('Decision:', response.data?.decision);
                console.log('Service Request Status:', response.data?.service_request_status);
                
                this.showNotification('Đã xử lý yêu cầu hỗ trợ thành công', 'success');
                this.closeAdminSupportModal();
                
                // Reload request detail to show updated status and admin decision
                await this.loadRequestDetail();
                
                // Show notification about admin decision
                if (response.data && response.data.decision) {
                    const decision = response.data.decision === 'approved' ? 'đã được phê duyệt' : 'đã bị từ chối';
                    const message = `📢 Yêu cầu hỗ trợ ${decision} bởi admin!`;
                    this.showNotification(message, response.data.decision === 'approved' ? 'success' : 'warning');
                }
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            this.showNotification('Lỗi kết nối', 'error');
        }
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

    // Close Request Modal Functions
    showCloseRequestModal(requestId) {
        document.getElementById('closeRequestId').value = requestId;
        
        // Load work performed details from resolution
        this.loadWorkPerformedDetails(requestId);
        
        document.getElementById('closeRequestModal').style.display = 'block';
    }

    closeCloseRequestModal() {
        document.getElementById('closeRequestModal').style.display = 'none';
        document.getElementById('closeRequestForm').reset();
    }

    async loadWorkPerformedDetails(requestId) {
        try {
            const response = await this.apiCall(`api/service_requests.php?action=get&id=${requestId}`);
            
            if (response.success && response.data.resolution) {
                const resolution = response.data.resolution;
                const workPerformedList = document.getElementById('workPerformedList');
                
                workPerformedList.innerHTML = `
                    <div class="work-performed-item">
                        <h5><i class="fas fa-tools"></i> Cách xử lý</h5>
                        <p>${resolution.solution_method}</p>
                    </div>
                    <div class="work-performed-item">
                        <h5><i class="fas fa-bug"></i> Loại lỗi đã xác định</h5>
                        <p>${this.getErrorTypeText(resolution.error_type)}</p>
                    </div>
                    <div class="work-performed-item">
                        <h5><i class="fas fa-exclamation-triangle"></i> Mô tả lỗi</h5>
                        <p>${resolution.error_description}</p>
                    </div>
                    ${resolution.replacement_materials ? `
                        <div class="work-performed-item">
                            <h5><i class="fas fa-box"></i> Vật tư thay thế</h5>
                            <p>${resolution.replacement_materials}</p>
                        </div>
                    ` : ''}
                    <div class="work-performed-item">
                        <h5><i class="fas fa-user"></i> Người thực hiện</h5>
                        <p>${resolution.resolver_name}</p>
                    </div>
                    <div class="work-performed-item">
                        <h5><i class="fas fa-calendar"></i> Thời gian hoàn thành</h5>
                        <p>${this.formatDate(resolution.resolved_at)}</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading work performed details:', error);
        }
    }

    async handleCloseRequestSubmit(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const requestId = formData.get('request_id') || document.getElementById('closeRequestId').value;
        
        console.log('=== DEBUG CLOSE REQUEST ===');
        console.log('Request ID:', requestId);
        console.log('Form data:', {
            rating: formData.get('rating'),
            feedback_service: formData.get('feedback_service'),
            feedback_software: formData.get('feedback_software'),
            would_recommend: formData.get('would_recommend'),
            ease_of_use: formData.get('ease_of_use'),
            speed_stability: formData.get('speed_stability'),
            requirement_meeting: formData.get('requirement_meeting')
        });
        
        try {
            const response = await this.apiCall('api/service_requests.php', {
                method: 'PUT',
                body: JSON.stringify({
                    action: 'close_request',
                    request_id: requestId,
                    rating: formData.get('rating'),
                    feedback: formData.get('feedback_service'),
                    software_feedback: formData.get('feedback_software'),
                    would_recommend: formData.get('would_recommend'),
                    ease_of_use: formData.get('ease_of_use'),
                    speed_stability: formData.get('speed_stability'),
                    requirement_meeting: formData.get('requirement_meeting')
                })
            });

            console.log('API Response:', response);

            if (response.success) {
                this.showNotification('Yêu cầu đã được đóng thành công!', 'success');
                this.closeCloseRequestModal();
                
                // Reload entire page to refresh all data
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                console.log('API Error:', response.message);
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            console.error('Close request error:', error);
            this.showNotification('Lỗi đóng yêu cầu', 'error');
        }
    }

    getPriorityText(priority) {
        const priorities = {
            'low': 'Thấp',
            'medium': 'Trung bình',
            'high': 'Cao',
            'critical': 'Khẩn cấp'
        };
        return priorities[priority] || priority;
    }

    getStatusText(status) {
        const statuses = {
            'open': 'Mở',
            'in_progress': 'Đang xử lý',
            'resolved': 'Đã giải quyết',
            'closed': 'Đã đóng',
            'cancelled': 'Đã hủy',
            'request_support': 'Yêu cầu hỗ trợ'
        };
        return statuses[status] || status;
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('vi-VN');
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    viewDocument(filePath, fileName, fileExt) {
        // Create document viewer modal
        let modal = document.getElementById('documentModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'documentModal';
            modal.className = 'document-modal';
            modal.innerHTML = `
                <div class="document-modal-content">
                    <div class="document-modal-header">
                        <h3 id="documentModalTitle">Document Viewer</h3>
                        <span class="document-modal-close" onclick="document.getElementById('documentModal').style.display='none'">&times;</span>
                    </div>
                    <div class="document-modal-body">
                        <div id="documentViewer">
                            <!-- Document content will be loaded here -->
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }
        
        document.getElementById('documentModalTitle').textContent = fileName;
        const viewer = document.getElementById('documentViewer');
        
        // Handle different file types
        if (fileExt === 'pdf') {
            const directFileUrl = `../uploads/requests/${fileName}`;
            viewer.innerHTML = `
                <iframe src="${directFileUrl}" 
                        style="width: 100%; height: 70vh; border: none;" 
                        onload="this.style.display='block'" 
                        onerror="this.parentElement.innerHTML='<div style=\\'text-align: center; padding: 50px;\\'><i class=\\'fas fa-exclamation-triangle\\' style=\\'font-size: 48px; color: #dc3545;\\'></i><p>Cannot display PDF. <a href=\\'${directFileUrl}\\' download=\\'${fileName}\\' class=\\'btn btn-primary\\'>Download PDF</a></p></div>'">
                </iframe>
                <div style="text-align: center; padding: 20px;">
                    <a href="${directFileUrl}" download="${fileName}" class="btn btn-primary">
                        <i class="fas fa-download"></i> Download PDF
                    </a>
                </div>
            `;
        } else if (['doc', 'docx'].includes(fileExt)) {
            const directFileUrl = `../uploads/requests/${fileName}`;
            viewer.innerHTML = `
                <div style="text-align: center; padding: 50px;">
                    <i class="fas fa-file-word" style="font-size: 64px; color: #2b579a;"></i>
                    <h4>Microsoft Word Document</h4>
                    <p>This is a Word document. You can download it:</p>
                    <div style="margin-top: 20px;">
                        <a href="${directFileUrl}" download="${fileName}" class="btn btn-primary">
                            <i class="fas fa-download"></i> Download Word Document
                        </a>
                    </div>
                </div>
            `;
        } else if (['xls', 'xlsx'].includes(fileExt)) {
            const directFileUrl = `../uploads/requests/${fileName}`;
            viewer.innerHTML = `
                <div style="text-align: center; padding: 50px;">
                    <i class="fas fa-file-excel" style="font-size: 64px; color: #217346;"></i>
                    <h4>Microsoft Excel Spreadsheet</h4>
                    <p>This is an Excel spreadsheet. You can download it:</p>
                    <div style="margin-top: 20px;">
                        <a href="${directFileUrl}" download="${fileName}" class="btn btn-primary">
                            <i class="fas fa-download"></i> Download Excel File
                        </a>
                    </div>
                </div>
            `;
        } else if (['ppt', 'pptx'].includes(fileExt)) {
            const directFileUrl = `../uploads/requests/${fileName}`;
            viewer.innerHTML = `
                <div style="text-align: center; padding: 50px;">
                    <i class="fas fa-file-powerpoint" style="font-size: 64px; color: #d24726;"></i>
                    <h4>Microsoft PowerPoint Presentation</h4>
                    <p>This is a PowerPoint presentation. You can download it:</p>
                    <div style="margin-top: 20px;">
                        <a href="${directFileUrl}" download="${fileName}" class="btn btn-primary">
                            <i class="fas fa-download"></i> Download PowerPoint
                        </a>
                    </div>
                </div>
            `;
        } else if (['txt', 'md'].includes(fileExt)) {
            // Load text file content
            const directFileUrl = `../uploads/requests/${fileName}`;
            fetch(directFileUrl)
                .then(response => response.text())
                .then(content => {
                    viewer.innerHTML = `
                        <div style="padding: 20px;">
                            <pre style="background: #f8f9fa; padding: 20px; border-radius: 5px; white-space: pre-wrap; word-wrap: break-word; max-height: 60vh; overflow-y: auto;">${content}</pre>
                            <div style="text-align: center; margin-top: 20px;">
                                <a href="${directFileUrl}" download="${fileName}" class="btn btn-secondary">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </div>
                        </div>
                    `;
                })
                .catch(error => {
                    viewer.innerHTML = `
                        <div style="text-align: center; padding: 50px;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #dc3545;"></i>
                            <p>Cannot load text file.</p>
                            <a href="${directFileUrl}" download="${fileName}" class="btn btn-primary">
                                <i class="fas fa-download"></i> Download Text File
                            </a>
                        </div>
                    `;
                });
        } else {
            const directFileUrl = `../uploads/requests/${fileName}`;
            viewer.innerHTML = `
                <div style="text-align: center; padding: 50px;">
                    <i class="fas fa-file" style="font-size: 64px; color: #6c757d;"></i>
                    <h4>Document</h4>
                    <p>This document type cannot be previewed. You can download it:</p>
                    <div style="margin-top: 20px;">
                        <a href="${directFileUrl}" download="${fileName}" class="btn btn-primary">
                            <i class="fas fa-download"></i> Download File
                        </a>
                    </div>
                </div>
            `;
        }
        
        modal.style.display = 'block';
    }

    showImageModal(imageSrc, imageName) {
        // Create image modal if it doesn't exist
        let modal = document.getElementById('imageModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'imageModal';
            modal.className = 'image-modal';
            modal.innerHTML = `
                <div class="image-modal-content">
                    <div class="image-modal-header">
                        <h3 id="imageModalTitle">Image Preview</h3>
                        <span class="image-modal-close" onclick="document.getElementById('imageModal').style.display='none'">&times;</span>
                    </div>
                    <div class="image-modal-body">
                        <img id="modalImage" src="" alt="" class="modal-image">
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }
        
        document.getElementById('modalImage').src = imageSrc;
        document.getElementById('imageModalTitle').textContent = imageName;
        modal.style.display = 'block';
    }

    closeModal(modal) {
        if (modal) {
            modal.style.display = 'none';
        }
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Remove after 3 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 3000);
    }

    async apiCall(url, options = {}) {
        const defaultOptions = {
            credentials: 'include'
        };
        
        const finalOptions = { ...defaultOptions, ...options };
        
        if (!finalOptions.headers) {
            finalOptions.headers = {
                'Content-Type': 'application/json'
            };
        }
        
        if (finalOptions.body && typeof finalOptions.body === 'object') {
            finalOptions.body = JSON.stringify(finalOptions.body);
        }
        
        const response = await fetch(url, finalOptions);
        return await response.json();
    }

    // Reject Request Functions
    async checkRejectRequestStatus() {
        try {
            const response = await this.apiCall(`api/reject_requests.php?action=check_status&service_request_id=${this.requestId}`);
            
            if (response.success && response.data) {
                this.rejectRequestStatus = response.data;
                
                // Thông báo cho staff nếu admin đã quyết định
                if (this.rejectRequestStatus.status !== 'pending') {
                    const decision = this.rejectRequestStatus.status === 'approved' ? 'được đồng ý' : 'bị từ chối';
                    const message = `📢 Yêu cầu từ chối đã ${decision} bởi admin!`;
                    this.showNotification(message, this.rejectRequestStatus.status === 'approved' ? 'success' : 'warning');
                }
            } else {
                this.rejectRequestStatus = null;
            }
        } catch (error) {
            console.error('Error checking reject request status:', error);
            this.rejectRequestStatus = null;
        }
    }

    showRejectModal(requestId) {
        const modal = document.getElementById('rejectModal');
        if (modal) {
            modal.style.display = 'block';
            this.rejectRequestId = requestId;
        }
    }

    closeRejectModal() {
        const modal = document.getElementById('rejectModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    async handleRejectSubmit(event) {
        event.preventDefault();
        
        const form = document.getElementById('rejectForm');
        const formData = new FormData(form);
        
        const rejectData = {
            request_id: this.rejectRequestId,
            reject_reason: formData.get('reject_reason'),
            reject_details: formData.get('reject_details')
        };
        
        try {
            const response = await this.apiCall('api/service_requests.php', {
                method: 'PUT',
                body: JSON.stringify({
                    action: 'reject_request',
                    ...rejectData
                })
            });
            
            if (response.success) {
                this.showNotification('Yêu cầu từ chối đã được gửi đến admin để duyệt', 'success');
                this.closeRejectModal();
                await this.loadRequestDetail();
            } else {
                this.showNotification(response.message || 'Lỗi khi gửi yêu cầu từ chối', 'error');
            }
        } catch (error) {
            console.error('Reject request error:', error);
            this.showNotification('Lỗi khi gửi yêu cầu từ chối', 'error');
        }
    }

    getRejectStatusText(status) {
        const statuses = {
            'pending': 'Chờ duyệt',
            'approved': 'Đã phê duyệt',
            'rejected': 'Đã từ chối'
        };
        return statuses[status] || status;
    }
}

// Initialize app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.app = new RequestDetailApp();
});
