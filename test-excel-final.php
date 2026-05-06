<?php
// Final Test Excel Export
require_once 'config/session.php';
require_once 'config/database.php';

// Start session and login as admin
startSession();

// Simulate admin login
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

echo "<h2>🧪 Final Test Excel Export</h2>";

// Simulate GET parameters for export
$_GET['action'] = 'export_kpi';
$_GET['start_date'] = '2026-04-01';
$_GET['end_date'] = '2026-04-30';

echo "<p><strong>Parameters:</strong> action={$_GET['action']}, start_date={$_GET['start_date']}, end_date={$_GET['end_date']}</p>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "<p style='color: green;'>✅ Database connected</p>";
        
        // Capture the output
        ob_start();
        include 'api/kpi_export.php';
        $output = ob_get_clean();
        
        echo "<p><strong>Output length:</strong> " . strlen($output) . " characters</p>";
        
        if (strlen($output) > 0) {
            // Save to file for inspection
            file_put_contents('test_export_final.csv', $output);
            echo "<p style='color: green;'>✅ Export saved to <a href='test_export_final.csv' target='_blank'>test_export_final.csv</a></p>";
            
            // Show first few lines
            $lines = explode("\n", $output);
            echo "<h4>First 15 lines of CSV:</h4>";
            echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 400px; overflow-y: auto;'>";
            for ($i = 0; $i < min(15, count($lines)); $i++) {
                echo htmlspecialchars($lines[$i]) . "\n";
            }
            echo "</pre>";
            
            // Check if it contains expected data
            if (strpos($output, 'John Smith') !== false && strpos($output, '93') !== false) {
                echo "<p style='color: green;'>✅ File contains expected data (John Smith, 93 requests)</p>";
            } else {
                echo "<p style='color: red;'>❌ File missing expected data</p>";
            }
            
            // Check for date range info
            if (strpos($output, '2026-04-01 đến 2026-04-30') !== false) {
                echo "<p style='color: green;'>✅ File contains date range information</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ File missing date range information</p>";
            }
            
            // Check for export timestamp
            if (strpos($output, 'Ngày xuất:') !== false) {
                echo "<p style='color: green;'>✅ File contains export timestamp</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ File missing export timestamp</p>";
            }
            
        } else {
            echo "<p style='color: red;'>❌ No output from export function</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3, h4 { color: #333; }
hr { margin: 20px 0; }
table { border-collapse: collapse; margin: 10px 0; }
th, td { padding: 8px; text-align: center; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
</style>";
?>
