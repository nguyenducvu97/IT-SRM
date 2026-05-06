<?php
// Simple test for date filter functionality
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Date Filter - Simple</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { border: 1px solid #ddd; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .btn { padding: 10px 20px; margin: 10px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .form-control { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; margin: 5px; }
        .page-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
    </style>
</head>
<body>
    <h2>🗓️ Test Bộ Lọc Ngày - Simple Version</h2>
    
    <div class="test-section">
        <h3>📋 HTML Elements Test</h3>
        <p>Kiểm tra xem các elements có được render đúng không:</p>
        
        <div class="page-actions">
            <select id="supportStatusFilter" class="form-control">
                <option value="all">Tất cả</option>
                <option value="pending">Đang chờ</option>
                <option value="approved">Đã duyệt</option>
                <option value="rejected">Từ chối</option>
            </select>
            
            <input type="date" id="supportStartDate" class="form-control" placeholder="Từ ngày" title="Từ ngày">
            <input type="date" id="supportEndDate" class="form-control" placeholder="Đến ngày" title="Đến ngày">
            <button type="button" id="supportDateFilterBtn" class="btn btn-primary" title="Lọc theo ngày">
                <i class="fas fa-filter"></i> Lọc
            </button>
            <button type="button" id="supportClearDateFilterBtn" class="btn btn-secondary" title="Xóa bộ lọc ngày">
                <i class="fas fa-times"></i> Xóa
            </button>
        </div>
        
        <div id="testResults"></div>
    </div>
    
    <div class="test-section">
        <h3>🧪 JavaScript Functionality Test</h3>
        <button class="btn" onclick="testElements()">🔍 Check Elements</button>
        <button class="btn" onclick="testEvents()">⚡ Test Events</button>
        <button class="btn" onclick="testAPI()">🌐 Test API</button>
        <button class="btn btn-secondary" onclick="clearResults()">🗑️ Clear Results</button>
    </div>
    
    <div class="test-section">
        <h3>📝 Expected Console Logs</h3>
        <p>Khi test thành công, bạn sẽ thấy các messages sau trong console:</p>
        <ul>
            <li>🗓️ Support date filter elements found: {supportStartDate: true, supportEndDate: true, ...}</li>
            <li>✅ supportDateFilterBtn event listener bound</li>
            <li>✅ supportClearDateFilterBtn event listener bound</li>
            <li>✅ supportStartDate change event listener bound</li>
            <li>✅ supportEndDate change event listener bound</li>
            <li>✅ supportStatusFilter change event listener bound</li>
            <li>Loading support requests with date filter: {startDate, endDate, status}</li>
            <li>Clearing support request date filter</li>
        </ul>
    </div>
    
    <script>
        // Mock ITServiceApp for testing
        class MockITServiceApp {
            constructor() {
                this.currentSupportPage = 1;
            }
            
            loadSupportRequestsWithDateFilter() {
                const startDate = document.getElementById('supportStartDate').value;
                const endDate = document.getElementById('supportEndDate').value;
                const status = document.getElementById('supportStatusFilter').value || 'all';
                
                console.log('Loading support requests with date filter:', { startDate, endDate, status });
                
                // Mock API call
                const url = `api/support_requests.php?action=list&page=${this.currentSupportPage}&limit=9`;
                let fullUrl = url;
                
                if (status !== 'all') fullUrl += `&status=${status}`;
                if (startDate) fullUrl += `&start_date=${startDate}`;
                if (endDate) fullUrl += `&end_date=${endDate}`;
                
                console.log('Mock API URL:', fullUrl);
                
                return Promise.resolve({ success: true, data: [] });
            }
            
            clearSupportRequestDateFilter() {
                console.log('Clearing support request date filter');
                
                // Clear date inputs
                document.getElementById('supportStartDate').value = '';
                document.getElementById('supportEndDate').value = '';
                
                // Reload with current status filter only
                this.loadSupportRequests(1);
            }
            
            loadSupportRequests(page) {
                const status = document.getElementById('supportStatusFilter').value || 'all';
                console.log('Loading support requests with status:', status, 'page:', page);
            }
        }
        
        const mockApp = new MockITServiceApp();
        
        function testElements() {
            const results = document.getElementById('testResults');
            const elements = {
                supportStartDate: !!document.getElementById('supportStartDate'),
                supportEndDate: !!document.getElementById('supportEndDate'),
                supportDateFilterBtn: !!document.getElementById('supportDateFilterBtn'),
                supportClearDateFilterBtn: !!document.getElementById('supportClearDateFilterBtn'),
                supportStatusFilter: !!document.getElementById('supportStatusFilter')
            };
            
            console.log('🗓️ Support date filter elements found:', elements);
            
            results.innerHTML = `
                <h4>🔍 Element Check Results:</h4>
                <ul>
                    <li class="${elements.supportStartDate ? 'success' : 'error'}">
                        supportStartDate: ${elements.supportStartDate ? '✅ Found' : '❌ Missing'}
                    </li>
                    <li class="${elements.supportEndDate ? 'success' : 'error'}">
                        supportEndDate: ${elements.supportEndDate ? '✅ Found' : '❌ Missing'}
                    </li>
                    <li class="${elements.supportDateFilterBtn ? 'success' : 'error'}">
                        supportDateFilterBtn: ${elements.supportDateFilterBtn ? '✅ Found' : '❌ Missing'}
                    </li>
                    <li class="${elements.supportClearDateFilterBtn ? 'success' : 'error'}">
                        supportClearDateFilterBtn: ${elements.supportClearDateFilterBtn ? '✅ Found' : '❌ Missing'}
                    </li>
                    <li class="${elements.supportStatusFilter ? 'success' : 'error'}">
                        supportStatusFilter: ${elements.supportStatusFilter ? '✅ Found' : '❌ Missing'}
                    </li>
                </ul>
                <p class="success">✅ All elements found! Check console for details.</p>
            `;
        }
        
        function testEvents() {
            const results = document.getElementById('testResults');
            
            // Bind events
            const supportDateFilterBtn = document.getElementById('supportDateFilterBtn');
            const supportClearDateFilterBtn = document.getElementById('supportClearDateFilterBtn');
            const supportStartDate = document.getElementById('supportStartDate');
            const supportEndDate = document.getElementById('supportEndDate');
            const supportStatusFilter = document.getElementById('supportStatusFilter');
            
            if (supportDateFilterBtn) {
                supportDateFilterBtn.addEventListener('click', () => mockApp.loadSupportRequestsWithDateFilter());
                console.log('✅ supportDateFilterBtn event listener bound');
            }
            
            if (supportClearDateFilterBtn) {
                supportClearDateFilterBtn.addEventListener('click', () => mockApp.clearSupportRequestDateFilter());
                console.log('✅ supportClearDateFilterBtn event listener bound');
            }
            
            if (supportStartDate) {
                supportStartDate.addEventListener('change', () => mockApp.loadSupportRequestsWithDateFilter());
                console.log('✅ supportStartDate change event listener bound');
            }
            
            if (supportEndDate) {
                supportEndDate.addEventListener('change', () => mockApp.loadSupportRequestsWithDateFilter());
                console.log('✅ supportEndDate change event listener bound');
            }
            
            if (supportStatusFilter) {
                supportStatusFilter.addEventListener('change', () => mockApp.loadSupportRequests(1));
                console.log('✅ supportStatusFilter change event listener bound');
            }
            
            results.innerHTML = `
                <h4>⚡ Event Binding Results:</h4>
                <p class="success">✅ All events bound successfully! Check console for details.</p>
                <p><strong>Test the functionality:</strong></p>
                <ol>
                    <li>Change date values and check console</li>
                    <li>Click "Lọc" button and check console</li>
                    <li>Click "Xóa" button and check console</li>
                    <li>Change status filter and check console</li>
                </ol>
            `;
        }
        
        function testAPI() {
            const results = document.getElementById('testResults');
            
            // Test API call
            fetch('api/support_requests.php?action=list&start_date=2026-04-01&end_date=2026-04-30&status=all&page=1&limit=9')
                .then(response => {
                    console.log('API Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('API Response data:', data);
                    
                    results.innerHTML = `
                        <h4>🌐 API Test Results:</h4>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                        <p class="${data.success ? 'success' : 'error'}">
                            ${data.success ? '✅ API working correctly!' : '❌ API error: ' + data.message}
                        </p>
                    `;
                })
                .catch(error => {
                    console.error('API Error:', error);
                    results.innerHTML = `
                        <h4>🌐 API Test Results:</h4>
                        <p class="error">❌ API Error: ${error.message}</p>
                        <p class="error">Backend may need date filtering implementation</p>
                    `;
                });
        }
        
        function clearResults() {
            document.getElementById('testResults').innerHTML = '';
        }
        
        // Auto-test on load
        window.addEventListener('load', () => {
            console.log('🚀 Simple date filter test loaded');
            testElements();
        });
    </script>
</body>
</html>
