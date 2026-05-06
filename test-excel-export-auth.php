<?php
// Test Excel Export with authentication
require_once 'config/session.php';
require_once 'config/database.php';

// Start session and login as admin
startSession();

// Simulate admin login
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

echo "<h2>🧪 Test Excel Export với Authentication</h2>";

// Test with date range that has actual data
echo "<h3>📊 Test với khoảng ngày có dữ liệu thực tế</h3>";

$test_ranges = [
    ['start' => '2026-04-01', 'end' => '2026-04-30', 'desc' => 'Tháng 4/2026 (có dữ liệu)'],
    ['start' => '2026-04-20', 'end' => '2026-04-30', 'desc' => '20-30/4/2026'],
    ['start' => '2026-04-10', 'end' => '2026-04-28', 'desc' => 'Toàn bộ dữ liệu'],
];

foreach ($test_ranges as $range) {
    echo "<h4>Test: {$range['desc']}</h4>";
    
    // Include the KPI export file to test the function
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        if ($db) {
            // Test getKPIDataArray function directly
            $kpi_data = getKPIDataArray($db, $range['start'], $range['end']);
            
            $total_requests = 0;
            $total_completed = 0;
            $staff_count = count($kpi_data);
            
            foreach ($kpi_data as $staff) {
                $total_requests += $staff['total_requests'];
                $total_completed += $staff['completed_requests'];
            }
            
            echo "<p style='color: green;'>✅ Success - Staff: $staff_count, Total Requests: $total_requests, Completed: $total_completed</p>";
            
            // Show first few staff data
            if (!empty($kpi_data)) {
                echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr><th>Staff</th><th>Total</th><th>Completed</th><th>K1</th><th>K2</th><th>K3</th><th>K4</th><th>KPI Total</th></tr>";
                $count = 0;
                foreach ($kpi_data as $staff) {
                    if ($count >= 3) break; // Only show first 3
                    echo "<tr>";
                    echo "<td>{$staff['full_name']}</td>";
                    echo "<td>{$staff['total_requests']}</td>";
                    echo "<td>{$staff['completed_requests']}</td>";
                    echo "<td>{$staff['k1_score']}</td>";
                    echo "<td>{$staff['k2_score']}</td>";
                    echo "<td>{$staff['k3_score']}</td>";
                    echo "<td>{$staff['k4_score']}</td>";
                    echo "<td><strong>{$staff['total_kpi_score']}</strong></td>";
                    echo "</tr>";
                    $count++;
                }
                echo "</table>";
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
    echo "<hr>";
}

// Test direct API call with session
echo "<h3>🔗 Test API Call với Session</h3>";
foreach ($test_ranges as $range) {
    echo "<h4>API Test: {$range['desc']}</h4>";
    
    $api_url = "http://localhost/it-service-request/api/kpi_export.php?action=get_kpi_data&start_date={$range['start']}&end_date={$range['end']}";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Cookie: PHPSESSID=" . session_id()
        ]
    ]);
    
    $response = file_get_contents($api_url, false, $context);
    
    if ($response) {
        $data = json_decode($response, true);
        if ($data && $data['success']) {
            $total_requests = 0;
            foreach ($data['data'] as $staff) {
                $total_requests += $staff['total_requests'];
            }
            echo "<p style='color: green;'>✅ API Success - Total requests: $total_requests</p>";
        } else {
            echo "<p style='color: red;'>❌ API Error: " . ($data['message'] ?? 'Unknown error') . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Failed to call API</p>";
    }
    echo "<hr>";
}

// Include the KPI export function
function getKPIDataArray($db, $start_date, $end_date) {
    $kpi_data = [];
    
    // Get staff users only (exclude admin from KPI calculation)
    $staff_query = "SELECT id, username, email, full_name, department 
                   FROM users 
                   WHERE role = 'staff' 
                   ORDER BY full_name";
    $staff_stmt = $db->prepare($staff_query);
    $staff_stmt->execute();
    $staff_list = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($staff_list as $staff) {
        $staff_id = $staff['id'];
        
        // Get statistics for date range
        $stats_query = "SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN sr.status IN ('resolved', 'closed') THEN 1 ELSE 0 END) as completed_requests,
        SUM(CASE WHEN sr.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_requests,
        SUM(CASE WHEN sr.status = 'open' THEN 1 ELSE 0 END) as open_requests,
        AVG(CASE WHEN sr.assigned_at IS NOT NULL 
            THEN TIMESTAMPDIFF(MINUTE, sr.created_at, sr.assigned_at) 
            ELSE NULL END) as avg_response_time_minutes,
        AVG(CASE WHEN sr.status IN ('resolved', 'closed') AND sr.resolved_at IS NOT NULL
            THEN TIMESTAMPDIFF(HOUR, sr.created_at, sr.resolved_at) 
            ELSE NULL END) as avg_completion_time_hours
        FROM service_requests sr
        WHERE sr.assigned_to = :staff_id 
        AND sr.created_at BETWEEN :start_date AND CONCAT(:end_date, ' 23:59:59')";
        
        $stats_stmt = $db->prepare($stats_query);
        $stats_stmt->bindParam(':staff_id', $staff_id);
        $stats_stmt->bindParam(':start_date', $start_date);
        $stats_stmt->bindParam(':end_date', $end_date);
        $stats_stmt->execute();
        $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get feedback data
        $feedback_query = "SELECT 
            COUNT(rf.id) as total_feedback,
            AVG(rf.rating) as avg_rating,
            SUM(CASE WHEN rf.rating >= 4 THEN 1 ELSE 0 END) as positive_feedback,
            SUM(CASE WHEN rf.rating <= 2 THEN 1 ELSE 0 END) as negative_feedback,
            COUNT(DISTINCT rf.service_request_id) as rated_requests
            FROM request_feedback rf
            JOIN service_requests sr ON rf.service_request_id = sr.id
            WHERE sr.assigned_to = :staff_id
            AND sr.status IN ('resolved', 'closed')
            AND sr.created_at BETWEEN :start_date AND CONCAT(:end_date, ' 23:59:59')
            AND sr.assigned_at IS NOT NULL
            AND sr.estimated_completion IS NOT NULL
            AND sr.resolved_at IS NOT NULL
            AND rf.rating IS NOT NULL
            AND rf.processing_results IS NOT NULL";
        
        $feedback_stmt = $db->prepare($feedback_query);
        $feedback_stmt->bindParam(':staff_id', $staff_id);
        $feedback_stmt->bindParam(':start_date', $start_date);
        $feedback_stmt->bindParam(':end_date', $end_date);
        $feedback_stmt->execute();
        $feedback = $feedback_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate KPI scores (simplified for testing)
        $avg_response_time_minutes = (float)($stats['avg_response_time_minutes'] ?? 0);
        $completed_requests = (int)($stats['completed_requests'] ?? 0);
        $total_requests = (int)($stats['total_requests'] ?? 0);
        $avg_rating_score = (float)($feedback['avg_rating'] ?? 0);
        $total_feedback = (int)($feedback['total_feedback'] ?? 0);
        
        // K1 Score
        $k1_score = 1;
        if ($avg_response_time_minutes > 0) {
            if ($avg_response_time_minutes <= 15) {
                $k1_score = 5;
            } elseif ($avg_response_time_minutes <= 60) {
                $k1_score = 3;
            } else {
                $k1_score = 1;
            }
        }
        
        // K2 Score (simplified)
        $k2_score = $completed_requests > 0 ? 3 : 1;
        
        // K3 Score
        $k3_score = $avg_rating_score > 0 ? round($avg_rating_score, 1) : 1;
        
        // K4 Score (simplified)
        $k4_score = $total_feedback > 0 ? 4 : 1;
        
        // Total KPI Score
        $total_kpi_score = ($k1_score * 0.15) + ($k2_score * 0.35) + ($k3_score * 0.40) + ($k4_score * 0.10);
        
        $kpi_data[] = array_merge($staff, [
            'total_requests' => $total_requests,
            'completed_requests' => $completed_requests,
            'in_progress_requests' => (int)($stats['in_progress_requests'] ?? 0),
            'open_requests' => (int)($stats['open_requests'] ?? 0),
            'avg_response_time_minutes' => $avg_response_time_minutes,
            'avg_completion_time_hours' => (float)($stats['avg_completion_time_hours'] ?? 0),
            'total_feedback' => $total_feedback,
            'avg_rating' => $avg_rating_score,
            'positive_feedback' => (int)($feedback['positive_feedback'] ?? 0),
            'negative_feedback' => (int)($feedback['negative_feedback'] ?? 0),
            'rated_requests' => (int)($feedback['rated_requests'] ?? 0),
            'k1_score' => $k1_score,
            'k2_score' => $k2_score,
            'k3_score' => $k3_score,
            'k4_score' => $k4_score,
            'total_kpi_score' => round($total_kpi_score, 2)
        ]);
    }
    
    return $kpi_data;
}

echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3, h4 { color: #333; }
hr { margin: 20px 0; }
table { margin: 10px 0; }
th, td { padding: 5px; text-align: center; }
</style>";
?>
