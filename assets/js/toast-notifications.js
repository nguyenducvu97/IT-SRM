/**
 * Toast Notification Manager - Beautiful, smooth notifications
 */
class ToastNotificationManager {
    constructor(options = {}) {
        this.container = document.getElementById('toastContainer');
        this.toasts = new Map();
        this.toastId = 0;
        
        // Configuration options
        this.options = {
            position: options.position || 'top-center', // 'top-center' or 'top-right'
            defaultDuration: 5000, // 5 seconds
            maxToasts: 5
        };
        
        // Initialize container if not exists
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'toastContainer';
            this.container.className = 'toast-container';
            if (this.options.position === 'top-right') {
                this.container.classList.add('top-right');
            }
            document.body.appendChild(this.container);
        } else {
            // Update existing container class
            if (this.options.position === 'top-right') {
                this.container.classList.add('top-right');
            } else {
                this.container.classList.remove('top-right');
            }
        }
    }
    
    /**
     * Show a toast notification
     * @param {string} message - The message to display
     * @param {string} type - Type of notification (success, error, warning, info)
     * @param {string} title - Optional title
     * @param {number} duration - Duration in milliseconds (auto-hide)
     * @param {boolean} persistent - If true, won't auto-hide
     */
    show(message, type = 'info', title = null, duration = null, persistent = false) {
        const toastId = ++this.toastId;
        
        // Don't show too many toasts at once
        if (this.toasts.size >= this.options.maxToasts) {
            const oldestToast = this.toasts.keys().next().value;
            this.remove(oldestToast);
        }
        
        // Create toast element
        const toast = this.createToast(toastId, message, type, title);
        
        // Add to container
        this.container.appendChild(toast);
        this.toasts.set(toastId, toast);
        
        // Auto-hide if not persistent
        if (!persistent) {
            const hideDuration = duration || this.options.defaultDuration;
            setTimeout(() => {
                this.remove(toastId);
            }, hideDuration);
        }
        
        return toastId;
    }
    
    /**
     * Create toast element
     */
    createToast(id, message, type, title) {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.dataset.toastId = id;
        
        // Icon based on type
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };
        
        // Titles based on type (if no custom title)
        const defaultTitles = {
            success: 'Thành công',
            error: 'Lỗi',
            warning: 'Cảnh báo',
            info: 'Thông báo'
        };
        
        toast.innerHTML = `
            <div class="toast-icon">
                <i class="${icons[type]}"></i>
            </div>
            <div class="toast-content">
                ${title ? `<div class="toast-title">${title}</div>` : ''}
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" onclick="toastManager.remove(${id})">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        return toast;
    }
    
    /**
     * Remove toast by ID
     */
    remove(toastId) {
        const toast = this.toasts.get(toastId);
        if (toast) {
            toast.classList.add('removing');
            
            // Remove from DOM after animation
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
                this.toasts.delete(toastId);
            }, 300);
        }
    }
    
    /**
     * Clear all toasts
     */
    clear() {
        this.toasts.forEach((toast, id) => {
            this.remove(id);
        });
    }
    
    /**
     * Show success notification
     */
    success(message, title = null, duration = null) {
        return this.show(message, 'success', title || 'Thành công', duration);
    }
    
    /**
     * Show error notification
     */
    error(message, title = null, duration = null) {
        return this.show(message, 'error', title || 'Lỗi', duration);
    }
    
    /**
     * Show warning notification
     */
    warning(message, title = null, duration = null) {
        return this.show(message, 'warning', title || 'Cảnh báo', duration);
    }
    
    /**
     * Show info notification
     */
    info(message, title = null, duration = null) {
        return this.show(message, 'info', title || 'Thông báo', duration);
    }
    
    /**
     * Show login success notification
     */
    loginSuccess(username) {
        return this.success(
            `Chào mừng ${username} đã quay trở lại!`,
            'Đăng nhập thành công',
            4000
        );
    }
    
    /**
     * Show logout notification
     */
    logoutSuccess() {
        return this.info(
            'Bạn đã đăng xuất thành công',
            'Đăng xuất',
            3000
        );
    }
}

// Initialize toast manager
let toastManager;

document.addEventListener('DOMContentLoaded', function() {
    toastManager = new ToastNotificationManager({ position: 'top-center' });
    
    // Make it globally available with both names for compatibility
    window.toastManager = toastManager;
    window.notificationManager = toastManager; // Add this line for app.js compatibility
    
    // Override the existing notification system
    if (window.app && window.app.showNotification) {
        const originalShowNotification = window.app.showNotification;
        window.app.showNotification = function(message, type = 'info') {
            toastManager.show(message, type);
        };
    }
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ToastNotificationManager;
}
