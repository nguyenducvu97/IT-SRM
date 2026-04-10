<!DOCTYPE html>
<html>
<head>
    <title>Test Request #88 Frontend</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h2>Test Request #88 Frontend Display</h2>
    
    <div id="test-results">
        <h3>Frontend Test Results</h3>
        <button onclick="testRequest88API()">Test Request #88 API</button>
        <button onclick="testRequest88Detail()">Test Request #88 Detail Page</button>
        <div id="results"></div>
    </div>
    
    <div id="request-display">
        <h3>Request #88 Information</h3>
        <div id="request-info"></div>
    </div>
    
    <div id="time-display">
        <h3>Time Information</h3>
        <div id="time-info"></div>
    </div>
    
    <script>
        async function testRequest88API() {
            console.log('Testing Request #88 API...');
            
            try {
                const response = await fetch('api/service_requests.php?action=get&id=88');
                const data = await response.json();
                
                console.log('Request #88 API Response:', data);
                
                if (data.success && data.data) {
                    const request = data.data;
                    
                    document.getElementById('results').innerHTML = `
                        <h4>API Test Success</h4>
                        <p><strong>Title:</strong> ${request.title}</p>
                        <p><strong>Status:</strong> ${request.status}</p>
                        <p><strong>Assigned To:</strong> ${request.assigned_name} (ID: ${request.assigned_to})</p>
                        <p><strong>Ngày tao:</strong> ${request.created_at}</p>
                        <p><strong>Ngày cap nhat:</strong> ${request.updated_at}</p>
                        <p><strong>Assigned At:</strong> ${request.assigned_at || 'NULL'}</p>
                        <p><strong>Accepted At:</strong> ${request.accepted_at || 'NULL'}</p>
                    `;
                    
                    displayRequestInfo(request);
                    displayTimeInfo(request);
                    
                } else {
                    document.getElementById('results').innerHTML = `
                        <h4>API Test Failed</h4>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                }
                
            } catch (error) {
                console.error('Error testing Request #88:', error);
                document.getElementById('results').innerHTML = `
                    <h4>API Test Error</h4>
                    <p style="color: red;">${error.message}</p>
                `;
            }
        }
        
        function displayRequestInfo(request) {
            const createdDate = new Date(request.created_at);
            const acceptedDate = request.accepted_at ? new Date(request.accepted_at) : null;
            const assignedDate = request.assigned_at ? new Date(request.assigned_at) : null;
            
            document.getElementById('request-info').innerHTML = `
                <div style="border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px;">
                    <h4>Chi Tiêu Yêu Câu #88</h4>
                    <p><strong>Tiêu de:</strong> ${request.title}</p>
                    <p><strong>Mo ta:</strong> ${request.description}</p>
                    <p><strong>Ngày tao:</strong> ${createdDate.toLocaleString('vi-VN')}</p>
                    <p><strong>Trang thai:</strong> ${request.status}</p>
                    <p><strong>Nguyên tao:</strong> ${request.requester_name}</p>
                    <p><strong>Nguyên nhan:</strong> ${request.assigned_name}</p>
                    <p><strong>Danh muc:</strong> ${request.category_name}</p>
                </div>
            `;
        }
        
        function displayTimeInfo(request) {
            const createdDate = new Date(request.created_at);
            const acceptedDate = request.accepted_at ? new Date(request.accepted_at) : null;
            const assignedDate = request.assigned_at ? new Date(request.assigned_at) : null;
            
            let timeHtml = '<div style="border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px;">';
            timeHtml += '<h4>Thong Tin Thoi Gian</h4>';
            timeHtml += `<p><strong>Ngày tao:</strong> ${createdDate.toLocaleString('vi-VN')}</p>`;
            
            if (acceptedDate) {
                timeHtml += `<p style="color: green;"><strong>Thoi gian staff nhan:</strong> ${acceptedDate.toLocaleString('vi-VN')}</p>`;
                timeHtml += `<p><strong>Thoi gian cho:</strong> ${Math.round((acceptedDate - createdDate) / 1000 / 60)} phút</p>`;
            } else {
                timeHtml += `<p style="color: red;"><strong>Thoi gian staff nhan:</strong> CHUA CO</p>`;
            }
            
            if (assignedDate) {
                timeHtml += `<p><strong>Thoi gian giao:</strong> ${assignedDate.toLocaleString('vi-VN')}</p>`;
            } else {
                timeHtml += `<p><strong>Thoi gian giao:</strong> CHUA CO</p>`;
            }
            
            timeHtml += '</div>';
            
            document.getElementById('time-info').innerHTML = timeHtml;
        }
        
        function testRequest88Detail() {
            console.log('Opening Request #88 Detail Page...');
            window.open('request-detail.html?id=88', '_blank');
        }
        
        // Auto-test on page load
        window.onload = function() {
            console.log('Page loaded - testing Request #88...');
            testRequest88API();
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
        
        #test-results, #request-display, #time-display {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: white;
        }
        
        h2, h3, h4 {
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
