<!DOCTYPE html>
<html>
<head>
    <title>Test Notifications</title>
</head>
<body>
    <h1>Test Notifications API</h1>
    <button onclick="testNotifications()">Test Notifications</button>
    <div id="result"></div>

    <script>
    async function testNotifications() {
        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = 'Testing...';
        
        try {
            // First login to get session
            const loginResponse = await fetch('http://localhost/it-service-request/api/auth.php', {
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
            
            const loginData = await loginResponse.json();
            console.log('Login response:', loginData);
            
            if (loginData.success) {
                // Now test notifications
                const notifResponse = await fetch('http://localhost/it-service-request/api/notifications.php?action=list', {
                    method: 'GET',
                    credentials: 'include'
                });
                
                const notifData = await notifResponse.json();
                console.log('Notifications response:', notifData);
                
                resultDiv.innerHTML = `
                    <h3>Results:</h3>
                    <p><strong>Login Status:</strong> ${loginData.success ? 'SUCCESS' : 'FAILED'}</p>
                    <p><strong>Notifications Status:</strong> ${notifResponse.status}</p>
                    <p><strong>Notifications Data:</strong></p>
                    <pre>${JSON.stringify(notifData, null, 2)}</pre>
                `;
            } else {
                resultDiv.innerHTML = `<p style="color: red;">Login failed: ${loginData.message}</p>`;
            }
            
        } catch (error) {
            resultDiv.innerHTML = `<p style="color: red;">Error: ${error.message}</p>`;
            console.error('Error:', error);
        }
    }
    </script>
</body>
</html>
