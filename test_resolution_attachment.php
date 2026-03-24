<!DOCTYPE html>
<html>
<head>
    <title>Test Resolution Attachment API</title>
</head>
<body>
    <h1>Test Resolution Attachment API</h1>
    
    <h2>Test Image: Picture1.png</h2>
    <p>Direct URL: <code>api/attachment.php?file=69c2432932d05_Picture1.png&action=view</code></p>
    <img src="api/attachment.php?file=69c2432932d05_Picture1.png&action=view" 
         alt="Test Image" 
         style="max-width: 200px; border: 1px solid #ccc;"
         onerror="console.error('Image failed to load'); this.style.background='red';"
         onload="console.log('Image loaded successfully');">
    
    <h2>Test PDF</h2>
    <p>Direct URL: <code>api/attachment.php?file=69c2432933a93_Chuyên viên IT_Nguyễn Đức Vũ_0559438559.pdf&action=view</code></p>
    <iframe src="api/attachment.php?file=69c2432933a93_Chuyên viên IT_Nguyễn Đức Vũ_0559438559.pdf&action=view" 
            style="width: 100%; height: 300px; border: 1px solid #ccc;"
            onerror="console.error('PDF failed to load');">
    </iframe>
    
    <h2>Debug API Response</h2>
    <button onclick="testAPI()">Test API Response</button>
    <div id="apiResult"></div>
    
    <script>
        function testAPI() {
            fetch('api/attachment.php?file=69c2432932d05_Picture1.png&action=view')
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    console.log('Response type:', response.type);
                    
                    document.getElementById('apiResult').innerHTML = `
                        <p><strong>Status:</strong> ${response.status}</p>
                        <p><strong>Content-Type:</strong> ${response.headers.get('content-type')}</p>
                        <p><strong>Content-Length:</strong> ${response.headers.get('content-length')}</p>
                    `;
                    
                    return response.blob();
                })
                .then(blob => {
                    console.log('Blob size:', blob.size);
                    console.log('Blob type:', blob.type);
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('apiResult').innerHTML += `<p style="color: red;"><strong>Error:</strong> ${error.message}</p>`;
                });
        }
    </script>
</body>
</html>
