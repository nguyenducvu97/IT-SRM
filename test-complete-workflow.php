<!DOCTYPE html>
<html>
<head>
    <title>Complete Workflow Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .result { margin: 10px 0; padding: 10px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
        pre { background: #f8f9fa; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>IT Service Request - Complete Workflow Test</h1>
    
    <div class="test-section">
        <h2>1. Authentication Test</h2>
        <button onclick="testAuth()">Test Login</button>
        <div id="auth-result" class="result"></div>
    </div>
    
    <div class="test-section">
        <h2>2. Session Test</h2>
        <button onclick="testSession()">Test Session</button>
        <div id="session-result" class="result"></div>
    </div>
    
    <div class="test-section">
        <h2>3. Notifications Test</h2>
        <button onclick="testNotifications()">Test Notifications</button>
        <div id="notifications-result" class="result"></div>
    </div>
    
    <div class="test-section">
        <h2>4. Comments Test</h2>
        <button onclick="testComments()">Test Comments</button>
        <div id="comments-result" class="result"></div>
    </div>

    <script>
        const API_BASE = 'http://localhost/it-service-request/api';
        
        async function testAuth() {
            const resultDiv = document.getElementById('auth-result');
            resultDiv.innerHTML = '<div class="info">Testing authentication...</div>';
            
            try {
                const response = await fetch(`${API_BASE}/auth.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'include',
                    body: JSON.stringify({
                        action: 'login',
                        username: 'admin',
                        password: 'admin'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="success">
                            ✅ Login Successful<br>
                            User: ${data.data.full_name} (${data.data.role})<br>
                            User ID: ${data.data.id}
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `<div class="error">❌ Login Failed: ${data.message}</div>`;
                }
                
            } catch (error) {
                resultDiv.innerHTML = `<div class="error">❌ Error: ${error.message}</div>`;
            }
        }
        
        async function testSession() {
            const resultDiv = document.getElementById('session-result');
            resultDiv.innerHTML = '<div class="info">Testing session...</div>';
            
            try {
                const response = await fetch(`${API_BASE}/auth.php?action=check_session`, {
                    method: 'GET',
                    credentials: 'include'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="success">
                            ✅ Session Active<br>
                            User: ${data.data.full_name} (${data.data.role})<br>
                            User ID: ${data.data.id}
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `<div class="error">❌ No Active Session: ${data.message}</div>`;
                }
                
            } catch (error) {
                resultDiv.innerHTML = `<div class="error">❌ Error: ${error.message}</div>`;
            }
        }
        
        async function testNotifications() {
            const resultDiv = document.getElementById('notifications-result');
            resultDiv.innerHTML = '<div class="info">Testing notifications...</div>';
            
            try {
                const response = await fetch(`${API_BASE}/notifications.php?action=list`, {
                    method: 'GET',
                    credentials: 'include'
                });
                
                const data = await response.json();
                
                if (response.ok && Array.isArray(data)) {
                    resultDiv.innerHTML = `
                        <div class="success">
                            ✅ Notifications Loaded<br>
                            Count: ${data.length}<br>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `<div class="error">❌ Failed: ${JSON.stringify(data)}</div>`;
                }
                
            } catch (error) {
                resultDiv.innerHTML = `<div class="error">❌ Error: ${error.message}</div>`;
            }
        }
        
        async function testComments() {
            const resultDiv = document.getElementById('comments-result');
            resultDiv.innerHTML = '<div class="info">Testing comments...</div>';
            
            try {
                const response = await fetch(`${API_BASE}/comments.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'include',
                    body: JSON.stringify({
                        service_request_id: 31,
                        comment: 'Test comment from workflow test at ' + new Date().toISOString()
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="success">
                            ✅ Comment Added<br>
                            Comment ID: ${data.data.id}<br>
                            Response: <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `<div class="error">❌ Failed: ${data.message}</div>`;
                }
                
            } catch (error) {
                resultDiv.innerHTML = `<div class="error">❌ Error: ${error.message}</div>`;
            }
        }
        
        // Auto-run auth test on page load
        window.addEventListener('load', () => {
            setTimeout(testAuth, 500);
        });
    </script>
</body>
</html>
