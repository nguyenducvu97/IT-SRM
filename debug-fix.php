<!DOCTYPE html>
<html>
<head>
    <title>Debug Fix</title>
    <link rel="stylesheet" href="assets/css/style.css?v=20260411-1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Force hide loading screen */
        #loadingScreen {
            display: none !important;
        }
        
        /* Force body background */
        body {
            background-color: #f5f5f5 !important;
        }
        
        /* Debug styles */
        .debug-info {
            position: fixed;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
            z-index: 99999;
        }
    </style>
</head>
<body>
    <div class="debug-info" id="debugInfo">
        Loading...
    </div>
    
    <div id="app">
        <!-- Loading Screen (should be hidden) -->
        <div id="loadingScreen" class="screen active">
            <div class="loading-container">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
            </div>
        </div>
        
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
            <h1>Debug Fix Test</h1>
            <p>This page tests both issues:</p>
            <ul>
                <li>Background should be #f5f5f5</li>
                <li>Notification count should update</li>
            </ul>
            <button onclick="testNotification()">Test Notification</button>
            <button onclick="forceUpdateCount()">Force Update Count</button>
        </div>
    </div>
    
    <script src="assets/js/notifications.js?v=20260411-3"></script>
    
    <script>
        // Force hide loading screen
        document.addEventListener('DOMContentLoaded', () => {
            const loadingScreen = document.getElementById('loadingScreen');
            if (loadingScreen) {
                loadingScreen.style.display = 'none';
                loadingScreen.classList.remove('active');
            }
            
            // Debug info
            const debugInfo = document.getElementById('debugInfo');
            debugInfo.innerHTML = `
                Loading Screen: ${loadingScreen ? 'Found & Hidden' : 'Not Found'}<br>
                NotificationManager: ${typeof NotificationManager !== 'undefined' ? 'Available' : 'Not Available'}<br>
                Count Element: ${!!document.getElementById('notificationCount')}<br>
                List Element: ${!!document.getElementById('notificationList')}
            `;
            
            // Initialize notification manager
            if (typeof NotificationManager !== 'undefined') {
                window.notificationManager = new NotificationManager();
                console.log('NotificationManager initialized');
            }
        });
        
        function testNotification() {
            if (window.notificationManager) {
                window.notificationManager.renderNotificationCount();
                console.log('Test notification called');
            }
        }
        
        function forceUpdateCount() {
            const countElement = document.getElementById('notificationCount');
            if (countElement) {
                countElement.textContent = '5';
                countElement.classList.remove('empty');
                console.log('Force updated count to 5');
            }
        }
    </script>
</body>
</html>
