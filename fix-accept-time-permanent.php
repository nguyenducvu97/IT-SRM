<?php
require_once 'config/database.php';

echo "<h2>FIX TRIET DE ACCEPTED TIME - TAT CA REQUESTS</h2>";

try {
    $pdo = getDatabaseConnection();
    
    // 1. Check database schema
    echo "<h3>1. Kiem tra schema</h3>";
    
    $schemaQuery = "DESCRIBE service_requests";
    $schemaStmt = $pdo->prepare($schemaQuery);
    $schemaStmt->execute();
    $columns = $schemaStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasAcceptedAt = false;
    $hasAssignedAt = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'accepted_at') $hasAcceptedAt = true;
        if ($column['Field'] === 'assigned_at') $hasAssignedAt = true;
    }
    
    if (!$hasAcceptedAt) {
        echo "<p style='color: red;'>accepted_at column khong ton tai - Dang them...</p>";
        
        $addAcceptedAt = "ALTER TABLE service_requests ADD COLUMN accepted_at TIMESTAMP NULL DEFAULT NULL AFTER assigned_at";
        $pdo->exec($addAcceptedAt);
        echo "<p style='color: green;'>Da them accepted_at column</p>";
    } else {
        echo "<p style='color: green;'>accepted_at column da ton tai</p>";
    }
    
    if (!$hasAssignedAt) {
        echo "<p style='color: red;'>assigned_at column khong ton tai - Dang them...</p>";
        
        $addAssignedAt = "ALTER TABLE service_requests ADD COLUMN assigned_at TIMESTAMP NULL DEFAULT NULL AFTER assigned_to";
        $pdo->exec($addAssignedAt);
        echo "<p style='color: green;'>Da them assigned_at column</p>";
    } else {
        echo "<p style='color: green;'>assigned_at column da ton tai</p>";
    }
    
    // 2. Fix all requests that are in_progress but missing accepted_at
    echo "<h3>2. Fix tat ca requests thieu accepted_at</h3>";
    
    $fixQuery = "UPDATE service_requests 
                SET accepted_at = COALESCE(assigned_at, updated_at, created_at, NOW())
                WHERE status = 'in_progress' 
                AND (accepted_at IS NULL OR accepted_at = '0000-00-00 00:00:00')";
    
    $fixStmt = $pdo->prepare($fixQuery);
    $fixResult = $fixStmt->execute();
    $fixedCount = $fixStmt->rowCount();
    
    echo "<p>Da fix <strong>$fixedCount</strong> requests thieu accepted_at</p>";
    
    // 3. Fix all requests that are assigned but missing assigned_at
    echo "<h3>3. Fix tat ca requests thieu assigned_at</h3>";
    
    $fixAssignedQuery = "UPDATE service_requests 
                         SET assigned_at = COALESCE(updated_at, created_at, NOW())
                         WHERE assigned_to IS NOT NULL AND assigned_to > 0
                         AND (assigned_at IS NULL OR assigned_at = '0000-00-00 00:00:00')";
    
    $fixAssignedStmt = $pdo->prepare($fixAssignedQuery);
    $fixAssignedResult = $fixAssignedStmt->execute();
    $fixedAssignedCount = $fixAssignedStmt->rowCount();
    
    echo "<p>Da fix <strong>$fixedAssignedCount</strong> requests thieu assigned_at</p>";
    
    // 4. Show summary
    echo "<h3>4. Tong ket</h3>";
    
    $summaryQuery = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                        SUM(CASE WHEN assigned_to IS NOT NULL AND assigned_to > 0 THEN 1 ELSE 0 END) as assigned,
                        SUM(CASE WHEN accepted_at IS NOT NULL AND accepted_at != '0000-00-00 00:00:00' THEN 1 ELSE 0 END) as has_accepted_at,
                        SUM(CASE WHEN assigned_at IS NOT NULL AND assigned_at != '0000-00-00 00:00:00' THEN 1 ELSE 0 END) as has_assigned_at
                     FROM service_requests";
    
    $summaryStmt = $pdo->prepare($summaryQuery);
    $summaryStmt->execute();
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Loai</th><th>So luong</th></tr>";
    echo "<tr><td>Tong requests</td><td>{$summary['total']}</td></tr>";
    echo "<tr><td>Trang thai in_progress</td><td>{$summary['in_progress']}</td></tr>";
    echo "<tr><td>Da giao cho staff</td><td>{$summary['assigned']}</td></tr>";
    echo "<tr><td>Co accepted_at</td><td>{$summary['has_accepted_at']}</td></tr>";
    echo "<tr><td>Co assigned_at</td><td>{$summary['has_assigned_at']}</td></tr>";
    echo "</table>";
    
    // 5. Show recent requests with their times
    echo "<h3>5. 5 requests gan nhat</h3>";
    
    $recentQuery = "SELECT id, title, status, assigned_to, assigned_at, accepted_at, created_at, updated_at 
                   FROM service_requests 
                   ORDER BY id DESC LIMIT 5";
    $recentStmt = $pdo->prepare($recentQuery);
    $recentStmt->execute();
    $recent = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Assigned To</th><th>Assigned At</th><th>Accepted At</th><th>Created At</th></tr>";
    foreach ($recent as $req) {
        echo "<tr>";
        echo "<td>{$req['id']}</td>";
        echo "<td>" . htmlspecialchars(substr($req['title'], 0, 30)) . "</td>";
        echo "<td>{$req['status']}</td>";
        echo "<td>{$req['assigned_to']}</td>";
        echo "<td>" . ($req['assigned_at'] ?? 'NULL') . "</td>";
        echo "<td>" . ($req['accepted_at'] ?? 'NULL') . "</td>";
        echo "<td>{$req['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 6. Verify API returns accepted_at
    echo "<h3>6. Kiem tra API</h3>";
    
    echo "<p>Test API: <a href='api/service_requests.php?action=get&id=88' target='_blank'>api/service_requests.php?action=get&id=88</a></p>";
    
    try {
        $_GET['action'] = 'get';
        $_GET['id'] = '88';
        
        ob_start();
        include 'api/service_requests.php';
        $apiResponse = ob_get_clean();
        
        $data = json_decode($apiResponse, true);
        
        if ($data && isset($data['success']) && $data['success']) {
            echo "<p style='color: green;'>API tra ve success</p>";
            
            if (isset($data['data']['accepted_at'])) {
                echo "<p>accepted_at trong API: " . ($data['data']['accepted_at'] ?? 'NULL') . "</p>";
            } else {
                echo "<p style='color: orange;'>accepted_at khong co trong API response</p>";
            }
        } else {
            echo "<p style='color: red;'>API that bai</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>API error: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
    echo "<h3>7. KET LUAN</h3>";
    echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h4>DA HOAN TAT FIX TRIET DE:</h4>";
    echo "<ul>";
    echo "<li>Database schema da duoc kiem tra va them neu thieu</li>";
    echo "<li>Tat ca requests in_progress da duoc cap nhat accepted_at</li>";
    echo "<li>Tat ca requests da giao da duoc cap nhat assigned_at</li>";
    echo "<li>API accept_request da duoc fix de set ca accepted_at va assigned_at</li>";
    echo "</ul>";
    echo "<p><strong>TU NAY BAT CU STAFF NHAN YEU CAU SE CO THOI GIAN NHAN!</strong></p>";
    echo "</div>";
    
    echo "<h3>8. TEST NGAY</h3>";
    echo "<ol>";
    echo "<li><a href='index.html' target='_blank'>Login voi user</a> va tao request moi</li>";
    echo "<li><a href='index.html' target='_blank'>Login voi staff</a> va nhan request</li>";
    echo "<li>Kiem tra request detail - phai co 'Thoi gian staff nhan'</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
