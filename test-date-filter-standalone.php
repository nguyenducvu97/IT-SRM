<?php
// Standalone test for date filter - no dependencies on broken app.js
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>🗓️ Test Bộ Lọc Ngày - Standalone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .test-section { background: white; border: 1px solid #ddd; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; }
        .btn { padding: 10px 20px; margin: 5px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; transition: background 0.3s; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #545b62; }
        .btn-warning { background: #ffc107; color: #000; }
        .btn-warning:hover { background: #e0a800; }
        .form-control { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; margin: 5px; font-size: 14px; }
        .page-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; padding: 15px; background: #f8f9fa; border-radius: 5px; margin: 10px 0; }
        .status-indicator { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .status-found { background: #d4edda; color: #155724; }
        .status-missing { background: #f8d7da; color: #721c24; }
        .code-block { background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #007bff; margin: 10px 0; }
        pre { margin: 0; overflow-x: auto; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .console-output { background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 5px; font-family: 'Courier New', monospace; font-size: 12px; max-height: 300px; overflow-y: auto; }
        .console-log { color: #d4d4d4; }
        .console-success { color: #4ec9b0; }
        .console-error { color: #f48771; }
        .console-warning { color: #dcdcaa; }
        .console-info { color: #9cdcfe; }
        @media (max-width: 768px) {
            .grid { grid-template-columns: 1fr; }
            .page-actions { flex-direction: column; align-items: stretch; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🗓️ Test Bộ Lọc Ngày Yêu Cầu Hỗ Trợ - Standalone</h2>
        <p class="info">📝 Test này không phụ thuộc vào app.js bị lỗi. Nó sẽ kiểm tra từng thành phần riêng biệt.</p>
        
        <div class="test-section">
            <h3>📋 1. HTML Elements Check</h3>
            <p>Kiểm tra xem các elements đã được thêm vào HTML đúng chưa:</p>
            
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
            
            <div id="elementCheckResults"></div>
        </div>
        
        <div class="test-section">
            <h3>⚡ 2. JavaScript Events Test</h3>
            <p>Kiểm tra xem event listeners có hoạt động đúng không:</p>
            
            <button class="btn btn-success" onclick="testElements()">🔍 Check Elements</button>
            <button class="btn btn-success" onclick="bindEvents()">⚡ Bind Events</button>
            <button class="btn btn-warning" onclick="testFunctionality()">🧪 Test Functionality</button>
            <button class="btn btn-secondary" onclick="clearResults()">🗑️ Clear Results</button>
            
            <div id="eventTestResults"></div>
        </div>
        
        <div class="test-section">
            <h3>🌐 3. API Integration Test</h3>
            <p>Kiểm tra xem API có nhận được parameters đúng không:</p>
            
            <button class="btn" onclick="testAPI()">🌐 Test API Call</button>
            <button class="btn btn-warning" onclick="testAPIWithDates()">📅 Test API with Dates</button>
            <button class="btn btn-secondary" onclick="clearAPIClear()">🗑️ Clear API Results</button>
            
            <div id="apiTestResults"></div>
        </div>
        
        <div class="test-section">
            <h3>📊 4. Console Output</h3>
            <p>Theo dõi tất cả console messages ở đây:</p>
            
            <button class="btn btn-secondary" onclick="clearConsole()">🗑️ Clear Console</button>
            <div id="consoleOutput" class="console-output"></div>
        </div>
        
        <div class="test-section">
            <h3>🎯 5. Expected Results in Main App</h3>
            <div class="grid">
                <div>
                    <h4>✅ Success Indicators:</h4>
                    <ul>
                        <li>Date inputs appear in page actions</li>
                        <li>Buttons styled and clickable</li>
                        <li>Console shows debug messages</li>
                        <li>API calls include date parameters</li>
                        <li>Data reloads with filters</li>
                    </ul>
                </div>
                <div>
                    <h4>⚠️ Common Issues:</h4>
                    <ul>
                        <li><strong>app.js errors:</strong> File bị syntax errors</li>
                        <li><strong>Missing elements:</strong> HTML chưa được thêm</li>
                        <li><strong>CSS conflicts:</strong> Styling bị override</li>
                        <li><strong>Backend errors:</strong> API không support date filter</li>
                        <li><strong>Cache issues:</strong> Browser cũ</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="test-section">
            <h3>🔧 6. Implementation Status</h3>
            <div id="implementationStatus"></div>
        </div>
    </div>
    
    <script>
        // Console logging system
        const consoleOutput = document.getElementById('consoleOutput');
        
        function logToConsole(message, type = 'log') {
            const timestamp = new Date().toLocaleTimeString();
            const className = `console-${type}`;
            const logEntry = document.createElement('div');
            logEntry.className = className;
            logEntry.innerHTML = `<span style="color: #666">[${timestamp}]</span> ${message}`;
            consoleOutput.appendChild(logEntry);
            consoleOutput.scrollTop = consoleOutput.scrollHeight;
            
            // Also log to actual console
            console[type](message);
        }
        
        function clearConsole() {
            consoleOutput.innerHTML = '';
            logToConsole('Console cleared', 'info');
        }
        
        // Test functions
        function testElements() {
            const results = document.getElementById('elementCheckResults');
            const elements = {
                'supportStartDate': document.getElementById('supportStartDate'),
                'supportEndDate': document.getElementById('supportEndDate'),
                'supportDateFilterBtn': document.getElementById('supportDateFilterBtn'),
                'supportClearDateFilterBtn': document.getElementById('supportClearDateFilterBtn'),
                'supportStatusFilter': document.getElementById('supportStatusFilter')
            };
            
            logToConsole('🔍 Checking HTML elements...', 'info');
            
            let html = '<h4>🔍 Element Check Results:</h4><div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">';
            
            for (const [id, element] of Object.entries(elements)) {
                const found = !!element;
                const statusClass = found ? 'status-found' : 'status-missing';
                const statusText = found ? '✅ Found' : '❌ Missing';
                
                html += `
                    <div class="status-indicator ${statusClass}">
                        <strong>${id}:</strong> ${statusText}
                    </div>
                `;
                
                logToConsole(`${found ? '✅' : '❌'} ${id}: ${found ? 'Found' : 'Missing'}`, found ? 'success' : 'error');
            }
            
            html += '</div>';
            
            const allFound = Object.values(elements).every(el => !!el);
            if (allFound) {
                html += '<p class="success">✅ All elements found successfully!</p>';
                logToConsole('✅ All elements found successfully!', 'success');
            } else {
                html += '<p class="error">❌ Some elements are missing. Check HTML implementation.</p>';
                logToConsole('❌ Some elements are missing', 'error');
            }
            
            results.innerHTML = html;
        }
        
        function bindEvents() {
            const results = document.getElementById('eventTestResults');
            
            logToConsole('⚡ Binding event listeners...', 'info');
            
            // Mock app methods
            const mockApp = {
                loadSupportRequestsWithDateFilter: function() {
                    const startDate = document.getElementById('supportStartDate').value;
                    const endDate = document.getElementById('supportEndDate').value;
                    const status = document.getElementById('supportStatusFilter').value || 'all';
                    
                    logToConsole(`Loading support requests with date filter: {startDate: "${startDate}", endDate: "${endDate}", status: "${status}"}`, 'info');
                    
                    // Build URL
                    let url = `api/support_requests.php?action=list&page=1&limit=9`;
                    if (status !== 'all') url += `&status=${status}`;
                    if (startDate) url += `&start_date=${startDate}`;
                    if (endDate) url += `&end_date=${endDate}`;
                    
                    logToConsole(`API URL: ${url}`, 'info');
                },
                
                clearSupportRequestDateFilter: function() {
                    logToConsole('Clearing support request date filter', 'warning');
                    document.getElementById('supportStartDate').value = '';
                    document.getElementById('supportEndDate').value = '';
                    logToConsole('Date inputs cleared', 'success');
                },
                
                loadSupportRequests: function(page) {
                    const status = document.getElementById('supportStatusFilter').value || 'all';
                    logToConsole(`Loading support requests with status: "${status}", page: ${page}`, 'info');
                }
            };
            
            // Bind events
            const events = [
                { id: 'supportDateFilterBtn', event: 'click', handler: () => mockApp.loadSupportRequestsWithDateFilter() },
                { id: 'supportClearDateFilterBtn', event: 'click', handler: () => mockApp.clearSupportRequestDateFilter() },
                { id: 'supportStartDate', event: 'change', handler: () => mockApp.loadSupportRequestsWithDateFilter() },
                { id: 'supportEndDate', event: 'change', handler: () => mockApp.loadSupportRequestsWithDateFilter() },
                { id: 'supportStatusFilter', event: 'change', handler: () => mockApp.loadSupportRequests(1) }
            ];
            
            let boundCount = 0;
            let html = '<h4>⚡ Event Binding Results:</h4>';
            
            events.forEach(({ id, event, handler }) => {
                const element = document.getElementById(id);
                if (element) {
                    element.addEventListener(event, handler);
                    boundCount++;
                    logToConsole(`✅ ${id} ${event} event bound`, 'success');
                    html += `<p class="success">✅ ${id} ${event} event bound</p>`;
                } else {
                    logToConsole(`❌ ${id} not found for ${event} event`, 'error');
                    html += `<p class="error">❌ ${id} not found for ${event} event</p>`;
                }
            });
            
            html += `<p class="info">📊 ${boundCount}/${events.length} events bound successfully</p>`;
            html += '<p><strong>Test the functionality:</strong> Change dates, click buttons, and check console output.</p>';
            
            results.innerHTML = html;
        }
        
        function testFunctionality() {
            logToConsole('🧪 Testing date filter functionality...', 'info');
            
            // Set test dates
            document.getElementById('supportStartDate').value = '2026-04-01';
            document.getElementById('supportEndDate').value = '2026-04-30';
            document.getElementById('supportStatusFilter').value = 'approved';
            
            logToConsole('📅 Set test dates: 2026-04-01 to 2026-04-30', 'info');
            logToConsole('📋 Set status: approved', 'info');
            
            // Trigger filter
            const filterBtn = document.getElementById('supportDateFilterBtn');
            if (filterBtn) {
                filterBtn.click();
                logToConsole('🔘 Clicked filter button', 'info');
            }
            
            setTimeout(() => {
                // Test clear filter
                const clearBtn = document.getElementById('supportClearDateFilterBtn');
                if (clearBtn) {
                    clearBtn.click();
                    logToConsole('🔘 Clicked clear button', 'info');
                }
            }, 1000);
            
            const results = document.getElementById('eventTestResults');
            results.innerHTML += '<p class="success">✅ Functionality test completed! Check console output above.</p>';
        }
        
        function testAPI() {
            const results = document.getElementById('apiTestResults');
            
            logToConsole('🌐 Testing basic API call...', 'info');
            
            fetch('api/support_requests.php?action=list&page=1&limit=9')
                .then(response => {
                    logToConsole(`API Response status: ${response.status}`, 'info');
                    return response.json();
                })
                .then(data => {
                    logToConsole('API Response received', 'success');
                    logToConsole(JSON.stringify(data, null, 2), 'info');
                    
                    results.innerHTML = `
                        <h4>🌐 Basic API Test Results:</h4>
                        <div class="code-block">
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                        <p class="${data.success ? 'success' : 'error'}">
                            ${data.success ? '✅ Basic API working!' : '❌ API error: ' + data.message}
                        </p>
                    `;
                })
                .catch(error => {
                    logToConsole(`API Error: ${error.message}`, 'error');
                    results.innerHTML = `
                        <h4>🌐 API Test Results:</h4>
                        <p class="error">❌ API Error: ${error.message}</p>
                        <p class="warning">⚠️ Check if api/support_requests.php exists and is working</p>
                    `;
                });
        }
        
        function testAPIWithDates() {
            const results = document.getElementById('apiTestResults');
            
            logToConsole('📅 Testing API with date parameters...', 'info');
            
            const startDate = '2026-04-01';
            const endDate = '2026-04-30';
            const status = 'approved';
            
            const url = `api/support_requests.php?action=list&start_date=${startDate}&end_date=${endDate}&status=${status}&page=1&limit=9`;
            
            logToConsole(`API URL: ${url}`, 'info');
            
            fetch(url)
                .then(response => {
                    logToConsole(`API Response status: ${response.status}`, 'info');
                    return response.json();
                })
                .then(data => {
                    logToConsole('API with dates response received', 'success');
                    logToConsole(JSON.stringify(data, null, 2), 'info');
                    
                    results.innerHTML = `
                        <h4>📅 API with Dates Test Results:</h4>
                        <div class="code-block">
                            <strong>URL:</strong> ${url}<br><br>
                            <strong>Response:</strong><br>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                        <p class="${data.success ? 'success' : 'warning'}">
                            ${data.success ? '✅ API with dates working!' : '⚠️ API may not support date filtering yet'}
                        </p>
                    `;
                })
                .catch(error => {
                    logToConsole(`API with dates Error: ${error.message}`, 'error');
                    results.innerHTML = `
                        <h4>📅 API with Dates Test Results:</h4>
                        <p class="error">❌ API Error: ${error.message}</p>
                        <p class="warning">⚠️ Backend may need date filtering implementation</p>
                    `;
                });
        }
        
        function clearResults() {
            document.getElementById('elementCheckResults').innerHTML = '';
            document.getElementById('eventTestResults').innerHTML = '';
            logToConsole('Results cleared', 'info');
        }
        
        function clearAPIClear() {
            document.getElementById('apiTestResults').innerHTML = '';
            logToConsole('API results cleared', 'info');
        }
        
        function checkImplementationStatus() {
            const status = document.getElementById('implementationStatus');
            
            const checks = [
                { name: 'HTML Elements Added', check: () => document.getElementById('supportStartDate') },
                { name: 'CSS Styling Applied', check: () => document.querySelector('.page-actions') },
                { name: 'JavaScript Events Bound', check: () => false }, // Will be updated after binding
                { name: 'API Endpoint Available', check: () => false }, // Will be updated after API test
                { name: 'Backend Date Support', check: () => false } // Will be updated after API test
            ];
            
            let html = '<h4>🔧 Implementation Status:</h4><div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">';
            
            checks.forEach(({ name, check }) => {
                const status = check();
                const statusClass = status ? 'status-found' : 'status-missing';
                const statusText = status ? '✅ Done' : '⏳ Pending';
                
                html += `
                    <div class="status-indicator ${statusClass}">
                        <strong>${name}:</strong> ${statusText}
                    </div>
                `;
            });
            
            html += '</div>';
            html += '<p class="info">📝 Run the tests above to update this status.</p>';
            
            status.innerHTML = html;
        }
        
        // Initialize on load
        window.addEventListener('load', () => {
            logToConsole('🚀 Standalone date filter test loaded', 'success');
            testElements();
            checkImplementationStatus();
        });
    </script>
</body>
</html>
