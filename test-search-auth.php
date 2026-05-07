<!DOCTYPE html>
<html>
<head>
    <title>Test Search with Authentication</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; }
        .result { margin: 10px 0; padding: 10px; background: #f0f0f0; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        input, button { margin: 5px; padding: 10px; }
        .search-box { width: 300px; }
        button { background: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test Search Functionality</h1>
        
        <div class="test-section">
            <h3>Test Search with Real Authentication</h3>
            <p>First, login to the system, then test search functionality:</p>
            
            <div>
                <a href="index.html" target="_blank">Open Main System</a>
                <span style="margin: 0 20px;">|</span>
                <a href="test-search-functionality.php" target="_blank">Test Search API</a>
            </div>
        </div>
        
        <div class="test-section">
            <h3>Quick Search Test</h3>
            <p>Test search without authentication (should show "Unauthorized"):</p>
            
            <div>
                <input type="text" id="searchInput" class="search-box" placeholder="Enter search term...">
                <button onclick="testSearch()">Test Search</button>
            </div>
            
            <div id="searchResult"></div>
        </div>
        
        <div class="test-section">
            <h3>Instructions</h3>
            <ol>
                <li>Open the main system in a new tab and login with valid credentials</li>
                <li>Come back to this tab and click "Test Search API"</li>
                <li>The search should work if you're properly logged in</li>
                <li>Check browser console for any errors</li>
                <li>Check network tab for API requests</li>
            </ol>
        </div>
    </div>
    
    <script>
        function testSearch() {
            const searchTerm = document.getElementById('searchInput').value.trim();
            if (!searchTerm) {
                document.getElementById('searchResult').innerHTML = 
                    '<div class="error">Please enter a search term</div>';
                return;
            }
            
            const testUrl = `api/search_requests.php?search=${encodeURIComponent(searchTerm)}&limit=3`;
            document.getElementById('searchResult').innerHTML = 
                '<div class="info">Testing: ' + testUrl + '</div>';
            
            fetch(testUrl)
                .then(response => response.json())
                .then(data => {
                    let result = '<div class="result">';
                    if (data.success) {
                        result += '<div class="success">✓ SUCCESS: Found ' + 
                                 data.data.pagination.total + ' requests</div>';
                        if (data.data.requests.length > 0) {
                            result += '<div>First result: ' + 
                                         data.data.requests[0].title + '</div>';
                        }
                    } else {
                        result += '<div class="error">✗ FAILED: ' + data.message + '</div>';
                    }
                    result += '</div>';
                    document.getElementById('searchResult').innerHTML = result;
                })
                .catch(error => {
                    document.getElementById('searchResult').innerHTML = 
                        '<div class="error">✗ ERROR: ' + error.message + '</div>';
                });
        }
    </script>
</body>
</html>
