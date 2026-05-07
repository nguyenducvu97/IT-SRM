<!DOCTYPE html>
<html>
<head>
    <title>Test Date Filter</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; }
        .result { margin: 10px 0; padding: 10px; background: #f0f0f0; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Test Date Filter Functionality</h1>
    
    <div class="test-section">
        <h3>1. Check HTML Elements</h3>
        <div id="htmlCheck"></div>
    </div>
    
    <div class="test-section">
        <h3>2. Test API Calls</h3>
        <button onclick="testDateFilterAPI()">Test Date Filter API</button>
        <div id="apiResult"></div>
    </div>
    
    <script>
        // Check if date filter elements exist in main page
        function checkHTMLElements() {
            fetch('index.html')
                .then(response => response.text())
                .then(html => {
                    const hasStartDate = html.includes('id="startDate"');
                    const hasEndDate = html.includes('id="endDate"');
                    const hasClearButton = html.includes('id="clearDateFilter"');
                    const hasDateFilterScript = html.includes('date-filter-handler.js');
                    
                    let result = '<div class="result">';
                    if (hasStartDate) {
                        result += '<div class="success">✓ startDate input found</div>';
                    } else {
                        result += '<div class="error">✗ startDate input NOT found</div>';
                    }
                    
                    if (hasEndDate) {
                        result += '<div class="success">✓ endDate input found</div>';
                    } else {
                        result += '<div class="error">✗ endDate input NOT found</div>';
                    }
                    
                    if (hasClearButton) {
                        result += '<div class="success">✓ clearDateFilter button found</div>';
                    } else {
                        result += '<div class="error">✗ clearDateFilter button NOT found</div>';
                    }
                    
                    if (hasDateFilterScript) {
                        result += '<div class="success">✓ date-filter-handler.js script included</div>';
                    } else {
                        result += '<div class="error">✗ date-filter-handler.js script NOT included</div>';
                    }
                    
                    result += '</div>';
                    document.getElementById('htmlCheck').innerHTML = result;
                })
                .catch(error => {
                    document.getElementById('htmlCheck').innerHTML = 
                        '<div class="error">Error loading index.html: ' + error.message + '</div>';
                });
        }
        
        // Test API with date filter
        function testDateFilterAPI() {
            const testUrl = 'api/service_requests.php?action=list&start_date=2024-01-01&end_date=2024-12-31&limit=3';
            
            document.getElementById('apiResult').innerHTML = 
                '<div class="result">Testing API: ' + testUrl + '</div>';
            
            fetch(testUrl)
                .then(response => response.json())
                .then(data => {
                    let result = '<div class="result">';
                    if (data.success) {
                        result += '<div class="success">✓ API Response Success</div>';
                        result += '<div>Found ' + data.data.requests.length + ' requests</div>';
                        result += '<div>Total: ' + data.data.pagination.total + '</div>';
                    } else {
                        result += '<div class="error">✗ API Response Failed: ' + data.message + '</div>';
                    }
                    result += '</div>';
                    document.getElementById('apiResult').innerHTML += result;
                })
                .catch(error => {
                    document.getElementById('apiResult').innerHTML += 
                        '<div class="error">✗ API Error: ' + error.message + '</div>';
                });
        }
        
        // Run tests on page load
        window.onload = function() {
            checkHTMLElements();
        };
    </script>
</body>
</html>
