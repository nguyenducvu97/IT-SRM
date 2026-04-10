<!DOCTYPE html>
<html>
<head>
    <title>Clear Cache & Test</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h2>Clear Cache & Test Notifications</h2>
    
    <div id="cache-info">
        <h3>Cache Information</h3>
        <p><strong>Current Time:</strong> <span id="current-time"></span></p>
        <p><strong>Notifications.js Version:</strong> v=20260410-2</p>
        <p><strong>Browser Cache:</strong> Clear and reload</p>
    </div>
    
    <div id="test-actions">
        <h3>Test Actions</h3>
        <button onclick="forceReload()">Force Reload (Ctrl+F5)</button>
        <button onclick="clearCacheAndReload()">Clear Cache & Reload</button>
        <button onclick="testNotificationsDirect()">Test Notifications Direct</button>
        <button onclick="openMainApp()">Open Main App</button>
    </div>
    
    <div id="test-results">
        <h3>Test Results</h3>
        <div id="results"></div>
    </div>
    
    <script>
        function updateTime() {
            document.getElementById('current-time').textContent = new Date().toLocaleString();
        }
        
        function forceReload() {
            console.log('Force reloading page...');
            location.reload(true);
        }
        
        function clearCacheAndReload() {
            console.log('Clearing cache and reloading...');
            
            // Clear localStorage
            localStorage.clear();
            
            // Clear sessionStorage
            sessionStorage.clear();
            
            // Force reload with cache busting
            const url = new URL(window.location);
            url.searchParams.set('v', Date.now());
            window.location.href = url.toString();
        }
        
        async function testNotificationsDirect() {
            console.log('Testing notifications directly...');
            
            try {
                const response = await fetch('assets/js/notifications.js?v=20260410-2');
                console.log('Notifications.js response status:', response.status);
                
                const content = await response.text();
                console.log('Notifications.js loaded, length:', content.length);
                
                // Check if our fix is present
                const hasArrayCheck = content.includes('Array.isArray(this.notifications)');
                const hasApiFix = content.includes('action=get');
                
                document.getElementById('results').innerHTML = `
                    <h4>✅ Direct Test Results:</h4>
                    <p><strong>File Status:</strong> Loaded successfully</p>
                    <p><strong>File Size:</strong> ${content.length} characters</p>
                    <p><strong>Array Check Fix:</strong> ${hasArrayCheck ? '✅ Present' : '❌ Missing'}</p>
                    <p><strong>API Fix:</strong> ${hasApiFix ? '✅ Present' : '❌ Missing'}</p>
                    <p><strong>Status:</strong> ${hasArrayCheck && hasApiFix ? '🎉 All fixes present!' : '⚠️ Some fixes missing'}</p>
                `;
                
            } catch (error) {
                console.error('Error testing notifications:', error);
                document.getElementById('results').innerHTML = `
                    <h4>❌ Test Error:</h4>
                    <p>${error.message}</p>
                `;
            }
        }
        
        function openMainApp() {
            console.log('Opening main app...');
            window.open('index.html?v=' + Date.now(), '_blank');
        }
        
        // Update time every second
        setInterval(updateTime, 1000);
        updateTime();
        
        // Auto-test on page load
        window.onload = function() {
            console.log('Cache test page loaded...');
            testNotificationsDirect();
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
        
        #cache-info, #test-actions, #test-results {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: white;
        }
        
        h2, h3, h4 {
            color: #333;
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
