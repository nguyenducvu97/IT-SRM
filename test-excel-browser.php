<!DOCTYPE html>
<html>
<head>
    <title>Test Excel Export</title>
</head>
<body>
    <h2>Test Excel Export via Browser</h2>
    
    <p><a href="api/kpi_export.php?action=export_kpi&start_date=2026-04-01&end_date=2026-04-30" target="_blank">📥 Download Excel (April 2026)</a></p>
    
    <p><a href="api/kpi_export.php?action=get_kpi_data&start_date=2026-04-01&end_date=2026-04-30" target="_blank">🔍 Get KPI Data (JSON)</a></p>
    
    <h3>Manual Test Form</h3>
    <form action="api/kpi_export.php" method="GET" target="_blank">
        <input type="hidden" name="action" value="export_kpi">
        <label>Start Date: <input type="date" name="start_date" value="2026-04-01"></label><br><br>
        <label>End Date: <input type="date" name="end_date" value="2026-04-30"></label><br><br>
        <input type="submit" value="Export Excel">
    </form>
    
    <h3>Debug Information</h3>
    <p>Open browser developer tools (F12) and check Network tab when clicking the links above.</p>
    <p>Expected: CSV file download with KPI data for April 2026.</p>
</body>
</html>
