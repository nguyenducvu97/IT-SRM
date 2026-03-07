<!DOCTYPE html>
<html>
<head>
    <title>Create Test Request</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea, select { padding: 8px; width: 400px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; margin-right: 10px; }
        button:hover { background: #0056b3; }
        .result { margin-top: 20px; padding: 15px; border: 1px solid #ddd; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <h2>🧪 Create Test Service Request</h2>
    
    <form id="testRequestForm">
        <div class="form-group">
            <label for="title">Title:</label>
            <input type="text" id="title" value="TEST EMAIL REQUEST - <?= date('Y-m-d H:i:s') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="description">Description:</label>
            <textarea id="description" rows="4" required>Đây là yêu cầu test thực tế để kiểm tra hệ thống email gửi đến admin và staff khi có yêu cầu mới.</textarea>
        </div>
        
        <div class="form-group">
            <label for="category_id">Category:</label>
            <select id="category_id" required>
                <option value="1">Hardware</option>
                <option value="2">Software</option>
                <option value="3">Network</option>
                <option value="4">Security</option>
                <option value="5">Account</option>
                <option value="6">Other</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="priority">Priority:</label>
            <select id="priority" required>
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high" selected>High</option>
                <option value="critical">Critical</option>
            </select>
        </div>
        
        <button type="button" onclick="createTestRequest()">📧 Create Test Request</button>
        <button type="button" onclick="checkEmailLogs()">📊 Check Email Logs</button>
    </form>
    
    <div id="result"></div>
    
    <script>
        function createTestRequest() {
            const data = {
                title: document.getElementById('title').value,
                description: document.getElementById('description').value,
                category_id: parseInt(document.getElementById('category_id').value),
                priority: document.getElementById('priority').value
            };
            
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<div class="info">⏳ Creating test request and sending emails...</div>';
            
            fetch('api/service_requests.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    resultDiv.innerHTML = `
                        <div class="success">
                            <h3>✅ Test Request Created Successfully!</h3>
                            <p><strong>Request ID:</strong> ${result.data.id}</p>
                            <p><strong>Message:</strong> ${result.message}</p>
                            <hr>
                            <h4>📧 Email Notifications Sent:</h4>
                            <ul>
                                <li>✅ Admin: ndvu@sgitech.com.vn</li>
                                <li>✅ Staff: nguyenducvu101223@gmail.com</li>
                            </ul>
                            <p><strong>Please check both email inboxes (including Spam folders)!</strong></p>
                            <p><strong>Subject:</strong> 🔔 Yêu cầu dịch vụ mới #${result.data.id}</p>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="error">
                            <h3>❌ Failed to Create Request</h3>
                            <p><strong>Error:</strong> ${result.message}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `
                    <div class="error">
                        <h3>❌ Network Error</h3>
                        <p><strong>Error:</strong> ${error.message}</p>
                    </div>
                `;
            });
        }
        
        function checkEmailLogs() {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<div class="info">📊 Checking recent email logs...</div>';
            
            fetch('test-final-email-fix.php')
            .then(response => response.text())
            .then(html => {
                // Extract the logs section from the response
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const logsSection = doc.querySelector('pre');
                
                if (logsSection) {
                    resultDiv.innerHTML = `
                        <div class="info">
                            <h3>📊 Recent Email Logs:</h3>
                            <pre>${logsSection.textContent}</pre>
                            <p><em>Logs show email sending status to admin and staff recipients</em></p>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = '<div class="error">Could not retrieve email logs</div>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `<div class="error">Error fetching logs: ${error.message}</div>`;
            });
        }
        
        // Auto-create request on page load for quick testing
        window.onload = function() {
            setTimeout(createTestRequest, 1000);
        };
    </script>
</body>
</html>
