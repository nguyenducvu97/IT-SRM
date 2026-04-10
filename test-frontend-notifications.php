<!DOCTYPE html>
<html>
<head>
    <title>Test Frontend Notifications</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h2>Test Frontend Notifications</h2>
    
    <div id="test-results">
        <h3>Testing API Calls</h3>
        <button onclick="testNotificationsAPI()">Test Notifications API</button>
        <button onclick="testUserNotifications()">Test User 4 Notifications</button>
        <button onclick="testAdminNotifications()">Test Admin 1 Notifications</button>
        
        <div id="api-results"></div>
    </div>
    
    <div id="notification-display">
        <h3>Notification Display Test</h3>
        <div id="notifications-list"></div>
    </div>
    
    <script>
        function testNotificationsAPI() {
            console.log('Testing notifications API...');
            
            fetch('api/notifications.php?action=get', {
                method: 'GET',
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                console.log('API Response:', data);
                document.getElementById('api-results').innerHTML = `
                    <h4>API Response:</h4>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                `;
                
                if (data.success && data.data) {
                    displayNotifications(data.data);
                }
            })
            .catch(error => {
                console.error('API Error:', error);
                document.getElementById('api-results').innerHTML = `
                    <div style="color: red;">API Error: ${error.message}</div>
                `;
            });
        }
        
        function testUserNotifications() {
            console.log('Testing user 4 notifications...');
            
            // Simulate session for user 4
            fetch('api/notifications.php?action=get&user_id=4', {
                method: 'GET',
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                console.log('User 4 Notifications:', data);
                document.getElementById('api-results').innerHTML = `
                    <h4>User 4 Notifications:</h4>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                `;
                
                if (data.success && data.data) {
                    displayNotifications(data.data);
                }
            })
            .catch(error => {
                console.error('User API Error:', error);
            });
        }
        
        function testAdminNotifications() {
            console.log('Testing admin 1 notifications...');
            
            fetch('api/notifications.php?action=get&user_id=1', {
                method: 'GET',
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                console.log('Admin 1 Notifications:', data);
                document.getElementById('api-results').innerHTML = `
                    <h4>Admin 1 Notifications:</h4>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                `;
                
                if (data.success && data.data) {
                    displayNotifications(data.data);
                }
            })
            .catch(error => {
                console.error('Admin API Error:', error);
            });
        }
        
        function displayNotifications(notifications) {
            const container = document.getElementById('notifications-list');
            container.innerHTML = '<h4>Notifications Display:</h4>';
            
            if (notifications.length === 0) {
                container.innerHTML += '<p>No notifications found</p>';
                return;
            }
            
            const ul = document.createElement('ul');
            notifications.forEach(notif => {
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
        
        // Auto-test on page load
        window.onload = function() {
            console.log('Page loaded - auto-testing...');
            testNotificationsAPI();
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
        
        #api-results, #notification-display {
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
