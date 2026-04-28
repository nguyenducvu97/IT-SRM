<?php
/**
 * Test file to verify Edit Request functionality for Admin
 * This test checks:
 * 1. Button visibility (admin only)
 * 2. API endpoint availability
 * 3. Permission check (admin only)
 * 4. Database update logic
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'config/session.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Edit Request - Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .test-title {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .status {
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        pre {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background: #007bff;
            color: white;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <h1>🧪 Test Edit Request Functionality - Admin Role</h1>
    
    <?php if (!isset($_SESSION['user_id'])): ?>
        <div class="status error">
            ⚠️ Bạn chưa đăng nhập. Vui lòng đăng nhập với tài khoản admin để test.
            <br><br>
            <a href="index.html">Đăng nhập</a>
        </div>
    <?php else: ?>
        <div class="status info">
            ✅ Đã đăng nhập: <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Unknown'); ?></strong>
            <br>Role: <strong><?php echo htmlspecialchars($_SESSION['role'] ?? 'Unknown'); ?></strong>
        </div>
        
        <?php if ($_SESSION['role'] !== 'admin'): ?>
            <div class="status error">
                ❌ Tài khoản hiện tại không phải là admin. Vui lòng đăng nhập với tài khoản admin.
                <br><br>
                <a href="profile.php?logout=1">Đăng xuất</a>
            </div>
        <?php else: ?>
            <!-- Test 1: Check User Role -->
            <div class="test-section">
                <h2 class="test-title">Test 1: Kiểm tra Role Admin</h2>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <div class="status success">✅ User có role admin - Có thể sử dụng chức năng sửa</div>
                <?php else: ?>
                    <div class="status error">❌ User không có role admin - Không thể sử dụng chức năng sửa</div>
                <?php endif; ?>
            </div>
            
            <!-- Test 2: Check API Endpoint -->
            <div class="test-section">
                <h2 class="test-title">Test 2: Kiểm tra API Endpoint</h2>
                <?php
                $api_file = 'api/service_requests.php';
                if (file_exists($api_file)) {
                    echo '<div class="status success">✅ File API tồn tại: ' . $api_file . '</div>';
                    
                    // Check if PUT update action exists
                    $content = file_get_contents($api_file);
                    if (strpos($content, "if (\$action == 'update')") !== false) {
                        echo '<div class="status success">✅ Action "update" tồn tại trong API</div>';
                    } else {
                        echo '<div class="status error">❌ Action "update" không tồn tại trong API</div>';
                    }
                    
                    // Check admin permission
                    if (strpos($content, "if (\$user_role != 'admin')") !== false) {
                        echo '<div class="status success">✅ Permission check cho admin tồn tại</div>';
                    } else {
                        echo '<div class="status error">❌ Permission check cho admin không tồn tại</div>';
                    }
                } else {
                    echo '<div class="status error">❌ File API không tồn tại: ' . $api_file . '</div>';
                }
                ?>
            </div>
            
            <!-- Test 3: Check Database Structure -->
            <div class="test-section">
                <h2 class="test-title">Test 3: Kiểm tra Database Structure</h2>
                <?php
                try {
                    $db = getDBConnection();
                    
                    // Check service_requests table
                    $stmt = $db->query("DESCRIBE service_requests");
                    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo '<h3>Các cột trong bảng service_requests:</h3>';
                    echo '<table>';
                    echo '<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>';
                    $required_columns = ['id', 'title', 'description', 'category_id', 'priority', 'status', 'assigned_to', 'assigned_at'];
                    $missing_columns = [];
                    
                    foreach ($columns as $column) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($column['Field']) . '</td>';
                        echo '<td>' . htmlspecialchars($column['Type']) . '</td>';
                        echo '<td>' . htmlspecialchars($column['Null']) . '</td>';
                        echo '<td>' . htmlspecialchars($column['Key']) . '</td>';
                        echo '</tr>';
                        
                        if (!in_array($column['Field'], $required_columns)) {
                            $missing_columns[] = $column['Field'];
                        }
                    }
                    echo '</table>';
                    
                    if (empty($missing_columns)) {
                        echo '<div class="status success">✅ Tất cả các cột cần thiết đều tồn tại</div>';
                    } else {
                        echo '<div class="status error">❌ Thiếu các cột: ' . implode(', ', $missing_columns) . '</div>';
                    }
                    
                } catch (Exception $e) {
                    echo '<div class="status error">❌ Lỗi database: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                ?>
            </div>
            
            <!-- Test 4: Check Sample Request -->
            <div class="test-section">
                <h2 class="test-title">Test 4: Kiểm tra Request Sample để Test</h2>
                <?php
                try {
                    $db = getDBConnection();
                    
                    // Get a sample request
                    $stmt = $db->prepare("SELECT id, title, status, priority, category_id, assigned_to FROM service_requests ORDER BY id DESC LIMIT 5");
                    $stmt->execute();
                    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($requests) > 0) {
                        echo '<div class="status success">✅ Tìm thấy ' . count($requests) . ' requests để test</div>';
                        echo '<table>';
                        echo '<tr><th>ID</th><th>Title</th><th>Status</th><th>Priority</th><th>Category ID</th><th>Assigned To</th><th>Action</th></tr>';
                        foreach ($requests as $req) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($req['id']) . '</td>';
                            echo '<td>' . htmlspecialchars($req['title']) . '</td>';
                            echo '<td>' . htmlspecialchars($req['status']) . '</td>';
                            echo '<td>' . htmlspecialchars($req['priority']) . '</td>';
                            echo '<td>' . htmlspecialchars($req['category_id']) . '</td>';
                            echo '<td>' . htmlspecialchars($req['assigned_to'] ?? 'NULL') . '</td>';
                            echo '<td><button onclick="testEditRequest(' . $req['id'] . ')">Test Edit</button></td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                    } else {
                        echo '<div class="status error">❌ Không tìm thấy request nào để test</div>';
                    }
                    
                } catch (Exception $e) {
                    echo '<div class="status error">❌ Lỗi database: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                ?>
            </div>
            
            <!-- Test 5: API Call Test -->
            <div class="test-section">
                <h2 class="test-title">Test 5: Test API Call (PUT update)</h2>
                <p>Chọn một request từ bảng trên và click "Test Edit" để test API call.</p>
                <div id="apiTestResult"></div>
            </div>
            
            <!-- Test 6: Frontend Code Check -->
            <div class="test-section">
                <h2 class="test-title">Test 6: Kiểm tra Frontend Code</h2>
                <?php
                $app_js = 'assets/js/app.js';
                if (file_exists($app_js)) {
                    $content = file_get_contents($app_js);
                    
                    // Check edit button visibility
                    if (strpos($content, "role === 'admin'") !== false) {
                        echo '<div class="status success">✅ Frontend có check role admin cho button sửa</div>';
                    } else {
                        echo '<div class="status error">❌ Frontend không có check role admin cho button sửa</div>';
                    }
                    
                    // Check handleEditRequestSubmit function
                    if (strpos($content, "handleEditRequestSubmit") !== false) {
                        echo '<div class="status success">✅ Function handleEditRequestSubmit tồn tại</div>';
                    } else {
                        echo '<div class="status error">❌ Function handleEditRequestSubmit không tồn tại</div>';
                    }
                    
                    // Check API call
                    if (strpos($content, "method: 'PUT'") !== false) {
                        echo '<div class="status success">✅ Frontend có sử dụng method PUT cho API call</div>';
                    } else {
                        echo '<div class="status error">❌ Frontend không sử dụng method PUT cho API call</div>';
                    }
                } else {
                    echo '<div class="status error">❌ File app.js không tồn tại</div>';
                }
                ?>
            </div>
            
        <?php endif; ?>
    <?php endif; ?>
    
    <script>
    function testEditRequest(requestId) {
        const resultDiv = document.getElementById('apiTestResult');
        resultDiv.innerHTML = '<div class="status info">🔄 Đang test API call cho request #' + requestId + '...</div>';
        
        // Prepare test data
        const testData = {
            action: 'update',
            id: requestId,
            title: 'Test Edit Request - ' + new Date().toISOString(),
            description: 'This is a test description for edit request',
            category_id: 1,
            priority: 'medium',
            status: 'open',
            assigned_to: null
        };
        
        console.log('Sending test data:', testData);
        
        fetch('api/service_requests.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(testData)
        })
        .then(response => response.json())
        .then(data => {
            console.log('API Response:', data);
            if (data.success) {
                resultDiv.innerHTML = '<div class="status success">✅ API call thành công! Request #' + requestId + ' đã được cập nhật.<br><pre>' + JSON.stringify(data, null, 2) + '</pre></div>';
            } else {
                resultDiv.innerHTML = '<div class="status error">❌ API call thất bại: ' + data.message + '<br><pre>' + JSON.stringify(data, null, 2) + '</pre></div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            resultDiv.innerHTML = '<div class="status error">❌ Lỗi khi gọi API: ' + error.message + '</div>';
        });
    }
    </script>
</body>
</html>
