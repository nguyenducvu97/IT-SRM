<!DOCTYPE html>
<html>
<head>
    <title>Test Notification Manager</title>
    <link rel="stylesheet" href="assets/css/style.css?v=20260411-1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div id="app">
        <!-- Header with notification -->
        <header class="header">
            <div class="header-right">
                <div class="notification-container">
                    <button id="notificationBtn" class="notification-btn">
                        <i class="fas fa-bell"></i>
                        <span id="notificationCount" class="notification-count">0</span>
                    </button>
                    <div id="notificationDropdown" class="notification-dropdown">
                        <div class="notification-header">
                            <h4>Thông báo</h4>
                            <button id="markAllReadBtn" class="mark-all-read-btn">Mark all read</button>
                        </div>
                        <div id="notificationList" class="notification-list">
                            <!-- Notifications will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <div style="padding: 20px;">
            <h1>Test Notification Manager</h1>
            <div id="debugInfo"></div>
        </div>
    </div>
    
    <script src="assets/js/notifications.js?v=20260411-3"></script>
    
    <script>
        // Debug script
        document.addEventListener('DOMContentLoaded', () => {
            console.log('DOM loaded');
            
            // Check if NotificationManager is available
            if (typeof NotificationManager !== 'undefined') {
                console.log('NotificationManager class found');
                
                // Create instance
                window.notificationManager = new NotificationManager();
                console.log('NotificationManager instance created');
                
                // Debug info
                const debugInfo = document.getElementById('debugInfo');
                debugInfo.innerHTML = `
                    <p>NotificationManager: <span style="color: green">Available</span></p>
                    <p>Instance created: <span style="color: green">Yes</span></p>
                    <p>Count element: <span style="color: green">${!!document.getElementById('notificationCount')}</span></p>
                    <p>List element: <span style="color: green">${!!document.getElementById('notificationList')}</span></p>
                `;
                
            } else {
                console.error('NotificationManager class not found');
                document.getElementById('debugInfo').innerHTML = `
                    <p>NotificationManager: <span style="color: red">Not Available</span></p>
                `;
            }
        });
    </script>
</body>
</html>
