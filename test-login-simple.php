<?php
// Simple Login Test
header("Content-Type: text/html; charset=UTF-8");

// Handle login submission
if ($_POST['username'] && $_POST['password']) {
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/config/session.php';
    
    startSession();
    
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
        
        echo "<h2>✅ Login Successful!</h2>";
        echo "<p>Session ID: " . session_id() . "</p>";
        echo "<p>User: " . htmlspecialchars($user['username']) . " (" . htmlspecialchars($user['role']) . ")</p>";
        echo "<p><a href='test-search-with-session.php'>Test Search Now</a></p>";
        echo "<p><a href='index.html'>Go to Main App</a></p>";
        
        // Test session check
        echo "<h3>Session Check Test:</h3>";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost/it-service-request/api/auth.php?action=check_session');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
        curl_setopt($ch, CURLOPT_HEADER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        
        echo "<pre>Auth Check Response: " . htmlspecialchars($response) . "</pre>";
        
    } else {
        echo "<h2>❌ Login Failed</h2>";
        echo "<p>Invalid username or password</p>";
    }
    exit();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Login Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .login-form { max-width: 400px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; }
        input { width: 100%; padding: 8px; margin: 5px 0; }
        button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <div class="login-form">
        <h2>Simple Login Test</h2>
        <p>Test login functionality directly</p>
        
        <form method="post">
            <input type="text" name="username" placeholder="Username" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <button type="submit">Login</button>
        </form>
        
        <p><small>Default admin: admin / admin123</small></p>
        <hr>
        <p><a href="test-search-debug.php">Search Debug Test</a></p>
        <p><a href="index.html">Main Application</a></p>
    </div>
</body>
</html>
