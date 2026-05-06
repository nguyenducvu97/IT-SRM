<?php
// Test Support Requests Date Filter
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Support Requests Date Filter</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { border: 1px solid #ddd; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .btn { padding: 10px 20px; margin: 10px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .form-control { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; margin: 5px; }
    </style>
</head>
<body>
    <h2>🧪 Test Bộ Lọc Ngày cho Yêu Cầu Hỗ Trợ</h2>
    
    <div class="test-section">
        <h3>📋 Kiểm tra Frontend</h3>
        
        <p><strong>1. HTML Elements:</strong></p>
        <p>Kiểm tra xem các elements đã được thêm:</p>
        <ul>
            <li>supportStartDate - Input ngày bắt đầu</li>
            <li>supportEndDate - Input ngày kết thúc</li>
            <li>supportDateFilterBtn - Nút lọc theo ngày</li>
            <li>supportClearDateFilterBtn - Nút xóa bộ lọc ngày</li>
        </ul>
        
        <p><strong>2. CSS Styling:</strong></p>
        <p>Kiểm tra xem CSS đã được thêm cho các elements mới:</p>
        <ul>
            <li>#supportStartDate, #supportEndDate - Styling cho input date</li>
            <li>#supportDateFilterBtn, #supportClearDateFilterBtn - Styling cho buttons</li>
            <li>Responsive design cho mobile</li>
        </ul>
        
        <p><strong>3. JavaScript Event Listeners:</strong></p>
        <p>Kiểm tra xem event listeners đã được thêm:</p>
        <ul>
            <li>supportDateFilterBtn click event</li>
            <li>supportClearDateFilterBtn click event</li>
            <li>supportStartDate change event</li>
            <li>supportEndDate change event</li>
        </ul>
        
        <p><strong>4. JavaScript Methods:</strong></p>
        <p>Kiểm tra xem methods đã được thêm:</p>
        <ul>
            <li>loadSupportRequestsWithDateFilter() - Tải yêu cầu với bộ lọc ngày</li>
            <li>clearSupportRequestDateFilter() - Xóa bộ lọc ngày</li>
        </ul>
        
        <p><strong>5. Testing Instructions:</strong></p>
        <ol>
            <li>Mở trang http://localhost/it-service-request/</li>
            <li>Đăng nhập với tài khoản admin/staff</li>
            <li>Đến trang Yêu cầu hỗ trợ</li>
            <li>Kiểm tra console browser (F12) để xem log messages</li>
            <li>Thử các chức năng lọc ngày</li>
        </ol>
    </div>
    
    <div class="test-section">
        <h3>🔧 Kiểm tra Backend API</h3>
        
        <p><strong>API Endpoint:</strong> api/support_requests.php</p>
        <p><strong>Parameters cần hỗ trợ:</strong></p>
        <ul>
            <li>start_date - Ngày bắt đầu (YYYY-MM-DD)</li>
            <li>end_date - Ngày kết thúc (YYYY-MM-DD)</li>
            <li>status - Trạng thái (all/pending/approved/rejected)</li>
            <li>page - Số trang</li>
            <li>limit - Số lượng item/trang</li>
        </ul>
        
        <p><strong>Test API Calls:</strong></p>
        <button class="btn" onclick="testAPIWithDates()">Test API với bộ lọc ngày</button>
        <button class="btn" onclick="testAPIClearDates()">Test API xóa bộ lọc ngày</button>
        
        <div id="apiResults"></div>
    </div>
    
    <script>
        function testAPIWithDates() {
            const startDate = '2026-04-01';
            const endDate = '2026-04-30';
            
            console.log('Testing API with date filter:', { startDate, endDate });
            
            fetch('api/support_requests.php?action=list&start_date=' + startDate + '&end_date=' + endDate + '&status=all&page=1&limit=9')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('apiResults').innerHTML = `
                        <h4>API Response với bộ lọc ngày:</h4>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                    
                    if (data.success) {
                        document.getElementById('apiResults').innerHTML += '<p class="success">✅ API thành công! Check console để xem chi tiết.</p>';
                    } else {
                        document.getElementById('apiResults').innerHTML += '<p class="error">❌ API lỗi: ' + data.message + '</p>';
                    }
                })
                .catch(error => {
                    document.getElementById('apiResults').innerHTML = '<p class="error">❌ Lỗi kết nối: ' + error.message + '</p>';
                });
        }
        
        function testAPIClearDates() {
            console.log('Testing API without date filter');
            
            fetch('api/support_requests.php?action=list&status=all&page=1&limit=9')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('apiResults').innerHTML = `
                        <h4>API Response xóa bộ lọc ngày:</h4>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                    
                    if (data.success) {
                        document.getElementById('apiResults').innerHTML += '<p class="success">✅ API thành công! Check console để xem chi tiết.</p>';
                    } else {
                        document.getElementById('apiResults').innerHTML += '<p class="error">❌ API lỗi: ' + data.message + '</p>';
                    }
                })
                .catch(error => {
                    document.getElementById('apiResults').innerHTML = '<p class="error">❌ Lỗi kết nối: ' + error.message + '</p>';
                });
        }
    </script>
</body>
</html>
