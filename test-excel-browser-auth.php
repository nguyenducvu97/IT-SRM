<!DOCTYPE html>
<html>
<head>
    <title>Test Excel Export with Auth</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { border: 1px solid #ddd; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h2>🧪 Test Excel Export with Authentication</h2>
    
    <div class="test-section">
        <h3>Step 1: Login as Admin</h3>
        <p>First, login to the system as admin, then test the export.</p>
        <a href="index.html" target="_blank">🔐 Go to Login Page</a>
    </div>
    
    <div class="test-section">
        <h3>Step 2: Test Export Links</h3>
        <p>After logging in, click these links to test the export:</p>
        
        <p><strong>Test 1: April 2026 (should have data)</strong></p>
        <a href="api/kpi_export.php?action=export_kpi&start_date=2026-04-01&end_date=2026-04-30" target="_blank">📥 Download Excel (April 2026)</a>
        
        <p><strong>Test 2: May 2026 (should be empty - no assigned requests)</strong></p>
        <a href="api/kpi_export.php?action=export_kpi&start_date=2026-05-01&end_date=2026-05-06" target="_blank">📥 Download Excel (May 2026)</a>
        
        <p><strong>Test 3: Get KPI Data (JSON)</strong></p>
        <a href="api/kpi_export.php?action=get_kpi_data&start_date=2026-04-01&end_date=2026-04-30" target="_blank">🔍 Get KPI Data (JSON)</a>
    </div>
    
    <div class="test-section">
        <h3>Step 3: Manual Test Form</h3>
        <form action="api/kpi_export.php" method="GET" target="_blank">
            <input type="hidden" name="action" value="export_kpi">
            <label>Start Date: <input type="date" name="start_date" value="2026-04-01" required></label><br><br>
            <label>End Date: <input type="date" name="end_date" value="2026-04-30" required></label><br><br>
            <input type="submit" value="Export Excel">
        </form>
    </div>
    
    <div class="test-section">
        <h3>📋 Expected Results</h3>
        <p><strong>For April 2026:</strong></p>
        <ul>
            <li>CSV file should download automatically</li>
            <li>File should contain 2 staff members</li>
            <li>John Smith should have 93 total requests, 10 completed</li>
            <li>Test staff should have 1 total request, 0 completed</li>
            <li>File should include date range: "2026-04-01 đến 2026-04-30"</li>
            <li>File should include export timestamp</li>
        </ul>
        
        <p><strong>For May 2026:</strong></p>
        <ul>
            <li>CSV file should download but contain only headers and summary</li>
            <li>No staff data because requests are not assigned</li>
        </ul>
    </div>
    
    <div class="test-section">
        <h3>🔍 Debug Information</h3>
        <p><strong>If export doesn't work:</strong></p>
        <ol>
            <li>Open browser developer tools (F12)</li>
            <li>Go to Network tab</li>
            <li>Click the export link</li>
            <li>Check the response:</li>
            <ul>
                <li>If you see JSON error → Authentication failed</li>
                <li>If you see CSV content → Export working</li>
                <li>If you see 500 error → Server error</li>
            </ul>
        </ol>
    </div>
    
    <div class="test-section">
        <h3>📊 Current Database Status</h3>
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
</body>
</html>
