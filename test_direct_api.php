<!DOCTYPE html>
<html>
<head>
    <title>Test Attachment API Direct</title>
</head>
<body>
    <h1>Test Attachment API Direct</h1>
    
    <h2>Test Resolution Attachment</h2>
    <button onclick="testDirectAPI()">Test API Directly</button>
    <div id="result"></div>
    
    <script>
        function testDirectAPI() {
            const url = 'http://localhost/it-service-request/api/attachment.php?file=69c257fd27677_2026-03-20%20-%20Hi%E1%BB%87n%20tr%E1%BA%A1ng%20theo%20d%C3%B5i%20Lot..jpg&action=view';
            
            fetch(url)
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', [...response.headers.entries()]);
                    
                    document.getElementById('result').innerHTML = `
                        <p><strong>Status:</strong> ${response.status}</p>
                        <p><strong>Content-Type:</strong> ${response.headers.get('content-type')}</p>
                        <p><strong>Content-Length:</strong> ${response.headers.get('content-length')}</p>
                    `;
                    
                    return response.text();
                })
                .then(text => {
                    console.log('Response text:', text);
                    document.getElementById('result').innerHTML += `<p><strong>Response:</strong> ${text.substring(0, 200)}...</p>`;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('result').innerHTML += `<p style="color: red;"><strong>Error:</strong> ${error.message}</p>`;
                });
        }
    </script>
</body>
</html>
