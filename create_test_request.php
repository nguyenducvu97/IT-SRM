<?php
// Create test open request for staff to accept
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Tạo Yêu cầu Test cho Staff</h2>";

try {
    // Get a category
    $category_query = "SELECT id, name FROM categories LIMIT 1";
    $category_stmt = $db->prepare($category_query);
    $category_stmt->execute();
    $category = $category_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$category) {
        echo "❌ Không có category nào trong hệ thống<br>";
        exit;
    }
    
    // Create test request
    $title = "Yêu cầu test cho staff - " . date('Y-m-d H:i:s');
    $description = "Đây là yêu cầu test để staff có thể nhận. Status: open, assigned_to: NULL";
    $category_id = $category['id'];
    $priority = 'medium';
    $user_id = 1; // Admin tạo
    
    $insert_query = "INSERT INTO service_requests 
    (title, description, category_id, priority, status, user_id, assigned_to, created_at, updated_at) 
    VALUES (:title, :description, :category_id, :priority, 'open', :user_id, NULL, NOW(), NOW())";
    
    $insert_stmt = $db->prepare($insert_query);
    $insert_stmt->bindParam(':title', $title);
    $insert_stmt->bindParam(':description', $description);
    $insert_stmt->bindParam(':category_id', $category_id);
    $insert_stmt->bindParam(':priority', $priority);
    $insert_stmt->bindParam(':user_id', $user_id);
    
    if ($insert_stmt->execute()) {
        $request_id = $db->lastInsertId();
        echo "✅ Đã tạo yêu cầu test thành công!<br>";
        echo "<table border='1'>";
        echo "<tr><th>Request ID</th><td>$request_id</td></tr>";
        echo "<tr><th>Title</th><td>$title</td></tr>";
        echo "<tr><th>Description</th><td>$description</td></tr>";
        echo "<tr><th>Category</th><td>" . $category['name'] . "</td></tr>";
        echo "<tr><th>Status</th><td>open</td></tr>";
        echo "<tr><th>Assigned To</th><td>NULL (chưa assign)</td></tr>";
        echo "</table>";
        
        echo "<h3>Các bước tiếp theo:</h3>";
        echo "<ol>";
        echo "<li><a href='/it-service-request/debug_accept_button.html' target='_blank'>Mở debug page</a></li>";
        echo "<li>Click 'Load Acceptable Requests'</li>";
        echo "<li>Bạn sẽ thấy request #$request_id trong danh sách</li>";
        echo "<li>Click 'Nhận yêu cầu' để test</li>";
        echo "</ol>";
        
        echo "<h3>Hoặc test trực tiếp:</h3>";
        echo "<button onclick='testAccept($request_id)'>Test Nhận Yêu cầu #$request_id</button>";
        echo "<div id='testResult'></div>";
        
        echo "<script>
        async function testAccept(id) {
            try {
                const response = await fetch('/it-service-request/api/service_requests.php', {
                    method: 'PUT',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'accept_request',
                        request_id: id
                    }),
                    credentials: 'include'
                });
                const result = await response.json();
                
                const resultDiv = document.getElementById('testResult');
                if (result.success) {
                    resultDiv.innerHTML = '<div style=\"color: green;\">✅ Nhận yêu cầu thành công! Status: ' + result.message + '</div>';
                } else {
                    resultDiv.innerHTML = '<div style=\"color: red;\">❌ Lỗi: ' + result.message + '</div>';
                }
            } catch (error) {
                document.getElementById('testResult').innerHTML = '<div style=\"color: red;\">❌ Error: ' + error.message + '</div>';
            }
        }
        </script>";
        
    } else {
        echo "❌ Lỗi tạo yêu cầu<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "<br>";
}

// Also check current requests
echo "<h2>Current Requests Status:</h2>";
$check_query = "SELECT id, title, status, assigned_to, created_at 
               FROM service_requests 
               ORDER BY id DESC 
               LIMIT 10";
$check_stmt = $db->prepare($check_query);
$check_stmt->execute();
$requests = $check_stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Assigned To</th><th>Can Accept?</th></tr>";

foreach ($requests as $req) {
    $can_accept = $req['status'] === 'open' && $req['assigned_to'] === null;
    echo "<tr>";
    echo "<td>" . $req['id'] . "</td>";
    echo "<td>" . substr($req['title'], 0, 50) . "</td>";
    echo "<td>" . $req['status'] . "</td>";
    echo "<td>" . ($req['assigned_to'] ?? 'NULL') . "</td>";
    echo "<td>" . ($can_accept ? '✅ YES' : '❌ NO') . "</td>";
    echo "</tr>";
}

echo "</table>";

?>
