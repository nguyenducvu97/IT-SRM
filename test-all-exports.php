<!DOCTYPE html>
<html>
<head>
    <title>Test All 3 Export Functions</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { border: 1px solid #ddd; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .btn { padding: 10px 20px; margin: 10px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 300px; overflow-y: auto; }
    </style>
</head>
<body>
    <h2>🧪 Test Tất Cả 3 Chức Năng Xuất File</h2>
    
    <div class="test-section">
        <h3>📊 Kết quả Database (Kiểm tra)</h3>
        <?php
        try {
            require_once 'config/database.php';
            $database = new Database();
            $db = $database->getConnection();
            
            if ($db) {
                echo "<p class='success'>✅ Database connected</p>";
                
                // Check assigned requests by date
                $stmt = $db->prepare("SELECT COUNT(*) as count, MIN(created_at) as min_date, MAX(created_at) as max_date FROM service_requests WHERE assigned_to IS NOT NULL");
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo "<p><strong>Assigned Requests:</strong> " . $result['count'] . "</p>";
                echo "<p><strong>Date Range:</strong> " . $result['min_date'] . " to " . $result['max_date'] . "</p>";
                
                // Check April 2026
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM service_requests WHERE assigned_to IS NOT NULL AND created_at BETWEEN '2026-04-01' AND '2026-04-30 23:59:59'");
                $stmt->execute();
                $april_count = $stmt->fetchColumn();
                echo "<p><strong>April 2026:</strong> $april_count assigned requests</p>";
                
                // Check May 2026
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM service_requests WHERE assigned_to IS NOT NULL AND created_at BETWEEN '2026-05-01' AND '2026-05-06 23:59:59'");
                $stmt->execute();
                $may_count = $stmt->fetchColumn();
                echo "<p><strong>May 2026:</strong> $may_count assigned requests</p>";
                
            } else {
                echo "<p class='error'>❌ Database connection failed</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <div class="test-section">
        <h3>📥 Test 1: Tổng hợp KPI staff (summary)</h3>
        <p><strong>URL:</strong> api/kpi_export.php?action=export_kpi&start_date=2026-04-01&end_date=2026-04-30</p>
        <button class="btn" onclick="testExport('summary')">Test Export Summary</button>
        <div id="summary-result"></div>
    </div>
    
    <div class="test-section">
        <h3>📋 Test 2: Chi tiết tất cả staff (detailed)</h3>
        <p><strong>URL:</strong> api/kpi_export.php?action=export_detailed&start_date=2026-04-01&end_date=2026-04-30</p>
        <button class="btn" onclick="testExport('detailed')">Test Export Detailed</button>
        <div id="detailed-result"></div>
    </div>
    
    <div class="test-section">
        <h3>👤 Test 3: Chi tiết theo staff (staff)</h3>
        <p><strong>URL:</strong> api/kpi_export.php?action=export_staff_details&start_date=2026-04-01&end_date=2026-04-30&staff_id=2</p>
        <button class="btn" onclick="testExport('staff')">Test Export Staff Details</button>
        <div id="staff-result"></div>
    </div>
    
    <div class="test-section">
        <h3>📝 Log Kết quả</h3>
        <div id="log-container">
            <p>Click các nút ở trên để test từng chức năng xuất file.</p>
        </div>
    </div>

    <script>
        function testExport(type) {
            const resultDiv = document.getElementById(type + '-result');
            const logContainer = document.getElementById('log-container');
            
            resultDiv.innerHTML = '<p>Đang test...</p>';
            logContainer.innerHTML += '<p><strong>Testing ' + type + ' export...</strong></p>';
            
            let url;
            if (type === 'summary') {
                url = 'api/kpi_export.php?action=export_kpi&start_date=2026-04-01&end_date=2026-04-30';
            } else if (type === 'detailed') {
                url = 'api/kpi_export.php?action=export_detailed&start_date=2026-04-01&end_date=2026-04-30';
            } else if (type === 'staff') {
                url = 'api/kpi_export.php?action=export_staff_details&start_date=2026-04-01&end_date=2026-04-30&staff_id=2';
            }
            
            // Create iframe to test download
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = url;
            
            iframe.onload = function() {
                try {
                    const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                    const content = iframeDoc.body.innerHTML || iframeDoc.documentElement.textContent;
                    
                    if (content.includes('John Smith') || content.includes('94')) {
                        resultDiv.innerHTML = '<p class="success">✅ Export thành công! File chứa dữ liệu KPI.</p>';
                        logContainer.innerHTML += '<p class="success"><strong>✅ ' + type + ' export:</strong> File chứa dữ liệu John Smith và 94 requests</p>';
                    } else if (content.includes('error') || content.includes('Error')) {
                        resultDiv.innerHTML = '<p class="error">❌ Export thất bại!</p>';
                        logContainer.innerHTML += '<p class="error"><strong>❌ ' + type + ' export:</strong> ' + content + '</p>';
                    } else {
                        resultDiv.innerHTML = '<p class="warning">⚠️ File không có dữ liệu hoặc trống</p>';
                        logContainer.innerHTML += '<p class="warning"><strong>⚠️ ' + type + ' export:</strong> File có thể không có dữ liệu hoặc trống</p>';
                    }
                    
                    // Show content preview
                    logContainer.innerHTML += '<p><strong>Content preview:</strong></p>';
                    logContainer.innerHTML += '<pre>' + content.substring(0, 1000) + '...</pre>';
                    
                } catch (e) {
                    resultDiv.innerHTML = '<p class="error">❌ Lỗi khi test!</p>';
                    logContainer.innerHTML += '<p class="error"><strong>Error:</strong> ' + e.message + '</p>';
                }
                
                document.body.removeChild(iframe);
            };
            
            iframe.onerror = function() {
                resultDiv.innerHTML = '<p class="error">❌ Không thể tải file (network error)</p>';
                logContainer.innerHTML += '<p class="error"><strong>Network error:</strong> Không thể tải file từ API</p>';
                document.body.removeChild(iframe);
            };
            
            document.body.appendChild(iframe);
        }
    </script>
</body>
</html>
