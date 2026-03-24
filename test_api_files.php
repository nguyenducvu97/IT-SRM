<!DOCTYPE html>
<html>
<head>
    <title>Test API Files</title>
</head>
<body>
    <h1>Test API Files</h1>
    
    <h2>Test Simple PHP</h2>
    <button onclick="testSimple()">Test test.php</button>
    <div id="simpleResult"></div>
    
    <h2>Test Attachment API</h2>
    <button onclick="testAttachment()">Test attachment.php</button>
    <div id="attachmentResult"></div>
    
    <script>
        function testSimple() {
            fetch('http://localhost/it-service-request/api/test.php')
                .then(response => response.text())
                .then(text => {
                    console.log('Simple test result:', text);
                    document.getElementById('simpleResult').innerHTML = text;
                })
                .catch(error => {
                    console.error('Simple test error:', error);
                    document.getElementById('simpleResult').innerHTML = 'Error: ' + error.message;
                });
        }
        
        function testAttachment() {
            fetch('http://localhost/it-service-request/api/attachment.php?file=test&action=view')
                .then(response => response.text())
                .then(text => {
                    console.log('Attachment test result:', text);
                    document.getElementById('attachmentResult').innerHTML = text;
                })
                .catch(error => {
                    console.error('Attachment test error:', error);
                    document.getElementById('attachmentResult').innerHTML = 'Error: ' + error.message;
                });
        }
    </script>
</body>
</html>
