/**
 * Advanced Notification Manager with Browser Push Notifications and Sound Alerts
 */
class AdvancedNotificationManager {
    constructor() {
        this.notificationCount = null;
        this.notificationList = null;
        this.markAllReadBtn = null;
        this.notifications = [];
        this.unreadCount = 0;
        this.isDropdownOpen = false;
        this.refreshInterval = null;
        this.soundEnabled = true;
        this.desktopNotificationsEnabled = true;
        this.notificationSound = new Audio('assets/sounds/notification.mp3');
        
        this.init();
    }
    
    init() {
        // Check for browser notification support
        if ('Notification' in window) {
            // Request permission for desktop notifications
            this.requestNotificationPermission();
        }
        
        // Load user preferences
        this.loadUserPreferences();
        
        // Initialize DOM elements
        this.initializeElements();
        
        // Bind events
        this.bindEvents();
        
        // Load initial notifications
        console.log('AdvancedNotificationManager: Loading initial notifications...');
        this.loadNotifications();
        
        // Set up auto-refresh every 15 seconds (faster for real-time feel)
        this.startAutoRefresh();
        
        // Set up Service Worker for background notifications
        this.setupServiceWorker();
    }
    
    initializeElements() {
        this.notificationCount = document.getElementById('notificationCount');
        this.notificationList = document.getElementById('notificationList');
        this.markAllReadBtn = document.getElementById('markAllReadBtn');
    }
    
    bindEvents() {
        // Notification bell click
        const notificationBell = document.getElementById('notificationBell');
        if (notificationBell) {
            notificationBell.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleDropdown();
            });
        }
        
        // Mark all as read
        if (this.markAllReadBtn) {
            this.markAllReadBtn.addEventListener('click', () => this.markAllAsRead());
        }
        
        // Settings buttons
        const soundToggle = document.getElementById('notificationSoundToggle');
        if (soundToggle) {
            soundToggle.addEventListener('change', (e) => {
                this.soundEnabled = e.target.checked;
                this.saveUserPreferences();
            });
        }
        
        const desktopToggle = document.getElementById('desktopNotificationToggle');
        if (desktopToggle) {
            desktopToggle.addEventListener('change', (e) => {
                this.desktopNotificationsEnabled = e.target.checked;
                this.saveUserPreferences();
            });
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.notification-dropdown')) {
                this.closeDropdown();
            }
        });
    }
    
    async requestNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            try {
                const permission = await Notification.requestPermission();
                console.log('Notification permission:', permission);
                this.desktopNotificationsEnabled = permission === 'granted';
                this.saveUserPreferences();
            } catch (error) {
                console.error('Error requesting notification permission:', error);
            }
        }
    }
    
    setupServiceWorker() {
        // Register service worker for background notifications
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
                .then(registration => {
                    console.log('Service Worker registered');
                })
                .catch(error => {
                    console.error('Service Worker registration failed:', error);
                });
        }
    }
    
    toggleDropdown() {
        if (this.isDropdownOpen) {
            this.closeDropdown();
        } else {
            this.openDropdown();
        }
    }
    
    openDropdown() {
        if (this.notificationDropdown) {
            this.notificationDropdown.classList.add('show');
            this.isDropdownOpen = true;
            
            // Load latest notifications when opening
            this.loadNotifications();
        }
    }
    
    closeDropdown() {
        if (this.notificationDropdown) {
            this.notificationDropdown.classList.remove('show');
            this.isDropdownOpen = false;
        }
    }
    
    startAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
        
        // Refresh every 15 seconds for more real-time feel
        this.refreshInterval = setInterval(() => {
            this.loadNotifications();
        }, 15000);
    }
    
    async loadNotifications() {
        try {
            console.log('AdvancedNotificationManager: Loading notifications...');
            
            // Load notifications list
            const response = await fetch('api/notifications.php?action=list');
            console.log('AdvancedNotificationManager: Response status:', response.status);
            
            if (!response.ok) throw new Error('Failed to load notifications');
            
            this.notifications = await response.json();
            console.log('AdvancedNotificationManager: Loaded notifications:', this.notifications);
            
            this.renderNotifications();
            
            // Load unread count
            await this.updateNotificationCount();
            
        } catch (error) {
            console.error('Error loading notifications:', error);
        }
    }
    
    async updateNotificationCount() {
        try {
            console.log('AdvancedNotificationManager: Updating notification count...');
            
            const response = await fetch('api/notifications.php?action=count');
            console.log('AdvancedNotificationManager: Count response status:', response.status);
            
            if (!response.ok) throw new Error('Failed to get notification count');
            
            const data = await response.json();
            this.unreadCount = data.count;
            
            this.updateNotificationBadge();
            
        } catch (error) {
            console.error('Error updating notification count:', error);
        }
    }
    
    updateNotificationBadge() {
        if (this.notificationCount) {
            this.notificationCount.textContent = this.unreadCount;
            this.notificationCount.style.display = this.unreadCount > 0 ? 'block' : 'none';
            
            // Add animation for new notifications
            if (this.unreadCount > 0) {
                this.notificationCount.classList.add('pulse');
                setTimeout(() => {
                    this.notificationCount.classList.remove('pulse');
                }, 1000);
            }
        }
    }
    
    renderNotifications() {
        if (!this.notificationList) return;
        
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
                 data-notification-id="${notification.id}">
                <div class="notification-content">
                    <div class="notification-header">
                        <span class="notification-type ${notification.type}">
                            ${this.getTypeIcon(notification.type)}
                        </span>
                        <span class="notification-time">${this.formatTime(notification.created_at)}</span>
                    </div>
                    <div class="notification-body">
                        <h4 class="notification-title">${notification.title}</h4>
                        <p class="notification-message">${notification.message}</p>
                    </div>
                </div>
                <div class="notification-actions">
                    ${notification.related_id ? `
                        <button class="btn btn-sm btn-primary" onclick="window.location.href='request-detail.html?id=${notification.related_id}'">
                            <i class="fas fa-external-link-alt"></i> Xem
                        </button>
                    ` : ''}
                    <button class="btn btn-sm btn-secondary" onclick="notificationManager.markAsRead(${notification.id})">
                        <i class="fas fa-check"></i> Đọc
                    </button>
                </div>
            </div>
        `).join('');
        
        this.notificationList.innerHTML = notificationsHtml;
        
        // Add click handlers to notification items
        this.notificationList.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', (e) => {
                if (!e.target.closest('.notification-actions')) {
                    const notificationId = item.dataset.notificationId;
                    this.handleNotificationClick(notificationId);
                }
            });
        });
    }
    
    getTypeIcon(type) {
        const icons = {
            'info': '🔵',
            'success': '🟢',
            'warning': '🟡',
            'error': '🔴'
        };
        return icons[type] || '🔵';
    }
    
    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);
        
        if (diffMins < 1) return 'Vừa xong';
        if (diffMins < 60) return `${diffMins} phút trước`;
        if (diffHours < 24) return `${diffHours} giờ trước`;
        if (diffDays < 7) return `${diffDays} ngày trước`;
        return date.toLocaleDateString('vi-VN');
    }
    
    async handleNotificationClick(notificationId) {
        const notification = this.notifications.find(n => n.id == notificationId);
        if (!notification) return;
        
        // Mark as read if unread
        if (!notification.is_read) {
            await this.markAsRead(notificationId);
        }
        
        // Navigate to related content if available
        if (notification.related_id) {
            switch (notification.related_type) {
                case 'request':
                case 'service_request':
                    window.location.href = `request-detail.html?id=${notification.related_id}`;
                    break;
                case 'support_request':
                    window.location.href = 'support-requests.html';
                    break;
                case 'reject_request':
                    window.location.href = 'reject-requests.html';
                    break;
            }
        }
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
    
    showDesktopNotification(title, message, type = 'info', relatedId = null) {
        if (!this.desktopNotificationsEnabled || !('Notification' in window)) {
            return;
        }
        
        if (Notification.permission !== 'granted') {
            return;
        }
        
        try {
            const notification = new Notification(title, {
                body: message,
                icon: '/assets/images/favicon.png',
                badge: '/assets/images/badge.png',
                tag: `notification-${relatedId || Date.now()}`,
                requireInteraction: false,
                silent: !this.soundEnabled
            });
            
            // Auto-close after 5 seconds
            setTimeout(() => {
                notification.close();
            }, 5000);
            
            // Click handler
            notification.onclick = () => {
                if (relatedId) {
                    window.location.href = `request-detail.html?id=${relatedId}`;
                } else {
                    window.focus();
                }
                notification.close();
            };
            
        } catch (error) {
            console.error('Error showing desktop notification:', error);
        }
    }
    
    playNotificationSound(type = 'info') {
        if (!this.soundEnabled) return;
        
        try {
            // Play different sounds based on type
            const soundFiles = {
                'info': 'assets/sounds/notification.mp3',
                'success': 'assets/sounds/success.mp3',
                'warning': 'assets/sounds/warning.mp3',
                'error': 'assets/sounds/error.mp3'
            };
            
            const soundFile = soundFiles[type] || soundFiles['info'];
            const audio = new Audio(soundFile);
            audio.volume = 0.3;
            audio.play().catch(error => {
                console.log('Could not play notification sound:', error);
            });
            
        } catch (error) {
            console.error('Error playing notification sound:', error);
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
            
            // Show desktop notification for immediate feedback
            this.showDesktopNotification(title, message, type, relatedId);
            
            // Play sound
            this.playNotificationSound(type);
            
            // Refresh notifications
            await this.loadNotifications();
            
        } catch (error) {
            console.error('Error creating notification:', error);
        }
    }
    
    loadUserPreferences() {
        try {
            const preferences = localStorage.getItem('notificationPreferences');
            if (preferences) {
                const prefs = JSON.parse(preferences);
                this.soundEnabled = prefs.soundEnabled !== false;
                this.desktopNotificationsEnabled = prefs.desktopNotificationsEnabled !== false;
                
                // Update UI
                const soundToggle = document.getElementById('notificationSoundToggle');
                const desktopToggle = document.getElementById('desktopNotificationToggle');
                
                if (soundToggle) soundToggle.checked = this.soundEnabled;
                if (desktopToggle) desktopToggle.checked = this.desktopNotificationsEnabled;
            }
        } catch (error) {
            console.error('Error loading user preferences:', error);
        }
    }
    
    saveUserPreferences() {
        try {
            const preferences = {
                soundEnabled: this.soundEnabled,
                desktopNotificationsEnabled: this.desktopNotificationsEnabled
            };
            localStorage.setItem('notificationPreferences', JSON.stringify(preferences));
        } catch (error) {
            console.error('Error saving user preferences:', error);
        }
    }
    
    // Method to check for new notifications and show alerts
    async checkForNewNotifications() {
        const currentCount = this.unreadCount;
        await this.updateNotificationCount();
        
        if (this.unreadCount > currentCount) {
            // New notifications arrived
            this.playNotificationSound('info');
            
            // Show browser notification if page is not visible
            if (document.hidden) {
                this.showDesktopNotification(
                    `Bạn có ${this.unreadCount - currentCount} thông báo mới`,
                    'Vui lòng kiểm tra hệ thống IT Service Request',
                    'info'
                );
            }
        }
    }
}

// Initialize the advanced notification manager
document.addEventListener('DOMContentLoaded', function() {
    window.notificationManager = new AdvancedNotificationManager();
    
    // Check for new notifications every 10 seconds
    setInterval(() => {
        window.notificationManager.checkForNewNotifications();
    }, 10000);
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdvancedNotificationManager;
}
