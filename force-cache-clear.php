<!DOCTYPE html>
<html>
<head>
    <title>Force Cache Clear & Test</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h2>Force Cache Clear & Test</h2>
    
    <div id="cache-status">
        <h3>Cache Status</h3>
        <p><strong>Current Time:</strong> <span id="current-time"></span></p>
        <p><strong>Notifications.js Version:</strong> v=20260410-3</p>
        <p><strong>Browser:</strong> <span id="browser-info"></span></p>
    </div>
    
    <div id="force-actions">
        <h3>Force Actions</h3>
        <button onclick="hardRefresh()">Hard Refresh (Ctrl+F5)</button>
        <button onclick="clearAllCache()">Clear All Cache</button>
        <button onclick="testNotificationsFile()">Test Notifications File</button>
        <button onclick="openMainAppWithBust()">Open Main App (Cache Bust)</button>
    </div>
    
    <div id="test-results">
        <h3>Test Results</h3>
        <div id="results"></div>
    </div>
    
    <div id="user4-test">
        <h3>User 4 Notification Test</h3>
        <button onclick="testUser4Notifications()">Test User 4 Notifications</button>
        <div id="user4-results"></div>
    </div>
    
    <script>
        function updateTime() {
            document.getElementById('current-time').textContent = new Date().toLocaleString();
        }
        
        function getBrowserInfo() {
            const userAgent = navigator.userAgent;
            let browserInfo = "Unknown";
            
            if (userAgent.indexOf("Chrome") > -1) {
                browserInfo = "Chrome";
            } else if (userAgent.indexOf("Safari") > -1) {
                browserInfo = "Safari";
            } else if (userAgent.indexOf("Firefox") > -1) {
                browserInfo = "Firefox";
            } else if (userAgent.indexOf("Edge") > -1) {
                browserInfo = "Edge";
            }
            
            document.getElementById('browser-info').textContent = browserInfo;
        }
        
        function hardRefresh() {
            console.log('Performing hard refresh...');
            location.reload(true);
        }
        
        function clearAllCache() {
            console.log('Clearing all cache...');
            
            // Clear localStorage
            localStorage.clear();
            
            // Clear sessionStorage
            sessionStorage.clear();
            
            // Clear service workers
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.getRegistrations().then(function(registrations) {
                    for (let registration of registrations) {
                        registration.unregister();
                    }
                });
            }
            
            // Force reload with cache busting
            const url = new URL(window.location);
            url.searchParams.set('v', Date.now());
            url.searchParams.set('clear', 'true');
            window.location.href = url.toString();
        }
        
        async function testNotificationsFile() {
            console.log('Testing notifications file...');
            
            try {
                // Test with new version
                const response = await fetch('assets/js/notifications.js?v=20260410-3');
                console.log('Notifications.js response status:', response.status);
                
                const content = await response.text();
                console.log('Notifications.js loaded, length:', content.length);
                
                // Check if our fixes are present
                const hasArrayCheck = content.includes('Array.isArray(this.notifications)');
                const hasApiFix = content.includes('action=get');
                const hasErrorHandling = content.includes('console.error');
                
                document.getElementById('results').innerHTML = `
                    <h4>Notifications File Test:</h4>
                    <p><strong>File Status:</strong> Loaded successfully</p>
                    <p><strong>File Size:</strong> ${content.length} characters</p>
                    <p><strong>Array Check Fix:</strong> ${hasArrayCheck ? 'Present' : 'Missing'}</p>
                    <p><strong>API Fix:</strong> ${hasApiFix ? 'Present' : 'Missing'}</p>
                    <p><strong>Error Handling:</strong> ${hasErrorHandling ? 'Present' : 'Missing'}</p>
                    <p><strong>Overall Status:</strong> ${hasArrayCheck && hasApiFix && hasErrorHandling ? 'All fixes present!' : 'Some fixes missing'}</p>
                    
                    <h5>File Content Preview:</h5>
                    <pre style="background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; font-size: 11px; max-height: 200px;">
${content.substring(0, 1000)}...
                    </pre>
                `;
                
            } catch (error) {
                console.error('Error testing notifications file:', error);
                document.getElementById('results').innerHTML = `
                    <h4>File Test Error:</h4>
                    <p style="color: red;">${error.message}</p>
                `;
            }
        }
        
        function openMainAppWithBust() {
            console.log('Opening main app with cache busting...');
            const timestamp = Date.now();
            window.open(`index.html?v=${timestamp}&clear=true`, '_blank');
        }
        
        async function testUser4Notifications() {
            console.log('Testing User 4 notifications...');
            
            try {
                const response = await fetch('api/notifications.php?action=get&user_id=4');
                const data = await response.json();
                
                console.log('User 4 API Response:', data);
                
                if (data.success && data.data) {
                    const notifications = data.data;
                    const request87Notifications = notifications.filter(n => 
                        n.message.includes('#87') || n.related_id == 87
                    );
                    
                    document.getElementById('user4-results').innerHTML = `
                        <h4>User 4 Notifications Test Results:</h4>
                        <p><strong>Total notifications:</strong> ${notifications.length}</p>
                        <p><strong>Request #87 notifications:</strong> ${request87Notifications.length}</p>
                        
                        ${request87Notifications.length > 0 ? `
                            <h5>Request #87 Notifications:</h5>
                            ${request87Notifications.map(notif => `
                                <div style="margin: 10px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; background: ${notif.is_read ? '#f8f9fa' : '#e7f3ff'};">
                                    <strong>${notif.title}</strong><br>
                                    <small>${notif.message}</small><br>
                                    <small style="color: #666;">${notif.created_at} | Type: ${notif.type} | Read: ${notif.is_read ? 'Yes' : 'No'}</small>
                                </div>
                            `).join('')}
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
                    document.getElementById('user4-results').innerHTML = `
                        <h4>User 4 API Error:</h4>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                }
                
            } catch (error) {
                console.error('Error testing User 4:', error);
                document.getElementById('user4-results').innerHTML = `
                    <h4>User 4 Test Error:</h4>
                    <p style="color: red;">${error.message}</p>
                `;
            }
        }
        
        // Update time and browser info
        updateTime();
        getBrowserInfo();
        setInterval(updateTime, 1000);
        
        // Auto-test on page load
        window.onload = function() {
            console.log('Cache clear page loaded...');
            testNotificationsFile();
            testUser4Notifications();
        };
    </script>
    
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        
        button {
            padding: 12px 24px;
            margin: 8px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        button:hover {
            background: #0056b3;
            transform: translateY(-1px);
        }
        
        #cache-status, #force-actions, #test-results, #user4-test {
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
