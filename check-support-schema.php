<?php
require_once 'config/database.php';

try {
    $db = getDatabaseConnection();
    
    echo "<h2>🔍 SUPPORT REQUESTS SCHEMA CHECK</h2>";
    
    // Check support_requests table structure
    echo "<h3>📋 Support Requests Table Structure:</h3>";
    
    $result = $db->query('DESCRIBE support_requests');
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td><strong>{$column['Field']}</strong></td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>" . ($column['Default'] ?: 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check what columns actually exist
    echo "<h3>🔍 Available Columns Analysis:</h3>";
    
    $column_names = array_column($columns, 'Field');
    
    echo "<ul>";
    foreach ($column_names as $col) {
        echo "<li>✅ $col</li>";
    }
    echo "</ul>";
    
    // Check for common column names that might be what we need
    echo "<h3>🎯 Column Mapping:</h3>";
    
    $mappings = [
        'reason' => 'Support reason/description',
        'details' => 'Support details', 
        'description' => 'Support description',
        'message' => 'Support message',
        'content' => 'Support content',
        'staff_reason' => 'Staff reason for escalation',
        'admin_reason' => 'Admin decision reason'
    ];
    
    foreach ($mappings as $possible_col => $description) {
        if (in_array($possible_col, $column_names)) {
            echo "<p>✅ <strong>$possible_col</strong> - $description</p>";
        } else {
            echo "<p>❌ <strong>$possible_col</strong> - $description (NOT FOUND)</p>";
        }
    }
    
    // Check sample data
    echo "<h3>📄 Sample Data Check:</h3>";
    
    $sample_query = "SELECT * FROM support_requests LIMIT 3";
    $sample_data = $db->query($sample_query)->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($sample_data) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr>";
        foreach ($column_names as $col) {
            echo "<th>$col</th>";
        }
        echo "</tr>";
        
        foreach ($sample_data as $row) {
            echo "<tr>";
            foreach ($column_names as $col) {
                $value = $row[$col];
                if (strlen($value) > 50) {
                    $value = substr($value, 0, 50) . "...";
                }
                echo "<td>" . htmlspecialchars($value ?: 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No sample data found</p>";
    }
    
    // Create corrected performance analysis
    echo "<h3>🔧 Corrected Performance Analysis:</h3>";
    
    // Build query with existing columns
    $select_fields = ["sr.id", "sr.service_request_id", "sr.status"];
    $join_fields = ["sreq.title as request_title", "sreq.created_at as request_created"];
    $user_fields = ["u1.full_name as staff_name", "u2.full_name as admin_name"];
    
    // Add available columns for support details
    $support_detail_fields = [];
    foreach (['details', 'description', 'message', 'content', 'reason'] as $col) {
        if (in_array($col, $column_names)) {
            $support_detail_fields[] = "sr.$col as support_detail";
        }
    }
    
    $all_fields = array_merge($select_fields, $join_fields, $user_fields, $support_detail_fields);
    
    $corrected_query = "
        SELECT " . implode(', ', $all_fields) . ",
               TIMESTAMPDIFF(MINUTE, sr.created_at, sr.updated_at) as support_processing_minutes,
               TIMESTAMPDIFF(MINUTE, sreq.created_at, sr.created_at) as time_to_escalate_minutes,
               TIMESTAMPDIFF(MINUTE, sreq.created_at, sr.updated_at) as total_time_minutes
        FROM support_requests sr
        LEFT JOIN service_requests sreq ON sr.service_request_id = sreq.id
        LEFT JOIN users u1 ON sr.staff_id = u1.id
        LEFT JOIN users u2 ON sr.admin_id = u2.id
        ORDER BY sr.created_at DESC
        LIMIT 5
    ";
    
    echo "<p><strong>Corrected Query:</strong></p>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
    echo htmlspecialchars($corrected_query);
    echo "</pre>";
    
    // Test the corrected query
    try {
        $test_result = $db->query($corrected_query)->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>✅ Corrected Query Test Results:</h4>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Request Title</th><th>Staff</th><th>Admin</th><th>Status</th><th>Processing Time</th>";
        if (!empty($support_detail_fields)) {
            echo "<th>Support Detail</th>";
        }
        echo "</tr>";
        
        foreach ($test_result as $req) {
            $row_style = $req['support_processing_minutes'] > 30 ? 'background: #f8d7da;' : '';
            
            echo "<tr style='$row_style'>";
            echo "<td>{$req['id']}</td>";
            echo "<td>" . htmlspecialchars(substr($req['request_title'], 0, 30)) . "...</td>";
            echo "<td>{$req['staff_name']}</td>";
            echo "<td>{$req['admin_name']}</td>";
            echo "<td>{$req['status']}</td>";
            echo "<td><strong>" . ($req['support_processing_minutes'] ? "{$req['support_processing_minutes']} min" : 'N/A') . "</strong></td>";
            
            if (!empty($support_detail_fields) && isset($req['support_detail'])) {
                echo "<td>" . htmlspecialchars(substr($req['support_detail'], 0, 30)) . "...</td>";
            }
            
            echo "</tr>";
        }
        
        echo "</table>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Corrected query failed: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>📝 Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Use the corrected query for performance analysis</li>";
    echo "<li>Update the performance analysis script with proper column names</li>";
    echo "<li>Consider adding missing columns if needed for better tracking</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
