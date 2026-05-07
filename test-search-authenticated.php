<?php
// Test Search with Authenticated Session
header("Content-Type: text/html; charset=UTF-8");

// Start session and check authentication
require_once __DIR__ . '/config/session.php';
startSession();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<h2>❌ Not Logged In</h2>";
    echo "<p>Please <a href='test-login-simple.php'>login first</a></p>";
    exit();
}

$user_role = $_SESSION['role'] ?? 'guest';
$username = $_SESSION['username'] ?? 'Guest';
$session_id = session_id();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Search Test (Authenticated)</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
        .user-info { background: #e8f4fd; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .search-form { background: #f9f9f9; padding: 15px; margin: 10px 0; border-radius: 5px; }
        input[type="text"] { width: 300px; padding: 8px; margin: 5px; }
        button { padding: 8px 15px; background: #007bff; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>🔍 Search Test (Authenticated)</h1>
    
    <div class="user-info">
        <strong>Logged in as:</strong> <?= htmlspecialchars($username) ?> (<?= htmlspecialchars($user_role) ?>)
        <br><strong>Session ID:</strong> <?= htmlspecialchars($session_id) ?>
        <br><a href="?logout=1">Logout</a>
    </div>
    
    <?php
    if (isset($_GET['logout'])) {
        session_destroy();
        echo "<script>location.href = 'test-search-authenticated.php';</script>";
        exit();
    }
    ?>
    
    <div class="search-form">
        <h3>Test Search Functionality</h3>
        <form method="get">
            <input type="text" name="search_term" placeholder="Enter search term..." value="<?= htmlspecialchars($_GET['search_term'] ?? '') ?>">
            <button type="submit">🔍 Search</button>
        </form>
    </div>
    
    <?php
    if (isset($_GET['search_term'])) {
        $search_term = trim($_GET['search_term']);
        echo "<div class='test-section'>";
        echo "<h3>Search Results for: '" . htmlspecialchars($search_term) . "'</h3>";
        
        // Test search API with session
        $context = stream_context_create([
            'http' => [
                'header' => 'Cookie: ' . session_name() . '=' . session_id()
            ]
        ]);
        
        $url = 'http://localhost/it-service-request/api/search_requests.php?search=' . urlencode($search_term) . '&limit=5';
        $response = file_get_contents($url, false, $context);
        
        echo "<p><strong>API URL:</strong> " . htmlspecialchars($url) . "</p>";
        echo "<p><strong>Session Cookie:</strong> " . htmlspecialchars(session_name() . '=' . session_id()) . "</p>";
        
        if ($response) {
            echo "<p><strong>Response:</strong></p>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
            
            // Parse and display results
            $data = json_decode($response, true);
            if ($data) {
                echo "<p class='success'>✅ Valid JSON response</p>";
                if (isset($data['success']) && $data['success']) {
                    $total = $data['data']['pagination']['total'] ?? 0;
                    $requests = $data['data']['requests'] ?? [];
                    
                    echo "<p class='success'>✅ Found $total requests</p>";
                    
                    if (!empty($requests)) {
                        echo "<table border='1' style='width: 100%; margin-top: 10px;'>";
                        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Requester</th></tr>";
                        foreach ($requests as $req) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($req['id'] ?? '') . "</td>";
                            echo "<td>" . htmlspecialchars($req['title'] ?? '') . "</td>";
                            echo "<td>" . htmlspecialchars($req['status'] ?? '') . "</td>";
                            echo "<td>" . htmlspecialchars($req['requester_name'] ?? '') . "</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    }
                } else {
                    echo "<p class='error'>❌ API returned error: " . htmlspecialchars($data['message'] ?? 'Unknown error') . "</p>";
                }
            } else {
                echo "<p class='error'>❌ Invalid JSON response</p>";
            }
        } else {
            echo "<p class='error'>❌ No response from API</p>";
        }
        echo "</div>";
    }
    ?>
    
    <div class="test-section">
        <h3>Quick Test Links</h3>
        <ul>
            <li><a href="?search_term=test">Search for "test"</a></li>
            <li><a href="?search_term=admin">Search for "admin"</a></li>
            <li><a href="?search_term=yêu">Search for "yêu"</a></li>
            <li><a href="?search_term=1">Search for "1" (request ID)</a></li>
        </ul>
    </div>
    
    <div class="test-section">
        <h3>Session Debug Info</h3>
        <pre>
Session ID: <?= session_id() ?>
Session Data: <?= htmlspecialchars(json_encode($_SESSION, JSON_PRETTY_PRINT)) ?>
User Role: <?= htmlspecialchars($user_role) ?>
        </pre>
    </div>
    
    <div class="test-section">
        <h3>Quick Links</h3>
        <ul>
            <li><a href="index.html" target="_blank">🏠 Main Application</a></li>
            <li><a href="test-search-debug.php" target="_blank">🔍 Original Debug Test</a></li>
            <li><a href="test-search-simple.php?search=test" target="_blank">🔍 Simple API Test</a></li>
        </ul>
    </div>
</body>
</html>
