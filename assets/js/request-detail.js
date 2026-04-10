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







        this.isLoading = false; // Prevent infinite loops







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















        // Navigation events







        const navLinks = document.querySelectorAll('.nav-link');







        console.log('=== REQUEST DETAIL BINDING NAVIGATION ===');







        console.log('Found nav links:', navLinks.length);







        navLinks.forEach((link, index) => {







            const page = link.dataset.page;







            const href = link.href;







            console.log(`Nav link ${index}:`, {page, text: link.textContent.trim(), href, dataset: link.dataset});







            link.addEventListener('click', (e) => this.handleNavigation(e));







        });















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







        







        // File upload events for all modals







        document.getElementById('resolveAttachments').addEventListener('change', (e) => this.handleFileUpload(e, 'resolveAttachmentPreview'));







        document.getElementById('supportAttachments').addEventListener('change', (e) => this.handleFileUpload(e, 'supportAttachmentPreview'));







        document.getElementById('rejectAttachments').addEventListener('change', (e) => this.handleFileUpload(e, 'rejectAttachmentPreview'));















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







        







        // Disable submit button and show loading state







        const submitBtn = e.target.querySelector('button[type="submit"]');







        if (submitBtn) {







            submitBtn.disabled = true;







            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang đăng nhập...';







        }







        







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







                // Re-enable button if failed







                if (submitBtn) {







                    submitBtn.disabled = false;







                    submitBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Đăng nhập';







                }







            }







        } catch (error) {







            this.showNotification('Lỗi kết nối', 'error');







            // Re-enable button if error







            if (submitBtn) {







                submitBtn.disabled = false;







                submitBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Đăng nhập';







            }







        } finally {







            this.hideLoading();







        }







    }















    async handleRegister(e) {







        e.preventDefault();







        const formData = new FormData(e.target);







        







        // Disable submit button and show loading state







        const submitBtn = e.target.querySelector('button[type="submit"]');







        if (submitBtn) {







            submitBtn.disabled = true;







            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang đăng ký...';







        }







        







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







                // Re-enable button if failed







                if (submitBtn) {







                    submitBtn.disabled = false;







                    submitBtn.innerHTML = '<i class="fas fa-user-plus"></i> Đăng ký';







                }







            }







        } catch (error) {







            this.showNotification('Lỗi kết nối', 'error');







            // Re-enable button if error







            if (submitBtn) {







                submitBtn.disabled = false;







                submitBtn.innerHTML = '<i class="fas fa-user-plus"></i> Đăng ký';







            }







        } finally {







            this.hideLoading();







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















    async handleNavigation(e) {







        console.log('=== handleNavigation called ===');







        console.log('Event target:', e.target);







        console.log('Event currentTarget:', e.currentTarget);







        







        // Prevent multiple calls







        if (this._navigating) {







            console.log('Already navigating, ignoring');







            return;







        }







        this._navigating = true;







        







        const navLink = e.currentTarget;







        const page = navLink.dataset.page;







        const href = navLink.href;







        







        console.log('Navigation data:', { page, href });







        console.log('Dataset:', navLink.dataset);







        







        // Check if this is an internal navigation (has data-page)







        if (page) {







            console.log('Internal navigation detected for page:', page);







            







            // Check if user is trying to access admin-only pages







            const adminPages = ['users', 'departments'];







            const staffPages = ['support-requests', 'reject-requests'];







            







            if (adminPages.includes(page) && this.currentUser.role !== 'admin') {







                console.log('❌ Non-admin user trying to access admin page:', page);







                this.showNotification('Chỉ admin mới có quyền truy cập trang này', 'error');







                this._navigating = false;







                return;







            }







            







            if (staffPages.includes(page) && !['admin', 'staff'].includes(this.currentUser.role)) {







                console.log('❌ Non-admin/staff user trying to access staff page:', page);







                this.showNotification('Chỉ admin và staff mới có quyền truy cập trang này', 'error');







                this._navigating = false;







                return;







            }







            







            console.log('✅ Redirecting to index.html with page:', page);







            e.preventDefault(); // Only prevent default for internal navigation







            







            // Use postMessage to communicate with parent window







            try {







                if (window.opener && window.opener !== window) {







                    console.log('Using postMessage to parent window');







                    window.opener.postMessage({







                        action: 'showPage',







                        page: page,







                        timestamp: Date.now()







                    }, '*');







                    







                    console.log('PostMessage sent to parent window');







                } else {







                    // This is a standalone window, use URL redirect







                    console.log('Using URL redirect for standalone window');







                    const redirectUrl = `index.html?page=${page}`;







                    console.log('Redirecting to:', redirectUrl);







                    window.location.href = redirectUrl;







                }







                







            } catch (error) {







                console.error('Error sending navigation request:', error);







                // Fallback to URL redirect







                const redirectUrl = `index.html?page=${page}`;







                console.log('Fallback to URL redirect:', redirectUrl);







                window.location.href = redirectUrl;







            }







        } else {







            console.log('External link detected, allowing navigation');







            // This is an external link, let browser handle it naturally







            // Don't prevent default - let browser navigate







        }







        







        this._navigating = false;







    }















    logout() {







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







    }















    async checkAuth() {







        





        







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



    // Function to convert would_recommend number to text

    getRecommendationText(value) {

        const texts = {

            1: 'Rất không hài lòng',

            2: 'Không hài lòng',

            3: 'Bình thường',

            4: 'Khá tốt',

            5: 'Rất hài lòng'

        };

        return texts[value] || 'Không có đánh giá';

    }



    async loadRequestDetail() {

        // Prevent infinite loops and flickering

        if (this.isLoading) return;

        this.isLoading = true;

        



        // Show loading state







        this.showLoading('Đang tải chi tiết yêu cầu...');







        







        try {







            const response = await this.apiCall(`api/service_requests.php?action=get&id=${this.requestId}`);







            







            if (response.success) {







                this.request = response.data;







                







                // Load reject request status for staff BEFORE displaying







                if (this.currentUser && this.currentUser.role === 'staff') {



                    await this.checkRejectRequestStatus();

                    await this.checkSupportRequestStatus();



                }







                







                this.displayRequestDetail(this.request);







                







                // Show notification for reject request decision







                if (this.request.reject_request && this.request.reject_request.status !== 'pending') {







                    if (this.currentUser && ['admin', 'staff'].includes(this.currentUser.role)) {







                        const decision = this.request.reject_request.status === 'approved' ? 'đã được phê duyệt' : 'đã bị từ chối';







                        const message = `📢 Yêu cầu từ chối ${decision} bởi admin!`;







                        this.showNotification(message, this.request.reject_request.status === 'approved' ? 'success' : 'warning');







                    } else {







                        // Regular users see generic message







                        const message = '📢 Yêu cầu của bạn đã được xử lý!';







                        this.showNotification(message, 'info');







                    }







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







        } finally {







            this.hideLoading();







        }







    }















    displayRequestDetail(request) {







        console.log('=== DISPLAY REQUEST DETAIL ===');







        console.log('Request ID:', request.id);







        console.log('Request title:', request.title);







        







        const container = document.getElementById('requestDetails');







        if (!container) {







            console.error('Request details container not found!');







            return;







        }







        







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







                    <h3>${request.title} <span class="request-id">#${request.id}</span></h3>







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







                    ${request.assigned_to && request.accepted_at ? `

                        <div class="meta-item">
                            <strong>Thời gian staff nhận:</strong> ${formatDate(request.accepted_at)}

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







                                const isImage = attachment.mime_type && attachment.mime_type.startsWith('image/');







                                const fileExt = attachment.filename.split('.').pop().toLowerCase();







                                const isPDF = fileExt === 'pdf';







                                const isWord = ['doc', 'docx'].includes(fileExt);







                                const isExcel = ['xls', 'xlsx'].includes(fileExt);







                                const isPowerPoint = ['ppt', 'pptx'].includes(fileExt);







                                const isText = ['txt', 'md'].includes(fileExt);







                                const isViewable = isPDF || isWord || isExcel || isPowerPoint || isText;







                                







                                console.log('Attachment info:', {isImage, fileExt, isPDF, isViewable});







                                







                                return `







                                    <div class="attachment-item">







                                        <div class="attachment-info">







                                            <i class="fas fa-${isImage ? 'image' : isPDF ? 'file-pdf' : isWord ? 'file-word' : isExcel ? 'file-excel' : isPowerPoint ? 'file-powerpoint' : 'file'}"></i>







                                            <span class="attachment-name">${attachment.original_name}</span>







                                            <span class="attachment-size">(${formatFileSize(attachment.file_size)})</span>







                                        </div>







                                        <div class="attachment-actions">







                                            ${isImage ? `







                                                <img src="api/attachment.php?file=${attachment.filename}&action=view" 







                                                     alt="${attachment.original_name}" 







                                                     class="attachment-preview"







                                                     onclick="requestDetailApp.showImageModal('api/attachment.php?file=${attachment.filename}&action=view', '${attachment.original_name}')"







                                                     style="cursor: pointer;">







                                                <div class="image-overlay">







                                                    <i class="fas fa-search-plus"></i>







                                                </div>







                                            ` : ''}







                                            ${isViewable ? `







                                                <button class="btn btn-sm btn-primary" 







                                                        onclick="requestDetailApp.viewDocument('api/attachment.php?file=${attachment.filename}&action=view', '${attachment.original_name}', '${fileExt}')">







                                                    <i class="fas fa-eye"></i> Xem







                                                </button>







                                            ` : ''}







                                            <a href="api/attachment.php?file=${attachment.filename}&action=download" 







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

                        

                        ${request.resolution_attachments && request.resolution_attachments.length > 0 ? `

                            <div class="attachments-section">

                                <h4><i class="fas fa-paperclip"></i> Tệp đính kèm giải quyết (${request.resolution_attachments.length})</h4>

                                <div class="attachments-list">

                                    ${request.resolution_attachments.map(attachment => {

                                        

                                        const isImage = attachment.mime_type && attachment.mime_type.startsWith('image/');

                                        const isPDF = attachment.mime_type === 'application/pdf';

                                        const isWord = attachment.mime_type && (attachment.mime_type.includes('word') || attachment.mime_type.includes('document'));

                                        const isExcel = attachment.mime_type && (attachment.mime_type.includes('sheet') || attachment.mime_type.includes('excel'));

                                        const isPowerPoint = attachment.mime_type && (attachment.mime_type.includes('presentation') || attachment.mime_type.includes('powerpoint'));

                                        const fileExt = attachment.original_name.split('.').pop().toLowerCase();

                                        const isViewable = isPDF || isWord || isExcel || isPowerPoint;

                                        

                                        return `

                                            <div class="attachment-item">

                                                <div class="attachment-info">

                                                    <i class="fas fa-${isImage ? 'image' : isPDF ? 'file-pdf' : isWord ? 'file-word' : isExcel ? 'file-excel' : isPowerPoint ? 'file-powerpoint' : 'file'}"></i>

                                                    <span class="attachment-name">${attachment.original_name}</span>

                                                    <span class="attachment-size">(${formatFileSize(attachment.file_size)})</span>

                                                </div>

                                                <div class="attachment-actions">

                                                    ${isImage ? `

                                                        <div class="image-preview-container" style="position: relative;">

                                                            <img src="api/attachment.php?file=${attachment.filename}&action=view" 

                                                                 alt="${attachment.original_name}" 

                                                                 class="attachment-preview"

                                                                 onclick="requestDetailApp.showImageModal('api/attachment.php?file=${attachment.filename}&action=view', '${attachment.original_name}')"

                                                                 style="cursor: pointer; max-width: 100px; max-height: 100px; object-fit: cover; border-radius: 4px;">

                                                        </div>

                                                    ` : ''}

                                                    ${isViewable ? `

                                                        <button class="btn btn-sm btn-primary" 

                                                                onclick="requestDetailApp.viewDocument('api/attachment.php?file=${attachment.filename}&action=view', '${attachment.original_name}', '${fileExt}')">

                                                            <i class="fas fa-eye"></i> Xem

                                                        </button>

                                                    ` : ''}

                                                    <a href="api/attachment.php?file=${attachment.filename}&action=download" 

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

                ` : ''}







                







                ${request.reject_request && currentUser && ['admin', 'staff'].includes(currentUser.role) ? `







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







                        







                        ${request.reject_request.attachments && request.reject_request.attachments.length > 0 ? `







                            <div class="reject-attachments">







                                <h4><i class="fas fa-paperclip"></i> Tệp đính kèm (${request.reject_request.attachments.length})</h4>







                                <div class="attachments-list">







                                    ${request.reject_request.attachments.map(attachment => {







                                        const isImage = attachment.mime_type && attachment.mime_type.startsWith('image/');







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




                                                        <img src="api/reject_request_attachment.php?file=${attachment.filename}&action=view" 

                                                             alt="${attachment.original_name}" 

                                                             class="attachment-preview"

                                                             onclick="requestDetailApp.showImageModal('api/reject_request_attachment.php?file=${attachment.filename}&action=view', '${attachment.original_name}')"

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







                                                                onclick="requestDetailApp.viewDocument('api/reject_request_attachment.php?file=${attachment.filename}&action=view', '${attachment.original_name}', '${fileExt}')">







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







                ` : ''}







                







                ${request.support_request && currentUser && ['admin', 'staff'].includes(currentUser.role) ? `







                    <div class="support-request-info" style="background-color: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107; margin-bottom: 20px;">







                        <h4><i class="fas fa-hands-helping"></i> Yêu cầu hỗ trợ từ Staff</h4>







                        <div class="support-details">







                            <div class="support-item">







                                <strong>Loại hỗ trợ:</strong> ${getSupportTypeText(request.support_request.support_type)}







                            </div>







                            <div class="support-item">







                                <strong>Chi tiết:</strong> ${request.support_request.support_details}







                            </div>







                            <div class="support-item">







                                <strong style="display: block; margin-bottom: 5px;">Lý do:</strong> 
                                <div style="background-color: #fff3cd; padding: 10px; border-radius: 5px; border-left: 4px solid #ffc107;">
                                    ${request.support_request.support_reason}
                                </div>







                            </div>







                            <div class="support-item">







                                <strong>Trạng thái:</strong> <span class="badge status-${request.support_request.status}" ${request.support_request.status === 'pending' ? 'style="background-color: #dc3545; color: white; font-weight: bold; padding: 6px 12px; border-radius: 6px; box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);"' : ''}>${getSupportStatusText(request.support_request.status)}</span>







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







                        







                        ${request.support_request.attachments && request.support_request.attachments.length > 0 ? `







                            <div class="support-attachments">







                                <h4><i class="fas fa-paperclip"></i> Tệp đính kèm (${request.support_request.attachments.length})</h4>







                                <div class="attachments-list">







                                    ${request.support_request.attachments.map(attachment => {







                                        const isImage = attachment.mime_type && attachment.mime_type.startsWith('image/');







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







                                                        <img src="api/support_request_attachment.php?file=${attachment.filename}&action=view" 







                                                             alt="${attachment.original_name}" 







                                                             class="attachment-preview"







                                                             onclick="requestDetailApp.showImageModal('api/support_request_attachment.php?file=${attachment.filename}&action=view', '${attachment.original_name}')"







                                                             style="cursor: pointer;">







                                                        <div class="image-overlay">







                                                            <i class="fas fa-search-plus"></i>







                                                        </div>







                                                    ` : ''}







                                                    ${isViewable ? `







                                                        <button class="btn btn-sm btn-primary" 







                                                                onclick="requestDetailApp.viewDocument('api/support_request_attachment.php?file=${attachment.filename}&action=view', '${attachment.original_name}', '${fileExt}')">







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







                ` : ''}







                







                <div class="request-actions">







                    ${currentUser && currentUser.role === 'admin' ? `







                        ${request.status === 'open' && !request.assigned_to ? `







                            <button class="btn btn-success" onclick="requestDetailApp.acceptRequest(${request.id})">







                                <i class="fas fa-check"></i> Nhận yêu cầu







                            </button>







                        ` : ''}







                        ${request.status === 'in_progress' && request.assigned_to == currentUser.id && (!request.reject_request || ['rejected', 'approved'].includes(request.reject_request.status)) ? `







                            <button class="btn btn-primary" onclick="requestDetailApp.showResolveModal(${request.id})">







                                <i class="fas fa-check-circle"></i> Đã giải quyết







                            </button>







                            <button class="btn btn-warning" onclick="requestDetailApp.showNeedSupportModal(${request.id})">







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







                        <button class="btn btn-primary" onclick="requestDetailApp.updateRequestStatus(${request.id})">Cập nhật</button>





    ` : currentUser && currentUser.role === 'staff' ? `



        ${(() => {

            // Debug condition for accept button

            const showAcceptBtn = request.status === 'open' && !request.assigned_to;



            return showAcceptBtn ? `

            <button class="btn btn-success" onclick="requestDetailApp.acceptRequest(${request.id})">



                <i class="fas fa-check"></i> Nhận yêu cầu



                            </button>

                        ` : '';

                        })()}



                        ${request.status === 'in_progress' && request.assigned_to == currentUser.id && (!request.reject_request || ['rejected', 'approved'].includes(request.reject_request.status)) ? `







                            <button class="btn btn-primary" onclick="requestDetailApp.showResolveModal(${request.id})">







                                <i class="fas fa-check-circle"></i> Đã giải quyết







                            </button>







                            <button class="btn btn-warning" onclick="requestDetailApp.showNeedSupportModal(${request.id})">







                                <i class="fas fa-hands-helping"></i> Cần hỗ trợ







                            </button>







                            <button class="btn btn-danger" onclick="requestDetailApp.showRejectModal(${request.id})">







                                <i class="fas fa-times"></i> Từ chối







                            </button>







                        ` : ''}







                    ` : ''}







                    







                    <!-- Show Close Request button for requesters when request is resolved -->







                    ${currentUser && currentUser.role === 'user' && request.status === 'resolved' && (request.user_id == currentUser.id || request.requester_id == currentUser.id) ? `







                        <button class="btn btn-danger" onclick="requestDetailApp.showCloseRequestModal(${request.id})">







                            <i class="fas fa-times-circle"></i> Đóng lại yêu cầu







                        </button>







                    ` : ''}



                </div>

                

                <!-- Feedback section - OUTSIDE request-actions -->

                ${(request.feedback_rating || request.feedback_text) ? `

                    <div class="feedback-section">

                        <h4><i class="fas fa-star"></i> Đánh giá của người dùng</h4>

                        <div class="feedback-content">

                            ${request.feedback_rating ? `

                                <div class="feedback-rating">

                                    <strong>Đánh giá chung:</strong>

                                    <div class="stars">

                                        ${Array.from({length: 5}, (_, i) => 

                                            `<i class="fas fa-star ${i < request.feedback_rating ? 'active' : ''}"></i>`

                                        ).join('')}

                                        <span class="rating-text">(${request.feedback_rating}/5)</span>

                                    </div>

                                </div>

                            ` : ''}

                            

                            ${request.feedback_text ? `

                                <div class="feedback-item">

                                    <strong>Nhận xét về dịch vụ:</strong>

                                    <p>${request.feedback_text}</p>

                                </div>

                            ` : ''}

                            

                            ${request.software_feedback ? `

                                <div class="feedback-item">

                                    <strong>Nhận xét về hệ thống IT SRM:</strong>

                                    <p>${request.software_feedback}</p>

                                </div>

                            ` : ''}

                            

                            ${request.would_recommend ? `

                                <div class="feedback-item">

                                    <strong>Đánh giá về xử lý yêu cầu:</strong>

                                    <span class="recommend-badge ${request.would_recommend}">

                                        ${this.getRecommendationText(request.would_recommend)}

                                    </span>

                                </div>

                            ` : ''}

                            

                            ${(request.ease_of_use || request.speed_stability || request.requirement_meeting) ? `

                                <div class="feedback-ratings-detailed">

                                    <h5>Đánh giá chi tiết:</h5>

                                    <div class="rating-grid">

                                        ${request.ease_of_use ? `

                                            <div class="rating-item">

                                                <span class="rating-label">Dễ sử dụng:</span>

                                                <div class="rating-stars">

                                                    ${Array.from({length: 5}, (_, i) => 

                                                        `<i class="fas fa-star ${i < request.ease_of_use ? 'active' : ''}"></i>`

                                                    ).join('')}

                                                    <span>(${request.ease_of_use}/5)</span>

                                                </div>

                                            </div>

                                        ` : ''}

                                        

                                        ${request.speed_stability ? `

                                            <div class="rating-item">

                                                <span class="rating-label">Tốc độ & Ổn định:</span>

                                                <div class="rating-stars">

                                                    ${Array.from({length: 5}, (_, i) => 

                                                        `<i class="fas fa-star ${i < request.speed_stability ? 'active' : ''}"></i>`

                                                    ).join('')}

                                                    <span>(${request.speed_stability}/5)</span>

                                                </div>

                                            </div>

                                        ` : ''}

                                        

                                        ${request.requirement_meeting ? `

                                            <div class="rating-item">

                                                <span class="rating-label">Đáp ứng yêu cầu:</span>

                                                <div class="rating-stars">

                                                    ${Array.from({length: 5}, (_, i) => 

                                                        `<i class="fas fa-star ${i < request.requirement_meeting ? 'active' : ''}"></i>`

                                                    ).join('')}

                                                    <span>(${request.requirement_meeting}/5)</span>

                                                </div>

                                            </div>

                                        ` : ''}

                                    </div>

                                </div>

                            ` : ''}

                            

                            ${request.feedback_created_at ? `

                                <div class="feedback-meta">

                                    <small><i class="fas fa-clock"></i> Đánh giá vào ${formatDate(request.feedback_created_at)}</small>

                                </div>

                            ` : ''}

                        </div>

                    </div>

                ` : ''}

                

                <!-- Show closed status when request is closed - OUTSIDE request-actions -->

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







            <div class="comment" data-comment-id="${comment.id}">







                <div class="comment-header">







                    <span class="comment-author">${comment.user_name}</span>







                    <span class="comment-date">${this.formatDate(comment.created_at)}</span>







                    ${this.canDeleteComment(comment) ? `







                        <button class="delete-comment-btn" onclick="requestDetail.deleteComment(${comment.id})" title="Xóa bình luận">







                            <i class="fas fa-trash"></i>







                        </button>







                    ` : ''}







                </div>







                <div class="comment-text">${comment.comment}</div>







            </div>







        `).join('');







    }















    canDeleteComment(comment) {







        // Admin can delete any comment







        if (this.currentUser.role === 'admin') {







            return true;







        }







        







        // Staff can delete their own comments







        if (this.currentUser.role === 'staff' && comment.user_id === this.currentUser.id) {







            return true;







        }







        







        // Users can delete their own comments







        if (this.currentUser.role === 'user' && comment.user_id === this.currentUser.id) {







            return true;







        }







        







        return false;







    }















    async deleteComment(commentId) {







        if (!confirm('Bạn có chắc chắn muốn xóa bình luận này?')) {







            return;







        }















        try {







            const response = await this.apiCall(`api/comments.php?id=${commentId}`, {







                method: 'DELETE'







            });















            if (response.success) {







                this.showNotification('Bình luận đã được xóa', 'success');







                // Reload request detail to refresh comments







                this.loadRequestDetail();







            } else {







                this.showNotification(response.message || 'Lỗi khi xóa bình luận', 'error');







            }







        } catch (error) {







            this.showNotification('Lỗi kết nối', 'error');







        }







    }















    async acceptRequest(id) {







        // Disable button immediately to prevent double-click







        const acceptBtn = document.querySelector(`button[onclick="requestDetailApp.acceptRequest(${id})"]`);







        if (acceptBtn) {







            acceptBtn.disabled = true;







            acceptBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang nhận yêu cầu...';







        }







        







        // Show loading state







        this.showLoading('Đang nhận yêu cầu...');







        







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







                // Reset loading state

                this.isLoading = false;



                // Reload the page to refresh all data

                window.location.reload();



            } else {







                this.showNotification(response.message, 'error');







                // Re-enable button if failed







                if (acceptBtn) {







                    acceptBtn.disabled = false;







                    acceptBtn.innerHTML = '<i class="fas fa-check"></i> Nhận yêu cầu';







                }







            }







        } catch (error) {







            this.showNotification('Lỗi kết nối', 'error');







            // Re-enable button if error







            if (acceptBtn) {







                acceptBtn.disabled = false;







                acceptBtn.innerHTML = '<i class="fas fa-check"></i> Nhận yêu cầu';







            }







        } finally {







            this.hideLoading();







        }







    }















    async updateRequestStatus(id) {







        const status = document.getElementById('statusUpdate').value;







        







        if (!status) {







            this.showNotification('Vui lòng chọn trạng thái', 'error');







            return;







        }















        // Disable button and show loading state







        const updateBtn = document.querySelector('button[onclick="requestDetailApp.updateRequestStatus(' + id + ')"]');







        if (updateBtn) {







            updateBtn.disabled = true;







            updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang cập nhật...';







        }















        // Show loading state







        this.showLoading('Đang cập nhật trạng thái...');















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







                // Reset loading state

                this.isLoading = false;



                // Reload the page to refresh all data

                window.location.reload();







            } else {







                this.showNotification(response.message, 'error');







                // Re-enable button if failed







                if (updateBtn) {







                    updateBtn.disabled = false;







                    updateBtn.innerHTML = 'Cập nhật';







                }







            }







        } catch (error) {







            this.showNotification('Lỗi kết nối', 'error');







            // Re-enable button if error







            if (updateBtn) {







                updateBtn.disabled = false;







                updateBtn.innerHTML = 'Cập nhật';







            }







        } finally {







            this.hideLoading();







        }







    }















    async addComment() {







        const commentText = document.getElementById('commentText').value.trim();







        







        if (!commentText) {







            this.showNotification('Vui lòng nhập bình luận', 'error');







            return;







        }















        // Disable button and show loading state







        const addBtn = document.getElementById('addCommentBtn');







        if (addBtn) {







            addBtn.disabled = true;







            addBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi...';







        }















        // Show loading state







        this.showLoading('Đang gửi bình luận...');







        







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







                // Reset loading state

                this.isLoading = false;



                // Reload the page to refresh all data

                window.location.reload();



                this.showNotification('Bình luận đã được thêm', 'success');







                







                // Re-enable button after success







                if (addBtn) {







                    addBtn.disabled = false;







                    addBtn.innerHTML = '<i class="fas fa-plus"></i> Thêm bình luận';







                }







            } else {







                this.showNotification(response.message, 'error');







                // Re-enable button if failed







                if (addBtn) {







                    addBtn.disabled = false;







                    addBtn.innerHTML = '<i class="fas fa-plus"></i> Thêm bình luận';







                }







            }







        } catch (error) {







            this.showNotification('Lỗi kết nối', 'error');







            // Re-enable button if error







            if (addBtn) {







                addBtn.disabled = false;







                addBtn.innerHTML = '<i class="fas fa-plus"></i> Thêm bình luận';







            }







        } finally {







            this.hideLoading();







        }







    }















    // Resolution Functions







    showResolveModal(requestId) {







        document.getElementById('resolveRequestId').value = requestId;







        document.getElementById('resolveForm').reset();







        document.getElementById('resolveAttachmentPreview').innerHTML = '<div class="no-files">Chưa có tệp nào được chọn</div>';







        document.getElementById('resolveAttachmentPreview').classList.remove('has-files');







        document.getElementById('resolveModal').style.display = 'block';







    }











    showRejectModal(requestId) {

        console.log('showRejectModal called with requestId:', requestId);

        console.log('requestDetailApp object:', window.requestDetailApp);

        

        // Debug CSS loading

        const cssLink = document.querySelector('link[href*="style.css"]');

        console.log('CSS link element:', cssLink);

        if (cssLink) {

            console.log('CSS href:', cssLink.href);

            console.log('CSS loaded:', cssLink.sheet ? true : false);

        } else {

            console.error('CSS link not found!');

        }

        

        try {

            document.getElementById('rejectRequestId').value = requestId;

            

            // Reset only text fields, not file input

            document.getElementById('rejectReason').value = '';

            document.getElementById('rejectDetails').value = '';

            

            // Reset attachment preview

            document.getElementById('rejectAttachmentPreview').innerHTML = '<div class="no-files">Chưa có tệp nào được chọn</div>';

            document.getElementById('rejectAttachmentPreview').classList.remove('has-files');

            

            const modal = document.getElementById('rejectModal');

            console.log('rejectModal element:', modal);

            

            if (modal) {

                modal.style.display = 'block';

                console.log('Modal display set to block');

                

                // Debug computed styles

                const computedStyles = window.getComputedStyle(modal);

                console.log('Computed display:', computedStyles.display);

                console.log('Computed z-index:', computedStyles.zIndex);

                console.log('Computed position:', computedStyles.position);

                console.log('Computed visibility:', computedStyles.visibility);

                console.log('Computed opacity:', computedStyles.opacity);

                

                // Check if modal is actually visible

                const rect = modal.getBoundingClientRect();

                console.log('Modal dimensions:', {

                    width: rect.width,

                    height: rect.height,

                    top: rect.top,

                    left: rect.left

                });

                

                // Check if modal is in viewport

                const isInViewport = rect.width > 0 && rect.height > 0;

                console.log('Modal is in viewport:', isInViewport);

                

                // Force visibility

                modal.style.visibility = 'visible';

                modal.style.opacity = '1';

                modal.style.zIndex = '10000';

                

                // Force modal dimensions

                modal.style.width = '100%';

                modal.style.height = '100%';

                modal.style.top = '0';

                modal.style.left = '0';

                modal.style.position = 'fixed';

                

                console.log('Modal forced dimensions applied');

                

                // Debug parent container

                const parent = modal.parentElement;

                console.log('Modal parent element:', parent);

                if (parent) {

                    const parentStyles = window.getComputedStyle(parent);

                    console.log('Parent display:', parentStyles.display);

                    console.log('Parent position:', parentStyles.position);

                    console.log('Parent overflow:', parentStyles.overflow);

                    console.log('Parent dimensions:', {

                        width: parent.offsetWidth,

                        height: parent.offsetHeight

                    });

                    

                    // Check if parent is body

                    if (parent !== document.body) {

                        console.log('Modal is not in body, moving to body');

                        document.body.appendChild(modal);

                    }

                }

                

                // Debug viewport

                console.log('Viewport dimensions:', {

                    width: window.innerWidth,

                    height: window.innerHeight,

                    scrollX: window.scrollX,

                    scrollY: window.scrollY

                });

                

                // Debug all CSS rules affecting modal

                try {

                    const cssRules = [];

                    if (modal.sheet) {

                        for (let i = 0; i < modal.sheet.cssRules.length; i++) {

                            const rule = modal.sheet.cssRules[i];

                            if (rule.selectorText && rule.selectorText.includes('.modal')) {

                                cssRules.push({

                                    selector: rule.selectorText,

                                    styles: rule.style.cssText

                                });

                            }

                        }

                    }

                    console.log('CSS rules affecting modal:', cssRules);

                } catch (e) {

                    console.log('Cannot access CSS rules:', e.message);

                }

                

                // Re-check dimensions after forcing

                const newRect = modal.getBoundingClientRect();

                console.log('Modal dimensions after forcing:', {

                    width: newRect.width,

                    height: newRect.height,

                    top: newRect.top,

                    left: newRect.left

                });

                

                const isNowInViewport = newRect.width > 0 && newRect.height > 0;

                console.log('Modal is now in viewport:', isNowInViewport);

                

                // Debug modal content

                const modalContent = modal.querySelector('.modal-content');

                if (modalContent) {

                    console.log('Modal content element found:', modalContent);

                    const contentStyles = window.getComputedStyle(modalContent);

                    console.log('Modal content display:', contentStyles.display);

                    console.log('Modal content visibility:', contentStyles.visibility);

                    console.log('Modal content opacity:', contentStyles.opacity);

                    

                    // Force modal content visibility

                    modalContent.style.display = 'block';

                    modalContent.style.visibility = 'visible';

                    modalContent.style.opacity = '1';

                } else {

                    console.error('Modal content element not found!');

                }

                

                console.log('Modal should be visible now - forced styles applied');

                

                // Try creating a test modal to verify HTML structure

                if (!isNowInViewport) {

                    console.log('Creating test modal to verify HTML structure...');

                    const testModal = document.createElement('div');

                    testModal.style.cssText = `

                        position: fixed;

                        top: 0;

                        left: 0;

                        width: 100%;

                        height: 100%;

                        background-color: red;

                        z-index: 99999;

                        display: block;

                    `;

                    testModal.innerHTML = '<div style="color: white; padding: 20px;">TEST MODAL - If you see this, HTML structure works</div>';

                    document.body.appendChild(testModal);

                    

                    setTimeout(() => {

                        document.body.removeChild(testModal);

                        console.log('Test modal removed');

                    }, 3000);

                }

            } else {

                console.error('rejectModal element not found!');

            }

        } catch (error) {

            console.error('Error in showRejectModal:', error);

        }

    }











    closeResolveModal() {







        const modal = document.getElementById('resolveModal');







        if (modal) {







            // Move modal to body if it's nested







            if (modal.parentElement !== document.body) {







                document.body.appendChild(modal);







            }







            modal.style.display = 'none';







        }







    }







    closeRejectModal() {







        const modal = document.getElementById('rejectModal');







        if (modal) {







            // Move modal to body if it's nested







            if (modal.parentElement !== document.body) {







                document.body.appendChild(modal);







            }







            modal.style.display = 'none';







        }







    }















    // Need Support Functions







    showNeedSupportModal(requestId) {







        document.getElementById('supportRequestId').value = requestId;







        document.getElementById('needSupportForm').reset();







        document.getElementById('supportAttachmentPreview').innerHTML = '<div class="no-files">Chưa có tệp nào được chọn</div>';







        document.getElementById('supportAttachmentPreview').classList.remove('has-files');







        document.getElementById('needSupportModal').style.display = 'block';







    }















    closeNeedSupportModal() {







        const modal = document.getElementById('needSupportModal');







        if (modal) {







            // Move modal to body if it's nested







            if (modal.parentElement !== document.body) {







                document.body.appendChild(modal);







            }







            modal.style.display = 'none';







        }







    }















    // Admin Support Functions







    showAdminSupportModal(supportId) {







        document.getElementById('adminSupportId').value = supportId;







        document.getElementById('adminSupportForm').reset();







        this.loadSupportRequestDetails(supportId);







        document.getElementById('adminSupportModal').style.display = 'block';







    }















    closeAdminSupportModal() {







        const modal = document.getElementById('adminSupportModal');







        if (modal) {







            // Move modal to body if it's nested







            if (modal.parentElement !== document.body) {







                document.body.appendChild(modal);







            }







            modal.style.display = 'none';







        }







    }







    async handleResolveSubmit(e) {

        e.preventDefault();

        const formData = new FormData(e.target);

        const requestId = document.getElementById('resolveRequestId').value;

        

        // Disable submit button and show loading state

        const submitBtn = e.target.querySelector('button[type="submit"]');

        if (submitBtn) {

            submitBtn.disabled = true;

            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang giải quyết...';

        }

        

        // Show loading state

        this.showLoading('Đang giải quyết yêu cầu...');

        

        try {

                        console.log('Form data:', formData);

            console.log('Request ID:', requestId);

            

            // Create FormData for file upload

            const uploadFormData = new FormData();

            

            // Add form fields

            uploadFormData.append('id', requestId);

            uploadFormData.append('action', 'resolve');

            uploadFormData.append('error_description', formData.get('error_description'));

            uploadFormData.append('error_type', formData.get('error_type'));

            uploadFormData.append('replacement_materials', formData.get('replacement_materials'));

            uploadFormData.append('solution_method', formData.get('solution_method'));

            

            // Add files if any

            const fileInput = document.getElementById('resolveAttachments');

            if (fileInput && fileInput.files.length > 0) {

                console.log('Adding', fileInput.files.length, 'files to resolve upload');

                for (let i = 0; i < fileInput.files.length; i++) {

                    uploadFormData.append('attachments[]', fileInput.files[i]);

                }

            }

            

            const response = await this.apiCall('api/service_requests.php', {

                method: 'POST',

                body: uploadFormData

            });

            

            console.log('API Response:', response);

            console.log('Response success:', response.success);

            console.log('==========================');



            if (response.success) {

                // Force page refresh immediately to ensure latest data is displayed

                window.location.reload();

                // Show notification after page reload

                setTimeout(() => {

                    this.showNotification('Yêu cầu đã được giải quyết thành công', 'success');

                }, 100);

                this.closeResolveModal();

                this.loadRequestDetail();

            } else {

                this.showNotification(response.message, 'error');

                // Re-enable button if failed

                if (submitBtn) {

                    submitBtn.disabled = false;

                    submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Đã giải quyết';

                }

            }

        } catch (error) {

            console.error('Resolve submit error:', error);

            this.showNotification('Lỗi kết nối', 'error');

            // Re-enable button if error

            if (submitBtn) {

                submitBtn.disabled = false;

                submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Đã giải quyết';

            }

        } finally {

            this.hideLoading();

        }

    }



    async handleRejectSubmit(e) {

        e.preventDefault();

        const formData = new FormData(e.target);

        const requestId = document.getElementById('rejectRequestId').value;

        

        // Disable submit button and show loading state

        const submitBtn = e.target.querySelector('button[type="submit"]');

        if (submitBtn) {

            submitBtn.disabled = true;

            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang từ chối...';

        }

        

        // Show loading state

        this.showLoading('Đang từ chối yêu cầu...');

        

        try {

            console.log('Reject form data:', formData);

            console.log('Request ID:', requestId);

            

            // Create FormData for API call with file upload support

            const apiFormData = new FormData();

            apiFormData.append('request_id', requestId);

            apiFormData.append('action', 'reject_request');

            apiFormData.append('reject_reason', formData.get('reject_reason'));

            apiFormData.append('reject_details', formData.get('reject_details') || '');

            

            // Add files if any

            const fileInput = document.getElementById('rejectAttachments');

            console.log('File input element:', fileInput);

            console.log('Files in input:', fileInput ? fileInput.files : 'Input not found');

            console.log('Files length:', fileInput ? fileInput.files.length : 'N/A');

            

            if (fileInput && fileInput.files.length > 0) {

                console.log('Adding', fileInput.files.length, 'files to FormData...');

                console.log('File details:', Array.from(fileInput.files).map(f => ({name: f.name, size: f.size, type: f.type})));

                

                for (let i = 0; i < fileInput.files.length; i++) {

                    apiFormData.append('attachments[]', fileInput.files[i]);

                }

            } else {

                console.log('No files found to upload');

            }

            

            console.log('Sending FormData to service_requests API...');

            

            const response = await this.apiCall('api/service_requests.php', {

                method: 'POST',

                body: apiFormData

            });

            

            console.log('Reject API Response:', response);

            

            if (response.success) {

                const message = response.updated ? 'Yêu cầu từ chối đã được cập nhật' : 'Yêu cầu đã bị từ chối';

                this.showNotification(message, 'success');

                this.closeRejectModal();

                // Force page refresh to ensure latest data is displayed

                window.location.reload();

            } else {

                this.showNotification(response.message, 'error');

                // Re-enable button if failed

                if (submitBtn) {

                    submitBtn.disabled = false;

                    submitBtn.innerHTML = '<i class="fas fa-times"></i> Từ chối yêu cầu';

                }

            }

        } catch (error) {

            console.error('Reject submit error:', error);

            this.showNotification('Lỗi kết nối', 'error');

            // Re-enable button if error

            if (submitBtn) {

                submitBtn.disabled = false;

                submitBtn.innerHTML = '<i class="fas fa-times"></i> Từ chối yêu cầu';

            }

        } finally {

            this.hideLoading();

        }

    }



    async handleAdminSupportSubmit(e) {



        e.preventDefault();







        const formData = new FormData(e.target);







        const supportId = document.getElementById('adminSupportId').value;







        







        // Disable submit button and show loading state







        const submitBtn = e.target.querySelector('button[type="submit"]');







        if (submitBtn) {







            submitBtn.disabled = true;







            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';







        }







        







        // Show loading state







        this.showLoading('Đang xử lý yêu cầu hỗ trợ...');







        







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







                    if (this.currentUser && ['admin', 'staff'].includes(this.currentUser.role)) {







                        const decision = response.data.decision === 'approved' ? 'đã được phê duyệt' : 'đã bị từ chối';







                        const message = `📢 Yêu cầu hỗ trợ ${decision} bởi admin!`;







                        this.showNotification(message, response.data.decision === 'approved' ? 'success' : 'warning');







                    } else {







                        // Regular users see generic message







                        const message = '📢 Yêu cầu hỗ trợ của bạn đã được xử lý!';







                        this.showNotification(message, 'info');







                    }







                }







            } else {







                this.showNotification(response.message, 'error');







                // Re-enable button if failed







                if (submitBtn) {







                    submitBtn.disabled = false;







                    submitBtn.innerHTML = '<i class="fas fa-check"></i> Xác nhận quyết định';







                }







            }







        } catch (error) {







            this.showNotification('Lỗi kết nối', 'error');







            // Re-enable button if error







            if (submitBtn) {







                submitBtn.disabled = false;







                submitBtn.innerHTML = '<i class="fas fa-check"></i> Xác nhận quyết định';







            }







        } finally {







            this.hideLoading();







        }







    }







    async handleNeedSupportSubmit(e) {

        e.preventDefault();

        

        const formData = new FormData(e.target);

        const requestId = document.getElementById('supportRequestId').value;

        

        // Disable submit button and show loading state

        const submitBtn = e.target.querySelector('button[type="submit"]');

        if (submitBtn) {

            submitBtn.disabled = true;

            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi yêu cầu...';

        }

        

        // Show loading state

        this.showLoading('Đang gửi yêu cầu hỗ trợ...');

        

        try {

            // Create FormData for file upload

            const uploadFormData = new FormData();

            

            // Add form fields

            uploadFormData.append('service_request_id', requestId);

            uploadFormData.append('support_type', formData.get('support_type'));

            uploadFormData.append('support_details', formData.get('support_details'));

            uploadFormData.append('support_reason', formData.get('support_reason'));

            

            // Add files if any

            const fileInput = document.getElementById('supportAttachments');

            if (fileInput && fileInput.files.length > 0) {

                console.log('Adding', fileInput.files.length, 'files to support request upload');

                for (let i = 0; i < fileInput.files.length; i++) {

                    uploadFormData.append('attachments[]', fileInput.files[i]);

                }

            }

            

            const response = await this.apiCall('api/support_requests.php', {

                method: 'POST',

                body: uploadFormData

            });

            

            console.log('Support Request API Response:', response);

            

            if (response.success) {

                this.showNotification('Yêu cầu hỗ trợ đã được gửi thành công!', 'success');

                this.closeNeedSupportModal();

                // Add small delay to ensure server updates before reload

                setTimeout(() => {

                    // Force reload by resetting loading flag

                    this.isLoading = false;

                    this.loadRequestDetail();

                }, 500);

            } else {

                this.showNotification(response.message, 'error');

                // Re-enable button if failed

                if (submitBtn) {

                    submitBtn.disabled = false;

                    submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Gửi yêu cầu';

                }

            }

        } catch (error) {

            console.error('Support request submit error:', error);

            this.showNotification('Lỗi kết nối', 'error');

            // Re-enable button if error

            if (submitBtn) {

                submitBtn.disabled = false;

                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Gửi yêu cầu';

            }

        } finally {

            this.hideLoading();

        }

    }



    async loadSupportRequestDetails(supportId) {

        try {

            const response = await this.apiCall(`api/support_requests.php?action=get&id=${supportId}`);

            

            if (response.success) {

                const support = response.data;

                const container = document.getElementById('supportRequestDetails');







                







                container.innerHTML = `







                    <div class="support-request-info" style="background-color: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107; margin-bottom: 20px;">







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







                                <strong style="display: block; margin-bottom: 5px;">Lý do:</strong> 
                                <div style="background-color: #fff3cd; padding: 10px; border-radius: 5px; border-left: 4px solid #ffc107;">
                                    ${support.support_reason}
                                </div>







                            </div>







                            <div class="support-item">







                                <strong>Ngày tạo:</strong> ${this.formatDate(support.created_at)}







                            </div>







                            <div class="support-item">







                                <strong>Trạng thái:</strong> <span class="badge status-${support.status}" ${support.status === 'pending' ? 'style="background-color: #dc3545; color: white; font-weight: bold; padding: 6px 12px; border-radius: 6px; box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);"' : ''}>${this.getSupportStatusText(support.status)}</span>







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







        const modal = document.getElementById('closeRequestModal');







        if (modal) {







            // Move modal to body if it's nested







            if (modal.parentElement !== document.body) {







                document.body.appendChild(modal);







            }







            modal.style.display = 'none';







            // Reset form







            document.getElementById('closeRequestForm').reset();







        }







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







        







        // Disable submit button and show loading state







        const submitBtn = e.target.querySelector('button[type="submit"]');







        if (submitBtn) {







            submitBtn.disabled = true;







            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang đóng yêu cầu...';







        }







        







        // Show loading state







        this.showLoading('Đang đóng yêu cầu...');







        







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

                

                // Submit feedback to database if provided

                const wouldRecommend = formData.get('would_recommend');

                if (wouldRecommend && this.currentUser) {

                    try {

                        const feedbackData = {

                            service_request_id: requestId,

                            rating: parseInt(wouldRecommend),

                            feedback: formData.get('feedback_service') || '',

                            software_feedback: formData.get('feedback_software') || '',

                            ease_of_use: formData.get('ease_of_use') ? parseInt(formData.get('ease_of_use')) : null,

                            speed_stability: formData.get('speed_stability') ? parseInt(formData.get('speed_stability')) : null,

                            requirement_meeting: formData.get('requirement_meeting') ? parseInt(formData.get('requirement_meeting')) : null,

                            created_by: this.currentUser.id

                        };

                        

                        console.log('Submitting detailed feedback on close:', feedbackData);

                        

                        const feedbackResponse = await this.apiCall('api/feedback.php', {

                            method: 'POST',

                            headers: {

                                'Content-Type': 'application/json'

                            },

                            body: JSON.stringify(feedbackData)

                        });

                        

                        console.log('Feedback response:', feedbackResponse);

                        

                        if (feedbackResponse.success) {

                            console.log('Feedback saved successfully');

                        } else {

                            console.warn('Failed to save feedback:', feedbackResponse.message);

                        }

                    } catch (feedbackError) {

                        console.error('Error submitting feedback:', feedbackError);

                        // Don't fail the whole process if feedback fails

                    }

                }



                this.showNotification('Yêu cầu đã được đóng thành công!', 'success');



                this.closeCloseRequestModal();



                



                // Reload entire page to refresh all data







                setTimeout(() => {







                    window.location.reload();







                }, 1500);







            } else {







                console.log('API Error:', response.message);







                this.showNotification(response.message, 'error');







                // Re-enable button if failed







                if (submitBtn) {







                    submitBtn.disabled = false;







                    submitBtn.innerHTML = '<i class="fas fa-times"></i> Đóng yêu cầu';







                }







            }







        } catch (error) {







            console.error('Close request error:', error);







            this.showNotification('Lỗi đóng yêu cầu', 'error');







            // Re-enable button if error







            if (submitBtn) {







                submitBtn.disabled = false;







                submitBtn.innerHTML = '<i class="fas fa-times"></i> Đóng yêu cầu';







            }







        } finally {







            this.hideLoading();







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







            'rejected': this.currentUser && ['admin', 'staff'].includes(this.currentUser.role) ? 'Đã từ chối' : 'Đã xử lý',







            'closed': 'Đã đóng',







            'cancelled': 'Đã hủy',







            'request_support': 'Cần hỗ trợ'







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















    // File Upload Functions







    handleFileUpload(event, previewContainerId) {







        const files = event.target.files;







        const previewContainer = document.getElementById(previewContainerId);







        







        if (files.length === 0) {







            previewContainer.innerHTML = '<div class="no-files">Chưa có tệp nào được chọn</div>';







            previewContainer.classList.remove('has-files');







            return;







        }







        







        previewContainer.innerHTML = '';







        previewContainer.classList.add('has-files');







        







        Array.from(files).forEach((file, index) => {







            const fileItem = document.createElement('div');







            fileItem.className = 'attachment-item';







            







            // Check file size (10MB limit)







            if (file.size > 10 * 1024 * 1024) {







                fileItem.style.backgroundColor = '#f8d7da';







                fileItem.innerHTML = `







                    <div class="attachment-info">







                        <i class="fas fa-exclamation-triangle attachment-icon"></i>







                        <div>







                            <div class="attachment-name">${file.name}</div>







                            <div class="attachment-size" style="color: #dc3545;">Kích thước vượt quá giới hạn (tối đa 10MB)</div>







                        </div>







                    </div>







                    <button type="button" class="attachment-remove" onclick="this.parentElement.remove()">







                        <i class="fas fa-times"></i>







                    </button>







                `;







            } else {







                const icon = this.getFileIcon(file.name);







                fileItem.innerHTML = `







                    <div class="attachment-info">







                        <i class="fas ${icon} attachment-icon"></i>







                        <div>







                            <div class="attachment-name">${file.name}</div>







                            <div class="attachment-size">${this.formatFileSize(file.size)}</div>







                        </div>







                    </div>







                    <button type="button" class="attachment-remove" onclick="this.parentElement.remove()">







                        <i class="fas fa-times"></i>







                    </button>







                `;







            }







            







            previewContainer.appendChild(fileItem);







        });







    }







    







    getFileIcon(filename) {







        const ext = filename.split('.').pop().toLowerCase();







        const iconMap = {







            'jpg': 'fa-image', 'jpeg': 'fa-image', 'png': 'fa-image', 'gif': 'fa-image',







            'pdf': 'fa-file-pdf', 'doc': 'fa-file-word', 'docx': 'fa-file-word',







            'xls': 'fa-file-excel', 'xlsx': 'fa-file-excel',







            'ppt': 'fa-file-powerpoint', 'pptx': 'fa-file-powerpoint',







            'txt': 'fa-file-alt', 'zip': 'fa-file-archive'







        };







        return iconMap[ext] || 'fa-file';







    }







    







    formatFileSize(bytes) {







        if (bytes === 0) return '0 Bytes';







        const k = 1024;







        const sizes = ['Bytes', 'KB', 'MB', 'GB'];







        const i = Math.floor(Math.log(bytes) / Math.log(k));







        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];







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

        

        // Don't set Content-Type for FormData - let browser set it automatically

        if (finalOptions.body instanceof FormData) {

            delete finalOptions.headers['Content-Type'];

        }

        

        if (finalOptions.body && typeof finalOptions.body === 'object' && !(finalOptions.body instanceof FormData)) {

            finalOptions.body = JSON.stringify(finalOptions.body);

        }

        

        const response = await fetch(url, finalOptions);

        

        // Check if response is OK before parsing JSON

        if (!response.ok) {

            throw new Error(`HTTP ${response.status}: ${response.statusText}`);

        }

        

        const text = await response.text();

        

        // Try to parse JSON, handle errors gracefully

        try {

            return JSON.parse(text);

        } catch (parseError) {

            console.error('JSON Parse Error:', parseError);

            console.error('Response Text:', text);

            throw new Error(`Invalid JSON response: ${parseError.message}`);

        }

    }







    // Reject Request Functions







    async checkRejectRequestStatus() {

        try {

            const response = await this.apiCall(`api/reject_requests.php?action=check_status&service_request_id=${this.requestId}`);

            

            if (response.success && response.data) {

                this.rejectRequestStatus = response.data;

                

                // Thông báo cho staff nếu admin đã quyết định

                if (this.rejectRequestStatus.status !== 'pending') {

                    if (this.currentUser && ['admin', 'staff'].includes(this.currentUser.role)) {

                        const decision = this.rejectRequestStatus.status === 'approved' ? 'được đồng ý' : 'bị từ chối';

                        const message = `📢 Yêu cầu từ chối đã ${decision} bởi admin!`;

                        this.showNotification(message, this.rejectRequestStatus.status === 'approved' ? 'success' : 'warning');

                    } else {

                        // Regular users see generic message

                        const message = '📢 Yêu cầu từ chối của bạn đã được xử lý!';

                        this.showNotification(message, 'info');

                    }

                }

            } else {

                this.rejectRequestStatus = null;

            }

        } catch (error) {

            console.error('Error checking reject request status:', error);

            this.rejectRequestStatus = null;

        }

    };



    // Support Request Functions



    async checkSupportRequestStatus() {

        try {

            const response = await this.apiCall(`api/support_requests.php?action=check_status&service_request_id=${this.requestId}`);

            

            if (response.success && response.data) {

                this.supportRequestStatus = response.data;

                

                // Thông báo cho staff nếu admin đã quyết định

                if (this.supportRequestStatus.status !== 'pending') {

                    if (this.currentUser && ['admin', 'staff'].includes(this.currentUser.role)) {

                        const decision = this.supportRequestStatus.status === 'approved' ? 'được đồng ý' : 'bị từ chối';

                        const message = `📢 Yêu cầu hỗ trợ đã ${decision} bởi admin!`;

                        this.showNotification(message, this.supportRequestStatus.status === 'approved' ? 'success' : 'warning');

                    } else {

                        // Regular users see generic message

                        const message = '📢 Yêu cầu hỗ trợ của bạn đã được xử lý!';

                        this.showNotification(message, 'info');

                    }

                }

            } else {

                this.supportRequestStatus = null;

            }

        } catch (error) {

            console.error('Error checking support request status:', error);

            this.supportRequestStatus = null;

        }

    }









    closeAdminRejectModal() {







        const modal = document.getElementById('adminRejectModal');







        if (modal) {







            modal.style.display = 'none';







        }







    }















    goBack() {







        // Check if we have history to go back to







        if (window.history.length > 1) {







            // If we have history, go back







            window.history.back();







        } else {







            // If no history (opened in new tab), go to main page







            window.location.href = 'index.html';







        }







    }















    showLoading(message = 'Đang xử lý...') {







        // Create or update loading overlay







        let loadingOverlay = document.getElementById('loadingOverlay');







        if (!loadingOverlay) {







            loadingOverlay = document.createElement('div');







            loadingOverlay.id = 'loadingOverlay';







            loadingOverlay.style.cssText = `







                position: fixed;







                top: 0;







                left: 0;







                width: 100%;







                height: 100%;







                background: rgba(0, 0, 0, 0.5);







                display: flex;







                justify-content: center;







                align-items: center;







                z-index: 99999;







                backdrop-filter: blur(3px);







            `;







            







            const loadingContent = document.createElement('div');







            loadingContent.style.cssText = `







                background: white;







                padding: 30px;







                border-radius: 10px;







                text-align: center;







                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);







            `;







            







            const spinner = document.createElement('div');







            spinner.style.cssText = `







                border: 4px solid #f3f3f3;







                border-top: 4px solid #007bff;







                border-radius: 50%;







                width: 40px;







                height: 40px;







                animation: spin 1s linear infinite;







                margin: 0 auto 15px;







            `;







            







            const loadingText = document.createElement('div');







            loadingText.id = 'loadingText';







            loadingText.style.cssText = `







                font-size: 16px;







                color: #333;







                font-weight: 500;







            `;







            







            loadingContent.appendChild(spinner);







            loadingContent.appendChild(loadingText);







            loadingOverlay.appendChild(loadingContent);







            document.body.appendChild(loadingOverlay);







            







            // Add CSS animation







            const style = document.createElement('style');







            style.textContent = `







                @keyframes spin {







                    0% { transform: rotate(0deg); }







                    100% { transform: rotate(360deg); }







                }







            `;







            document.head.appendChild(style);







        }







        







        document.getElementById('loadingText').textContent = message;







        loadingOverlay.style.display = 'flex';







    }















    hideLoading() {







        const loadingOverlay = document.getElementById('loadingOverlay');







        if (loadingOverlay) {







            loadingOverlay.style.display = 'none';







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



// Initialize request detail app when DOM is loaded

document.addEventListener('DOMContentLoaded', () => {

    console.log('DOM loaded, initializing request detail app...');

    

    try {

        window.requestDetailApp = new RequestDetailApp();

        console.log('requestDetailApp created:', window.requestDetailApp);

        console.log('showRejectModal method exists:', typeof window.requestDetailApp.showRejectModal);

    } catch (error) {

        console.error('Error creating requestDetailApp:', error);

    }

    

    // Wait for translation system to be ready

    setTimeout(() => {

        if (window.t && window.translationSystem) {

            console.log('Translation system is ready');

        } else {

            console.log('Translation system not ready yet');

        }

    }, 1000);

});

