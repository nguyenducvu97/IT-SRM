class NotificationManager {
    constructor() {
        this.notificationBtn = null;
        this.notificationDropdown = null;
        this.notificationCount = null;
        this.notificationList = null;
        this.markAllReadBtn = null;
        this.notifications = [];
        this.unreadCount = 0;
        this.isDropdownOpen = false;
        this.refreshInterval = null;
        
        this.init();
    }
    
    init() {
        console.log('NotificationManager: Initializing...');
        
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupElements());
        } else {
            this.setupElements();
        }
    }
    
    setupElements() {
        console.log('NotificationManager: Setting up elements...');
        
        this.notificationBtn = document.getElementById('notificationBtn');
        this.notificationDropdown = document.getElementById('notificationDropdown');
        this.notificationCount = document.getElementById('notificationCount');
        this.notificationList = document.getElementById('notificationList');
        this.markAllReadBtn = document.getElementById('markAllReadBtn');
        
        console.log('NotificationManager: Elements found:', {
            btn: !!this.notificationBtn,
            dropdown: !!this.notificationDropdown,
            count: !!this.notificationCount,
            list: !!this.notificationList,
            markAllBtn: !!this.markAllReadBtn
        });
        
        if (this.notificationBtn) {
            this.notificationBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleDropdown();
            });
        }
        
        if (this.markAllReadBtn) {
            this.markAllReadBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.markAllAsRead();
            });
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (this.isDropdownOpen && !this.notificationDropdown.contains(e.target)) {
                this.closeDropdown();
            }
        });
        
        // Load initial notifications
        console.log('NotificationManager: Loading initial notifications...');
        this.loadNotifications();
        
        // Set up auto-refresh every 30 seconds
        this.startAutoRefresh();
    }
    
    toggleDropdown() {
        if (this.isDropdownOpen) {
            this.closeDropdown();
        } else {
            this.openDropdown();
        }
    }
    
    openDropdown() {
        this.notificationDropdown.classList.add('show');
        this.isDropdownOpen = true;
        
        // Load latest notifications when opening
        this.loadNotifications();
    }
    
    closeDropdown() {
        this.notificationDropdown.classList.remove('show');
        this.isDropdownOpen = false;
    }
    
    async loadNotifications() {
        try {
            console.log('NotificationManager: Loading notifications...');
            
            // Load notifications list
            const response = await fetch('api/notifications.php?action=get');
            console.log('NotificationManager: Response status:', response.status);
            
            if (!response.ok) throw new Error('Failed to load notifications');
            
            const data = await response.json();
            if (data.success && data.data) {
                this.notifications = data.data;
            } else {
                this.notifications = [];
            }
            console.log('NotificationManager: Loaded notifications:', this.notifications);
            
            this.renderNotifications();
            
            // Load unread count
            await this.updateNotificationCount();
            
        } catch (error) {
            console.error('Error loading notifications:', error);
        }
    }
    
    async updateNotificationCount() {
        try {
            console.log('NotificationManager: Updating notification count...');
            
            const response = await fetch('api/notifications.php?action=count');
            console.log('NotificationManager: Count response status:', response.status);
            
            if (!response.ok) throw new Error('Failed to get notification count');
            
            const data = await response.json();
            console.log('NotificationManager: Count response data:', data);
            
            this.unreadCount = data.data ? data.data.unread_count : 0;
            console.log('NotificationManager: Unread count set to:', this.unreadCount);
            
            this.renderNotificationCount();
            
        } catch (error) {
            console.error('Error updating notification count:', error);
        }
    }
    
    renderNotificationCount() {
        console.log('NotificationManager: Rendering notification count...');
        console.log('NotificationManager: Elements:', {
            notificationCount: !!this.notificationCount,
            unreadCount: this.unreadCount
        });
        
        // Debug: Count unread from DOM
        if (this.notificationList) {
            const unreadItems = this.notificationList.querySelectorAll('.notification-item.unread');
            const domUnreadCount = unreadItems.length;
            console.log('NotificationManager: DOM unread count:', domUnreadCount);
            console.log('NotificationManager: API unread count:', this.unreadCount);
            
            // Use DOM count if API count is 0 but DOM has unread items
            if (this.unreadCount === 0 && domUnreadCount > 0) {
                this.unreadCount = domUnreadCount;
                console.log('NotificationManager: Using DOM count instead of API count');
            }
        }
        
        if (this.notificationCount) {
            if (this.unreadCount > 0) {
                this.notificationCount.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
                this.notificationCount.classList.remove('empty');
                console.log('NotificationManager: Set count to:', this.unreadCount);
            } else {
                this.notificationCount.textContent = '0';
                this.notificationCount.classList.add('empty');
                console.log('NotificationManager: Set count to 0');
            }
        } else {
            console.log('NotificationManager: notificationCount element not found!');
        }
    }
    
    renderNotifications() {
        if (!this.notificationList) return;
        
        // Ensure notifications is an array
        if (!Array.isArray(this.notifications)) {
            console.error('NotificationManager: notifications is not an array:', this.notifications);
            this.notifications = [];
        }
        
        if (this.notifications.length === 0) {
            this.notificationList.innerHTML = `
                <div class="notification-empty">
                    <i class="fas fa-bell-slash"></i>
                    <p>Không có thông báo nào</p>
                </div>
            `;
            return;
        }
        
        const notificationsHtml = this.notifications.map(notification => `
            <div class="notification-item ${!notification.is_read ? 'unread' : ''}" 
                 data-notification-id="${notification.id}"
                 onclick="window.notificationManager.handleNotificationClick(${notification.id})"
                 style="cursor: pointer;">
                <div class="notification-title">
                    ${notification.title}
                    <span class="notification-type ${notification.type}">${this.getTypeText(notification.type)}</span>
                </div>
                <div class="notification-message">${notification.message}</div>
                <div class="notification-time">${notification.time_ago}</div>
            </div>
        `).join('');
        
        this.notificationList.innerHTML = notificationsHtml;
        
        // Update notification count after rendering
        this.renderNotificationCount();
    }
    
    getTypeText(type) {
        const labels = {
            'info': 'Thông tin',
            'success': 'Thành công',
            'warning': 'Cảnh báo',
            'error': 'Lỗi'
        };
        return labels[type] || 'Thông tin';
    }
    
    async handleNotificationClick(notificationId) {
        console.log('NotificationManager: Handling click for notification ID:', notificationId);
        
        const notification = this.notifications.find(n => n.id == notificationId);
        if (!notification) {
            console.error('NotificationManager: Notification not found:', notificationId);
            return;
        }
        
        console.log('NotificationManager: Found notification:', notification);
        
        // Mark as read if unread
        if (!notification.is_read) {
            console.log('NotificationManager: Marking as read...');
            await this.markAsRead(notificationId);
        }
        
        // Handle navigation based on related_type
        if (notification.related_type && notification.related_id) {
            console.log('NotificationManager: Navigating to:', notification.related_type, notification.related_id);
            this.navigateToRelatedItem(notification.related_type, notification.related_id);
        } else {
            console.log('NotificationManager: No navigation data found');
        }
        
        // Close dropdown
        this.closeDropdown();
    }
    
    async markAsRead(notificationId) {
        try {
            const response = await fetch('api/notifications.php?action=mark_read', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    notification_id: notificationId
                })
            });
            
            if (!response.ok) throw new Error('Failed to mark notification as read');
            
            // Update local data
            const notification = this.notifications.find(n => n.id == notificationId);
            if (notification) {
                notification.is_read = true;
                notification.read_at = new Date().toISOString();
            }
            
            // Update UI
            this.renderNotifications();
            await this.updateNotificationCount();
            
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }
    
    async markAllAsRead() {
        try {
            const response = await fetch('api/notifications.php?action=mark_all_read', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                }
            });
            
            if (!response.ok) throw new Error('Failed to mark all notifications as read');
            
            // Update local data
            this.notifications.forEach(notification => {
                notification.is_read = true;
                notification.read_at = new Date().toISOString();
            });
            
            // Update UI
            this.renderNotifications();
            await this.updateNotificationCount();
            
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
        }
    }
    
    navigateToRelatedItem(type, id) {
        console.log('NotificationManager: Navigating to related item:', type, id);
        
        switch (type) {
            case 'request':
            case 'service_request':
                // Navigate to request detail page
                const requestUrl = `request-detail.html?id=${id}`;
                console.log('NotificationManager: Navigating to request:', requestUrl);
                window.location.href = requestUrl;
                break;
            case 'comment':
                // Navigate to request with comment
                const commentUrl = `request-detail.html?id=${id}#comments`;
                console.log('NotificationManager: Navigating to comment:', commentUrl);
                window.location.href = commentUrl;
                break;
            case 'assignment':
            case 'resolution':
                // Navigate to request
                const assignmentUrl = `request-detail.html?id=${id}`;
                console.log('NotificationManager: Navigating to assignment:', assignmentUrl);
                window.location.href = assignmentUrl;
                break;
            default:
                console.log('NotificationManager: Unknown type, defaulting to dashboard:', type);
                // Default to dashboard
                window.location.href = 'index.html';
        }
    }
    
    startAutoRefresh() {
        // Refresh notification count every 30 seconds
        this.refreshInterval = setInterval(() => {
            this.updateNotificationCount();
        }, 30000);
    }
    
    stopAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }
    
    // Public method to create new notification (for real-time updates)
    async createNotification(userId, title, message, type = 'info', relatedId = null, relatedType = null) {
        try {
            const response = await fetch('api/notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: userId,
                    title: title,
                    message: message,
                    type: type,
                    related_id: relatedId,
                    related_type: relatedType
                })
            });
            
            if (!response.ok) throw new Error('Failed to create notification');
            
            // Refresh notifications
            await this.loadNotifications();
            
        } catch (error) {
            console.error('Error creating notification:', error);
        }
    }
    
    // Clean up when page is unloaded
    destroy() {
        this.stopAutoRefresh();
        document.removeEventListener('click', this.closeDropdown);
    }
    
    // Debug function to force show count
    forceShowCount(count) {
        console.log('NotificationManager: Force showing count:', count);
        this.unreadCount = count;
        this.renderNotificationCount();
    }
    
    // Debug function to check elements
    debugElements() {
        console.log('NotificationManager: Debug Elements:', {
            notificationBtn: !!this.notificationBtn,
            notificationDropdown: !!this.notificationDropdown,
            notificationCount: !!this.notificationCount,
            notificationList: !!this.notificationList,
            markAllReadBtn: !!this.markAllReadBtn,
            countElement: this.notificationCount ? this.notificationCount.textContent : 'N/A',
            countVisible: this.notificationCount ? this.notificationCount.style.display : 'N/A'
        });
    }
}

// Note: NotificationManager instances are created in individual HTML pages
