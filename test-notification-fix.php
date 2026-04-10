<!DOCTYPE html>
<html>
<head>
    <title>Test Notification Fix</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h2>Test Notification Fix</h2>
    
    <div id="test-results">
        <h3>Testing Fixed NotificationManager</h3>
        <button onclick="testFixedNotifications()">Test Fixed Notifications</button>
        <button onclick="testNotificationCount()">Test Notification Count</button>
        <button onclick="simulateUserLogin()">Simulate User 4 Login</button>
        
        <div id="results"></div>
    </div>
    
    <div id="notification-display">
        <h3>Notification Display</h3>
        <div id="notification-count"></div>
        <div id="notification-list"></div>
    </div>
    
    <script>
        // Simulate the fixed NotificationManager
        class TestNotificationManager {
            constructor() {
                this.notifications = [];
                this.unreadCount = 0;
            }
            
            async loadNotifications() {
                try {
                    console.log('Loading notifications...');
                    
                    const response = await fetch('api/notifications.php?action=get');
                    console.log('Response status:', response.status);
                    
                    if (!response.ok) throw new Error('Failed to load notifications');
                    
                    const data = await response.json();
                    if (data.success && data.data) {
                        this.notifications = data.data;
                    } else {
                        this.notifications = [];
                    }
                    console.log('Loaded notifications:', this.notifications);
                    
                    this.renderNotifications();
                    await this.updateNotificationCount();
                    
                    return this.notifications;
                    
                } catch (error) {
                    console.error('Error loading notifications:', error);
                    return [];
                }
            }
            
            async updateNotificationCount() {
                try {
                    console.log('Updating notification count...');
                    
                    const response = await fetch('api/notifications.php?action=count');
                    console.log('Count response status:', response.status);
                    
                    if (!response.ok) throw new Error('Failed to get notification count');
                    
                    const data = await response.json();
                    console.log('Count response data:', data);
                    
                    this.unreadCount = data.count;
                    console.log('Unread count set to:', this.unreadCount);
                    
                    this.renderNotificationCount();
                    
                } catch (error) {
                    console.error('Error updating notification count:', error);
                }
            }
            
            renderNotificationCount() {
                const countElement = document.getElementById('notification-count');
                if (countElement) {
                    countElement.innerHTML = `<h4>Unread Count: ${this.unreadCount}</h4>`;
                }
            }
            
            renderNotifications() {
                const container = document.getElementById('notification-list');
                container.innerHTML = '<h4>Notifications:</h4>';
                
                if (this.notifications.length === 0) {
                    container.innerHTML += '<p>No notifications found</p>';
                    return;
                }
                
                const ul = document.createElement('ul');
                this.notifications.forEach(notif => {
                    const li = document.createElement('li');
                    li.style.marginBottom = '10px';
                    li.style.padding = '10px';
                    li.style.border = '1px solid #ddd';
                    li.style.borderRadius = '5px';
                    li.style.backgroundColor = notif.is_read ? '#f8f9fa' : '#e7f3ff';
                    
                    li.innerHTML = `
                        <strong>${notif.title}</strong><br>
                        <small>${notif.message}</small><br>
                        <small style="color: #666;">${notif.created_at} | Type: ${notif.type} | Read: ${notif.is_read ? 'Yes' : 'No'}</small>
                    `;
                    
                    ul.appendChild(li);
                });
                
                container.appendChild(ul);
            }
        }
        
        const notificationManager = new TestNotificationManager();
        
        async function testFixedNotifications() {
            console.log('Testing fixed notifications...');
            const notifications = await notificationManager.loadNotifications();
            
            document.getElementById('results').innerHTML = `
                <h4>Fixed API Test Results:</h4>
                <pre>${JSON.stringify(notifications, null, 2)}</pre>
                <p>Total notifications: ${notifications.length}</p>
            `;
        }
        
        async function testNotificationCount() {
            console.log('Testing notification count...');
            await notificationManager.updateNotificationCount();
            
            document.getElementById('results').innerHTML = `
                <h4>Notification Count Test:</h4>
                <p>Unread count: ${notificationManager.unreadCount}</p>
            `;
        }
        
        function simulateUserLogin() {
            console.log('Simulating user 4 login...');
            
            // Create a simple session simulation
            fetch('test-user-notifications.php')
                .then(response => response.text())
                .then(html => {
                    document.getElementById('results').innerHTML = `
                        <h4>User 4 Login Simulation:</h4>
                        <p>Check console for user 4 notifications</p>
                        <iframe src="test-user-notifications.php" style="width: 100%; height: 500px; border: 1px solid #ddd;"></iframe>
                    `;
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
        
        // Auto-test on page load
        window.onload = function() {
            console.log('Page loaded - auto-testing fixed notifications...');
            testFixedNotifications();
        };
    </script>
    
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        
        button {
            padding: 10px 20px;
            margin: 5px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        button:hover {
            background: #0056b3;
        }
        
        #test-results, #notification-display {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</body>
</html>
