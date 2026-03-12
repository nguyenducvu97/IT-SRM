<!DOCTYPE html>
<html>
<head>
    <title>Test Comment API</title>
</head>
<body>
    <h1>Test Comment API</h1>
    <button onclick="testComment()">Test Add Comment</button>
    <div id="result"></div>

    <script>
    async function testComment() {
        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = 'Testing...';
        
        try {
            const response = await fetch('http://localhost/it-service-request/api/comments.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    service_request_id: 31,
                    comment: 'Test comment from browser at ' + new Date().toISOString()
                })
            });
            
            const text = await response.text();
            resultDiv.innerHTML = `
                <h3>Response:</h3>
                <p><strong>Status:</strong> ${response.status}</p>
                <p><strong>Headers:</strong> ${response.headers.get('content-type')}</p>
                <pre>${text}</pre>
            `;
            
            console.log('Response:', response);
            console.log('Response text:', text);
            
        } catch (error) {
            resultDiv.innerHTML = `<p style="color: red;">Error: ${error.message}</p>`;
            console.error('Error:', error);
        }
    }
    </script>
</body>
</html>
