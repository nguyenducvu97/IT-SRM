<!DOCTYPE html>
<html>
<head>
    <title>Test Final Result</title>
</head>
<body>
    <h1>Test Final Result</h1>
    
    <h2>Test Resolution Attachment</h2>
    <button onclick="testFinal()">Test Final Result</button>
    <div id="finalResult"></div>
    
    <h2>Check Latest Logs</h2>
    <button onclick="window.open('http://localhost/it-service-request/check_logs_100.php', '_blank')">Open Latest Logs</button>
    
    <script>
        function testFinal() {
            const url = 'http://localhost/it-service-request/api/attachment.php?file=69c257fd27677_2026-03-20%20-%20Hi%E1%BB%87n%20tr%E1%BA%A1ng%20theo%20d%C3%B5i%20Lot..jpg&action=view';
            
            fetch(url)
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', [...response.headers.entries()]);
                    
                    let resultHtml = `
                        <p><strong>Status:</strong> ${response.status}</p>
                        <p><strong>Content-Type:</strong> ${response.headers.get('content-type')}</p>
                        <p><strong>Content-Length:</strong> ${response.headers.get('content-length')}</p>
                    `;
                    
                    if (response.status === 200 && response.headers.get('content-type')?.startsWith('image/')) {
                        resultHtml += `<p style="color: green;"><strong>✅ SUCCESS: Image loaded successfully!</strong></p>`;
                        resultHtml += `<img src="${url}" style="max-width: 300px; border: 1px solid #ccc;">`;
                    } else {
                        return response.text().then(text => {
                            resultHtml += `<p style="color: red;"><strong>❌ FAILED:</strong> ${text}</p>`;
                            document.getElementById('finalResult').innerHTML = resultHtml;
                        });
                    }
                    
                    document.getElementById('finalResult').innerHTML = resultHtml;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('finalResult').innerHTML = `<p style="color: red;"><strong>Error:</strong> ${error.message}</p>`;
                });
        }
    </script>
</body>
</html>
