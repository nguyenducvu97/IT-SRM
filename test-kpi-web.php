<!DOCTYPE html>
<html>
<head>
    <title>KPI Export Test</title>
</head>
<body>
    <h2>KPI Export Web Test</h2>
    <div id="result"></div>
    
    <script>
        // Test KPI Export API
        fetch('api/kpi_export.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'start_date=2026-04-01&end_date=2026-05-30',
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('result').innerHTML = `
                <h3>Result:</h3>
                <pre>${JSON.stringify(data, null, 2)}</pre>
                <p>Status: ${data.success ? '✅ Success' : '❌ Failed'}</p>
            `;
        })
        .catch(error => {
            document.getElementById('result').innerHTML = `
                <h3>Error:</h3>
                <p style="color: red;">${error.message}</p>
            `;
        });
    </script>
</body>
</html>
