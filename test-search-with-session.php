<?php
// Test Search with Proper Session
header("Content-Type: text/html; charset=UTF-8");

// Start session first
require_once __DIR__ . '/config/session.php';
startSession();

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_role = $_SESSION['role'] ?? 'guest';
$username = $_SESSION['username'] ?? 'Guest';

// If not logged in, show login form
if (!$is_logged_in) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Login Required - Search Test</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .login-form { max-width: 400px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; }
            input { width: 100%; padding: 8px; margin: 5px 0; }
            button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; cursor: pointer; }
            .error { color: red; }
        </style>
    </head>
    <body>
        <div class="login-form">
            <h2>Login Required</h2>
            <p>You need to login to test search functionality.</p>
            
            <?php
            if ($_POST['username'] && $_POST['password']) {
                // Try to login
                require_once __DIR__ . '/config/database.php';
                $database = new Database();
                $db = $database->getConnection();
                
                $username = $_POST['username'];
                $password = $_POST['password'];
                
                $stmt = $db->prepare("SELECT id, username, password, role, full_name FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['full_name'] = $user['full_name'];
                    
                    echo "<p class='success'>Login successful! Refreshing...</p>";
                    echo "<script>setTimeout(() => location.reload(), 1000);</script>";
                } else {
                    echo "<p class='error'>Invalid username or password</p>";
                }
            }
            ?>
            
            <form method="post">
                <input type="text" name="username" placeholder="Username" required><br>
                <input type="password" name="password" placeholder="Password" required><br>
                <button type="submit">Login</button>
            </form>
            
            <p><small>Default admin: admin / admin123</small></p>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// If logged in, show search test
?>
<!DOCTYPE html>
<html>
<head>
    <title>Search Test (Logged In)</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
        .user-info { background: #e8f4fd; padding: 10px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔍 Search Test (Logged In)</h1>
    
    <div class="user-info">
        <strong>Logged in as:</strong> <?= htmlspecialchars($username) ?> (<?= htmlspecialchars($user_role) ?>)
        <br><a href="?logout=1">Logout</a>
    </div>
    
    <?php
    if (isset($_GET['logout'])) {
        session_destroy();
        echo "<script>location.href = 'test-search-with-session.php';</script>";
        exit();
    }
    ?>
    
    <div class="test-section">
        <h2>1. Test Search API with Session</h2>
        <p>Testing search API with proper authentication...</p>
        
        <?php
        // Test search with different terms
        $test_terms = ['test', 'yêu cầu', 'admin', ''];
        
        foreach ($test_terms as $term) {
            echo "<div class='info'>";
            echo "<h4>Testing search term: '" . htmlspecialchars($term) . "'</h4>";
            
            // Use file_get_contents with session context
            $context = stream_context_create([
                'http' => [
                    'header' => 'Cookie: ' . session_name() . '=' . session_id()
                ]
            ]);
            
            $url = 'http://localhost/it-service-request/api/search_requests.php?search=' . urlencode($term) . '&limit=5';
            $response = file_get_contents($url, false, $context);
            
            echo "<p><strong>URL:</strong> " . htmlspecialchars($url) . "</p>";
            echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
            
            if ($response) {
                echo "<p><strong>Response:</strong></p>";
                echo "<pre>" . htmlspecialchars($response) . "</pre>";
                
                // Try to parse JSON
                $data = json_decode($response, true);
                if ($data) {
                    echo "<p class='success'>✅ Valid JSON response</p>";
                    if (isset($data['success']) && $data['success']) {
                        $total = $data['data']['pagination']['total'] ?? 0;
                        echo "<p class='success'>✅ Found $total requests</p>";
                    } else {
                        echo "<p class='error'>❌ API returned error: " . htmlspecialchars($data['message'] ?? 'Unknown error') . "</p>";
                    }
                } else {
                    echo "<p class='error'>❌ Invalid JSON response</p>";
                }
            } else {
                echo "<p class='error'>❌ No response from API</p>";
            }
            echo "</div><hr>";
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>2. Session Debug Info</h2>
        <pre>
Session ID: <?= session_id() ?>
Session Data: <?= htmlspecialchars(json_encode($_SESSION, JSON_PRETTY_PRINT)) ?>
Cookies: <?= htmlspecialchars(json_encode($_COOKIE, JSON_PRETTY_PRINT)) ?>
        </pre>
    </div>
    
    <div class="test-section">
        <h2>3. Quick Links</h2>
        <ul>
            <li><a href="index.html" target="_blank">🏠 Main Application</a></li>
            <li><a href="test-search-debug.php" target="_blank">🔍 Original Debug Test</a></li>
        </ul>
    </div>
</body>
</html>
