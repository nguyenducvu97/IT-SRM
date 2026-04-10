<!DOCTYPE html>
<html>
<head>
    <title>Test Frontend Real-time Notifications</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h2>Test Frontend Real-time Notifications</h2>
    
    <div id="user-info">
        <h3>User Information</h3>
        <button onclick="testUser4()">Test as User 4 (Nguyêñ Ðúç Vû)</button>
        <button onclick="testAdmin1()">Test as Admin 1</button>
        <button onclick="testBothUsers()">Test Both Users</button>
    </div>
    
    <div id="api-test">
        <h3>API Test Results</h3>
        <div id="api-results"></div>
    </div>
    
    <div id="notification-display">
        <h3>Notification Display</h3>
        <div id="user4-notifications"></div>
        <div id="admin1-notifications"></div>
    </div>
    
    <div id="real-time-test">
        <h3>Real-time Test</h3>
        <button onclick="startRealTimeTest()">Start Real-time Test</button>
        <button onclick="stopRealTimeTest()">Stop Real-time Test</button>
        <div id="real-time-results"></div>
    </div>
    
    <script>
        let realTimeInterval = null;
        
        async function testUser4() {
            console.log('Testing User 4 notifications...');
            
            try {
                const response = await fetch('api/notifications.php?action=get&user_id=4');
                const data = await response.json();
                
                console.log('User 4 API Response:', data);
                
                displayResults('user4-notifications', 'User 4 (Nguyêñ Ðúç Vû)', data);
                
                document.getElementById('api-results').innerHTML = `
                    <h4>User 4 API Test:</h4>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                `;
                
            } catch (error) {
                console.error('Error testing User 4:', error);
                document.getElementById('api-results').innerHTML = `
                    <h4>User 4 API Error:</h4>
                    <p style="color: red;">${error.message}</p>
                `;
            }
        }
        
        async function testAdmin1() {
            console.log('Testing Admin 1 notifications...');
            
            try {
                const response = await fetch('api/notifications.php?action=get&user_id=1');
                const data = await response.json();
                
                console.log('Admin 1 API Response:', data);
                
                displayResults('admin1-notifications', 'Admin 1 (System Administrator)', data);
                
                document.getElementById('api-results').innerHTML = `
                    <h4>Admin 1 API Test:</h4>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                `;
                
            } catch (error) {
                console.error('Error testing Admin 1:', error);
                document.getElementById('api-results').innerHTML = `
                    <h4>Admin 1 API Error:</h4>
                    <p style="color: red;">${error.message}</p>
                `;
            }
        }
        
        async function testBothUsers() {
            console.log('Testing both users...');
            
            await Promise.all([testUser4(), testAdmin1()]);
        }
        
        function displayResults(containerId, userName, data) {
            const container = document.getElementById(containerId);
            
            if (data.success && data.data) {
                const notifications = data.data;
                const request87Notifications = notifications.filter(n => 
                    n.message.includes('#87') || n.related_id == 87
                );
                
                container.innerHTML = `
                    <h4>${userName}</h4>
                    <p><strong>Total notifications:</strong> ${notifications.length}</p>
                    <p><strong>Request #87 notifications:</strong> ${request87Notifications.length}</p>
                    
                    ${request87Notifications.length > 0 ? `
                        <h5>Request #87 Notifications:</h5>
                        <div style="border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 5px;">
                            ${request87Notifications.map(notif => `
                                <div style="margin-bottom: 10px; padding: 10px; background: ${notif.is_read ? '#f8f9fa' : '#e7f3ff'}; border-radius: 5px;">
                                    <strong>${notif.title}</strong><br>
                                    <small>${notif.message}</small><br>
                                    <small style="color: #666;">${notif.created_at} | Type: ${notif.type} | Read: ${notif.is_read ? 'Yes' : 'No'}</small>
                                </div>
                            `).join('')}
                        </div>
                    ` : '<p style="color: orange;">No notifications for Request #87</p>'}
                    
                    <h5>All Notifications:</h5>
                    <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 5px;">
                        ${notifications.map(notif => `
                            <div style="margin-bottom: 5px; padding: 5px; background: ${notif.is_read ? '#f8f9fa' : '#e7f3ff'}; border-radius: 3px; font-size: 12px;">
                                <strong>${notif.title}</strong> - ${notif.message.substring(0, 50)}...
                            </div>
                        `).join('')}
                    </div>
                `;
            } else {
                container.innerHTML = `
                    <h4>${userName}</h4>
                    <p style="color: red;">Failed to load notifications</p>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                `;
            }
        }
        
        function startRealTimeTest() {
            console.log('Starting real-time test...');
            
            if (realTimeInterval) {
                clearInterval(realTimeInterval);
            }
            
            realTimeInterval = setInterval(async () => {
                console.log('Real-time check...');
                
                try {
                    const [user4Response, admin1Response] = await Promise.all([
                        fetch('api/notifications.php?action=get&user_id=4'),
                        fetch('api/notifications.php?action=get&user_id=1')
                    ]);
                    
                    const user4Data = await user4Response.json();
                    const admin1Data = await admin1Response.json();
                    
                    const user4Count = user4Data.success ? user4Data.data.length : 0;
                    const admin1Count = admin1Data.success ? admin1Data.data.length : 0;
                    
                    const user4Request87 = user4Data.success ? user4Data.data.filter(n => n.message.includes('#87')).length : 0;
                    const admin1Request87 = admin1Data.success ? admin1Data.data.filter(n => n.message.includes('#87')).length : 0;
                    
                    document.getElementById('real-time-results').innerHTML = `
                        <h4>Real-time Status (Updated: ${new Date().toLocaleTimeString()})</h4>
                        <div style="display: flex; gap: 20px;">
                            <div style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                                <h5>User 4</h5>
                                <p>Total: ${user4Count}</p>
                                <p>Request #87: ${user4Request87}</p>
                                <p style="color: ${user4Request87 > 0 ? 'green' : 'orange'};">
                                    ${user4Request87 > 0 ? 'Has notifications! ' : 'No notifications'}
                                </p>
                            </div>
                            <div style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                                <h5>Admin 1</h5>
                                <p>Total: ${admin1Count}</p>
                                <p>Request #87: ${admin1Request87}</p>
                                <p style="color: ${admin1Request87 > 0 ? 'green' : 'orange'};">
                                    ${admin1Request87 > 0 ? 'Has notifications! ' : 'No notifications'}
                                </p>
                            </div>
                        </div>
                    `;
                    
                } catch (error) {
                    console.error('Real-time error:', error);
                }
            }, 5000); // Check every 5 seconds
            
            document.getElementById('real-time-results').innerHTML = '<p>Real-time monitoring started...</p>';
        }
        
        function stopRealTimeTest() {
            if (realTimeInterval) {
                clearInterval(realTimeInterval);
                realTimeInterval = null;
                document.getElementById('real-time-results').innerHTML = '<p>Real-time monitoring stopped.</p>';
            }
        }
        
        // Auto-test on page load
        window.onload = function() {
            console.log('Page loaded - testing both users...');
            testBothUsers();
        };
    </script>
    
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        
        button {
            padding: 10px 20px;
            margin: 5px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        button:hover {
            background: #0056b3;
        }
        
        #user-info, #api-test, #notification-display, #real-time-test {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: white;
        }
        
        h2, h3, h4, h5 {
            color: #333;
        }
        
        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
        }
        
        .success {
            color: #28a745;
            font-weight: bold;
        }
        
        .error {
            color: #dc3545;
            font-weight: bold;
        }
        
        .warning {
            color: #ffc107;
            font-weight: bold;
        }
    </style>
</body>
</html>
